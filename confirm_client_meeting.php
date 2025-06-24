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

        // Get id_client from id_utilisateur
        $stmt_client = $conn->prepare("SELECT id_client FROM Client WHERE id_utilisateur = ?");
        $stmt_client->bind_param("i", $id_utilisateur);
        $stmt_client->execute();
        $result_client = $stmt_client->get_result();
        $client_data = $result_client->fetch_assoc();
        $stmt_client->close();

        if (!$client_data) {
            $_SESSION['message'] = "Client profile not found.";
            $_SESSION['message_type'] = "error";
            header("Location: client_dashboard.php");
            exit();
        }
        $id_client = $client_data['id_client'];

        $current_time = date('Y-m-d H:i:s');

        // Verify that the devis belongs to the client and is in a confirmable state
        $stmt = $conn->prepare("SELECT d.id_devis, d.date_debut_travaux, d.client_meeting_confirmed, d.artisan_meeting_confirmed FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = ? AND r.id_client = ?");
        $stmt->bind_param("ii", $devis_id, $id_client);
        $stmt->execute();
        $result = $stmt->get_result();
        $devis_data = $result->fetch_assoc();

        if (!$devis_data) {
            $_SESSION['message'] = "Devis not found or you do not have permission for this devis.";
            $_SESSION['message_type'] = "error";
            header("Location: client_dashboard.php");
            exit();
        }
        $stmt->close();

        $date_debut_travaux = new DateTime($devis_data['date_debut_travaux']);

        if ($devis_data['client_meeting_confirmed']) {
            $_SESSION['message'] = "You have already confirmed this meeting.";
            $_SESSION['message_type'] = "info";
            header("Location: client_dashboard.php");
            exit();
        }

        // Update client_meeting_confirmed status
        $stmt = $conn->prepare("UPDATE Devis SET client_meeting_confirmed = TRUE WHERE id_devis = ?");
        $stmt->bind_param("i", $devis_id);

        // Update client_meeting_confirmed status
        $stmt = $conn->prepare("UPDATE Devis SET client_meeting_confirmed = TRUE WHERE id_devis = ?");
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
                    $_SESSION['message'] = "Meeting confirmed by both parties! Please evaluate the artisan.";
                    $_SESSION['message_type'] = "success";
                    header("Location: evaluation.php?devis_id=" . $devis_id . "&artisan_id=" . $artisan_id . "&client_id=" . $client_id);
                    exit();
                } else {
                    $_SESSION['message'] = "Meeting confirmed by both parties, but could not retrieve artisan/client details for evaluation.";
                    $_SESSION['message_type'] = "warning";
                    header("Location: client_dashboard.php");
                    exit();
                }
            } else {
                // Only client confirmed, wait for artisan
                $conn->commit();
                $_SESSION['message'] = "Meeting confirmed by you. Waiting for artisan's confirmation.";
                $_SESSION['message_type'] = "success";
                header("Location: client_dashboard.php");
                exit();
            }
        } else {
            throw new Exception("Error updating client meeting confirmation: " . $stmt->error);
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