<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'cancel_work' && isset($_POST['id_devis'])) {
    $id_devis = intval($_POST['id_devis']);
    $artisan_id = $_SESSION['artisan_id'];

    try {
        $conn->begin_transaction();

        // Verify that the devis belongs to the artisan
        $stmt_verify = $conn->prepare("SELECT d.id_devis, d.id_reservation FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = ? AND r.id_prestataire = ?");
        $stmt_verify->bind_param("ii", $id_devis, $artisan_id);
        $stmt_verify->execute();
        $result_verify = $stmt_verify->get_result();
        $devis_data = $result_verify->fetch_assoc();
        $stmt_verify->close();

        if (!$devis_data) {
            $_SESSION['message'] = "Devis not found or you do not have permission to cancel it.";
            $_SESSION['message_type'] = "error";
            header("Location: artisan_dashboard.php");
            exit();
        }

        // Update Devis status to 'cancelled'
        $stmt_devis = $conn->prepare("UPDATE Devis SET statut = 'cancelled' WHERE id_devis = ?");
        $stmt_devis->bind_param("i", $id_devis);
        if (!$stmt_devis->execute()) {
            throw new Exception("Error updating devis status: " . $stmt_devis->error);
        }
        $stmt_devis->close();

        // Optionally, update Reservation status to 'cancelled_by_artisan'
        // This assumes a 'cancelled_by_artisan' status exists or is desired for reservations.
        // If not, you might want to adjust this or remove it.
        $id_reservation = $devis_data['id_reservation'];
        $stmt_reservation = $conn->prepare("UPDATE Reservation SET statut = 'cancelled_by_artisan' WHERE id_reservation = ?");
        $stmt_reservation->bind_param("i", $id_reservation);
        if (!$stmt_reservation->execute()) {
            throw new Exception("Error updating reservation status: " . $stmt_reservation->error);
        }
        $stmt_reservation->close();

        $conn->commit();
        $_SESSION['message'] = "Work cancelled successfully.";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error cancelling work: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while cancelling the work.";
        $_SESSION['message_type'] = "error";
    } finally {
        $conn->close();
    }
    header("Location: view_devis.php?id=" . $id_devis);
    exit();
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: artisan_dashboard.php");
    exit();
}
?>