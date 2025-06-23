<?php
require 'config.php';

header('Content-Type: application/json');

$reservedDates = [];

if (isset($_GET['id_prestataire'])) {
    $id_prestataire = (int)$_GET['id_prestataire'];

    error_log("Fetching reserved dates for artisan ID: " . $id_prestataire);
    try {
        $sql = "SELECT id_devis, date_debut_travaux, date_fin_travaux, statut FROM Devis WHERE id_prestataire = ?";
        error_log("Executing SQL: " . $sql . " with id_prestataire: " . $id_prestataire);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_prestataire]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Number of results fetched: " . count($results));
        error_log("Fetched results: " . print_r($results, true));

        foreach ($results as $row) {
            error_log("Devis ID: " . $row['id_devis'] . ", Status: " . $row['statut'] . ", Start: " . $row['date_debut_travaux'] . ", End: " . $row['date_fin_travaux']);
            // Only add to reservedDates if status is one of the "booked" statuses and date is in future/today
            if ($row['statut'] === 'paid' || $row['statut'] === 'meeting_confirmed') {
                // Check if date_fin_travaux is today or in the future
                $endDate = new DateTime($row['date_fin_travaux']);
                $today = new DateTime();
                $today->setTime(0, 0, 0); // Reset time for accurate date comparison

                if ($endDate >= $today) {
                    $reservedDates[] = [
                        'from' => $row['date_debut_travaux'],
                        'to' => $row['date_fin_travaux']
                    ];
                }
            }
        }
    } catch (PDOException $e) {
        // Log the error, but don't expose sensitive info to the client
        error_log("Error fetching reserved dates: " . $e->getMessage());
        echo json_encode(['error' => 'Database error fetching reserved dates.']);
        exit();
    }
} else {
    echo json_encode(['error' => 'Artisan ID not provided.']);
    exit();
}

echo json_encode($reservedDates);
?>