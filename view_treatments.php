<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Use aggregation to join cat_treatments with doctors
$treatments = mongoAggregate('cat_treatments', [
    [
        '$lookup' => [
            'from' => 'doctors',
            'localField' => 'doctor_id',
            'foreignField' => '_id',
            'as' => 'doctor'
        ]
    ],
    ['$unwind' => '$doctor'],
    ['$sort' => ['created_at' => -1]],
    [
        '$project' => [
            'title' => 1,
            'content' => 1,
            'created_at' => 1,
            'doctor_name' => '$doctor.full_name',
            'specialization' => '$doctor.specialization'
        ]
    ]
]);

include __DIR__ . '/header.php';
?>

<div class="treatments-container">
    <h2>Cat Health Treatments</h2>
    <p>Find basic treatments and advice for your cat, provided by our registered doctors.</p>
    <br>
    <?php
    $treatmentsArray = iterator_to_array($treatments);
    if (count($treatmentsArray) > 0): ?>
        <?php foreach ($treatmentsArray as $treatment): ?>
            <div class="treatment-card">
                <h3><?= htmlspecialchars($treatment['title']) ?></h3>
                <div class="doctor-info">
                    By Dr. <?= htmlspecialchars($treatment['doctor_name']) ?> (<?= htmlspecialchars($treatment['specialization']) ?>)
                </div>
                <p><?= nl2br(htmlspecialchars($treatment['content'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No treatments have been posted yet. Please check back later.</p>
    <?php endif; ?>
</div>