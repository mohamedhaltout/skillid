<?php
session_start();
include 'config.php'; // Include your database configuration file

$devis_id = isset($_GET['id_devis']) ? $_GET['id_devis'] : null;
$client_secret = isset($_GET['client_secret']) ? $_GET['client_secret'] : null;
$amount = null;
$error_message = '';

if (isset($_SESSION['payment_error'])) {
    $error_message = $_SESSION['payment_error'];
    unset($_SESSION['payment_error']); // Clear the error after displaying
}

if ($devis_id) {
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch devis details to get the acompte amount
        $stmt = $pdo->prepare("SELECT acompte FROM Devis WHERE id_devis = ?");
        $stmt->execute([$devis_id]);
        $devis = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($devis) {
            $amount = $devis['acompte'];
        } else {
            $error_message = "Devis not found.";
        }
    } catch (PDOException $e) {
        error_log("Database error fetching devis: " . $e->getMessage());
        $error_message = "Error fetching devis details.";
    }
} else {
    $error_message = "No Devis ID provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link rel="stylesheet" href="style_home.css"> <!-- Assuming a general CSS file -->
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .payment-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .amount-display {
            font-size: 1.5em;
            font-weight: bold;
            color: #3E185B;
            margin-bottom: 25px;
        }
        #payment-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group {
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        #card-element {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 12px;
            background-color: #f8f9fa;
        }
        button {
            background-color: #3E185B;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color:rgb(77, 40, 105);
        }
        .error-message {
            color: #dc3545;
            margin-top: 15px;
            font-weight: bold;
        }
        .success-message {
            color: #28a745;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>Complete Your Payment</h1>
        <?php if ($amount): ?>
            <p class="amount-display">Amount to Pay: <?php echo htmlspecialchars(number_format($amount, 2)); ?> MAD</p>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($devis_id && $amount): ?>
            <form id="payment-form" action="process_payment.php" method="post">
                <div class="form-group">
                    <label for="card-element">Credit or debit card</label>
                    <div id="card-element">
                        <!-- A Stripe Element will be inserted here. -->
                    </div>
                    <!-- Used to display form errors. -->
                    <div id="card-errors" role="alert" class="error-message"></div>
                </div>

                <input type="hidden" name="devis_id" value="<?php echo htmlspecialchars($devis_id); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                <?php if ($client_secret): ?>
                    <input type="hidden" name="payment_intent_id" id="payment-intent-id" value="">
                <?php endif; ?>

                <button id="submit-button">Pay Now</button>
            </form>
        <?php else: ?>
            <p class="error-message">Unable to process payment. Please ensure a valid Devis ID is provided.</p>
        <?php endif; ?>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        const elements = stripe.elements();
        const card = elements.create('card');
        card.mount('#card-element');

        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const cardErrors = document.getElementById('card-errors');
        const clientSecret = '<?php echo $client_secret; ?>';
        const devisId = '<?php echo htmlspecialchars($devis_id); ?>';
        const amount = '<?php echo htmlspecialchars($amount); ?>';

        card.on('change', function(event) {
            if (event.error) {
                cardErrors.textContent = event.error.message;
            } else {
                cardErrors.textContent = '';
            }
            submitButton.disabled = !event.complete;
        });

        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            submitButton.disabled = true;
            cardErrors.textContent = '';

            if (clientSecret) {
                // Confirm the PaymentIntent for 3D Secure authentication
                const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
                    payment_method: {
                        card: card,
                    }
                });

                if (error) {
                    cardErrors.textContent = error.message;
                    submitButton.disabled = false;
                } else if (paymentIntent.status === 'succeeded') {
                    // Payment succeeded after 3D Secure, submit to backend to update database
                    document.getElementById('payment-intent-id').value = paymentIntent.id;
                    form.submit(); // Submit the form to process_payment.php
                } else {
                    cardErrors.textContent = 'Payment failed or requires further action: ' + paymentIntent.status;
                    submitButton.disabled = false;
                }
            } else {
                // Create PaymentMethod and then submit to backend
                const { paymentMethod, error } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: card,
                });

                if (error) {
                    cardErrors.textContent = error.message;
                    submitButton.disabled = false;
                } else {
                    // Add payment_method_id to the form and submit
                    const hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'payment_method_id');
                    hiddenInput.setAttribute('value', paymentMethod.id);
                    form.appendChild(hiddenInput);

                    form.submit(); // Submit the form to process_payment.php
                }
            }
        });
    </script>
</body>
</html>