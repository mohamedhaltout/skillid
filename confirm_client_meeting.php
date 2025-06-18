<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['devis_id'])) {
    $devis_id = intval($_GET['devis_id']);
    $id_utilisateur = $_SESSION['id_utilisateur'];

    try {
        $conn->begin_transaction();

        $current_time = date('Y-m-d H:i:s');

        // Verify that the devis belongs to the client and is in a confirmable state
        $stmt = $conn->prepare("SELECT d.id_devis FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = ? AND r.id_client = ? AND d.statut = 'paid' AND d.client_meeting_confirmed = FALSE AND d.client_confirmation_deadline > ?");
        $stmt->bind_param("iis", $devis_id, $id_utilisateur, $current_time);
        error_log("Confirm Meeting Debug: devis_id = " . $devis_id . ", id_utilisateur = " . $id_utilisateur . ", current_time = " . $current_time);
        // Note: For security, do not log actual SQL queries with sensitive data in production. This is for debugging.
        error_log("Confirm Meeting Debug: SQL Query (simulated) = SELECT d.id_devis FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = " . $devis_id . " AND r.id_client = " . $id_utilisateur . " AND d.statut = 'paid' AND d.client_meeting_confirmed = FALSE AND d.client_confirmation_deadline > '" . $current_time . "'");
        $stmt->execute();
        $result = $stmt->get_result();
        error_log("Confirm Meeting Debug: Rows returned = " . $result->num_rows);

        if ($result->num_rows === 0) {
            $_SESSION['message'] = "Devis not found or you don't have permission to confirm this meeting.";
            $_SESSION['message_type'] = "error";
            header("Location: client_dashboard.php");
            exit();
        }
        $stmt->close();

        // Update client_meeting_confirmed status
        $stmt = $conn->prepare("UPDATE Devis SET client_meeting_confirmed = TRUE WHERE id_devis = ?");
        $stmt->bind_param("i", $devis_id);

        if ($stmt->execute()) {
            $conn->commit();

            // Fetch artisan_id and client_id for redirection to evaluation page
            $stmt_fetch_ids = $conn->prepare("SELECT d.id_prestataire, r.id_client FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = ?");
            $stmt_fetch_ids->bind_param("i", $devis_id);
            $stmt_fetch_ids->execute();
            $result_ids = $stmt_fetch_ids->get_result();
            $ids_data = $result_ids->fetch_assoc();
            $stmt_fetch_ids->close();

            if ($ids_data) {
                $artisan_id = $ids_data['id_prestataire'];
                $client_id = $ids_data['id_client'];
                $_SESSION['message'] = "Meeting confirmed successfully! Please evaluate the artisan.";
                $_SESSION['message_type'] = "success";
                header("Location: evaluation.php?devis_id=" . $devis_id . "&artisan_id=" . $artisan_id . "&client_id=" . $client_id);
                exit();
            } else {
                $_SESSION['message'] = "Meeting confirmed, but could not retrieve artisan/client details for evaluation.";
                $_SESSION['message_type'] = "warning";
                header("Location: client_dashboard.php");
                exit();
            }
        } else {
            throw new Exception("Error updating meeting confirmation: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error confirming client meeting: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while confirming the meeting.";
        $_SESSION['message_type'] = "error";
        header("Location: client_dashboard.php");
        exit();
    } finally {
        $conn->close();
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: client_dashboard.php");
    exit();
}
?>