<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
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
        .failure-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .failure-container h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .failure-container p {
            color: #555;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
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
    <div class="failure-container">
        <h2>Payment Failed!</h2>
        <p>Unfortunately, your payment could not be processed.</p>
        <?php
        if (isset($_SESSION['payment_error'])) {
            echo '<p class="error-message">Error: ' . htmlspecialchars($_SESSION['payment_error']) . '</p>';
            unset($_SESSION['payment_error']); // Clear the error after displaying
        }
        ?>
        <p>Please try again or contact support if the issue persists.</p>
        <a href="client_dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>