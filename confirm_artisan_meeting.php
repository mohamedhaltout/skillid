<?php
session_start();
include('config.php');

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['devis_id'])) {
    $devis_id = intval($_GET['devis_id']);
    $artisan_id = $_SESSION['artisan_id'];

    try {
        $conn->begin_transaction();

        // Verify that the devis belongs to the artisan
        $stmt = $conn->prepare("SELECT id_devis, date_debut_travaux, client_meeting_confirmed, artisan_meeting_confirmed FROM Devis WHERE id_devis = ? AND id_prestataire = ?");
        $stmt->bind_param("ii", $devis_id, $artisan_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $devis_data = $result->fetch_assoc();
        if (!$devis_data) {
            $_SESSION['message'] = "Devis not found or you don't have permission to confirm this meeting.";
            $_SESSION['message_type'] = "error";
            header("Location: artisan_dashboard.php");
            exit();
        }
        $stmt->close();

        $date_debut_travaux = new DateTime($devis_data['date_debut_travaux']);
        $confirmation_deadline = clone $date_debut_travaux;
        $confirmation_deadline->modify('-72 hours'); // 72 hours before date_debut_travaux

        $current_time = new DateTime();

        if ($current_time < $confirmation_deadline) {
            $_SESSION['message'] = "Meeting confirmation is not yet open. You can confirm within 72 hours before the work start date.";
            $_SESSION['message_type'] = "error";
            header("Location: artisan_dashboard.php");
            exit();
        }

        if ($devis_data['artisan_meeting_confirmed']) {
            $_SESSION['message'] = "You have already confirmed this meeting.";
            $_SESSION['message_type'] = "info";
            header("Location: artisan_dashboard.php");
            exit();
        }

        // Update artisan_meeting_confirmed status
        $stmt = $conn->prepare("UPDATE Devis SET artisan_meeting_confirmed = TRUE WHERE id_devis = ?");
        $stmt->bind_param("i", $devis_id);

        if ($stmt->execute()) {
            // Re-fetch confirmation status after update
            $stmt_check_both = $conn->prepare("SELECT client_meeting_confirmed, artisan_meeting_confirmed FROM Devis WHERE id_devis = ?");
            $stmt_check_both->bind_param("i", $devis_id);
            $stmt_check_both->execute();
            $result_both = $stmt_check_both->get_result();
            $confirmation_status = $result_both->fetch_assoc();
            $stmt_check_both->close();

            if ($confirmation_status['client_meeting_confirmed'] && $confirmation_status['artisan_meeting_confirmed']) {
                // Both confirmed, update devis status to 'meeting_confirmed'
                $stmt_update_status = $conn->prepare("UPDATE Devis SET statut = 'meeting_confirmed' WHERE id_devis = ?");
                $stmt_update_status->bind_param("i", $devis_id);
                $stmt_update_status->execute();
                $stmt_update_status->close();

                $conn->commit();
                $_SESSION['message'] = "Meeting confirmed by both parties!";
                $_SESSION['message_type'] = "success";
            } else {
                // Only artisan confirmed, wait for client
                $conn->commit();
                $_SESSION['message'] = "Meeting confirmed by you. Waiting for client's confirmation.";
                $_SESSION['message_type'] = "success";
            }
        } else {
            throw new Exception("Error updating artisan meeting confirmation: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error confirming artisan meeting: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while confirming the meeting.";
        $_SESSION['message_type'] = "error";
    } finally {
        $conn->close();
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
}

header("Location: artisan_dashboard.php");
exit();
?>