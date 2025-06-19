<?php
require 'config.php';

// Fetch all experiences from the database
$stmt = $pdo->prepare("SELECT * FROM Experience_prestataire ORDER BY date_project DESC");
$stmt->execute();
$experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch media for each experience
foreach ($experiences as &$experience) {
    $stmt = $pdo->prepare("SELECT chemin_fichier, type_contenu FROM Media_experience WHERE id_experience = ? ORDER BY id_media ASC");
    $stmt->execute([$experience['id_experience']]);
    $experience['media'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($experience); // Break the reference with the last element

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Experiences</title>
    <link rel="stylesheet" href="experiences.css">
    <link rel="stylesheet" href="artisan.css"> <!-- Reusing some artisan styles for slider -->
</head>
<body>
    <header>
        <h1>Our Experiences</h1>
    </header>

    <main class="experiences-container">
        <?php if (empty($experiences)): ?>
            <p>No experiences to display yet.</p>
        <?php else: ?>
            <?php foreach ($experiences as $experience): ?>
                <div class="experience-card">
                    <div class="slider-container">
                        <div class="slider-images">
                            <?php if (!empty($experience['media'])): ?>
                                <?php foreach ($experience['media'] as $media): ?>
                                    <?php if ($media['type_contenu'] === 'image'): ?>
                                        <img src="<?= htmlspecialchars($media['chemin_fichier']) ?>" alt="Experience Image">
                                    <?php elseif ($media['type_contenu'] === 'video'): ?>
                                        <video src="<?= htmlspecialchars($media['chemin_fichier']) ?>" controls></video>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <img src="img/no_image_available.png" alt="No Image Available">
                            <?php endif; ?>
                        </div>
                        <?php if (count($experience['media']) > 1): ?>
                            <button class="prev-btn"><</button>
                            <button class="next-btn">></button>
                        <?php endif; ?>
                    </div>
                    <div class="experience-details">
                        <h2 class="experience-title"><?= htmlspecialchars($experience['titre_experience']) ?></h2>
                        <p class="experience-description"><?= htmlspecialchars($experience['description']) ?></p>
                        <p class="experience-year">Year: <?= htmlspecialchars($experience['date_project']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script src="experiences.js"></script>
</body>
</html>