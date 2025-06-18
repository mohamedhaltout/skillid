<?php
require 'config.php';
session_start();

// Redirect if not logged in or not a client
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$message = '';

// Fetch client data
$stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.email, c.telephone FROM Utilisateur u JOIN Client c ON u.id_utilisateur = c.id_utilisateur WHERE u.id_utilisateur = ?");
$stmt->execute([$id_utilisateur]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    // This should ideally not happen if session is valid
    header('Location: login.php');
    exit();
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_nom = trim($_POST['nom'] ?? '');
    $new_prenom = trim($_POST['prenom'] ?? '');
    $new_telephone = trim($_POST['telephone'] ?? '');

    if (empty($new_nom) || empty($new_prenom) || empty($new_telephone)) {
        $message = "All fields are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update Utilisateur table
            $stmt_user = $pdo->prepare("UPDATE Utilisateur SET nom = ?, prenom = ? WHERE id_utilisateur = ?");
            $stmt_user->execute([$new_nom, $new_prenom, $id_utilisateur]);

            // Update Client table
            $stmt_client = $pdo->prepare("UPDATE Client SET telephone = ? WHERE id_utilisateur = ?");
            $stmt_client->execute([$new_telephone, $id_utilisateur]);

            $pdo->commit();
            $message = "Profile updated successfully!";
            // Refresh client data after update
            $stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.email, c.telephone FROM Utilisateur u JOIN Client c ON u.id_utilisateur = c.id_utilisateur WHERE u.id_utilisateur = ?");
            $stmt->execute([$id_utilisateur]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update session variables if necessary
            $_SESSION['nom'] = $new_nom;
            $_SESSION['prenom'] = $new_prenom;

        } catch (PDOException $e) {
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
    <title>Client Profile - Skilled</title>
    <link rel="stylesheet" href="client_profile.css">
</head>
<body>
    <div class="profile-container">
        <h2>Client Profile</h2>
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Error') === false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form class="profile-form" method="POST">
            <label for="nom">First Name:</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($client['nom']) ?>" required>

            <label for="prenom">Last Name:</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($client['prenom']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($client['email']) ?>" disabled>

            <label for="telephone">Phone Number:</label>
            <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" required>

            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>