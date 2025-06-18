<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Payment!</title>
    <link rel="stylesheet" href="client_dashboard.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
        }
        .thank-you-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .thank-you-container h2 {
            color: #28a745;
            margin-bottom: 20px;
        }
        .thank-you-container p {
            color: #555;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .thank-you-container .transaction-id {
            font-weight: bold;
            color: #333;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <h2>Payment Successful!</h2>
        <p>Thank you for your payment. Your transaction has been processed successfully.</p>
        <?php
        if (isset($_GET['transaction_id'])) {
            echo '<p>Transaction ID: <span class="transaction-id">' . htmlspecialchars($_GET['transaction_id']) . '</span></p>';
        }
        ?>
        <p>You will be redirected to your dashboard shortly.</p>
        <a href="client_dashboard.php" class="back-link">Go to Dashboard</a>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'client_dashboard.php';
        }, 5000); // Redirect after 5 seconds
    </script>
</body>
</html>