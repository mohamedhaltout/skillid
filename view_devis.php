<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'client' && $_SESSION['role'] !== 'prestataire')) {
    header("Location: login.php");
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$user_role = $_SESSION['role'];
$user_specific_id = 0; 

$display_name = '';
$display_image = 'img/profil.svg'; 

if ($user_role === 'client') {
    
    $stmt_client = $conn->prepare("SELECT id_client FROM Client WHERE id_utilisateur = ?");
    $stmt_client->bind_param("i", $id_utilisateur);
    $stmt_client->execute();
    $result_client = $stmt_client->get_result();
    $client_data = $result_client->fetch_assoc();
    $stmt_client->close();

    if (!$client_data) {
        $_SESSION['message'] = "Client not found.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    }
    $user_specific_id = $client_data['id_client'];

    $stmt_user_details = $conn->prepare("SELECT nom, prenom FROM Utilisateur WHERE id_utilisateur = ?");
    $stmt_user_details->bind_param("i", $id_utilisateur);
    $stmt_user_details->execute();
    $result_user_details = $stmt_user_details->get_result();
    $user_details = $result_user_details->fetch_assoc();
    $stmt_user_details->close();
    if ($user_details) {
        $display_name = $user_details['nom'] . ' ' . $user_details['prenom'];
    }

} elseif ($user_role === 'prestataire') {
    
    if (!isset($_SESSION['artisan_id'])) {
        $_SESSION['message'] = "Artisan ID not found.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    }
    $user_specific_id = $_SESSION['artisan_id'];

    $stmt_user_details = $conn->prepare("SELECT u.nom, u.prenom, p.photo FROM Prestataire p JOIN Utilisateur u ON p.id_utilisateur = u.id_utilisateur WHERE p.id_prestataire = ?");
    $stmt_user_details->bind_param("i", $user_specific_id); 
    $stmt_user_details->execute();
    $result_user_details = $stmt_user_details->get_result();
    $user_details = $result_user_details->fetch_assoc();
    $stmt_user_details->close();
    if ($user_details) {
        $display_name = $user_details['nom'] . ' ' . $user_details['prenom'];
        $display_image = !empty($user_details['photo']) ? $user_details['photo'] : 'img/profil.svg';
    }
}

$id_devis = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_devis === 0) {
    $_SESSION['message'] = "Devis ID not provided.";
    $_SESSION['message_type'] = "error";
    
    if ($user_role === 'client') {
        header("Location: client_dashboard.php");
    } elseif ($user_role === 'prestataire') {
        header("Location: artisan_dashboard.php"); 
    }
    exit();
}

$sql_where_clause = "";
if ($user_role === 'client') {
    $sql_where_clause = "AND r.id_client = ?";
} elseif ($user_role === 'prestataire') {
    $sql_where_clause = "AND r.id_prestataire = ?";
}


$stmt = $conn->prepare("SELECT d.id_devis, d.cout_total, d.tarif_journalier, d.acompte, d.date_debut_travaux, d.date_fin_travaux,
                        r.id_reservation, r.description_service, r.date_debut AS reservation_date_debut, r.statut AS reservation_statut,
                        u.nom AS artisan_nom, u.prenom AS artisan_prenom, u.email AS artisan_email, pr.telephone AS artisan_telephone,
                        d.statut AS devis_statut
                        FROM Devis d
                        JOIN Reservation r ON d.id_reservation = r.id_reservation
                        JOIN Prestataire pr ON r.id_prestataire = pr.id_prestataire
                        JOIN Utilisateur u ON pr.id_utilisateur = u.id_utilisateur
                        WHERE d.id_devis = ? " . $sql_where_clause);

$stmt->bind_param("ii", $id_devis, $user_specific_id);
$stmt->execute();
$result = $stmt->get_result();
$devis_data = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$devis_data) {
    $_SESSION['message'] = "Devis not found or you do not have permission to view it.";
    $_SESSION['message_type'] = "error";
    header("Location: client_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis Details - Skilled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="view_devis.css">
</head>
<body>
    <header class="main-header">
        <div class="header-left">
            <img src="img/skilled_logo.svg" alt="Skilled Logo" class="logo-image">
            <div class="logo-text">Skilled<span class="logo-dot">.</span></div>
        </div>
        <div class="header-right">
            <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
            <a href="<?php echo ($user_role === 'client' ? 'client_profile.php' : 'artisan_profile.php'); ?>" class="profile-link">
                <span class="client-name"><?php echo htmlspecialchars($display_name); ?></span>
                <img src="<?php echo htmlspecialchars($display_image); ?>" alt="Profile Picture" class="profile-image">
            </a>
        </div>
    </header>
    <div class="header-divider"></div>

    <main class="dashboard-content">
        <h1 class="welcome-message">Devis Details</h1>

        <section class="devis-section">
            <h2 class="section-title">Devis Information</h2>
            <div class="devis-item">
                <div class="devis-details">
                    <div class="detail-group">
                        <span class="detail-label">Total Estimated Cost:</span>
                        <span class="detail-value"><?php echo htmlspecialchars(number_format($devis_data['cout_total'], 2)); ?> MAD</span>
                    </div>
                    <?php if (!is_null($devis_data['tarif_journalier'])): ?>
                    <div class="detail-group">
                        <span class="detail-label">Daily Rate:</span>
                        <span class="detail-value"><?php echo htmlspecialchars(number_format($devis_data['tarif_journalier'], 2)); ?> MAD</span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-group">
                        <span class="detail-label">Deposit Amount:</span>
                        <span class="detail-value"><?php echo htmlspecialchars(number_format($devis_data['acompte'], 2)); ?> MAD</span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Work Start Date:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['date_debut_travaux']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Work End Date:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['date_fin_travaux']); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="reservation-section">
            <h2 class="section-title">Associated Reservation</h2>
            <div class="devis-item">
                <div class="devis-details">
                    <div class="detail-group">
                        <span class="detail-label">Reservation ID:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['id_reservation']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Service Description:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['description_service']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Reservation Date:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['reservation_date_debut']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Reservation Status:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['reservation_statut']); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="artisan-section">
            <h2 class="section-title">Artisan Information</h2>
            <div class="devis-item">
                <div class="devis-details">
                    <div class="detail-group">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['artisan_nom'] . ' ' . $devis_data['artisan_prenom']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['artisan_email']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($devis_data['artisan_telephone']); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <div class="button-container">
            <button class="button view-request-button" onclick="location.href='<?php echo ($user_role === 'client' ? 'client_dashboard.php' : 'artisan_dashboard.php'); ?>'">Back to Dashboard</button>
            <?php if ($user_role === 'client'): ?>
                <?php if ($devis_data['devis_statut'] === 'pending'): ?>
                    <form action="handle_devis_action.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id_devis" value="<?php echo htmlspecialchars($devis_data['id_devis']); ?>">
                        <button type="submit" name="action" value="accept" class="button accept-button">Accept Devis</button>
                        <button type="submit" name="action" value="request_edit" class="button edit-request-button">Request Edit</button>
                    </form>
                <?php elseif ($devis_data['devis_statut'] === 'accepted'): ?>
                    <p class="status-message accepted">Devis Accepted</p>
                    <button class="button pay-now-button" onclick="location.href='payment_page.php?id_devis=<?php echo htmlspecialchars($devis_data['id_devis']); ?>&amount=<?php echo htmlspecialchars($devis_data['acompte']); ?>'">Pay Acompte</button>
                <?php elseif ($devis_data['devis_statut'] === 'edit_requested'): ?>
                    <p class="status-message edit-requested">Edit Requested</p>
                <?php elseif ($devis_data['devis_statut'] === 'rejected'): ?>
                    <p class="status-message rejected">Devis Rejected</p>
                <?php elseif ($devis_data['devis_statut'] === 'paid'): ?>
                    <p class="status-message paid">Devis Paid</p>
                <?php elseif ($devis_data['devis_statut'] === 'pending_payment'): ?>
                    <p class="status-message pending-payment">Pending Payment</p>
                <?php endif; ?>
            <?php elseif ($user_role === 'prestataire'): ?>
                <?php if ($devis_data['devis_statut'] === 'edit_requested'): ?>
                    <button class="button edit-devis-button" onclick="location.href='Create_quote.php?id_reservation=<?php echo htmlspecialchars($devis_data['id_reservation']); ?>&id_devis=<?php echo htmlspecialchars($devis_data['id_devis']); ?>'">Edit Quote</button>
                <?php elseif ($devis_data['devis_statut'] === 'rejected'): ?>
                    <button class="button create-new-devis-button" onclick="location.href='Create_quote.php?id_reservation=<?php echo htmlspecialchars($devis_data['id_reservation']); ?>'">Create New Devis</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="devis-status-container">
            <span class="status-label">Devis Status:</span>
            <span class="status-value status-<?php echo strtolower($devis_data['devis_statut']); ?>"><?php echo htmlspecialchars(ucfirst($devis_data['devis_statut'])); ?></span>
        </div>
    </main>
</body>
</html>