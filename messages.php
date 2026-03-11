<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];
$userName = $_SESSION['name'];

// Get list of doctors
$doctors = mongoFind('doctors', ['verification_status' => 'approved'], ['sort' => ['full_name' => 1]]);
$doctorsArray = mongoResultToArray($doctors);

// Get list of ALL users (not just adoption posters)
$allUsers = mongoFind('users', [
    'role' => 'user',
    '_id' => ['$ne' => stringToObjectId($uid)] // Exclude current user
], ['sort' => ['name' => 1]]);
$adoptionUsersArray = mongoResultToArray($allUsers);

// Format the user array to match expected structure
foreach ($adoptionUsersArray as &$user) {
    $user['_id'] = $user['_id'];
    $user['name'] = $user['name'];
    $user['email'] = $user['email'];
}
unset($user);

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
            'lastTime' => ['$first' => '$created_at'],
            'chat_type' => ['$first' => '$chat_type']
        ]
    ],
    [
        '$lookup' => [
            'from' => 'users',
            'localField' => '_id',
            'foreignField' => '_id',
            'as' => 'userInfo'
        ]
    ],
    [
        '$lookup' => [
            'from' => 'doctors',
            'localField' => '_id',
            'foreignField' => '_id',
            'as' => 'doctorInfo'
        ]
    ],
    [
        '$addFields' => [
            'contact_name' => [
                '$cond' => [
                    ['$gt' => [['$size' => '$doctorInfo'], 0]],
                    ['$arrayElemAt' => ['$doctorInfo.full_name', 0]],
                    ['$arrayElemAt' => ['$userInfo.name', 0]]
                ]
            ],
            'contact_type' => [
                '$cond' => [
                    ['$gt' => [['$size' => '$doctorInfo'], 0]],
                    'doctor',
                    'user'
                ]
            ],
            'specialization' => ['$arrayElemAt' => ['$doctorInfo.specialization', 0]]
        ]
    ],
    ['$sort' => ['lastTime' => -1]],
    ['$limit' => 10]
]);
$recentChatsArray = mongoResultToArray($recentChats);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PetCare</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .chat-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 0;
            height: calc(100vh - 160px);
            max-height: calc(100vh - 160px);
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .contacts-sidebar {
            background: #f8f9fa;
            border-right: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .chat-tabs {
            display: flex;
            background: white;
            border-bottom: 2px solid #e0e0e0;
        }

        .chat-tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .chat-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f8f9ff;
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
            border-left: 4px solid #667eea;
        }

        .contact-item.active {
            background: #667eea;
            color: white;
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea, #764ba2);
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

        .contact-role {
            font-size: 0.8rem;
            color: #999;
            opacity: 0.8;
        }

        .contact-item.active .contact-role {
            color: rgba(255, 255, 255, 0.8);
        }

        .unread-badge {
            background: #ff4757;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
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
            flex-shrink: 0;
            min-height: 80px;
        }

        .chat-header-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #43a047, #66bb6a);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
        }

        .send-button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                height: calc(100vh - 120px);
            }

            .contacts-sidebar {
                display: none;
            }

            .contacts-sidebar.mobile-show {
                display: flex;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 100;
            }

            .message-bubble {
                max-width: 80%;
            }
        }
    </style>
</head>

<body>
    <header class="top">
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="pets.php">Pets</a>
            <a href="reminders.php">Reminders</a>
            <a href="appointments.php">Appointments</a>
            <a href="view_memories.php">Memories</a>
            <a href="articles.php">Articles</a>
            <a href="adoption.php">Adoption</a>
            <a href="feed_guidelines.php">Feed Guidelines</a>
            <a href="messages.php" class="active">Messages</a>
            <a href="view_treatments.php">Treatments</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h2>💬 Messages</h2>

        <div class="chat-container">
            <!-- Contacts Sidebar -->
            <div class="contacts-sidebar">
                <div class="sidebar-header">
                    💬 Chats
                </div>

                <div class="chat-tabs">
                    <div class="chat-tab active" data-tab="doctors">
                        👨‍⚕️ Doctors
                    </div>
                    <div class="chat-tab" data-tab="users">
                        👥 Adopters
                    </div>
                    <div class="chat-tab" data-tab="recent">
                        🕐 Recent
                    </div>
                </div>

                <!-- Doctors List -->
                <div class="contacts-list" id="doctorsList">
                    <?php if (count($doctorsArray) > 0): ?>
                        <?php foreach ($doctorsArray as $doctor): ?>
                            <div class="contact-item"
                                data-id="<?= (string)$doctor['_id'] ?>"
                                data-name="<?= htmlspecialchars($doctor['full_name']) ?>"
                                data-type="doctor"
                                data-specialization="<?= htmlspecialchars($doctor['specialization'] ?? 'General') ?>">
                                <div class="contact-avatar">
                                    <?= strtoupper(substr($doctor['full_name'], 0, 1)) ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($doctor['full_name']) ?></div>
                                    <div class="contact-role">🩺 <?= htmlspecialchars($doctor['specialization'] ?? 'Veterinarian') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #999;">
                            No doctors available
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Users List (hidden by default) -->
                <div class="contacts-list" id="usersList" style="display: none;">
                    <?php if (count($adoptionUsersArray) > 0): ?>
                        <?php foreach ($adoptionUsersArray as $user): ?>
                            <?php if ((string)$user['_id'] !== $uid): ?>
                                <div class="contact-item"
                                    data-id="<?= (string)$user['_id'] ?>"
                                    data-name="<?= htmlspecialchars($user['name']) ?>"
                                    data-type="user"
                                    data-email="<?= htmlspecialchars($user['email']) ?>">
                                    <div class="contact-avatar">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                    <div class="contact-info">
                                        <div class="contact-name"><?= htmlspecialchars($user['name']) ?></div>
                                        <div class="contact-role">🐾 Pet Owner</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #999;">
                            No users available
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Chats List (hidden by default) -->
                <div class="contacts-list" id="recentList" style="display: none;">
                    <?php if (count($recentChatsArray) > 0): ?>
                        <?php foreach ($recentChatsArray as $chat): ?>
                            <div class="contact-item"
                                data-id="<?= (string)$chat['_id'] ?>"
                                data-name="<?= htmlspecialchars($chat['contact_name'] ?? 'Unknown') ?>"
                                data-type="<?= htmlspecialchars($chat['contact_type'] ?? 'user') ?>"
                                <?php if (isset($chat['specialization']) && $chat['specialization']): ?>
                                data-specialization="<?= htmlspecialchars($chat['specialization']) ?>"
                                <?php endif; ?>>
                                <div class="contact-avatar">
                                    <?php if ($chat['contact_type'] === 'doctor'): ?>
                                        👨‍⚕️
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($chat['contact_name'] ?? 'Unknown') ?></div>
                                    <div class="contact-role" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php if ($chat['contact_type'] === 'doctor' && isset($chat['specialization'])): ?>
                                            🩺 <?= htmlspecialchars($chat['specialization']) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars(substr($chat['lastMessage'], 0, 30)) ?>...
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #999;">
                            No recent chats
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
                        <h3 id="chatName">Select a contact</h3>
                        <p id="chatRole">Start a conversation</p>
                    </div>
                </div>

                <div class="messages-container" id="messagesContainer">
                    <div class="empty-chat">
                        <div class="empty-chat-icon">💬</div>
                        <h3>Select a contact to start chatting</h3>
                        <p>Choose a doctor for medical guidance or a user for adoption inquiries</p>
                    </div>
                </div>

                <div class="message-input-area" id="messageInputArea" style="display: none;">
                    <form class="message-input-form" id="messageForm">
                        <input type="hidden" id="receiverId" name="receiver_id">
                        <input type="hidden" id="chatType" name="chat_type">
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
        let currentChatType = null;
        let messageRefreshInterval = null;
        let contactRefreshInterval = null;

        // Refresh All Contacts
        function refreshAllContacts() {
            fetch('chat_contacts_api.php?action=get_user_contacts')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.contacts) {
                        updateContactLists(data.contacts);
                    }
                })
                .catch(error => console.error('Error refreshing contacts:', error));
        }

        // Update all contact lists with fresh data
        function updateContactLists(contacts) {
            const doctorsList = document.getElementById('doctorsList');
            const usersList = document.getElementById('usersList');
            const recentList = document.getElementById('recentList');

            // Clear all lists
            doctorsList.innerHTML = '';
            usersList.innerHTML = '';
            recentList.innerHTML = '';

            const doctors = contacts.filter(c => c.type === 'doctor');
            const users = contacts.filter(c => c.type === 'user');
            const recent = contacts.slice(0, 10); // First 10 are recent chats

            // Populate doctors list
            if (doctors.length > 0) {
                doctors.forEach(contact => {
                    doctorsList.appendChild(createContactElement(contact));
                });
            } else {
                doctorsList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #999;">No doctors available</div>';
            }

            // Populate users list
            if (users.length > 0) {
                users.forEach(contact => {
                    usersList.appendChild(createContactElement(contact));
                });
            } else {
                usersList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #999;">No users available</div>';
            }

            // Populate recent list
            if (recent.length > 0) {
                recent.forEach(contact => {
                    recentList.appendChild(createContactElement(contact));
                });
            } else {
                recentList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #999;">No recent chats</div>';
            }
        }

        // Create a contact element with click handler
        function createContactElement(contact) {
            const div = document.createElement('div');
            div.className = 'contact-item';
            div.dataset.id = contact.id;
            div.dataset.name = contact.name;
            div.dataset.type = contact.type;

            if (contact.specialization) {
                div.dataset.specialization = contact.specialization;
            }
            if (contact.email) {
                div.dataset.email = contact.email;
            }

            const isDoctor = contact.type === 'doctor';
            const avatarText = isDoctor ? '👨‍⚕️' : contact.name.charAt(0).toUpperCase();
            const roleText = isDoctor && contact.specialization ?
                '🩺 ' + contact.specialization :
                (contact.lastMessage === 'No messages yet' ? '🐾 Pet Owner' : contact.lastMessage);

            div.innerHTML = `
                <div class="contact-avatar">${avatarText}</div>
                <div class="contact-info">
                    <div class="contact-name">${contact.name}</div>
                    <div class="contact-role" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${roleText}
                    </div>
                </div>
                ${contact.unreadCount > 0 ? '<div class="unread-badge">' + contact.unreadCount + '</div>' : ''}
            `;

            // Add delete chat button (only show on hover)
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
                const receiverName = this.dataset.name || 'Chat';
                const chatType = this.dataset.type;
                const role = this.dataset.specialization || this.dataset.email || 'User';

                currentReceiverId = receiverId;
                currentChatType = chatType;

                document.getElementById('receiverId').value = receiverId;
                document.getElementById('chatType').value = chatType;
                document.getElementById('chatName').textContent = receiverName;
                document.getElementById('chatRole').textContent = role;
                document.getElementById('chatAvatar').textContent = receiverName.charAt(0).toUpperCase();

                document.getElementById('chatHeader').style.display = 'flex';
                document.getElementById('messageInputArea').style.display = 'block';

                loadMessages(receiverId, chatType);

                // Auto-refresh messages every 3 seconds
                if (messageRefreshInterval) clearInterval(messageRefreshInterval);
                messageRefreshInterval = setInterval(() => {
                    loadMessages(receiverId, chatType, true);
                }, 3000);
            });

            return div;
        }

        // Tab switching
        document.querySelectorAll('.chat-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.chat-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const tabType = this.dataset.tab;
                document.getElementById('doctorsList').style.display = 'none';
                document.getElementById('usersList').style.display = 'none';
                document.getElementById('recentList').style.display = 'none';

                if (tabType === 'doctors') {
                    document.getElementById('doctorsList').style.display = 'block';
                } else if (tabType === 'users') {
                    document.getElementById('usersList').style.display = 'block';
                } else if (tabType === 'recent') {
                    document.getElementById('recentList').style.display = 'block';
                }
            });
        });

        // Initialize and start auto-refresh
        refreshAllContacts(); // Load contacts immediately

        // Auto-refresh contacts every 10 seconds
        setInterval(() => {
            refreshAllContacts();
        }, 10000);

        // Load messages
        function loadMessages(receiverId, chatType, silent = false) {
            if (!silent) {
                document.getElementById('messagesContainer').innerHTML = '<div style="text-align: center; padding: 2rem; color: #999;">Loading messages...</div>';
            }

            fetch('chat_api.php?action=get_messages&receiver_id=' + receiverId + '&chat_type=' + chatType)
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
                    senderLabel.style.color = '#667eea';
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
                        if (currentReceiverId && currentChatType) {
                            loadMessages(currentReceiverId, currentChatType, false);
                            refreshAllContacts();
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
                        document.getElementById('messagesContainer').innerHTML = '<div class="empty-chat"><div class="empty-chat-icon">💬</div><h3>Conversation deleted</h3><p>Select another contact to start chatting</p></div>';
                        document.getElementById('chatHeader').style.display = 'none';
                        document.getElementById('messageInputArea').style.display = 'none';

                        // Clear current chat
                        currentReceiverId = null;
                        currentChatType = null;

                        // Clear message refresh interval
                        if (messageRefreshInterval) {
                            clearInterval(messageRefreshInterval);
                            messageRefreshInterval = null;
                        }

                        // Refresh contact lists
                        refreshAllContacts();
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
            formData.append('chat_type', currentChatType);
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
                        loadMessages(currentReceiverId, currentChatType, false);
                        // Refresh all contact lists
                        refreshAllContacts();
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
        });
    </script>
</body>

</html>