<?php
session_start();
include('config.php');

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

$reservation_id = isset($_GET['id_reservation']) ? intval($_GET['id_reservation']) : 0;
$devis_id = isset($_GET['id_devis']) ? intval($_GET['id_devis']) : 0;

$is_editing = ($devis_id > 0);
$devis_data = null;

if ($is_editing) {
    // Fetch existing devis details
    $stmt_devis = $conn->prepare("SELECT d.cout_total, d.tarif_journalier, d.acompte, d.date_debut_travaux, d.date_fin_travaux, r.id_reservation
                                FROM Devis d
                                JOIN Reservation r ON d.id_reservation = r.id_reservation
                                WHERE d.id_devis = ? AND r.id_prestataire = ?");
    $stmt_devis->bind_param("ii", $devis_id, $_SESSION['artisan_id']);
    $stmt_devis->execute();
    $result_devis = $stmt_devis->get_result();
    $devis_data = $result_devis->fetch_assoc();
    $stmt_devis->close();

    if (!$devis_data) {
        echo "<p>Error: Devis not found or you do not have permission to edit it.</p>";
        exit();
    }
    $reservation_id = $devis_data['id_reservation']; // Use reservation ID from devis data
} else {
    if ($reservation_id === 0) {
        echo "<p>Error: Reservation ID not provided.</p>";
        exit();
    }
}

// Fetch reservation details to display client info or service description if needed
$stmt = $conn->prepare("SELECT r.description_service, u.nom AS client_nom, u.prenom AS client_prenom
                        FROM Reservation r
                        JOIN Client cl ON r.id_client = cl.id_client
                        JOIN Utilisateur u ON cl.id_utilisateur = u.id_utilisateur
                        WHERE r.id_reservation = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation_details = $result->fetch_assoc();
$stmt->close();

if (!$reservation_details) {
    echo "<p>Error: Reservation not found.</p>";
    exit();
}

$client_name = htmlspecialchars($reservation_details['client_nom'] . ' ' . $reservation_details['client_prenom']);
$service_description = htmlspecialchars($reservation_details['description_service']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan - Create a Quote</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Create_quote.css">
</head>
<body>
    <div class="signup-container">
        <div class="left-decoration"></div>
        <div class="right-content">
            <div class="title">Create a Quote</div>

            <p class="quote-for-client">Creating quote for: <strong><?php echo $client_name; ?></strong> (Service: <?php echo $service_description; ?>)</p>

            <form action="save_devis.php" method="POST">
                <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                <?php if ($is_editing): ?>
                    <input type="hidden" name="devis_id" value="<?php echo $devis_id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <div class="form-info">
                        <label for="start-date-input">Start date</label>
                        <div class="description">Work start date (e.g., June 10)</div>
                    </div>
                    <div class="form-input">
                        <input type="date" id="start-date-input" name="start_date" placeholder="Select start date" value="<?php echo $is_editing ? htmlspecialchars($devis_data['date_debut_travaux']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-info">
                        <label for="end-date-input">End date</label>
                        <div class="description">Work end date (e.g., June 15)</div>
                    </div>
                    <div class="form-input">
                        <input type="date" id="end-date-input" name="end_date" placeholder="Select end date" value="<?php echo $is_editing ? htmlspecialchars($devis_data['date_fin_travaux']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-info">
                        <label for="estimated-budget-input">Estimated Project Budget</label>
                        <div class="description">(e.g., $300 for bedroom painting)</div>
                    </div>
                    <div class="form-input">
                        <input type="text" id="estimated-budget-input" name="estimated_budget" placeholder="Enter estimated budget" value="<?php echo $is_editing ? htmlspecialchars($devis_data['cout_total']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-info">
                        <label for="daily-budget-input">Daily budget</label>
                        <div class="description">(e.g., $100)</div>
                    </div>
                    <div class="form-input">
                        <input type="text" id="daily-budget-input" name="daily_budget" placeholder="Enter daily budget" value="<?php echo $is_editing ? htmlspecialchars($devis_data['tarif_journalier']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-info">
                        <label for="deposit-amount-input">Deposit amount</label>
                        <div class="description">(e.g., $200)</div>
                    </div>
                    <div class="form-input">
                        <input type="text" id="deposit-amount-input" name="deposit_amount" placeholder="Enter deposit amount" value="<?php echo $is_editing ? htmlspecialchars($devis_data['acompte']) : ''; ?>" required readonly>
                    </div>
                </div>

                <div class="divider"></div>

                <button type="submit" class="submit-button"><?php echo $is_editing ? 'Update Quote' : 'Submit Quote'; ?></button>
            </form>

            <div class="alert-box">
                <img src="img/avertisement.svg" alt="Alert Icon">
                <span>Please do not make any transactions, payments, or<br> contact outside of Skillid. This platform is designed to protect you.</span>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const estimatedBudgetInput = document.getElementById('estimated-budget-input');
            const depositAmountInput = document.getElementById('deposit-amount-input');

            function calculateDeposit() {
                const estimatedBudget = parseFloat(estimatedBudgetInput.value);
                if (!isNaN(estimatedBudget) && estimatedBudget > 0) {
                    const deposit = (estimatedBudget * 0.05).toFixed(2); // Calculate 5% and format to 2 decimal places
                    depositAmountInput.value = deposit;
                } else {
                    depositAmountInput.value = ''; // Clear if input is invalid or empty
                }
            }

            // Attach event listener for input changes
            estimatedBudgetInput.addEventListener('input', calculateDeposit);

            // Also calculate on page load if there's an initial value (for editing mode)
            calculateDeposit();
        });
    </script>
</body>
</html>