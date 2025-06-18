<?php
session_start();
include('config.php');

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$artisan_id = $_SESSION['artisan_id'];

$reservation_details = null;
if ($reservation_id > 0) {
    $stmt = $conn->prepare("SELECT r.*, c.full_name AS client_name, c.email AS client_email, c.phone_number AS client_phone
                            FROM reservations r
                            JOIN clients c ON r.client_id = c.client_id
                            WHERE r.reservation_id = ? AND r.artisan_id = ?");
    $stmt->bind_param("ii", $reservation_id, $artisan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation_details = $result->fetch_assoc();
    $stmt->close();
}
$conn->close();

if (!$reservation_details) {
    
    header("Location: artisan_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - Skilled</title>
    <link rel="preconnect" href="https:
    <link rel="preconnect" href="https:
    <link href="https:
    <link rel="stylesheet" href="artisan_dashboard.css"> <!-- Reusing dashboard CSS for consistency -->
    <style>
        .request-details-container {
            background-color: 
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 40px auto;
        }
        .request-details-container h2 {
            color: 
            margin-bottom: 25px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
        }
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            align-items: baseline;
        }
        .detail-row .label {
            font-weight: 600;
            color: 
            width: 150px; 
            flex-shrink: 0;
        }
        .detail-row .value {
            color: 
            flex-grow: 1;
        }
        .detail-row.description .value {
            white-space: pre-wrap; 
        }
        .actions-container {
            text-align: center;
            margin-top: 30px;
        }
        .actions-container .button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            margin: 0 10px;
        }
        .actions-container .accept-button {
            background-color: 
            color: white;
        }
        .actions-container .accept-button:hover {
            background-color: 
        }
        .actions-container .back-button {
            background-color: 
            color: white;
        }
        .actions-container .back-button:hover {
            background-color: 
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-left">
            <img src="img/skilled_logo.svg" alt="Skilled Logo" class="logo-image">
            <div class="logo-text">Skilled<span class="logo-dot">.</span></div>
        </div>
        <div class="header-right">
            <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
            <span class="client-name">Artisan Dashboard</span>
            <img src="img/profil.svg" alt="Profile Picture" class="profile-image">
        </div>
    </header>
    <div class="header-divider"></div>

    <main class="dashboard-content">
        <div class="request-details-container">
            <h2>Détails de la Demande</h2>
            <div class="detail-row">
                <span class="label">Client:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['client_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Email Client:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['client_email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Téléphone Client:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['client_phone']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Service:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['service_type']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Date Début:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['start_date']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Date Fin:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['end_date']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Ville:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['city']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Adresse:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['address']); ?></span>
            </div>
            <div class="detail-row description">
                <span class="label">Description:</span>
                <span class="value"><?php echo nl2br(htmlspecialchars($reservation_details['description'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Statut:</span>
                <span class="value"><?php echo htmlspecialchars($reservation_details['status']); ?></span>
            </div>

            <div class="actions-container">
                <?php if ($reservation_details['status'] == 'pending'): ?>
                    <button class="button accept-button" onclick="location.href='handle_request.php?action=accept&id=<?php echo $reservation_details['reservation_id']; ?>'">Accepter</button>
                <?php elseif ($reservation_details['status'] == 'accepted'): ?>
                    <button class="button create-devis-button" onclick="location.href='Create_quote.php?id_reservation=<?php echo $reservation_details['reservation_id']; ?>'">Créer un devis</button>
                <?php endif; ?>
                <button class="button back-button" onclick="location.href='artisan_dashboard.php'">Retour au Tableau de Bord</button>
            </div>
        </div>
    </main>
</body>
</html>