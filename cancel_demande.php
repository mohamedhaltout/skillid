<?php
session_start();
include('config.php');


// check if the user was log in and he is a client
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}


// Check if has in reservation id if not redirect the user to the client dashboard 
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No reservation ID specified.";
    $_SESSION['message_type'] = 'error';
    header("Location: client_dashboard.php");
    exit();
}

$id_reservation = (int)$_GET['id'];
$id_utilisateur = $_SESSION['id_utilisateur'];

try {
    // First, get the client_id from the id_utilisateur
    $stmt_client = $pdo->prepare("SELECT id_client FROM Client WHERE id_utilisateur = ?");
    $stmt_client->execute([$id_utilisateur]);
    $client_data = $stmt_client->fetch(PDO::FETCH_ASSOC);

    if (!$client_data) {
        $_SESSION['message'] = "Client profile not found.";
        $_SESSION['message_type'] = 'error';
        header("Location: client_dashboard.php");
        exit();
    }

    $id_client = $client_data['id_client'];

    // Verify the reservation belongs to the client and is in 'pending' status
    $stmt_check = $pdo->prepare("SELECT statut FROM Reservation WHERE id_reservation = ? AND id_client = ?");
    $stmt_check->execute([$id_reservation, $id_client]);
    $reservation = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $_SESSION['message'] = "Reservation not found or you do not have permission to cancel it.";
        $_SESSION['message_type'] = 'error';
        header("Location: client_dashboard.php");
        exit();
    }

    if ($reservation['statut'] !== 'pending') {
        $_SESSION['message'] = "Only pending requests can be cancelled.";
        $_SESSION['message_type'] = 'error';
        header("Location: client_dashboard.php");
        exit();
    }

    // Update the reservation status to 'cancelled'
    $stmt_update = $pdo->prepare("UPDATE Reservation SET statut = 'cancelled' WHERE id_reservation = ?");
    $stmt_update->execute([$id_reservation]);

    $_SESSION['message'] = "Service request cancelled successfully!";
    $_SESSION['message_type'] = 'success';

} catch (PDOException $e) {
    $_SESSION['message'] = "Error cancelling service request: " . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

header("Location: client_dashboard.php");
exit();
?>