<?php
session_start();
include('config.php');

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $reservation_id = $_GET['id'];
    $artisan_id = $_SESSION['artisan_id'];

    if ($action == 'accept') {
        $status = 'accepted';
    } else {
        // Invalid action
        header("Location: artisan_dashboard.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE Reservation SET statut = ? WHERE id_reservation = ? AND id_prestataire = ?");
    $stmt->bind_param("sii", $status, $reservation_id, $artisan_id);

    if ($stmt->execute()) {
        if ($action == 'accept') {
            // Fetch the details of the accepted request to return to the client
            $stmt_fetch = $conn->prepare("SELECT r.id_reservation, u.nom AS client_nom, u.prenom AS client_prenom, cl.telephone AS client_phone, r.date_debut, p.ville
                                        FROM Reservation r
                                        JOIN Client cl ON r.id_client = cl.id_client
                                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                                        JOIN Prestataire p ON r.id_prestataire = p.id_prestataire
                                        WHERE r.id_reservation = ?");
            $stmt_fetch->bind_param("i", $reservation_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $accepted_demande_details = $result_fetch->fetch_assoc();
            $stmt_fetch->close();

            echo json_encode(['status' => 'success', 'message' => 'Request accepted successfully.', 'demande' => $accepted_demande_details]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to ' . $action . ' request.']);
    }
    $stmt->close();
    $conn->close();
    exit(); // Ensure no further output after JSON
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action or missing ID.']);
    exit();
}
?>