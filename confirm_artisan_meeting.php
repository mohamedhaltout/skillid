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
        $stmt = $conn->prepare("SELECT id_devis FROM Devis WHERE id_devis = ? AND id_prestataire = ?");
        $stmt->bind_param("ii", $devis_id, $artisan_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['message'] = "Devis not found or you don't have permission to confirm this meeting.";
            $_SESSION['message_type'] = "error";
            header("Location: artisan_dashboard.php");
            exit();
        }
        $stmt->close();

        // Update artisan_meeting_confirmed status
        $stmt = $conn->prepare("UPDATE Devis SET artisan_meeting_confirmed = TRUE WHERE id_devis = ?");
        $stmt->bind_param("i", $devis_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Meeting confirmed successfully!";
            $_SESSION['message_type'] = "success";
            $conn->commit();
        } else {
            throw new Exception("Error updating meeting confirmation: " . $stmt->error);
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