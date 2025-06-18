<?php
session_start();

require 'config.php';

$erreur = '';

if (isset($_POST["email"], $_POST["password"])) {
    $email = trim($_POST["email"]);
    $mot_de_passe = trim($_POST["password"]);

    if (!empty($email) && !empty($mot_de_passe)) {
        $stmt = $pdo->prepare("SELECT id_utilisateur, email, mot_de_passe, role, nom, prenom FROM Utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur && isset($utilisateur['role']) && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {

          $role = strtolower(trim($utilisateur['role']));

            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['email'] = $utilisateur['email'];
            $_SESSION['role'] = $role;
            $_SESSION['nom'] = $utilisateur['nom'];
            $_SESSION['prenom'] = $utilisateur['prenom'];

            switch ($role) {
                case 'client':
                    header("Location: search.php");
                    exit();
                case 'prestataire':
                    // Fetch id_prestataire from Prestataire table
                    $stmt_prestataire = $pdo->prepare("SELECT id_prestataire FROM Prestataire WHERE id_utilisateur = ?");
                    $stmt_prestataire->execute([$utilisateur['id_utilisateur']]);
                    $prestataire_data = $stmt_prestataire->fetch(PDO::FETCH_ASSOC);

                    if ($prestataire_data) {
                        $_SESSION['artisan_id'] = $prestataire_data['id_prestataire'];
                        error_log("Login successful for artisan: " . $utilisateur['email'] . ", artisan_id: " . $_SESSION['artisan_id'] . ", role: " . $_SESSION['role']);
                        header("Location: artisan_dashboard.php");
                        exit();
                    } else {
                        $erreur = "Prestataire non trouvé pour cet utilisateur.";
                        error_log("Error: Prestataire not found for user ID: " . $utilisateur['id_utilisateur']);
                    }
                    break;
                case 'admin':
                    header("Location: admin_dashboard.php");
                    exit();
                default:
                    $erreur = "Rôle d'utilisateur inconnu : " . htmlspecialchars($role);
                    break; // Add break here
            }
        } else {
            if (!$utilisateur) {
                $erreur = "Adresse e-mail introuvable.";
            } else {
                $erreur = "Mot de passe incorrect.";
            }
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="login-container">
    <div class="left-decoration"></div>
    <div class="right-content">
      <div class="logo-container">
        <img src="img/Logo_image.png" alt="Logo" class="logo-img">
        <div class="logo-text">
          <span>Skilled</span><span>.</span>
        </div>
      </div>

      <div class="welcome-title">Connexion</div>
      <div class="mini-description">
        Veuillez vous connecter avec votre compte pour accéder à la plateforme Skilled.
      </div>

      <?php if (!empty($erreur)): ?>
        <p style="color:red; font-weight:bold; text-align:center;"><?= htmlspecialchars($erreur) ?></p>
      <?php endif; ?>

      <form class="login-form" method="POST" action="">
        <input type="email" name="email" placeholder="Email Adress" class="input-field" required>
        <input type="password" name="password" placeholder="Password" class="input-field" required>
        <button type="submit" class="login-button">Se connecter</button>
      </form>

      <div class="signup-link">
        Vous n’avez pas de compte ? <a href="sign_up_client.php"><b>Créer un compte</b></a>
      </div>
    </div>
  </div>
</body>
</html>
