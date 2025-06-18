<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Fetch client details
$stmt = $conn->prepare("SELECT u.nom, u.prenom, c.id_client FROM Utilisateur u JOIN Client c ON u.id_utilisateur = c.id_utilisateur WHERE u.id_utilisateur = ?");
$stmt->bind_param("i", $id_utilisateur);
$stmt->execute();
$result = $stmt->get_result();
$client_data = $result->fetch_assoc();

if (!$client_data) {
    header("Location: login.php");
    exit();
}

$client_id = $client_data['id_client'];
$client_name = $client_data['nom'] . ' ' . $client_data['prenom'];
$stmt->close();

// Fetch pending requests made by the client
$pending_requests = [];
$stmt = $conn->prepare("SELECT r.id_reservation, p.nom AS artisan_nom, p.prenom AS artisan_prenom, r.date_debut, r.description_service, r.statut
                        FROM Reservation r
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur p ON pr.id_utilisateur = p.id_utilisateur
                        WHERE r.id_client = ? AND r.statut = 'pending'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$stmt->close();

// Fetch accepted requests made by the client
$accepted_requests = [];
$stmt = $conn->prepare("SELECT r.id_reservation, p.nom AS artisan_nom, p.prenom AS artisan_prenom, r.date_debut, r.description_service, r.statut
                        FROM Reservation r
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur p ON pr.id_utilisateur = p.id_utilisateur
                        WHERE r.id_client = ? AND (r.statut = 'accepted' OR r.statut = 'quoted')");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $accepted_requests[] = $row;
}
$stmt->close();


// Fetch cancelled requests made by the client
$cancelled_requests = [];
$stmt = $conn->prepare("SELECT r.id_reservation, p.nom AS artisan_nom, p.prenom AS artisan_prenom, r.date_debut, r.description_service, r.statut
                        FROM Reservation r
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur p ON pr.id_utilisateur = p.id_utilisateur
                        WHERE r.id_client = ? AND r.statut = 'cancelled'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cancelled_requests[] = $row;
}
$stmt->close();

// Fetch completed projects count for the client
$completed_projects_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Reservation WHERE id_client = ? AND statut = 'completed'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$completed_projects_count = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch total requests submitted by the client
$submitted_requests_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Reservation WHERE id_client = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$submitted_requests_count = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch pending devis for the client's reservations
$pending_devis_list = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.tarif_journalier, d.acompte, d.date_debut_travaux, d.date_fin_travaux,
                        r.id_reservation, r.description_service,
                        u.nom AS artisan_nom, u.prenom AS artisan_prenom, d.statut AS devis_statut
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ? AND d.statut = 'pending'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_devis_list[] = $row;
}
$stmt->close();

// Fetch accepted devis for the client's reservations (accepted but not yet paid)
$accepted_devis_list = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.tarif_journalier, d.acompte, d.date_debut_travaux, d.date_fin_travaux,
                        r.id_reservation, r.description_service,
                        u.nom AS artisan_nom, u.prenom AS artisan_prenom, d.statut AS devis_statut
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ? AND d.statut = 'accepted' AND d.statut != 'paid'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $accepted_devis_list[] = $row;
}
$stmt->close();

// Fetch paid devis for the client's reservations
$paid_devis_list = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.tarif_journalier, d.acompte, d.date_debut_travaux, d.date_fin_travaux,
                        r.id_reservation, r.description_service,
                        u.nom AS artisan_nom, u.prenom AS artisan_prenom, d.statut AS devis_statut,
                        d.client_meeting_confirmed, d.client_confirmation_deadline
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ? AND d.statut = 'paid'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $paid_devis_list[] = $row;
}
$stmt->close();

// Fetch devis requiring client meeting confirmation
$meeting_confirmation_pending_client = [];
$current_time = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.date_debut_travaux, d.date_fin_travaux,
                        r.description_service, u.nom AS artisan_nom, u.prenom AS artisan_prenom,
                        d.client_confirmation_deadline
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ? AND d.statut = 'paid' AND d.client_meeting_confirmed = FALSE AND d.client_confirmation_deadline > ?");
$stmt->bind_param("is", $client_id, $current_time);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $meeting_confirmation_pending_client[] = $row;
}
$stmt->close();

// Fetch confirmed meetings for the client
$confirmed_meetings_client = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.date_debut_travaux, d.date_fin_travaux,
                        r.description_service, u.nom AS artisan_nom, u.prenom AS artisan_prenom
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ? AND d.client_meeting_confirmed = TRUE");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $confirmed_meetings_client[] = $row;
}
$stmt->close();


// Fetch devis with 'edit_requested' status for the client's reservations
$edit_requested_devis_list = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.tarif_journalier, d.acompte, d.date_debut_travaux, d.date_fin_travaux,
                        r.id_reservation, r.description_service,
                        u.nom AS artisan_nom, u.prenom AS artisan_prenom, d.statut AS devis_statut
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ? AND d.statut = 'edit_requested'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $edit_requested_devis_list[] = $row;
}
$stmt->close();

$total_payments_made = 0;
$stmt = $conn->prepare("SELECT SUM(p.montant) AS total_paid
                        FROM Paiement p
                        JOIN Devis d ON p.id_devis = d.id_devis
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        WHERE r.id_client = ? AND p.statut_paiement = 'effectué'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$total_payments_made = $result->fetch_assoc()['total_paid'] ?? 0;
$stmt->close();

// Fetch payment history for the client
$payment_history = [];
$stmt = $conn->prepare("SELECT p.id_paiement, p.montant, p.type_paiement, p.methode_paiement, p.date_paiement, p.statut_paiement,
                        d.cout_total AS devis_total_cost, r.description_service,
                        u.nom AS artisan_nom, u.prenom AS artisan_prenom
                        FROM Paiement p
                        JOIN Devis d ON p.id_devis = d.id_devis
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE r.id_client = ?
                        ORDER BY p.date_paiement DESC");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_history[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Skilled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="client_dashboard.css">
    <style>
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            <a href="client_profile.php" class="profile-link">
                <span class="client-name"><?php echo htmlspecialchars($client_name); ?></span>
                <img src="img/profil.svg" alt="Profile Picture" class="profile-image">
            </a>
        </div>
    </header>
    <div class="header-divider"></div>

    <main class="dashboard-content">
        <h1 class="welcome-message">Welcome, <?php echo htmlspecialchars($client_name); ?></h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <section class="summary-cards">
            <div class="card submitted-requests status-pending">
                <div class="card-number"><?php echo $submitted_requests_count; ?></div>
                <div class="card-text">Demandes soumises</div>
            </div>
            <div class="card accepted-requests status-accepted">
                <div class="card-number"><?php echo count($accepted_requests); ?></div>
                <div class="card-text">Demandes acceptées</div>
            </div>
            <div class="card completed-projects status-completed">
                <div class="card-number"><?php echo $completed_projects_count; ?></div>
                <div class="card-text">Projets terminés</div>
            </div>
            <div class="card cancelled-requests status-cancelled">
                <div class="card-number"><?php echo count($cancelled_requests); ?></div>
                <div class="card-text">Demandes annulées</div>
            </div>
            <div class="card total-payments status-paid">
                <div class="card-number"><?php echo htmlspecialchars(number_format($total_payments_made, 2)); ?><span class="currency"> MAD</span></div>
                <div class="card-text">Total des paiements effectués</div>
            </div>
        </section>

        <section class="demandes-section">
            <h2 class="section-title">Vos demandes en attente</h2>
            <div class="demande-list">
                <?php if (empty($pending_requests)): ?>
                    <p>No pending requests.</p>
                <?php else: ?>
                    <?php foreach ($pending_requests as $demande): ?>
                        <div class="demande-item">
                            <div class="demande-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['artisan_nom'] . ' ' . $demande['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['date_debut']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-<?php echo strtolower($demande['statut']); ?>"><?php echo htmlspecialchars($demande['statut']); ?></span>
                                </div>
                            </div>
                            <div class="demande-actions">
                                <button class="button view-request-button" onclick="location.href='demande_service.php?id=<?php echo $demande['id_reservation']; ?>&mode=view'">Voir la demande</button>
                                <button class="button cancel-request-button" onclick="confirmCancel(<?php echo $demande['id_reservation']; ?>)">Annuler la demande</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="accepted-demandes-section">
            <h2 class="section-title">Vos demandes acceptées</h2>
            <div class="accepted-demande-list">
                <?php if (empty($accepted_requests)): ?>
                    <p>No accepted requests.</p>
                <?php else: ?>
                    <?php foreach ($accepted_requests as $demande): ?>
                        <div class="accepted-demande-item">
                            <div class="accepted-demande-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['artisan_nom'] . ' ' . $demande['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['date_debut']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-<?php echo strtolower($demande['statut']); ?>"><?php echo htmlspecialchars($demande['statut']); ?></span>
                                </div>
                            </div>
                            <div class="accepted-demande-actions">
                                <button class="button view-request-button" onclick="location.href='demande_service.php?id=<?php echo $demande['id_reservation']; ?>&mode=view'">Voir la demande</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="devis-section">
            <h2 class="section-title">Vos devis en attente</h2>
            <div class="devis-list">
                <?php if (empty($pending_devis_list)): ?>
                    <p>No pending quotes.</p>
                <?php else: ?>
                    <?php foreach ($pending_devis_list as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['artisan_nom'] . ' ' . $devis['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description de la demande:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-<?php echo strtolower($devis['devis_statut']); ?>"><?php echo htmlspecialchars(ucfirst($devis['devis_statut'])); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button view-devis-button" onclick="location.href='view_devis.php?id=<?php echo $devis['id_devis']; ?>'">Voir le devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="accepted-devis-section">
            <h2 class="section-title">Vos devis acceptés</h2>
            <div class="accepted-devis-list">
                <?php if (empty($accepted_devis_list)): ?>
                    <p>No accepted quotes.</p>
                <?php else: ?>
                    <?php foreach ($accepted_devis_list as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['artisan_nom'] . ' ' . $devis['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description de la demande:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(number_format($devis['cout_total'], 2)); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_debut_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date fin travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_fin_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-accepted"><?php echo htmlspecialchars(ucfirst($devis['devis_statut'])); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button view-devis-button" onclick="location.href='view_devis.php?id=<?php echo $devis['id_devis']; ?>'">View Devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="paid-devis-section">
            <h2 class="section-title">Vos devis payés</h2>
            <div class="paid-devis-list">
                <?php if (empty($paid_devis_list)): ?>
                    <p>No paid quotes.</p>
                <?php else: ?>
                    <?php foreach ($paid_devis_list as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['artisan_nom'] . ' ' . $devis['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description de la demande:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(number_format($devis['cout_total'], 2)); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_debut_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date fin travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_fin_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-paid"><?php echo htmlspecialchars(ucfirst($devis['devis_statut'])); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button view-devis-button" onclick="location.href='view_devis.php?id=<?php echo $devis['id_devis']; ?>'">View Devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="meeting-confirmation-section">
            <h2 class="section-title">Confirmer la réunion</h2>
            <div class="meeting-confirmation-list">
                <?php if (empty($meeting_confirmation_pending_client)): ?>
                    <p>Aucune réunion en attente de confirmation.</p>
                <?php else: ?>
                    <?php foreach ($meeting_confirmation_pending_client as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['artisan_nom'] . ' ' . $devis['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_debut_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date fin travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_fin_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Confirmer avant:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($devis['client_confirmation_deadline']))); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button confirm-meeting-button" data-devis-id="<?php echo $devis['id_devis']; ?>">Confirmer la réunion</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="confirmed-meetings-section">
            <h2 class="section-title">Réunions confirmées</h2>
            <div class="confirmed-meetings-list">
                <?php if (empty($confirmed_meetings_client)): ?>
                    <p>Aucune réunion confirmée pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($confirmed_meetings_client as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['artisan_nom'] . ' ' . $devis['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_debut_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date fin travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_fin_travaux']); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button view-devis-button" onclick="location.href='view_devis.php?id=<?php echo $devis['id_devis']; ?>'">Voir le devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="edit-requested-devis-section">
            <h2 class="section-title">Vos devis en attente de modification</h2>
            <div class="edit-requested-devis-list">
                <?php if (empty($edit_requested_devis_list)): ?>
                    <p>No devis awaiting modification.</p>
                <?php else: ?>
                    <?php foreach ($edit_requested_devis_list as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['artisan_nom'] . ' ' . $devis['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description de la demande:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant initial:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(number_format($devis['cout_total'], 2)); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-edit-requested"><?php echo htmlspecialchars(ucfirst($devis['devis_statut'])); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button view-devis-button" onclick="location.href='view_devis.php?id=<?php echo $devis['id_devis']; ?>'">View Devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>



        <section class="payment-history-section">
            <h2 class="section-title">Historique des paiements</h2>
            <div class="payment-list">
                <?php if (empty($payment_history)): ?>
                    <p>Aucun paiement enregistré.</p>
                <?php else: ?>
                    <?php foreach ($payment_history as $payment): ?>
                        <div class="payment-item">
                            <div class="payment-details">
                                <div class="detail-group">
                                    <span class="detail-label">Artisan:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($payment['artisan_nom'] . ' ' . $payment['artisan_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($payment['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant payé:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(number_format($payment['montant'], 2)); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Type de paiement:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst($payment['type_paiement'])); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Méthode:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst($payment['methode_paiement'])); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($payment['date_paiement']))); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-<?php echo strtolower($payment['statut_paiement']); ?>"><?php echo htmlspecialchars(ucfirst($payment['statut_paiement'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script>
        function confirmCancel(reservationId) {
            if (confirm("Are you sure you want to cancel this request? This action cannot be undone.")) {
                window.location.href = 'cancel_demande.php?id=' + reservationId;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const confirmMeetingButtons = document.querySelectorAll('.confirm-meeting-button');

            confirmMeetingButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const devisId = this.dataset.devisId;
                    if (confirm("Êtes-vous sûr de vouloir confirmer cette réunion ?")) {
                        window.location.href = 'confirm_client_meeting.php?devis_id=' + devisId;
                    }
                });
            });
        });
    </script>
</body>
</html>