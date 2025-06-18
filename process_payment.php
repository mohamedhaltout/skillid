<?php
session_start();
include 'config.php'; // Include your database configuration file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $devis_id = $_POST['devis_id'];
    $amount = $_POST['amount'];
    $payment_type = $_POST['payment_type'];
    $payment_method = $_POST['payment_method'];
    $statut_paiement = 'effectué'; // Payment is completed

    // Basic validation
    if (empty($devis_id) || empty($amount) || empty($payment_type) || empty($payment_method)) {
        header("Location: failure_page.php?error=All fields are required.");
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO Paiement (id_devis, montant, type_paiement, methode_paiement, statut_paiement) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$devis_id, $amount, $payment_type, $payment_method, $statut_paiement]);

        // Update Devis status to 'pending_payment' or similar
        $stmt_devis = $pdo->prepare("UPDATE Devis SET statut = 'paid', date_paiement_effectue = NOW(), client_confirmation_deadline = DATE_ADD(NOW(), INTERVAL 72 HOUR), artisan_confirmation_deadline = DATE_ADD(NOW(), INTERVAL 72 HOUR) WHERE id_devis = ?");
        $stmt_devis->execute([$devis_id]);

        header("Location: thank_you_page.php");
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['payment_error'] = "Database error during payment processing.";
        header("Location: failure_page.php");
        exit();
    } catch (Exception $e) {
        error_log("General Payment Error: " . $e->getMessage());
        $_SESSION['payment_error'] = "An unexpected error occurred during payment processing.";
        header("Location: failure_page.php");
        exit();
    }
} else {
    $_SESSION['payment_error'] = "Invalid request method.";
    header("Location: client_dashboard.php");
    exit();
}
?>