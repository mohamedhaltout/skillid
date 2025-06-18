<?php
session_start();
include 'config.php'; // Include your database configuration file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'vendor/autoload.php'; // Include Stripe PHP library
    require_once 'config.php'; // Include your database configuration file

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $devis_id = $_POST['devis_id'];
    $amount = $_POST['amount']; // This is the amount in your local currency unit
    $payment_method_id = isset($_POST['payment_method_id']) ? $_POST['payment_method_id'] : null;
    $payment_intent_id = isset($_POST['payment_intent_id']) ? $_POST['payment_intent_id'] : null;
    $currency = 'mad'; // Or 'eur', 'mad', etc. based on your currency

    // Convert amount to the smallest currency unit (e.g., cents for USD)
    // Assuming 'MAD' is Moroccan Dirham, which typically has 2 decimal places.
    // If your amount is already in the smallest unit, you can skip this.
    $amount_cents = round($amount * 100);

    try {
        if ($payment_intent_id) {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            $paymentIntent->confirm();
        } else {
            // Create a PaymentIntent with the amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount_cents,
                'currency' => $currency,
                'payment_method' => $payment_method_id,
                'confirm' => true,
                'return_url' => 'http://localhost/skilled/thank_you_page.php', // URL to redirect after 3D Secure
            ]);
        }

        // Handle the PaymentIntent status
        if ($paymentIntent->status == 'succeeded') {
            $statut_paiement = 'effectué';
            $methode_paiement = 'Stripe Credit Card';

            $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO Paiement (id_devis, montant, type_paiement, methode_paiement, statut_paiement, stripe_payment_intent_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$devis_id, $amount, 'acompte', $methode_paiement, $statut_paiement, $paymentIntent->id]);

            $stmt_devis = $pdo->prepare("UPDATE Devis SET statut = 'paid', date_paiement_effectue = NOW(), client_confirmation_deadline = DATE_ADD(NOW(), INTERVAL 72 HOUR), artisan_confirmation_deadline = DATE_ADD(NOW(), INTERVAL 72 HOUR) WHERE id_devis = ?");
            $stmt_devis->execute([$devis_id]);

            header("Location: thank_you_page.php");
            exit();
        } elseif ($paymentIntent->status == 'requires_action' && $paymentIntent->next_action->type == 'use_stripe_sdk') {
            // This typically means 3D Secure authentication is required
            $_SESSION['payment_error'] = "Payment requires additional action. Please complete 3D Secure authentication.";
            $_SESSION['client_secret'] = $paymentIntent->client_secret;
            header("Location: payment_page.php?id_devis=" . $devis_id . "&client_secret=" . $paymentIntent->client_secret); // Redirect back to payment page to handle 3D Secure
            exit();
        } else {
            // Payment failed or other status
            $_SESSION['payment_error'] = "Payment failed with status: " . $paymentIntent->status;
            header("Location: failure_page.php");
            exit();
        }

    } catch (\Stripe\Exception\CardException $e) {
        // Since it's a decline, \Stripe\Exception\CardException will be caught
        $_SESSION['payment_error'] = $e->getError()->message;
        header("Location: payment_page.php?id_devis=" . $devis_id);
        exit();
    } catch (\Stripe\Exception\RateLimitException $e) {
        // Too many requests made to the API too quickly
        $_SESSION['payment_error'] = "Too many requests. Please try again later.";
        header("Location: failure_page.php");
        exit();
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Invalid parameters were supplied to Stripe's API
        $_SESSION['payment_error'] = "Invalid payment request. " . $e->getMessage();
        header("Location: failure_page.php");
        exit();
    } catch (\Stripe\Exception\AuthenticationException $e) {
        // Authentication with Stripe's API failed (maybe you changed API keys)
        $_SESSION['payment_error'] = "Stripe authentication failed. Please check API keys.";
        header("Location: failure_page.php");
        exit();
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        // Network communication with Stripe failed
        $_SESSION['payment_error'] = "Network error connecting to Stripe. Please check your internet connection.";
        header("Location: failure_page.php");
        exit();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Display a very generic error to the user, and maybe send
        // yourself an email
        $_SESSION['payment_error'] = "An unexpected Stripe error occurred. " . $e->getMessage();
        header("Location: failure_page.php");
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['payment_error'] = "Database error during payment processing: " . $e->getMessage();
        header("Location: failure_page.php");
        exit();
    } catch (Exception $e) {
        error_log("General Payment Error: " . $e->getMessage());
        $_SESSION['payment_error'] = "An unexpected error occurred during payment processing.";
        header("Location: failure_page.php");
        exit();
    }
} else {
    $_SESSION['payment_error'] = "Invalid request method.";
    header("Location: client_dashboard.php");
    exit();
}
?>