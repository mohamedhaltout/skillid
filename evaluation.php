<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$devis_id = isset($_GET['devis_id']) ? intval($_GET['devis_id']) : 0;
$artisan_id = isset($_GET['artisan_id']) ? intval($_GET['artisan_id']) : 0;
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

$artisan_name = "N/A";
$project_description = "N/A";
$project_date = "N/A";

if ($devis_id > 0 && $artisan_id > 0 && $client_id > 0) {
    // Fetch artisan and project details
    $stmt = $conn->prepare("
        SELECT
            U.nom AS artisan_nom,
            U.prenom AS artisan_prenom,
            R.description_service,
            D.date_debut_travaux
        FROM Devis D
        JOIN Reservation R ON D.id_reservation = R.id_reservation
        JOIN Prestataire P ON D.id_prestataire = P.id_prestataire
        JOIN Utilisateur U ON P.id_utilisateur = U.id_utilisateur
        WHERE D.id_devis = ? AND D.id_prestataire = ? AND R.id_client = ?
    ");
    $stmt->bind_param("iii", $devis_id, $artisan_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $artisan_name = htmlspecialchars($data['artisan_prenom'] . " " . $data['artisan_nom']);
        $project_description = htmlspecialchars($data['description_service']);
        $project_date = htmlspecialchars($data['date_debut_travaux']);
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = isset($_POST['note']) ? floatval($_POST['note']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $devis_id_post = isset($_POST['devis_id']) ? intval($_POST['devis_id']) : 0;
    $artisan_id_post = isset($_POST['artisan_id']) ? intval($_POST['artisan_id']) : 0;
    $client_id_post = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

    if ($note > 0 && $note <= 5 && $devis_id_post > 0 && $artisan_id_post > 0 && $client_id_post > 0) {
        $date_evaluation = date('Y-m-d');

        // Check if an evaluation already exists for this client, artisan, and date
        $stmt_check = $conn->prepare("SELECT id_evaluation FROM Evaluation WHERE id_client = ? AND id_prestataire = ? AND date_evaluation = ?");
        $stmt_check->bind_param("iis", $client_id_post, $artisan_id_post, $date_evaluation);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['message'] = "You have already submitted an evaluation for this artisan today.";
            $_SESSION['message_type'] = "warning";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO Evaluation (id_client, id_prestataire, note, commentaire, date_evaluation) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("iidss", $client_id_post, $artisan_id_post, $note, $comment, $date_evaluation);

            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Evaluation submitted successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error submitting evaluation: " . $stmt_insert->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    } else {
        $_SESSION['message'] = "Please provide a valid rating and ensure all details are present.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: client_dashboard.php"); // Redirect after form submission
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation - Skilled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="evaluation.css">
</head>
<body>

    <div class="popup-overlay">
        <form class="evaluation-popup" method="POST" action="evaluation.php">
            <div class="popup-header">
                <div class="header-left">
                    <img src="img/skilled_logo.svg" alt="Skilled Logo" class="logo-image">
                    <div class="logo-text">Skilled<span class="logo-dot">.</span></div>
                </div>
            </div>

            <h2 class="popup-title">Évaluez le Prestataire</h2>

            <div class="evaluation-card">
                <div class="evaluation-details">
                    <div class="detail-group">
                        <span class="detail-label">Artisan:</span>
                        <span class="detail-value"><?php echo $artisan_name; ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Projet:</span>
                        <span class="detail-value"><?php echo $project_description; ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value"><?php echo $project_date; ?></span>
                    </div>
                </div>
            </div>

            <h3 class="input-label">Note (sur 5)</h3>
            <div class="select-wrapper">
                <select id="note" name="note" required>
                    <option value="">-- Choisir une note --</option>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Très bien</option>
                    <option value="3">3 - Bien</option>
                    <option value="2">2 - Passable</option>
                    <option value="1">1 - Mauvais</option>
                </select>
                <span class="select-arrow">&#9660;</span>
            </div>

            <h3 class="input-label">Commentaire</h3>
            <textarea id="comment" name="comment" rows="5" placeholder="Exprimer Votre Avis" class="comment-box"></textarea>

            <input type="hidden" name="devis_id" value="<?php echo $devis_id; ?>">
            <input type="hidden" name="artisan_id" value="<?php echo $artisan_id; ?>">
            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

            <button type="submit" class="button submit-evaluation-button">Envoyer l'évaluation</button>
        </form>
    </div>

</body>
</html>