<?php
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password
$dbname = "Skilled"; // Corrected database name as per user feedback

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stripe API keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51NPG6fGonfD7cy55COxdJKFTHetGQcjJZGvLDVILX05yIHGSa2zcbxOq9BQsvhHfwmTKn4onMjEOeOVIXVz5Sfmj00xpDeuYMk'); // Replace with your actual publishable key
define('STRIPE_SECRET_KEY', 'sk_test_51NPG6fGonfD7cy55unV04Z00ZHLIR5BmRjYtvUTPOkBDBMVQ80roaDiWrlP57T1G7OT9mDBFkA7WBQv8IUldVlyj009mPbasWM');         // Replace with your actual secret key

// PDO connection for prepared statements
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

function get_image_path($image_name, $type = 'general') {
    if (empty($image_name)) {
        if ($type === 'prestataire') {
            return 'img/profil_prestataire.png';
        }
        return 'img/profil_prestataire.png'; // Use a known existing default image
    }

    if (strpos($image_name, 'uploads/') === 0) {
        return $image_name; // Path is already complete
    } elseif ($type === 'prestataire') {
        return 'uploads/' . $image_name;
    } elseif ($type === 'media') {
        return 'uploads/media/' . $image_name;
    } else {
        return 'img/' . $image_name;
    }
}
?>
