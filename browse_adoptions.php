<?php
require __DIR__ . '/functions.php';
require_login();

// Fetch all available adoptions with location
$adoptions = mongoAggregate('adoptions', [
    [
        '$match' => ['status' => 'available']
    ],
    [
        '$lookup' => [
            'from' => 'users',
            'localField' => 'user_id',
            'foreignField' => '_id',
            'as' => 'user'
        ]
    ],
    ['$unwind' => '$user'],
    ['$sort' => ['created_at' => -1]],
    [
        '$project' => [
            'pet_name' => 1,
            'pet_age' => 1,
            'pet_type' => 1,
            'photo' => 1,
            'description' => 1,
            'contact_info' => 1,
            'location' => 1,
            'city' => 1,
            'latitude' => 1,
            'longitude' => 1,
            'created_at' => 1,
            'owner_name' => '$user.name'
        ]
    ]
]);

$adoptionsArray = mongoResultToArray($adoptions);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Pets for Adoption - Pet Care</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f7fa;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .controls {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #667eea;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: #43a047;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 0;
            height: calc(100vh - 260px);
        }

        #map {
            height: 100%;
            width: 100%;
        }

        .pet-list {
            background: white;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .pet-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            margin-bottom: 1rem;
            padding: 1rem;
            transition: all 0.3s;
            cursor: pointer;
        }

        .pet-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
            transform: translateX(-4px);
        }

        .pet-card.selected {
            border-color: #43a047;
            background: #f1f8f4;
        }

        .pet-card-header {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pet-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .pet-info {
            flex: 1;
        }

        .pet-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .pet-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #667eea;
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pet-age {
            color: #666;
            margin-top: 0.5rem;
        }

        .pet-description {
            color: #555;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .pet-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .pet-contact {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #43a047;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .no-pets {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .no-pets h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .back-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 1rem;
        }

        .back-link:hover {
            background: #f0f0f0;
        }

        .pet-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
                height: auto;
            }

            #map {
                height: 400px;
            }

            .pet-list {
                height: auto;
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
            <a href="adoption.php" class="active">Adoption</a>
            <a href="feed_guidelines.php">Feed Guidelines</a>
            <a href="view_treatments.php">Treatments</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="page-header">
        <h1>🐾 Browse Pets for Adoption</h1>
        <p>Find your perfect companion nearby</p>
        <div style="margin-top: 1rem;">
            <span class="pet-count">📍 <?= count($adoptionsArray) ?> pets available</span>
            <a href="adoption.php" class="back-link" style="margin-left: 1rem;">+ Post Pet for Adoption</a>
        </div>
    </div>

    <div class="controls">
        <input type="text" id="searchBox" class="search-box" placeholder="🔍 Search by name, type, or location...">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="Dog">Dogs</button>
        <button class="filter-btn" data-filter="Cat">Cats</button>
        <button class="filter-btn" data-filter="Bird">Birds</button>
        <button class="filter-btn" data-filter="Other">Others</button>
    </div>

    <div class="main-content">
        <div id="map"></div>
        <div class="pet-list" id="petList">
            <?php if (count($adoptionsArray) > 0): ?>
                <?php foreach ($adoptionsArray as $pet): ?>
                    <div class="pet-card"
                        data-id="<?= (string)$pet['_id'] ?>"
                        data-type="<?= htmlspecialchars($pet['pet_type']) ?>"
                        data-lat="<?= $pet['latitude'] ?? '' ?>"
                        data-lng="<?= $pet['longitude'] ?? '' ?>">
                        <div class="pet-card-header">
                            <?php if ($pet['photo']): ?>
                                <img src="<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['pet_name']) ?>" class="pet-image">
                            <?php else: ?>
                                <div class="pet-image" style="background: #ddd; display: flex; align-items: center; justify-content: center; font-size: 2rem;">🐾</div>
                            <?php endif; ?>
                            <div class="pet-info">
                                <div class="pet-name"><?= htmlspecialchars($pet['pet_name']) ?></div>
                                <span class="pet-type"><?= htmlspecialchars($pet['pet_type']) ?></span>
                                <div class="pet-age">🎂 <?= htmlspecialchars($pet['pet_age']) ?> years old</div>
                            </div>
                        </div>
                        <div class="pet-description">
                            <?= htmlspecialchars($pet['description']) ?>
                        </div>
                        <?php if (!empty($pet['city']) || !empty($pet['location'])): ?>
                            <div class="pet-location">
                                📍
                                <?php
                                $locationParts = array_filter([$pet['location'] ?? '', $pet['city'] ?? '']);
                                echo htmlspecialchars(implode(', ', $locationParts));
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="pet-contact">
                            📞 <?= htmlspecialchars($pet['contact_info']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-pets">
                    <h3>No pets available yet</h3>
                    <p>Be the first to list a pet for adoption!</p>
                    <a href="adoption.php" class="filter-btn">Post Pet for Adoption</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize map
        const map = L.map('map').setView([23.8103, 90.4125], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Pet data from PHP
        const pets = <?= json_encode($adoptionsArray) ?>;

        // Custom pet icons
        const petIcons = {
            'Dog': '🐕',
            'Cat': '🐈',
            'Bird': '🦜',
            'Rabbit': '🐰',
            'Fish': '🐠',
            'Other': '🐾'
        };

        const markers = {};

        // Add markers to map
        pets.forEach(pet => {
            if (pet.latitude && pet.longitude) {
                const icon = L.divIcon({
                    className: 'custom-icon',
                    html: `<div style="font-size: 2rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">${petIcons[pet.pet_type] || '🐾'}</div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });

                const marker = L.marker([pet.latitude, pet.longitude], {
                    icon: icon
                }).addTo(map);

                const popupContent = `
                    <div style="text-align: center; min-width: 150px;">
                        ${pet.photo ? `<img src="${pet.photo}" style="width: 100%; max-width: 200px; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">` : ''}
                        <strong style="font-size: 1.1rem;">${pet.pet_name}</strong><br>
                        <span style="color: #667eea;">${pet.pet_type}</span><br>
                        <span style="color: #666;">Age: ${pet.pet_age} years</span><br>
                        <button onclick="showPetDetails('${pet._id}')" style="margin-top: 8px; padding: 6px 12px; background: #43a047; color: white; border: none; border-radius: 6px; cursor: pointer;">View Details</button>
                    </div>
                `;

                marker.bindPopup(popupContent);
                markers[pet._id] = marker;

                // Click marker to highlight card
                marker.on('click', () => {
                    highlightPetCard(pet._id);
                });
            }
        });

        // Fit map to show all markers
        if (Object.keys(markers).length > 0) {
            const group = new L.featureGroup(Object.values(markers));
            map.fitBounds(group.getBounds().pad(0.1));
        }

        // Pet card click
        document.querySelectorAll('.pet-card').forEach(card => {
            card.addEventListener('click', function() {
                const petId = this.dataset.id;
                const lat = parseFloat(this.dataset.lat);
                const lng = parseFloat(this.dataset.lng);

                highlightPetCard(petId);

                if (lat && lng && markers[petId]) {
                    map.setView([lat, lng], 15);
                    markers[petId].openPopup();
                }
            });
        });

        function highlightPetCard(petId) {
            document.querySelectorAll('.pet-card').forEach(c => c.classList.remove('selected'));
            const card = document.querySelector(`.pet-card[data-id="${petId}"]`);
            if (card) {
                card.classList.add('selected');
                card.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }

        function showPetDetails(petId) {
            highlightPetCard(petId);
        }

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.pet-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? 'block' : 'none';
            });
        });

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;

                document.querySelectorAll('.pet-card').forEach(card => {
                    if (filter === 'all' || card.dataset.type === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>