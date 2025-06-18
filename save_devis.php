<?php
session_start();
include('config.php');

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
    $devis_id = isset($_POST['devis_id']) ? intval($_POST['devis_id']) : 0; // New: Get devis_id
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $estimated_budget = isset($_POST['estimated_budget']) ? floatval($_POST['estimated_budget']) : 0.0;
    $daily_budget = isset($_POST['daily_budget']) ? floatval($_POST['daily_budget']) : NULL; // Can be NULL
    $deposit_amount = isset($_POST['deposit_amount']) ? floatval($_POST['deposit_amount']) : 0.0;
    $artisan_id = $_SESSION['artisan_id'];

    if ($reservation_id === 0 || empty($start_date) || empty($end_date) || $estimated_budget <= 0 || $deposit_amount <= 0) {
        $_SESSION['error_message'] = "Invalid input for quote. Please fill all required fields.";
        header("Location: Create_quote.php?id_reservation=" . $reservation_id . ($devis_id > 0 ? "&id_devis=" . $devis_id : ""));
        exit();
    }

    if ($devis_id > 0) {
        // Update existing devis
        $stmt = $conn->prepare("UPDATE Devis SET date_debut_travaux = ?, date_fin_travaux = ?, cout_total = ?, tarif_journalier = ?, acompte = ?, statut = 'pending' WHERE id_devis = ? AND id_prestataire = ?");
        $stmt->bind_param("ssddsii", $start_date, $end_date, $estimated_budget, $daily_budget, $deposit_amount, $devis_id, $artisan_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Quote successfully updated!";
            header("Location: artisan_dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating quote: " . $stmt->error;
            header("Location: Create_quote.php?id_reservation=" . $reservation_id . "&id_devis=" . $devis_id);
            exit();
        }
    } else {
        // Check if a quote already exists for this reservation (only for new creation)
        $stmt = $conn->prepare("SELECT id_devis FROM Devis WHERE id_reservation = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "A quote already exists for this reservation.";
            header("Location: Create_quote.php?id_reservation=" . $reservation_id);
            exit();
        }
        $stmt->close();

        // Insert the new quote into the Devis table
        $stmt = $conn->prepare("INSERT INTO Devis (id_reservation, id_prestataire, date_debut_travaux, date_fin_travaux, cout_total, tarif_journalier, acompte, statut) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iissdds", $reservation_id, $artisan_id, $start_date, $end_date, $estimated_budget, $daily_budget, $deposit_amount);

        if ($stmt->execute()) {
            // Update the reservation status to 'quoted' or similar if needed
            $update_reservation_stmt = $conn->prepare("UPDATE Reservation SET statut = 'quoted' WHERE id_reservation = ?");
            $update_reservation_stmt->bind_param("i", $reservation_id);
            $update_reservation_stmt->execute();
            $update_reservation_stmt->close();

            $_SESSION['success_message'] = "Quote successfully saved!";
            header("Location: artisan_dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error saving quote: " . $stmt->error;
            header("Location: Create_quote.php?id_reservation=" . $reservation_id);
            exit();
        }
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: artisan_dashboard.php");
    exit();
}
?>