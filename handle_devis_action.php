<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['id_devis'])) {
    error_log("Debug: handle_devis_action.php - Raw POST id_devis: " . (isset($_POST['id_devis']) ? $_POST['id_devis'] : 'NOT SET'));
    $id_devis = intval($_POST['id_devis']);
    error_log("Debug: handle_devis_action.php - id_devis (intval): " . $id_devis);
    $action = $_POST['action'];
    $id_client = 0;

    // Get client ID
    $stmt_client = $conn->prepare("SELECT id_client FROM Client WHERE id_utilisateur = ?");
    $stmt_client->bind_param("i", $_SESSION['id_utilisateur']);
    $stmt_client->execute();
    $result_client = $stmt_client->get_result();
    $client_data = $result_client->fetch_assoc();
    $stmt_client->close();

    if ($client_data) {
        $id_client = $client_data['id_client'];
    } else {
        $_SESSION['message'] = "Client not found.";
        $_SESSION['message_type'] = "error";
        header("Location: client_dashboard.php");
        exit();
    }

    // Verify the devis belongs to this client's reservation
    $stmt_verify = $conn->prepare("SELECT d.id_devis FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = ? AND r.id_client = ?");
    $stmt_verify->bind_param("ii", $id_devis, $id_client);
    $stmt_verify->execute();
    $stmt_verify->store_result();

    if ($stmt_verify->num_rows === 0) {
        $_SESSION['message'] = "Devis not found or you do not have permission to modify it.";
        $_SESSION['message_type'] = "error";
        header("Location: client_dashboard.php");
        exit();
    }
    $stmt_verify->close();

    if ($action === 'accept') {
        // Fetch acompte before updating status
        $stmt_acompte = $conn->prepare("SELECT acompte FROM Devis WHERE id_devis = ?");
        $stmt_acompte->bind_param("i", $id_devis);
        $stmt_acompte->execute();
        $result_acompte = $stmt_acompte->get_result();
        $devis_acompte_data = $result_acompte->fetch_assoc();
        $stmt_acompte->close();

        $acompte = 0;
        if ($devis_acompte_data) {
            $acompte = $devis_acompte_data['acompte'];
        }

        $stmt = $conn->prepare("UPDATE Devis SET statut = 'accepted' WHERE id_devis = ?");
        $stmt->bind_param("i", $id_devis);
        if ($stmt->execute()) {
            if ($acompte > 0) {
                $_SESSION['message'] = "Devis accepted successfully! Redirecting to payment page for deposit.";
                $_SESSION['message_type'] = "success";
                // Ensure id_devis is valid before redirecting
                if ($id_devis > 0) {
                    error_log("Debug: handle_devis_action.php - Redirecting to payment_page.php with id_devis=" . $id_devis);
                    header("Location: payment_page.php?id_devis=" . (int)$id_devis);
                    exit();
                } else {
                    error_log("Error: handle_devis_action.php - Invalid Devis ID (" . $id_devis . ") for payment redirection.");
                    $_SESSION['message'] = "Error: Invalid Devis ID for payment redirection.";
                    $_SESSION['message_type'] = "error";
                    header("Location: client_dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['message'] = "Devis accepted successfully! No deposit required.";
                $_SESSION['message_type'] = "success";
                header("Location: client_dashboard.php");
                exit();
            }
        } else {
            $_SESSION['message'] = "Error accepting devis: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } elseif ($action === 'request_edit') {
        $stmt = $conn->prepare("UPDATE Devis SET statut = 'edit_requested' WHERE id_devis = ?");
        $stmt->bind_param("i", $id_devis);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Edit request sent successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error sending edit request: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Invalid action.";
        $_SESSION['message_type'] = "error";
    }

    $conn->close();
    header("Location: view_devis.php?id=" . $id_devis);
    exit();
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: client_dashboard.php");
    exit();
}
?>