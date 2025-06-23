<?php
require 'config.php';
session_start();

$message = '';
$message_type = '';

// Check if user is logged in and is a client
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit();
}

$is_view_mode = isset($_GET['mode']) && $_GET['mode'] === 'view';
$reservation_data = [];

if ($is_view_mode) {
    if (!isset($_GET['id'])) {
        $message = "No reservation ID specified for viewing.";
        $message_type = 'error';
    } else {
        $id_reservation = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM Reservation WHERE id_reservation = ?");
        $stmt->execute([$id_reservation]);
        $reservation_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation_data) {
            $message = "Reservation not found.";
            $message_type = 'error';
        }
    }
} else {
    // Original logic for client submitting a new request
    if ($_SESSION['role'] !== 'client') {
        header('Location: login.php'); // Only clients can submit new requests
        exit();
    }

    // Fetch id_client from the Client table using id_utilisateur from the session
    $stmt_client = $pdo->prepare("SELECT id_client FROM Client WHERE id_utilisateur = ?");
    $stmt_client->execute([$_SESSION['id_utilisateur']]);
    $client_data = $stmt_client->fetch(PDO::FETCH_ASSOC);

    if (!$client_data) {
        $message = "Client not found. Please ensure your client profile is complete.";
        $message_type = 'error';
        $disable_submit_button = true;
    } else {
        $id_client = $client_data['id_client'];
    }

    // Get artisan ID from URL
    if (!isset($_GET['id_prestataire'])) {
        $message = "No artisan specified for the service request.";
        $message_type = 'error';
    } else {
        $id_prestataire = (int)$_GET['id_prestataire'];

        // Check if the artisan exists
        $stmt_artisan = $pdo->prepare("SELECT id_prestataire FROM Prestataire WHERE id_prestataire = ?");
        $stmt_artisan->execute([$id_prestataire]);
        if (!$stmt_artisan->fetch()) {
            $message = "Artisan not found.";
            $message_type = 'error';
        } else {
            // Determine the final message and disable state
            $disable_submit_button = false;
        }
    }

    // If there was an initial error (e.g., no artisan ID), it should still be displayed
    // and the button should be disabled.
    if (!empty($message) && $message_type === 'error') {
        $disable_submit_button = true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$disable_submit_button) {
        $description_service = trim($_POST['description_service'] ?? '');
        $budget_total = trim($_POST['budget_total'] ?? '');
        $date_debut = trim($_POST['date_debut'] ?? $_GET['start_date'] ?? ''); // Get from POST or GET
        $nb_jours_estime = trim($_POST['nb_jours_estime'] ?? '');

        // Validation
        if (empty($description_service) || empty($date_debut) || empty($nb_jours_estime) || empty($budget_total)) {
            $message = "Description, total budget, start date, and estimated days are required.";
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Reservation (id_client, id_prestataire, description_service, budget_total, date_debut, nb_jours_estime, statut) VALUES (?, ?, ?, ?, ?, ?, 'pending')");

                $stmt->execute([
                    $id_client,
                    $id_prestataire,
                    $description_service,
                    (float)$budget_total,
                    $date_debut,
                    (int)$nb_jours_estime
                ]);

                $message = "Service request submitted successfully!";
                $message_type = 'success';
                // Clear form fields after successful submission
                $_POST = array();
                header('Location: client_dashboard.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                exit();

            } catch (PDOException $e) {
                $message = "Error submitting service request: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Red+Hat+Text:ital,wght@0,300..700;1,300..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="demande_service.css">
  <!-- jQuery UI CSS -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
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
        /* Style for disabled dates in jQuery UI Datepicker */
        .ui-datepicker-unselectable.ui-state-disabled {
            background-color: #ffcccc !important; /* Light red background */
            color: #888 !important;
            cursor: not-allowed;
        }
        .ui-datepicker-unselectable.ui-state-disabled:hover {
            background-color: #ffcccc !important;
        }
    </style>
</head>
<body>
  <div class="request-container">
    <div class="left-decoration"></div>
    <div class="right-content">
      <div class="title"><?= $is_view_mode ? 'Service Request Details' : 'Service Request' ?></div>

      <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

      <?php if ($is_view_mode && $reservation_data): ?>
        <div class="view-form">
          <div class="form-group">
            <div class="form-info">
              <label>Description of the service:</label>
            </div>
            <div class="form-input">
              <p><?= htmlspecialchars($reservation_data['description_service']) ?></p>
            </div>
          </div>
          
          <div class="form-group">
            <div class="form-info">
              <label>Total cost:</label>
            </div>
            <div class="form-input">
              <p><?= htmlspecialchars($reservation_data['budget_total']) ?></p>
            </div>
          </div>
          
          <div class="form-group">
            <div class="form-info">
              <label>Start Date:</label>
            </div>
            <div class="form-input">
              <p><?= htmlspecialchars($reservation_data['date_debut']) ?></p>
            </div>
          </div>

          <div class="form-group">
            <div class="form-info">
              <label>Estimated Number of Days:</label>
            </div>
            <div class="form-input">
              <p><?= htmlspecialchars($reservation_data['nb_jours_estime']) ?></p>
            </div>
          </div>
        </div>
      <?php elseif (!$is_view_mode): ?>
        <form method="POST" action="demande_service.php?id_prestataire=<?= htmlspecialchars($id_prestataire ?? '') ?>">
          <div class="form-group">
            <div class="form-info">
              <label for="description_service">Description of the service you need</label>
              <div class="description">(e.g., I need to paint my room, I have a water leak)</div>
            </div>
            <div class="form-input">
              <input type="text" id="description_service" name="description_service" placeholder="Describe the service" required value="<?= htmlspecialchars($_POST['description_service'] ?? '') ?>" />
            </div>
          </div>
          
          <div class="form-group">
            <div class="form-info">
              <label for="budget_total">Total cost</label>
              <div class="description">(e.g., $300 to paint the bedroom)</div>
            </div>
            <div class="form-input">
              <input type="number" id="budget_total" name="budget_total" placeholder="Budget (e.g., $300)" step="0.01" value="<?= htmlspecialchars($_POST['budget_total'] ?? '') ?>" />
            </div>
          </div>
          
          <div class="form-group">
            <div class="form-info">
              <label for="tarif_par_jour">Daily budget</label>
              <div class="description">(e.g., $100)</div>
            </div>
            <div class="form-input">
              <input type="number" id="tarif_par_jour" name="tarif_par_jour" placeholder="Enter daily budget" step="0.01" value="<?= htmlspecialchars($_POST['tarif_par_jour'] ?? '') ?>" />
            </div>
          </div>

          <div class="form-group">
            <div class="form-info">
              <label for="date_debut">Start Date</label>
              <div class="description">When do you expect the service to start?</div>
            </div>
            <div class="form-input">
              <input type="text" id="date_debut" name="date_debut" required value="<?= htmlspecialchars($_POST['date_debut'] ?? ($_GET['start_date'] ?? '')) ?>" />
            </div>
          </div>

          <div class="form-group">
            <div class="form-info">
              <label for="nb_jours_estime">Estimated Number of Days</label>
            </div>
            <div class="form-input">
              <input type="number" id="nb_jours_estime" name="nb_jours_estime" placeholder="e.g., 3" required min="1" value="<?= htmlspecialchars($_POST['nb_jours_estime'] ?? '') ?>" />
            </div>
          </div>
          
          <div class="info-text">
            If the artisan accepts your service request, they will call you to discuss the details.
          </div>

          <div class="divider"></div>

          <button type="submit" class="submit-button" <?= $disable_submit_button ? 'disabled' : '' ?>>Submit request</button>
        </form>
      <?php endif; ?>

      <?php if (!$is_view_mode): ?>
        <div class="alert-box">
          <img src="img/avertisement.svg" alt="Alert Icon">
          <span>Please do not make any transactions, payments, or<br> contact outside of Skillid. This platform is designed to protect you.</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <!-- jQuery UI -->
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script>
    $(document).ready(function() {
        const startDateInput = $('#date_debut');
        const artisanId = <?= json_encode($id_prestataire ?? null) ?>; // Pass artisan ID from PHP
        let reservedRanges = [];

        if (artisanId) {
            fetch(`fetch_reserved_dates.php?id_prestataire=${artisanId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Raw data from fetch_reserved_dates.php:', data);
                    if (data.error) {
                        console.error('Error fetching reserved dates:', data.error);
                        return;
                    }
                    reservedRanges = data;
                    console.log('Processed reservedRanges for Datepicker:', reservedRanges);

                    startDateInput.datepicker({
                        dateFormat: "yy-mm-dd",
                        minDate: 0, // Today or any future date
                        beforeShowDay: function(date) {
                            const dateString = $.datepicker.formatDate('yy-mm-dd', date);
                            for (let i = 0; i < reservedRanges.length; i++) {
                                const range = reservedRanges[i];
                                if (dateString >= range.from && dateString <= range.to) {
                                    return [false, 'ui-datepicker-unselectable ui-state-disabled', 'Reserved'];
                                }
                            }
                            return [true, ''];
                        }
                    });

                    // Set initial date if available from GET parameter
                    const initialDate = startDateInput.val();
                    if (initialDate) {
                        startDateInput.datepicker('setDate', initialDate);
                    }

                })
                .catch(error => {
                    console.error('Network error fetching reserved dates:', error);
                });
        } else {
            // If no artisanId, initialize Datepicker without disabled dates
            startDateInput.datepicker({
                dateFormat: "yy-mm-dd",
                minDate: 0, // Today or any future date
            });

            const initialDate = startDateInput.val();
            if (initialDate) {
                startDateInput.datepicker('setDate', initialDate);
            }
        }
    });
  </script>
</body>
</html>
