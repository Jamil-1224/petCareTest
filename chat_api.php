<?php
require __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user or doctor is logged in
$uid = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
} elseif (isset($_SESSION['doctor_id'])) {
    $uid = $_SESSION['doctor_id'];
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'send_message') {
        // Send a new message
        $receiver_id = $_POST['receiver_id'] ?? '';
        $chat_type = $_POST['chat_type'] ?? 'user'; // user, doctor
        $message = trim($_POST['message'] ?? '');

        if (empty($receiver_id) || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        // Validate ObjectIds
        $senderObjId = stringToObjectId($uid);
        $receiverObjId = stringToObjectId($receiver_id);

        if (!$senderObjId || !$receiverObjId) {
            echo json_encode(['success' => false, 'error' => 'Invalid user IDs']);
            exit;
        }

        // Insert message
        $insertId = mongoInsert('messages', [
            'sender_id' => $senderObjId,
            'receiver_id' => $receiverObjId,
            'message' => $message,
            'chat_type' => $chat_type,
            'is_read' => 0,
            'created_at' => getCurrentDateTime()
        ]);

        if ($insertId) {
            echo json_encode(['success' => true, 'message_id' => (string)$insertId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    } elseif ($action === 'get_messages') {
        // Get conversation between current user and another user/doctor
        $receiver_id = $_GET['receiver_id'] ?? '';
        $chat_type = $_GET['chat_type'] ?? 'user';

        if (empty($receiver_id)) {
            echo json_encode(['success' => false, 'error' => 'Receiver ID required']);
            exit;
        }

        $receiverObjId = stringToObjectId($receiver_id);
        $senderObjId = stringToObjectId($uid);

        // Validate ObjectIds
        if (!$receiverObjId || !$senderObjId) {
            echo json_encode(['success' => false, 'error' => 'Invalid user IDs']);
            exit;
        }

        // Get all messages between these two users
        $messages = mongoAggregate('messages', [
            [
                '$match' => [
                    '$or' => [
                        [
                            'sender_id' => $senderObjId,
                            'receiver_id' => $receiverObjId
                        ],
                        [
                            'sender_id' => $receiverObjId,
                            'receiver_id' => $senderObjId
                        ]
                    ]
                ]
            ],
            ['$sort' => ['created_at' => 1]],
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'sender_id',
                    'foreignField' => '_id',
                    'as' => 'sender_user'
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'doctors',
                    'localField' => 'sender_id',
                    'foreignField' => '_id',
                    'as' => 'sender_doctor'
                ]
            ],
            [
                '$addFields' => [
                    'sender_name' => [
                        '$cond' => [
                            ['$gt' => [['$size' => '$sender_doctor'], 0]],
                            ['$arrayElemAt' => ['$sender_doctor.full_name', 0]],
                            ['$arrayElemAt' => ['$sender_user.name', 0]]
                        ]
                    ],
                    'sender_type' => [
                        '$cond' => [
                            ['$gt' => [['$size' => '$sender_doctor'], 0]],
                            'doctor',
                            'user'
                        ]
                    ]
                ]
            ],
            [
                '$project' => [
                    '_id' => 1,
                    'message' => 1,
                    'sender_id' => 1,
                    'receiver_id' => 1,
                    'created_at' => 1,
                    'is_read' => 1,
                    'chat_type' => 1,
                    'sender_name' => 1,
                    'sender_type' => 1
                ]
            ]
        ]);

        $messagesArray = mongoResultToArray($messages);

        // Format messages
        $formattedMessages = [];
        foreach ($messagesArray as $msg) {
            // Convert both IDs to strings for comparison
            $messageSenderId = (string)$msg['sender_id'];
            $currentUserId = (string)$uid;
            $isSender = ($messageSenderId === $currentUserId);

            // Format time
            $timestamp = $msg['created_at'];
            if ($timestamp instanceof MongoDB\BSON\UTCDateTime) {
                $dateTime = $timestamp->toDateTime();
                $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                $timeStr = $dateTime->format('h:i A');
            } else {
                $timeStr = date('h:i A', strtotime($timestamp));
            }

            $formattedMessages[] = [
                'id' => (string)$msg['_id'],
                'message' => $msg['message'],
                'is_sender' => $isSender,
                'time' => $timeStr,
                'is_read' => $msg['is_read'] ?? 0,
                'sender_id' => $messageSenderId,
                'sender_name' => $msg['sender_name'] ?? 'Unknown',
                'sender_type' => $msg['sender_type'] ?? 'user'
            ];
        }

        // Mark messages as read
        mongoUpdate(
            'messages',
            [
                'sender_id' => $receiverObjId,
                'receiver_id' => $senderObjId,
                'is_read' => 0
            ],
            ['$set' => ['is_read' => 1]],
            ['multiple' => true]
        );

        echo json_encode(['success' => true, 'messages' => $formattedMessages]);
    } elseif ($action === 'get_unread_count') {
        // Get unread message count
        $unreadCount = mongoCount('messages', [
            'receiver_id' => stringToObjectId($uid),
            'is_read' => 0
        ]);

        echo json_encode(['success' => true, 'count' => $unreadCount]);
    } elseif ($action === 'mark_read') {
        // Mark specific conversation as read
        $sender_id = $_POST['sender_id'] ?? '';

        if (empty($sender_id)) {
            echo json_encode(['success' => false, 'error' => 'Sender ID required']);
            exit;
        }

        mongoUpdate(
            'messages',
            [
                'sender_id' => stringToObjectId($sender_id),
                'receiver_id' => stringToObjectId($uid),
                'is_read' => 0
            ],
            ['$set' => ['is_read' => 1]],
            ['multiple' => true]
        );

        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_message') {
        // Delete a message (only sender can delete their own message)
        $message_id = $_POST['message_id'] ?? '';

        if (empty($message_id)) {
            echo json_encode(['success' => false, 'error' => 'Message ID required']);
            exit;
        }

        $messageObjId = stringToObjectId($message_id);
        if (!$messageObjId) {
            echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
            exit;
        }

        // Verify that the message belongs to the current user
        $message = mongoFindOne('messages', [
            '_id' => $messageObjId,
            'sender_id' => stringToObjectId($uid)
        ]);

        if (!$message) {
            echo json_encode(['success' => false, 'error' => 'Message not found or unauthorized']);
            exit;
        }

        // Delete the message
        $deleted = mongoDelete('messages', ['_id' => $messageObjId]);

        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Message deleted']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
        }
    } elseif ($action === 'delete_conversation') {
        // Delete entire conversation with a user/doctor
        $other_user_id = $_POST['other_user_id'] ?? '';

        if (empty($other_user_id)) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }

        $otherUserObjId = stringToObjectId($other_user_id);
        if (!$otherUserObjId) {
            echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
            exit;
        }

        // Delete all messages between current user and the other user
        $deleted = mongoDelete('messages', [
            '$or' => [
                [
                    'sender_id' => stringToObjectId($uid),
                    'receiver_id' => $otherUserObjId
                ],
                [
                    'sender_id' => $otherUserObjId,
                    'receiver_id' => stringToObjectId($uid)
                ]
            ]
        ], ['multiple' => true]);

        if ($deleted !== false) {
            echo json_encode(['success' => true, 'message' => 'Conversation deleted']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete conversation']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
