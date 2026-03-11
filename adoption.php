<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pet_name = $_POST['pet_name'];
    $age = (int)$_POST['pet_age'];
    $type = $_POST['pet_type'];
    $desc = $_POST['description'];
    $contact = $_POST['contact'];
    $location = $_POST['location'] ?? '';
    $city = $_POST['city'] ?? '';
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = "uploads/" . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    $insertId = mongoInsert('adoptions', [
        'user_id' => stringToObjectId($uid),
        'pet_name' => $pet_name,
        'pet_age' => $age,
        'pet_type' => $type,
        'photo' => $photo,
        'description' => $desc,
        'contact_info' => $contact,
        'location' => $location,
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'status' => 'available',
        'created_at' => getCurrentDateTime()
    ]);

    if ($insertId) {
        $success = "Pet listed for adoption with location!";
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Pet Adoption - Pet Care</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid #ddd;
        }

        .location-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .browse-link {
            display: inline-block;
            background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .browse-link:hover {
            transform: translateY(-2px);
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

    <main class="container">
        <h2>Pet Adoption</h2>

        <a href="browse_adoptions.php" class="browse-link">🗺️ Browse Pets on Map</a>

        <?php if (isset($success)): ?>
            <div class="alert success"><?= esc($success) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <label for="pet_name">Pet Name</label>
                <input type="text" id="pet_name" name="pet_name" placeholder="Pet Name" required>
            </div>

            <div class="form-group">
                <label for="pet_age">Pet Age</label>
                <input type="number" id="pet_age" name="pet_age" placeholder="Pet Age">
            </div>

            <div class="form-group">
                <label for="pet_type">Pet Type</label>
                <select id="pet_type" name="pet_type" required>
                    <option value="">Select Type</option>
                    <option value="Dog">Dog</option>
                    <option value="Cat">Cat</option>
                    <option value="Bird">Bird</option>
                    <option value="Rabbit">Rabbit</option>
                    <option value="Fish">Fish</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe your pet" required></textarea>
            </div>

            <div class="form-group">
                <label for="contact">Contact Information</label>
                <input type="text" id="contact" name="contact" placeholder="Your Contact Info (Phone/Email)" required>
            </div>

            <div class="form-group">
                <label for="photo">Pet Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*" required>
            </div>

            <h3 style="margin-top: 30px; margin-bottom: 15px;">📍 Location Information</h3>

            <div class="location-info">
                <strong>ℹ️ How to set location:</strong><br>
                1. Click on the map to set your pet's location<br>
                2. Or enter city name and click "Search City"<br>
                3. Location helps adopters find pets nearby!
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="city" name="city" placeholder="e.g., Dhaka, Chittagong" style="flex: 1;">
                    <button type="button" id="searchCity" class="btn" style="width: auto; padding: 0 20px;">🔍 Search City</button>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Address/Area (Optional)</label>
                <input type="text" id="location" name="location" placeholder="e.g., Gulshan, Banani">
            </div>

            <div class="form-group">
                <label>Select Location on Map</label>
                <div id="map"></div>
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <small style="color: #666;">Click anywhere on the map to mark pet's location</small>
            </div>

            <button type="submit" class="btn">Post for Adoption</button>
        </form>
    </main>

    <script>
        // Initialize map centered on Bangladesh
        const map = L.map('map').setView([23.8103, 90.4125], 7);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker = null;

        // Click on map to set location
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            // Remove old marker
            if (marker) {
                map.removeLayer(marker);
            }

            // Add new marker
            marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup("Pet Location").openPopup();

            // Set coordinates
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
        });

        // Search city
        document.getElementById('searchCity').addEventListener('click', async function() {
            const city = document.getElementById('city').value.trim();

            if (!city) {
                alert('Please enter a city name');
                return;
            }

            try {
                // Use Nominatim (OpenStreetMap) geocoding API
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(city + ', Bangladesh')}&format=json&limit=1`);
                const data = await response.json();

                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);

                    // Center map
                    map.setView([lat, lon], 13);

                    // Remove old marker
                    if (marker) {
                        map.removeLayer(marker);
                    }

                    // Add marker
                    marker = L.marker([lat, lon]).addTo(map);
                    marker.bindPopup(city).openPopup();

                    // Set coordinates
                    document.getElementById('latitude').value = lat.toFixed(6);
                    document.getElementById('longitude').value = lon.toFixed(6);
                } else {
                    alert('City not found. Please try another name or click on the map.');
                }
            } catch (error) {
                alert('Error searching city. Please click on the map instead.');
            }
        });

        // Get user's current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                map.setView([lat, lng], 13);
            }, function() {
                // Geolocation failed, stay at default (Bangladesh)
            });
        }
    </script>
</body>

</html>