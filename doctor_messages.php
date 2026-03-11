<?php
require_once 'db_connect.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor_login.php');
    exit;
}

$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'];

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
    [
        '$project' => [
            'user_id' => '$_id',
            'user_name' => '$user.name',
            'user_email' => '$user.email',
            'lastMessage' => 1,
            'lastTime' => 1,
            'unreadCount' => 1
        ]
    ]
]);

$userChatsArray = mongoResultToArray($userChats);

// Get all users (to show users who haven't messaged yet)
$allUsers = mongoFind('users', ['role' => 'user'], ['sort' => ['name' => 1]]);
$allUsersArray = mongoResultToArray($allUsers);

// Create a map of users who have chatted
$chattedUserIds = [];
foreach ($userChatsArray as $chat) {
    $chattedUserIds[(string)$chat['user_id']] = true;
}

// Add users who haven't chatted yet
foreach ($allUsersArray as $user) {
    $userId = (string)$user['_id'];
    if (!isset($chattedUserIds[$userId])) {
        $userChatsArray[] = [
            'user_id' => $user['_id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'lastMessage' => 'No messages yet',
            'lastTime' => null,
            'unreadCount' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Doctor Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e0f7fa, #ffffff);
            color: #333;
            min-height: 100vh;
        }

        header {
            background: #00796b;
            color: white;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.6rem;
            font-weight: 600;
        }

        header .user-info {
            font-size: 1rem;
            font-weight: 500;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 1rem;
        }

        nav ul li a {
            text-decoration: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background: #004d40;
        }

        main {
            padding: 2rem;
        }

        main h2 {
            margin-bottom: 1.5rem;
            color: #00796b;
        }

        .chat-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 0;
            height: calc(100vh - 160px);
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .contacts-sidebar {
            background: #f8f9fa;
            border-right: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #00897b 0%, #00695c 100%);
            color: white;
            padding: 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .contacts-list {
            overflow-y: auto;
            flex: 1;
        }

        .contact-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
        }

        .contact-item:hover {
            background: white;
            border-left: 4px solid #00897b;
        }

        .contact-item.active {
            background: #00897b;
            color: white;
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #00897b, #00695c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
        }

        .contact-info {
            flex: 1;
        }

        .contact-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .contact-preview {
            font-size: 0.8rem;
            color: #999;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .contact-item.active .contact-preview {
            color: rgba(255, 255, 255, 0.8);
        }

        .unread-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .delete-chat-btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: #ff4757;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 10;
        }

        .contact-item:hover .delete-chat-btn {
            display: flex;
        }

        .delete-chat-btn:hover {
            background: #e74c3c;
            transform: translateY(-50%) scale(1.1);
        }

        .contact-item.active:hover .delete-chat-btn {
            background: rgba(255, 71, 87, 0.9);
        }

        .chat-area {
            display: flex;
            flex-direction: column;
            background: white;
            height: 100%;
            max-height: 100%;
            overflow: hidden;
            position: relative;
        }

        .chat-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-header-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #00897b, #00695c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
        }

        .chat-header-info h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .chat-header-info p {
            margin: 0.25rem 0 0;
            font-size: 0.85rem;
            color: #666;
        }

        .messages-container {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1.5rem;
            background: #f5f7fa;
            min-height: 0;
            max-height: 100%;
        }

        .message {
            display: flex;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 60%;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
        }

        .message.received .message-bubble {
            background: white;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 4px;
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, #00897b 0%, #00695c 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-text {
            margin: 0;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .message-delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 71, 87, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 0.7rem;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .message-bubble:hover .message-delete-btn {
            display: flex;
        }

        .message-delete-btn:hover {
            background: #ff4757;
            transform: scale(1.1);
        }

        .message-input-area {
            padding: 1.5rem;
            background: white;
            border-top: 2px solid #e0e0e0;
            flex-shrink: 0;
            min-height: 90px;
            position: sticky;
            bottom: 0;
            z-index: 10;
        }

        .message-input-form {
            display: flex;
            gap: 0.75rem;
        }

        .message-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .message-input:focus {
            border-color: #00897b;
        }

        .send-button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #00897b 0%, #00695c 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .send-button:hover {
            transform: scale(1.05);
        }

        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }

        .empty-chat-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
            }

            .contacts-sidebar {
                display: none;
            }

            .message-bubble {
                max-width: 80%;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Doctor Dashboard - Messages</h1>
        <nav>
            <ul>
                <li><a href="doctor_dashboard.php">Appointments</a></li>
                <li><a href="doctor_messages.php" class="active">Messages</a></li>
                <li><a href="post_treatment.php">Post Treatment</a></li>
                <li><a href="doctor_profile.php">Profile</a></li>
                <li><a href="doctor_logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="user-info">Welcome, Dr. <?= htmlspecialchars($doctorName) ?></div>
    </header>

    <main class="container">
        <h2>💬 Patient Messages</h2>

        <div class="chat-container">
            <!-- Contacts Sidebar -->
            <div class="contacts-sidebar">
                <div class="sidebar-header">
                    💬 Patient Chats
                </div>

                <div class="contacts-list">
                    <?php if (count($userChatsArray) > 0): ?>
                        <?php foreach ($userChatsArray as $chat): ?>
                            <div class="contact-item"
                                data-id="<?= (string)$chat['user_id'] ?>"
                                data-name="<?= htmlspecialchars($chat['user_name']) ?>"
                                data-email="<?= htmlspecialchars($chat['user_email']) ?>">
                                <div class="contact-avatar">
                                    <?= strtoupper(substr($chat['user_name'], 0, 1)) ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($chat['user_name']) ?></div>
                                    <div class="contact-preview"><?= htmlspecialchars(substr($chat['lastMessage'], 0, 30)) ?>...</div>
                                </div>
                                <?php if ($chat['unreadCount'] > 0): ?>
                                    <div class="unread-badge"><?= $chat['unreadCount'] ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #999;">
                            No messages yet<br>
                            <small>Patients will appear here when they message you</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area">
                <div class="chat-header" id="chatHeader" style="display: none;">
                    <div class="chat-header-avatar" id="chatAvatar">
                        💬
                    </div>
                    <div class="chat-header-info">
                        <h3 id="chatName">Select a patient</h3>
                        <p id="chatEmail">Start a conversation</p>
                    </div>
                </div>

                <div class="messages-container" id="messagesContainer">
                    <div class="empty-chat">
                        <div class="empty-chat-icon">💬</div>
                        <h3>Select a patient to view conversation</h3>
                        <p>Your patient messages will appear here</p>
                    </div>
                </div>

                <div class="message-input-area" id="messageInputArea" style="display: none;">
                    <form class="message-input-form" id="messageForm">
                        <input type="hidden" id="receiverId" name="receiver_id">
                        <input type="text"
                            class="message-input"
                            id="messageInput"
                            placeholder="Type your message..."
                            autocomplete="off"
                            required>
                        <button type="submit" class="send-button">
                            📤 Send
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        let currentReceiverId = null;
        let messageRefreshInterval = null;
        let contactRefreshInterval = null;

        // Refresh patient contact list
        function refreshContacts() {
            fetch('chat_contacts_api.php?action=get_doctor_contacts')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.contacts.length > 0) {
                        const contactsList = document.querySelector('.contacts-list');
                        contactsList.innerHTML = '';

                        data.contacts.forEach(contact => {
                            const div = document.createElement('div');
                            div.className = 'contact-item';
                            div.dataset.id = contact.id;
                            div.dataset.name = contact.name;
                            div.dataset.email = contact.email;

                            div.innerHTML = `
                                <div class="contact-avatar">${contact.name.charAt(0).toUpperCase()}</div>
                                <div class="contact-info">
                                    <div class="contact-name">${contact.name}</div>
                                    <div class="contact-preview">${contact.lastMessage}...</div>
                                </div>
                                ${contact.unreadCount > 0 ? '<div class="unread-badge">' + contact.unreadCount + '</div>' : ''}
                            `;

                            // Add delete chat button
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'delete-chat-btn';
                            deleteBtn.innerHTML = '🗑️';
                            deleteBtn.title = 'Delete conversation';
                            deleteBtn.onclick = function(e) {
                                e.stopPropagation();
                                deleteConversation(contact.id, contact.name);
                            };
                            div.appendChild(deleteBtn);

                            // Add click handler
                            div.addEventListener('click', function() {
                                document.querySelectorAll('.contact-item').forEach(i => i.classList.remove('active'));
                                this.classList.add('active');

                                const receiverId = this.dataset.id;
                                const receiverName = this.dataset.name;
                                const receiverEmail = this.dataset.email;

                                currentReceiverId = receiverId;

                                document.getElementById('receiverId').value = receiverId;
                                document.getElementById('chatName').textContent = receiverName;
                                document.getElementById('chatEmail').textContent = receiverEmail;
                                document.getElementById('chatAvatar').textContent = receiverName.charAt(0).toUpperCase();

                                document.getElementById('chatHeader').style.display = 'flex';
                                document.getElementById('messageInputArea').style.display = 'block';

                                // Remove unread badge
                                const badge = this.querySelector('.unread-badge');
                                if (badge) badge.remove();

                                loadMessages(receiverId);

                                // Auto-refresh messages every 3 seconds
                                if (messageRefreshInterval) clearInterval(messageRefreshInterval);
                                messageRefreshInterval = setInterval(() => {
                                    loadMessages(receiverId, true);
                                }, 3000);
                            });

                            contactsList.appendChild(div);
                        });
                    }
                })
                .catch(error => console.error('Error refreshing contacts:', error));
        }

        // Load messages (using same chat_api.php)
        function loadMessages(receiverId, silent = false) {
            if (!silent) {
                document.getElementById('messagesContainer').innerHTML = '<div style="text-align: center; padding: 2rem; color: #999;">Loading messages...</div>';
            }

            fetch('chat_api.php?action=get_messages&receiver_id=' + receiverId + '&chat_type=doctor')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Messages loaded:', data);
                    if (data.success) {
                        console.log('Number of messages:', data.messages.length);
                        if (data.messages.length > 0) {
                            console.log('First message:', data.messages[0]);
                        }
                        displayMessages(data.messages);
                    } else {
                        console.error('Error from server:', data.error);
                        if (!silent) {
                            document.getElementById('messagesContainer').innerHTML = '<div class="empty-chat"><div class="empty-chat-icon">⚠️</div><p>Error: ' + (data.error || 'Unknown error') + '</p></div>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    if (!silent) {
                        document.getElementById('messagesContainer').innerHTML = '<div class="empty-chat"><div class="empty-chat-icon">⚠️</div><p>Failed to load messages. Please refresh.</p></div>';
                    }
                });
        }

        // Display messages
        function displayMessages(messages) {
            const container = document.getElementById('messagesContainer');
            const scrollAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;

            if (messages.length === 0) {
                container.innerHTML = '<div class="empty-chat"><div class="empty-chat-icon">💬</div><p>No messages yet. Start the conversation!</p></div>';
                return;
            }

            container.innerHTML = '';
            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message ' + (msg.is_sender ? 'sent' : 'received');

                const bubble = document.createElement('div');
                bubble.className = 'message-bubble';

                // Add sender name for received messages
                if (!msg.is_sender && msg.sender_name) {
                    const senderLabel = document.createElement('div');
                    senderLabel.className = 'message-sender';
                    const senderIcon = msg.sender_type === 'doctor' ? '👨‍⚕️ ' : '👤 ';
                    senderLabel.textContent = senderIcon + msg.sender_name;
                    senderLabel.style.fontSize = '0.75rem';
                    senderLabel.style.fontWeight = '600';
                    senderLabel.style.marginBottom = '0.25rem';
                    senderLabel.style.color = '#00897b';
                    bubble.appendChild(senderLabel);
                }

                const text = document.createElement('p');
                text.className = 'message-text';
                text.textContent = msg.message;

                const time = document.createElement('div');
                time.className = 'message-time';
                time.textContent = msg.time;

                bubble.appendChild(text);
                bubble.appendChild(time);

                // Add delete button only for sent messages
                if (msg.is_sender && msg.id) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'message-delete-btn';
                    deleteBtn.innerHTML = '🗑️';
                    deleteBtn.title = 'Delete message';
                    deleteBtn.onclick = function(e) {
                        e.stopPropagation();
                        deleteMessage(msg.id);
                    };
                    bubble.appendChild(deleteBtn);
                }

                messageDiv.appendChild(bubble);
                container.appendChild(messageDiv);
            });

            // Auto-scroll to bottom if user was already at bottom
            if (scrollAtBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Delete message
        function deleteMessage(messageId) {
            if (!confirm('Are you sure you want to delete this message?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_message');
            formData.append('message_id', messageId);

            fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload messages to reflect deletion
                        if (currentReceiverId) {
                            loadMessages(currentReceiverId, false);
                            refreshContacts();
                        }
                    } else {
                        alert('Failed to delete message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting message:', error);
                    alert('Failed to delete message');
                });
        }

        // Delete entire conversation
        function deleteConversation(userId, userName) {
            if (!confirm(`Are you sure you want to delete entire conversation with ${userName}? This will delete all messages.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_conversation');
            formData.append('other_user_id', userId);

            fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear chat area
                        document.getElementById('messagesContainer').innerHTML = '<div class="empty-chat"><div class="empty-chat-icon">💬</div><h3>Conversation deleted</h3><p>Select another patient to start chatting</p></div>';
                        document.getElementById('chatHeader').style.display = 'none';
                        document.getElementById('messageInputArea').style.display = 'none';

                        // Clear current chat
                        currentReceiverId = null;

                        // Clear message refresh interval
                        if (messageRefreshInterval) {
                            clearInterval(messageRefreshInterval);
                            messageRefreshInterval = null;
                        }

                        // Refresh contact lists
                        refreshContacts();
                    } else {
                        alert('Failed to delete conversation: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting conversation:', error);
                    alert('Failed to delete conversation');
                });
        }

        // Send message
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message || !currentReceiverId) return;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', currentReceiverId);
            formData.append('chat_type', 'doctor');
            formData.append('message', message);

            fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Send message response:', data);
                    if (data.success) {
                        messageInput.value = '';
                        console.log('Message sent successfully, reloading...');
                        // Immediately reload messages to show the sent message
                        loadMessages(currentReceiverId, false);
                        // Refresh the contact list
                        refreshContacts();
                    } else {
                        console.error('Failed to send:', data.error);
                        alert('Failed to send message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Failed to send message: ' + error.message);
                });
        });

        // Cleanup interval on page unload
        window.addEventListener('beforeunload', () => {
            if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            if (contactRefreshInterval) clearInterval(contactRefreshInterval);
        });

        // Load contacts on page load and refresh every 10 seconds
        refreshContacts();
        contactRefreshInterval = setInterval(refreshContacts, 10000);
    </script>
</body>

</html>