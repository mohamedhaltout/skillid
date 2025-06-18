<?php
session_start();
error_log("artisan_dashboard.php: Session ID at start: " . session_id());
error_log("artisan_dashboard.php: Session content at start: " . print_r($_SESSION, true));
include('config.php');

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    error_log("artisan_dashboard.php: Session check failed. artisan_id: " . (isset($_SESSION['artisan_id']) ? $_SESSION['artisan_id'] : 'NOT SET') . ", role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . ". Redirecting to login.php");
    header("Location: login.php");
    exit();
}
error_log("artisan_dashboard.php: Session check passed. artisan_id: " . $_SESSION['artisan_id'] . ", role: " . $_SESSION['role']);

$artisan_id = $_SESSION['artisan_id'];
error_log("artisan_dashboard.php: Attempting to fetch artisan details for artisan_id: " . $artisan_id);

// Fetch artisan details
$stmt = $conn->prepare("SELECT u.nom, u.prenom, p.photo FROM Prestataire p JOIN Utilisateur u ON p.id_utilisateur = u.id_utilisateur WHERE p.id_prestataire = ?");
if ($stmt === false) {
    error_log("artisan_dashboard.php: Failed to prepare statement for artisan details: " . $conn->error);
    header("Location: login.php");
    exit();
}
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
$artisan = $result->fetch_assoc();

if (!$artisan) {
    error_log("artisan_dashboard.php: Artisan data not found for id: " . $artisan_id . ". Redirecting to login.php");
    header("Location: login.php");
    exit();
}

$artisan_name = $artisan['nom'] . ' ' . $artisan['prenom'];
$artisan_image = !empty($artisan['photo']) ? $artisan['photo'] : 'img/profil.svg'; // Default image if not set
$stmt->close();

// Fetch pending requests
$pending_requests = [];
$stmt = $conn->prepare("SELECT r.id_reservation, u.nom AS client_nom, u.prenom AS client_prenom, r.date_debut, p.ville, r.description_service
                        FROM Reservation r
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        JOIN Prestataire p ON r.id_prestataire = p.id_prestataire
                        WHERE r.id_prestataire = ? AND r.statut = 'pending'");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$stmt->close();
error_log("Pending Requests: " . print_r($pending_requests, true));

// Fetch accepted requests
$accepted_requests = [];
$stmt = $conn->prepare("SELECT r.id_reservation, u.nom AS client_nom, u.prenom AS client_prenom, cl.telephone AS client_phone, r.date_debut, p.ville
                        FROM Reservation r
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        JOIN Prestataire p ON r.id_prestataire = p.id_prestataire
                        WHERE r.id_prestataire = ? AND (r.statut = 'accepted' OR r.statut = 'quoted')");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $accepted_requests[] = $row;
}
$stmt->close();
error_log("Accepted Requests: " . print_r($accepted_requests, true));

// Check if the artisan has an ongoing project
$is_artisan_busy = false;
$current_date = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) AS active_projects_count FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE r.id_prestataire = ? AND d.date_fin_travaux >= ?");
$stmt->bind_param("is", $artisan_id, $current_date);
$stmt->execute();
$result = $stmt->get_result();
$active_projects = $result->fetch_assoc();
if ($active_projects['active_projects_count'] > 0) {
    $is_artisan_busy = true;
}
$stmt->close();

// Fetch refused requests
$refused_requests = [];
$stmt = $conn->prepare("SELECT r.id_reservation, u.nom AS client_nom, u.prenom AS client_prenom, r.date_debut, p.ville, r.description_service
                        FROM Reservation r
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        JOIN Prestataire p ON r.id_prestataire = p.id_prestataire
                        WHERE r.id_prestataire = ? AND r.statut = 'refused'");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $refused_requests[] = $row;
}
$stmt->close();
error_log("Refused Requests: " . print_r($refused_requests, true));

// Fetch accepted devis
$accepted_devis = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total AS montant, d.date_debut_travaux AS date_creation, d.date_fin_travaux, d.statut AS devis_statut,
                        u.nom AS client_nom, u.prenom AS client_prenom, r.description_service
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE r.id_prestataire = ? AND d.statut = 'accepted'");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $accepted_devis[] = $row;
}
$stmt->close();
error_log("Accepted Devis: " . print_r($accepted_devis, true));

// Fetch devis with 'edit_requested' status
$edit_requested_devis = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total AS montant, d.date_debut_travaux AS date_creation, d.date_fin_travaux, d.statut AS devis_statut,
                        u.nom AS client_nom, u.prenom AS client_prenom, r.description_service, r.id_reservation
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE r.id_prestataire = ? AND d.statut = 'edit_requested'");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $edit_requested_devis[] = $row;
}
$stmt->close();
error_log("Edit Requested Devis: " . print_r($edit_requested_devis, true));



// Fetch completed projects (assuming 'completed' status)
$completed_projects_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Reservation WHERE id_prestataire = ? AND statut = 'completed'");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
$completed_projects_count = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch received requests count (including 'pending', 'accepted', and 'refused')
$received_requests_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Reservation WHERE id_prestataire = ?");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
$received_requests_count = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch average rating
$average_rating = "N/A";
$stmt = $conn->prepare("SELECT AVG(note) AS average_note FROM Evaluation WHERE id_prestataire = ?");
if ($stmt === false) {
    error_log("artisan_dashboard.php: Failed to prepare statement for average rating: " . $conn->error);
} else {
    $stmt->bind_param("i", $artisan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['average_note'] !== null) {
        $average_rating = number_format($row['average_note'], 1);
    }
    $stmt->close();
}

// Fetch Acompte Payments and calculate total earnings
$acompte_payments = [];
$total_earnings = 0;
$stmt = $conn->prepare("SELECT p.id_paiement, p.montant, p.date_paiement, d.cout_total AS devis_montant,
                        u.nom AS client_nom, u.prenom AS client_prenom, r.description_service
                        FROM Paiement p
                        JOIN Devis d ON p.id_devis = d.id_devis
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE d.id_prestataire = ? AND p.type_paiement = 'acompte' AND p.statut_paiement = 'effectué'");
if ($stmt === false) {
    error_log("artisan_dashboard.php: Failed to prepare statement for acompte payments: " . $conn->error);
} else {
    $stmt->bind_param("i", $artisan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $acompte_payments[] = $row;
        $total_earnings += $row['montant'];
    }
    $stmt->close();
}
error_log("Acompte Payments: " . print_r($acompte_payments, true));
error_log("Total Earnings: " . $total_earnings);

// Fetch paid devis
$paid_devis = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total AS montant, d.date_debut_travaux AS date_creation, d.date_fin_travaux, d.statut AS devis_statut,
                        u.nom AS client_nom, u.prenom AS client_prenom, r.description_service
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE r.id_prestataire = ? AND d.statut = 'paid'");
if ($stmt === false) {
    error_log("artisan_dashboard.php: Failed to prepare statement for paid devis: " . $conn->error);
} else {
    $stmt->bind_param("i", $artisan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $paid_devis[] = $row;
    }
    $stmt->close();
}
error_log("Paid Devis: " . print_r($paid_devis, true));

// Fetch devis requiring artisan meeting confirmation
$meeting_confirmation_pending_artisan = [];
$current_time = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.date_debut_travaux, d.date_fin_travaux,
                        r.description_service, u.nom AS client_nom, u.prenom AS client_prenom,
                        d.artisan_confirmation_deadline
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE d.id_prestataire = ? AND d.statut = 'paid' AND d.artisan_meeting_confirmed = FALSE AND d.artisan_confirmation_deadline > ?");
$stmt->bind_param("is", $artisan_id, $current_time);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $meeting_confirmation_pending_artisan[] = $row;
}
$stmt->close();

// Fetch confirmed meetings for the artisan
$confirmed_meetings_artisan = [];
$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.date_debut_travaux, d.date_fin_travaux,
                        r.description_service, u.nom AS client_nom, u.prenom AS client_prenom
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE d.id_prestataire = ? AND d.artisan_meeting_confirmed = TRUE");
$stmt->bind_param("i", $artisan_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $confirmed_meetings_artisan[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestataire Dashboard - Skilled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="artisan_dashboard.css">
</head>
<body>
    <header class="main-header">
        <div class="header-left">
            <img src="img/skilled_logo.svg" alt="Skilled Logo" class="logo-image">
            <div class="logo-text">Skilled<span class="logo-dot">.</span></div>
        </div>
        <div class="header-right">
            <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
            <a href="artisan_profile.php" class="profile-link">
                <span class="client-name"><?php echo htmlspecialchars($artisan_name); ?></span>
                <img src="<?php echo htmlspecialchars($artisan_image); ?>" alt="Profile Picture" class="profile-image">
            </a>
        </div>
    </header>
    <div class="header-divider"></div>

    <main class="dashboard-content">
        <h1 class="welcome-message">Welcome, <?php echo htmlspecialchars($artisan_name); ?></h1>

        <section class="summary-cards">
            <div class="card received-requests status-pending">
                <div class="card-number"><?php echo $received_requests_count; ?></div>
                <div class="card-text">Demandes reçues</div>
            </div>
            <div class="card accepted-requests status-accepted">
                <div class="card-number"><?php echo count($accepted_requests); ?></div>
                <div class="card-text">Demandes acceptées</div>
            </div>
            <div class="card refused-requests status-refused">
                <div class="card-number"><?php echo count($refused_requests); ?></div>
                <div class="card-text">Demandes refusées</div>
            </div>
            <div class="card completed-projects status-completed">
                <div class="card-number"><?php echo $completed_projects_count; ?></div>
                <div class="card-text">Projets terminés</div>
            </div>
            <div class="card average-rating">
                <div class="card-number"><?php echo $average_rating; ?></div>
                <div class="card-text">Note moyenne</div>
            </div>
            <div class="card total-earnings status-paid">
                <div class="card-number"><?php echo number_format($total_earnings, 2); ?> MAD</div>
                <div class="card-text">Gains totaux (Acompte)</div>
            </div>
        </section>

        <section class="demandes-section">
            <h2 class="section-title">Demandes en attente</h2>
            <div class="demande-list">
                <?php if (empty($pending_requests)): ?>
                    <p>No pending requests.</p>
                <?php else: ?>
                    <?php foreach ($pending_requests as $demande): ?>
                        <div class="demande-item" data-reservation-id="<?php echo $demande['id_reservation']; ?>">
                            <div class="demande-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['client_nom'] . ' ' . $demande['client_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['date_debut']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Ville:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['ville']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Description:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-pending">Pending</span>
                                </div>
                            </div>
                            <div class="demande-actions">
                                <button class="button view-request-button" onclick="location.href='demande_service.php?id=<?php echo $demande['id_reservation']; ?>&mode=view'">Voir la demande</button>
                                <button class="button refuse-button" data-id="<?php echo $demande['id_reservation']; ?>">Refuser</button>
                                <button class="button accept-button" data-id="<?php echo $demande['id_reservation']; ?>" <?php echo $is_artisan_busy ? 'style="display:none;"' : ''; ?>>Accepter</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="edit-requested-devis-section">
            <h2 class="section-title">Devis en attente de modification</h2>
            <div class="edit-requested-devis-list">
                <?php if (empty($edit_requested_devis)): ?>
                    <p>No devis awaiting modification.</p>
                <?php else: ?>
                    <?php foreach ($edit_requested_devis as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant initial:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['montant']); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-edit-requested"><?php echo htmlspecialchars(ucfirst($devis['devis_statut'])); ?></span>
                                </div>
                            </div>
                            <div class="devis-actions">
                                <button class="button edit-devis-button" onclick="location.href='Create_quote.php?id_reservation=<?php echo $devis['id_reservation']; ?>&id_devis=<?php echo $devis['id_devis']; ?>'">Edit Quote</button>
                                <button class="button view-devis-button" onclick="location.href='view_devis.php?id=<?php echo $devis['id_devis']; ?>'">View Devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="accepted-demandes-section">
            <h2 class="section-title">Demandes acceptées</h2>
            <div class="accepted-demande-list">
                <?php if (empty($accepted_requests)): ?>
                    <p>No accepted requests.</p>
                <?php else: ?>
                    <?php foreach ($accepted_requests as $demande): ?>
                        <div class="accepted-demande-item">
                            <div class="accepted-demande-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['client_nom'] . ' ' . $demande['client_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date début:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['date_debut']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Ville:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($demande['ville']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-accepted">Accepted</span>
                                </div>
                            </div>
                            <div class="accepted-demande-actions">
                                <button class="button call-client-button" data-phone="<?php echo htmlspecialchars($demande['client_phone']); ?>">Call the Client</button>
                                <button class="button create-devis-button" onclick="location.href='Create_quote.php?id_reservation=<?php echo $demande['id_reservation']; ?>'">Create Devis</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="accepted-devis-section">
            <h2 class="section-title">Devis acceptés</h2>
            <div class="accepted-devis-list">
                <?php if (empty($accepted_devis)): ?>
                    <p>No accepted devis.</p>
                <?php else: ?>
                    <?php foreach ($accepted_devis as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['montant']); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date de création:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_creation']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date fin travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_fin_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-<?php echo strtolower($devis['devis_statut']); ?>"><?php echo htmlspecialchars($devis['devis_statut']); ?></span>
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
                <?php if (empty($paid_devis)): ?>
                    <p>Aucun devis payé pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($paid_devis as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['montant']); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date de création:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_creation']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date fin travaux:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['date_fin_travaux']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Statut:</span>
                                    <span class="detail-value status-paid"><?php echo htmlspecialchars($devis['devis_statut']); ?></span>
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


        <section class="acompte-payments-section">
            <h2 class="section-title">Paiements d'acompte reçus</h2>
            <div class="acompte-payments-list">
                <?php if (empty($acompte_payments)): ?>
                    <p>Aucun paiement d'acompte reçu pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($acompte_payments as $payment): ?>
                        <div class="payment-item">
                            <div class="payment-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($payment['client_nom'] . ' ' . $payment['client_prenom']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($payment['description_service']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Montant Acompte:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(number_format($payment['montant'], 2)); ?> MAD</span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date de paiement:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($payment['date_paiement']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="meeting-confirmation-section">
            <h2 class="section-title">Confirmer la réunion</h2>
            <div class="meeting-confirmation-list">
                <?php if (empty($meeting_confirmation_pending_artisan)): ?>
                    <p>Aucune réunion en attente de confirmation.</p>
                <?php else: ?>
                    <?php foreach ($meeting_confirmation_pending_artisan as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></span>
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
                                    <span class="detail-value"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($devis['artisan_confirmation_deadline']))); ?></span>
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
                <?php if (empty($confirmed_meetings_artisan)): ?>
                    <p>Aucune réunion confirmée pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($confirmed_meetings_artisan as $devis): ?>
                        <div class="devis-item">
                            <div class="devis-details">
                                <div class="detail-group">
                                    <span class="detail-label">Client:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></span>
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

        <section class="experiences-section">
            <h2 class="section-title">Vos expériences</h2>
            <div class="experience-list">
                <?php
                $experiences = [];
                $stmt = $conn->prepare("SELECT id_experience, titre_experience, description, date_project FROM Experience_prestataire WHERE id_prestataire = ? ORDER BY date_project DESC");
                if ($stmt === false) {
                    error_log("artisan_dashboard.php: Failed to prepare statement for experiences: " . $conn->error);
                } else {
                    $stmt->bind_param("i", $artisan_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $experiences[] = $row;
                    }
                    $stmt->close();
                }
                ?>
                <?php if (empty($experiences)): ?>
                    <p>No experiences added yet.</p>
                <?php else: ?>
                    <?php foreach ($experiences as $experience): ?>
                        <div class="experience-item">
                            <div class="experience-details">
                                <div class="detail-group">
                                    <span class="detail-label">Projet:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($experience['titre_experience']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Année:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($experience['date_project']); ?></span>
                                </div>
                            </div>
                            <div class="experience-actions">
                                <button class="button modify-button" onclick="location.href='ad_exeperience.php?edit_id=<?php echo $experience['id_experience']; ?>'">Modifier</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refuseButtons = document.querySelectorAll('.refuse-button');
            const acceptButtons = document.querySelectorAll('.accept-button');
            let callClientButtons = document.querySelectorAll('.call-client-button'); // Use let for re-assignment
            const receivedRequestsCountElement = document.querySelector('.received-requests .card-number');
            const acceptedRequestsCountElement = document.querySelector('.accepted-requests .card-number');
            const refusedRequestsCountElement = document.querySelector('.refused-requests .card-number');
            const pendingDemandsList = document.querySelector('.demande-list');
            const acceptedDemandsList = document.querySelector('.accepted-demande-list');

            // Function to attach event listeners to call client buttons
            function attachCallClientListeners() {
                callClientButtons.forEach(button => {
                    // Remove existing listener to prevent duplicates if called multiple times
                    button.removeEventListener('click', handleCallClientClick);
                    button.addEventListener('click', handleCallClientClick);
                });
            }

            function handleCallClientClick() {
                const clientPhone = this.dataset.phone;
                alert('Client Phone Number: ' + clientPhone);
            }

            refuseButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior

                    const reservationId = this.dataset.id;
                    const demandeItem = this.closest('.demande-item');

                    fetch(`handle_request.php?action=refuse&id=${reservationId}`)
                        .then(response => response.text()) // Get response as text
                        .then(text => {
                            console.log('Response from server:', text); // Log the raw response
                            try {
                                const data = JSON.parse(text); // Try to parse as JSON
                                if (data.status === 'success') {
                                    if (demandeItem) {
                                        demandeItem.remove(); // Hide the demand
                                        // Update the received requests count
                                        let currentCount = parseInt(receivedRequestsCountElement.textContent);
                                        if (!isNaN(currentCount) && currentCount > 0) {
                                            receivedRequestsCountElement.textContent = currentCount - 1;
                                        }
                                        let currentRefusedCount = parseInt(refusedRequestsCountElement.textContent);
                                        if (!isNaN(currentRefusedCount)) {
                                            refusedRequestsCountElement.textContent = currentRefusedCount + 1;
                                        }
                                    }
                                } else {
                                    alert('Error refusing request: ' + data.message);
                                }
                            } catch (e) {
                                console.error('Failed to parse JSON response:', e);
                                alert('An unexpected error occurred. Please check the console for more details.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while processing your request.');
                        });
                });
            });

            const canAcceptNewRequests = <?php echo json_encode(!$is_artisan_busy); ?>;

            acceptButtons.forEach(button => {
                if (!canAcceptNewRequests) {
                    button.style.display = 'none'; // Hide the accept button if artisan cannot accept new requests
                }
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    const reservationId = this.dataset.id;
                    const demandeItem = this.closest('.demande-item');

                    fetch(`handle_request.php?action=accept&id=${reservationId}`)
                        .then(response => response.text())
                        .then(text => {
                            console.log('Response from server:', text);
                            try {
                                const data = JSON.parse(text);
                                if (data.status === 'success') {
                                    if (demandeItem) {
                                        demandeItem.remove(); // Remove from pending list

                                        // Add to accepted list
                                        const acceptedDemande = data.demande;
                                        const newAcceptedItem = `
                                            <div class="accepted-demande-item">
                                                <div class="accepted-demande-details">
                                                    <div class="detail-group">
                                                        <span class="detail-label">Client:</span>
                                                        <span class="detail-value">${acceptedDemande.client_nom} ${acceptedDemande.client_prenom}</span>
                                                    </div>
                                                    <div class="detail-group">
                                                        <span class="detail-label">Date début:</span>
                                                        <span class="detail-value">${acceptedDemande.date_debut}</span>
                                                    </div>
                                                    <div class="detail-group">
                                                        <span class="detail-label">Ville:</span>
                                                        <span class="detail-value">${acceptedDemande.ville}</span>
                                                    </div>
                                                </div>
                                                <div class="accepted-demande-actions">
                                                    <button class="button call-client-button" data-phone="${acceptedDemande.client_phone}">Call the Client</button>
                                                    <button class="button create-devis-button" onclick="location.href='Create_quote.php?id_reservation=${acceptedDemande.id_reservation}'">Create Devis</button>
                                                </div>
                                            </div>
                                        `;
                                        acceptedDemandsList.insertAdjacentHTML('beforeend', newAcceptedItem);

                                        // Update counts
                                        let currentReceivedCount = parseInt(receivedRequestsCountElement.textContent);
                                        if (!isNaN(currentReceivedCount) && currentReceivedCount > 0) {
                                            receivedRequestsCountElement.textContent = currentReceivedCount - 1;
                                        }
                                        let currentAcceptedCount = parseInt(acceptedRequestsCountElement.textContent);
                                        if (!isNaN(currentAcceptedCount)) {
                                            acceptedRequestsCountElement.textContent = currentAcceptedCount + 1;
                                        }
                                        // Re-fetch and attach listeners to new and existing call client buttons
                                        callClientButtons = document.querySelectorAll('.call-client-button');
                                        attachCallClientListeners();
                                    }
                                } else {
                                    alert('Error accepting request: ' + data.message);
                                }
                            } catch (e) {
                                console.error('Failed to parse JSON response:', e);
                                alert('An unexpected error occurred. Please check the console for more details.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while processing your request.');
                        });
                });
            });

            // Initial attachment of event listeners
            attachCallClientListeners();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmMeetingButtons = document.querySelectorAll('.confirm-meeting-button');
            const acceptButtons = document.querySelectorAll('.accept-button'); // Re-fetch accept buttons

            confirmMeetingButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const devisId = this.dataset.devisId;
                    if (confirm("Êtes-vous sûr de vouloir confirmer cette réunion ?")) {
                        window.location.href = 'confirm_artisan_meeting.php?devis_id=' + devisId;
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>