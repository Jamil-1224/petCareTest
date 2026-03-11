<?php
require __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    // Get user's recent chats
    if ($action === 'get_user_contacts') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $uid = $_SESSION['user_id'];

        // Get recent conversations
        $recentChats = mongoAggregate('messages', [
            [
                '$match' => [
                    '$or' => [
                        ['sender_id' => stringToObjectId($uid)],
                        ['receiver_id' => stringToObjectId($uid)]
                    ]
                ]
            ],
            ['$sort' => ['created_at' => -1]],
            [
                '$group' => [
                    '_id' => [
                        '$cond' => [
                            ['$eq' => ['$sender_id', stringToObjectId($uid)]],
                            '$receiver_id',
                            '$sender_id'
                        ]
                    ],
                    'lastMessage' => ['$first' => '$message'],
                    'chat_type' => ['$first' => '$chat_type'],
                    'lastTime' => ['$first' => '$created_at'],
                    'unreadCount' => [
                        '$sum' => [
                            '$cond' => [
                                [
                                    '$and' => [
                                        ['$eq' => ['$receiver_id', stringToObjectId($uid)]],
                                        ['$eq' => ['$is_read', 0]]
                                    ]
                                ],
                                1,
                                0
                            ]
                        ]
                    ]
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => '_id',
                    'foreignField' => '_id',
                    'as' => 'user_info'
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'doctors',
                    'localField' => '_id',
                    'foreignField' => '_id',
                    'as' => 'doctor_info'
                ]
            ],
            [
                '$addFields' => [
                    'contact_name' => [
                        '$cond' => [
                            ['$gt' => [['$size' => '$doctor_info'], 0]],
                            ['$arrayElemAt' => ['$doctor_info.full_name', 0]],
                            ['$arrayElemAt' => ['$user_info.name', 0]]
                        ]
                    ],
                    'contact_type' => [
                        '$cond' => [
                            ['$gt' => [['$size' => '$doctor_info'], 0]],
                            'doctor',
                            'user'
                        ]
                    ],
                    'specialization' => ['$arrayElemAt' => ['$doctor_info.specialization', 0]],
                    'email' => ['$arrayElemAt' => ['$user_info.email', 0]]
                ]
            ],
            ['$sort' => ['lastTime' => -1]],
            ['$limit' => 20]
        ]);

        $recentChatsArray = mongoResultToArray($recentChats);

        // Get all users to include those who haven't chatted yet
        $allUsers = mongoFind('users', [
            'role' => 'user',
            '_id' => ['$ne' => stringToObjectId($uid)]
        ], ['sort' => ['name' => 1]]);
        $allUsersArray = mongoResultToArray($allUsers);

        // Get all doctors
        $allDoctors = mongoFind('doctors', ['verification_status' => 'approved'], ['sort' => ['full_name' => 1]]);
        $allDoctorsArray = mongoResultToArray($allDoctors);

        $contacts = [];
        $contactsMap = [];

        // Add recent chats first
        foreach ($recentChatsArray as $chat) {
            $contactId = (string)$chat['_id'];
            $contacts[] = [
                'id' => $contactId,
                'name' => $chat['contact_name'] ?? 'Unknown',
                'type' => $chat['contact_type'] ?? 'user',
                'lastMessage' => substr($chat['lastMessage'], 0, 30),
                'unreadCount' => $chat['unreadCount'] ?? 0,
                'specialization' => $chat['specialization'] ?? null,
                'email' => $chat['email'] ?? null
            ];
            $contactsMap[$contactId] = true;
        }

        // Add all doctors who aren't already in contacts
        foreach ($allDoctorsArray as $doctor) {
            $doctorId = (string)$doctor['_id'];
            if (!isset($contactsMap[$doctorId])) {
                $contacts[] = [
                    'id' => $doctorId,
                    'name' => $doctor['full_name'] ?? 'Unknown Doctor',
                    'type' => 'doctor',
                    'lastMessage' => 'No messages yet',
                    'unreadCount' => 0,
                    'specialization' => $doctor['specialization'] ?? null,
                    'email' => $doctor['email'] ?? null
                ];
            }
        }

        // Add all users who aren't already in contacts
        foreach ($allUsersArray as $user) {
            $userId = (string)$user['_id'];
            if (!isset($contactsMap[$userId])) {
                $contacts[] = [
                    'id' => $userId,
                    'name' => $user['name'] ?? 'Unknown User',
                    'type' => 'user',
                    'lastMessage' => 'No messages yet',
                    'unreadCount' => 0,
                    'specialization' => null,
                    'email' => $user['email'] ?? null
                ];
            }
        }

        echo json_encode(['success' => true, 'contacts' => $contacts]);
    } elseif ($action === 'get_doctor_contacts') {
        if (!isset($_SESSION['doctor_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $doctorId = $_SESSION['doctor_id'];

        // Get all users who have messaged this doctor
        $userChats = mongoAggregate('messages', [
            [
                '$match' => [
                    '$or' => [
                        ['sender_id' => stringToObjectId($doctorId)],
                        ['receiver_id' => stringToObjectId($doctorId)]
                    ]
                ]
            ],
            ['$sort' => ['created_at' => -1]],
            [
                '$group' => [
                    '_id' => [
                        '$cond' => [
                            ['$eq' => ['$sender_id', stringToObjectId($doctorId)]],
                            '$receiver_id',
                            '$sender_id'
                        ]
                    ],
                    'lastMessage' => ['$first' => '$message'],
                    'lastTime' => ['$first' => '$created_at'],
                    'unreadCount' => [
                        '$sum' => [
                            '$cond' => [
                                [
                                    '$and' => [
                                        ['$eq' => ['$receiver_id', stringToObjectId($doctorId)]],
                                        ['$eq' => ['$is_read', 0]]
                                    ]
                                ],
                                1,
                                0
                            ]
                        ]
                    ]
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => '_id',
                    'foreignField' => '_id',
                    'as' => 'user'
                ]
            ],
            ['$unwind' => '$user'],
            ['$sort' => ['lastTime' => -1]],
            ['$limit' => 20]
        ]);

        $userChatsArray = mongoResultToArray($userChats);

        // Get all users to include those who haven't chatted yet
        $allUsers = mongoFind('users', ['role' => 'user'], ['sort' => ['name' => 1]]);
        $allUsersArray = mongoResultToArray($allUsers);

        $contacts = [];
        $contactsMap = [];

        // Add users with existing chats first
        foreach ($userChatsArray as $chat) {
            $userId = (string)$chat['_id'];
            $contacts[] = [
                'id' => $userId,
                'name' => $chat['user']['name'] ?? 'Unknown',
                'email' => $chat['user']['email'] ?? '',
                'lastMessage' => substr($chat['lastMessage'], 0, 30),
                'unreadCount' => $chat['unreadCount'] ?? 0
            ];
            $contactsMap[$userId] = true;
        }

        // Add all users who haven't chatted yet
        foreach ($allUsersArray as $user) {
            $userId = (string)$user['_id'];
            if (!isset($contactsMap[$userId])) {
                $contacts[] = [
                    'id' => $userId,
                    'name' => $user['name'] ?? 'Unknown',
                    'email' => $user['email'] ?? '',
                    'lastMessage' => 'No messages yet',
                    'unreadCount' => 0
                ];
            }
        }

        echo json_encode(['success' => true, 'contacts' => $contacts]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
