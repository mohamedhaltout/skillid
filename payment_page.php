<?php
session_start();
include 'config.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$id_devis = isset($_GET['id_devis']) ? intval($_GET['id_devis']) : 0;
$acompte = 0;
$devis_details = null;

if ($id_devis > 0) {
    $stmt = $conn->prepare("SELECT d.cout_total, d.acompte, r.description_service FROM Devis d JOIN Reservation r ON d.id_reservation = r.id_reservation WHERE d.id_devis = ?");
    $stmt->bind_param("i", $id_devis);
    $stmt->execute();
    $result = $stmt->get_result();
    $devis_details = $result->fetch_assoc();
    $stmt->close();

    if ($devis_details) {
        $acompte = $devis_details['acompte'];
    } else {
        $_SESSION['message'] = "Devis not found.";
        $_SESSION['message_type'] = "error";
        header("Location: client_dashboard.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid Devis ID.";
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
    <title>Payment Page</title>
    <link rel="stylesheet" href="client_dashboard.css"> <!-- Or a specific payment CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .payment-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .payment-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .payment-details p {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .payment-details span {
            font-weight: bold;
            color: #007bff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group select {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group input[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover {
            background-color: #218838;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2>Complete Your Payment</h2>
        <?php if (isset($_SESSION['payment_error'])): ?>
            <p class="error-message"><?php echo $_SESSION['payment_error']; unset($_SESSION['payment_error']); ?></p>
        <?php endif; ?>

        <div class="payment-details">
            <p>Devis ID: <span><?php echo htmlspecialchars($id_devis); ?></span></p>
            <p>Description: <span><?php echo htmlspecialchars($devis_details['description_service']); ?></span></p>
            <p>Total Amount: <span><?php echo htmlspecialchars($devis_details['cout_total']); ?> MAD</span></p>
            <p>Deposit Required: <span><?php echo htmlspecialchars($acompte); ?> MAD</span></p>
        </div>

        <form action="process_payment.php" method="POST">
            <input type="hidden" name="devis_id" value="<?php echo htmlspecialchars($id_devis); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($acompte); ?>">
            <input type="hidden" name="payment_type" value="acompte">

            <div class="form-group">
                <label for="payment_method">Payment Method:</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">Select Method</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </div>

            <!-- Add more payment fields as needed (e.g., card number, expiry, CVV) -->
            <!-- For simplicity, we'll just have a method selection for now -->

            <div class="form-group">
                <input type="submit" value="Pay Now">
            </div>
        </form>
    </div>
</body>
</html>