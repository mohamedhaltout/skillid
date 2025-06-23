<?php
require 'config.php';
session_start();

// Redirect if not logged in or not an artisan
if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$message = '';

// Fetch artisan data
$stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.email, p.photo, p.specialite, p.pays, p.ville, p.telephone, p.tarif_journalier, p.accepte_budget_global, p.id_categorie FROM Utilisateur u JOIN Prestataire p ON u.id_utilisateur = p.id_utilisateur WHERE u.id_utilisateur = ?");
$stmt->execute([$id_utilisateur]);
$artisan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artisan) {
    // This should ideally not happen if session is valid
    header('Location: login.php');
    exit();
}

// Fetch categories for the dropdown
$categories = $pdo->query("SELECT id_categorie, nom FROM Categories WHERE type = 'standard'")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_nom = trim($_POST['nom'] ?? '');
    $new_prenom = trim($_POST['prenom'] ?? '');
    $new_telephone = trim($_POST['telephone'] ?? '');
    $new_specialite_id = $_POST['specialite'] ?? '';
    $new_pays = trim($_POST['pays'] ?? '');
    $new_ville = trim($_POST['ville'] ?? '');
    $new_tarif_journalier = trim($_POST['tarif_journalier'] ?? '');
    $new_accepte_budget_global = isset($_POST['accept_budget']) ? 1 : 0;


    $photo_updated = false;
    $new_photo_path = $artisan['photo']; // Default to existing photo

    // Handle photo upload if a new one is provided
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir);

        $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            $new_photo_path = $uploadPath;
            $photo_updated = true;
        } else {
            $message = "Error uploading new photo.";
        }
    }


// Check if all the field is Not empty
    if (empty($new_nom) || empty($new_prenom) || empty($new_telephone) || empty($new_specialite_id) || empty($new_pays) || empty($new_ville) || empty($new_tarif_journalier)) {
        $message = "All fields are required.";
    } else {
        try {

            // Start The Transaction
            $pdo->beginTransaction();

            // Update Utilisateur table
            $stmt_user = $pdo->prepare("UPDATE Utilisateur SET nom = ?, prenom = ? WHERE id_utilisateur = ?");
            $stmt_user->execute([$new_nom, $new_prenom, $id_utilisateur]);

            // Update Prestataire table
            $sql_prestataire = "UPDATE Prestataire SET id_categorie = ?, pays = ?, ville = ?, telephone = ?, tarif_journalier = ?, accepte_budget_global = ?";
            $params_prestataire = [$new_specialite_id, $new_pays, $new_ville, $new_telephone, $new_tarif_journalier, $new_accepte_budget_global];

            if ($photo_updated) {
                $sql_prestataire .= ", photo = ?";
                $params_prestataire[] = $new_photo_path;
            }
            $sql_prestataire .= " WHERE id_utilisateur = ?";
            $params_prestataire[] = $id_utilisateur;

            $stmt_prestataire = $pdo->prepare($sql_prestataire);
            $stmt_prestataire->execute($params_prestataire);


            // If all Success Edit Commit
            $pdo->commit();
            $message = "Profile updated successfully!";
            // Refresh artisan data after update
            $stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.email, p.photo, p.specialite, p.pays, p.ville, p.telephone, p.tarif_journalier, p.accepte_budget_global, p.id_categorie FROM Utilisateur u JOIN Prestataire p ON u.id_utilisateur = p.id_utilisateur WHERE u.id_utilisateur = ?");
            $stmt->execute([$id_utilisateur]);
            $artisan = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update session names in the session
            $_SESSION['nom'] = $new_nom;
            $_SESSION['prenom'] = $new_prenom;

        } catch (PDOException $e) {
            // If not Update Roll Back and show a Error Message
            $pdo->rollBack();
            $message = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Profile - Skilled</title>
    <link rel="stylesheet" href="artisan_profile.css">
</head>
<body>
    <div class="profile-container">
        <h2>Artisan Profile</h2>
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Error') === false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form class="profile-form" method="POST" enctype="multipart/form-data">
            <img src="<?= htmlspecialchars($artisan['photo']) ?>" alt="Current Profile Photo" class="current-photo">

            <div class="form-group upload-group">
                <div class="form-info">
                    <label for="profile-photo">Change Profile Photo</label>
                    <div class="description">Upload a new photo to update your profile.</div>
                </div>
                <div class="form-input photo-upload-input">
                    <label for="profile-photo" class="upload-button">
                        <input type="file" id="profile-photo" name="photo" accept="image/*" hidden>
                        <img src="img/upload_image.svg" alt="Upload Icon" class="upload-icon">
                        <span class="upload-placeholder">Upload New Photo</span>
                    </label>
                </div>
            </div>

            <label for="nom">First Name:</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($artisan['nom']) ?>" required>

            <label for="prenom">Last Name:</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($artisan['prenom']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($artisan['email']) ?>" disabled>

            <label for="telephone">Phone Number:</label>
            <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($artisan['telephone']) ?>" required>

            <label for="specialite">Specialty:</label>
            <select name="specialite" id="specialite" required>
                <option value="" disabled>Choose your specialty</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id_categorie'] ?>" <?= $artisan['id_categorie'] == $cat['id_categorie'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="country-select">Country:</label>
            <select name="pays" id="country-select" onchange="updateCities()" required>
                <option disabled>Country</option>
                <?php
                $countries = ['Morocco', 'Spain', 'France', 'Belgium', 'Netherlands', 'United Kingdom'];
                foreach ($countries as $country): ?>
                    <option value="<?= htmlspecialchars($country) ?>" <?= $artisan['pays'] == $country ? 'selected' : '' ?>>
                        <?= htmlspecialchars($country) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="city-select">City:</label>
            <select name="ville" id="city-select" required>
                <option disabled>City</option>
                <?php if ($artisan['pays']):
                    $cities = [
                        'Morocco' => ['Tanger', 'Casablanca', 'Rabat', 'Marrakech', 'Fes'],
                        'Spain' => ['Madrid', 'Barcelona', 'Seville'],
                        'France' => ['Paris', 'Lyon', 'Marseille'],
                        'Belgium' => ['Brussels', 'Antwerp', 'Ghent'],
                        'Netherlands' => ['Amsterdam', 'Rotterdam', 'Utrecht'],
                        'United Kingdom' => ['London', 'Manchester', 'Birmingham']
                    ][$artisan['pays']] ?? [];
                    foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>" <?= $artisan['ville'] == $city ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city) ?>
                        </option>
                    <?php endforeach;
                endif; ?>
            </select>

            <label for="tarif_journalier">Daily Rate:</label>
            <input type="number" id="tarif_journalier" name="tarif_journalier" value="<?= htmlspecialchars($artisan['tarif_journalier']) ?>" required>

            <div class="form-group checkbox-group">
                <div class="form-info">
                    <label>Accept Total Budget</label>
                    <div class="description">Allow clients to propose a total project budget.</div>
                </div>
                <div class="form-input checkbox-input-container">
                    <input type="checkbox" id="accept-budget" name="accept_budget" class="custom-checkbox" <?= $artisan['accepte_budget_global'] ? 'checked' : '' ?>>
                    <label for="accept-budget" class="checkbox-label">
                        <img src="img/checkbox.svg" class="checkbox-icon">
                        <span>Accept total budget</span>
                    </label>
                </div>
            </div>

            <button type="submit">Update Profile</button>
        </form>
        <div class="add-experience-button-container">
            <a href="ad_exeperience.php" class="add-experience-button">Add New Experience</a>
        </div>
    </div>

    <script>
        let citiesByCountry = {
            "Morocco": ["Tanger", "Casablanca", "Rabat", "Marrakech", "Fes"],
            "Spain": ["Madrid", "Barcelona", "Seville"],
            "France": ["Paris", "Lyon", "Marseille"],
            "Belgium": ["Brussels", "Antwerp", "Ghent"],
            "Netherlands": ["Amsterdam", "Rotterdam", "Utrecht"],
            "United Kingdom": ["London", "Manchester", "Birmingham"]
        };

        function updateCities() {
            let country = document.getElementById("country-select").value;
            let citySelect = document.getElementById("city-select");
            citySelect.innerHTML = '<option disabled selected>City</option>';

            if (citiesByCountry[country]) {
                citiesByCountry[country].forEach(function(city) {
                    let option = document.createElement("option");
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        }

        // Call updateCities on page load to populate cities based on current country
        document.addEventListener('DOMContentLoaded', function() {
            updateCities();
            // Set the city dropdown to the artisan's current city after populating
            let currentCity = "<?= htmlspecialchars($artisan['ville']) ?>";
            if (currentCity) {
                document.getElementById("city-select").value = currentCity;
            }
        });

        // Handle custom checkbox appearance
        document.addEventListener('DOMContentLoaded', function() {
            const customCheckbox = document.getElementById('accept-budget');
            const checkboxIcon = customCheckbox.nextElementSibling.querySelector('.checkbox-icon');

            function updateCheckboxIcon() {
                if (customCheckbox.checked) {
                    checkboxIcon.src = 'img/cheked.svg'; // Path to checked icon
                } else {
                    checkboxIcon.src = 'img/checkbox.svg'; // Path to unchecked icon
                }
            }

            customCheckbox.addEventListener('change', updateCheckboxIcon);
            updateCheckboxIcon(); // Set initial state
        });
    </script>
</body>
</html>