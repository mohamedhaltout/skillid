<?php
require 'config.php';
session_start();

// Redirect if artisan tries to view another artisan's profile or if not logged in
if (isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire') {
    // If an artisan is logged in, redirect them to their own dashboard
    header("Location: artisan_dashboard.php");
    exit();
}

$artisan_profile_photo = get_image_path('', 'prestataire'); // Default profile photo

// Check if the Link Has In ID
if (!isset($_GET['id'])) {
    die("No artisan specified.");
}

// Convert the id to a int number to save from sql Injection
$id_prestataire = (int) $_GET['id'];


// Check if the artisan has an active reservation
$disable_demande_button = false; // Default to not disabled as artisans can accept multiple requests
$reservation_message = ''; // No reservation message needed


// Get the Standard Categories
$stmt = $pdo->query("SELECT nom, icone FROM Categories WHERE type = 'standard'");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get the artisan Info 
$stmt = $pdo->prepare("SELECT p.*, u.nom AS user_nom, u.prenom AS user_prenom, c.nom AS categorie_nom FROM Prestataire p JOIN Utilisateur u ON p.id_utilisateur = u.id_utilisateur JOIN Categories c ON p.id_categorie = c.id_categorie WHERE p.id_prestataire = ?");
$stmt->execute([$id_prestataire]);
$prestataire = $stmt->fetch();
if (!$prestataire) die("Artisan not found.");


// Get the latest Experience Prestataire based on the date_project
$stmt = $pdo->prepare("SELECT * FROM Experience_prestataire WHERE id_prestataire = ? ORDER BY date_project DESC LIMIT 1");
$stmt->execute([$id_prestataire]);
$experience = $stmt->fetch();


// Get the experience from Media Experience Table and if he is valable store him in this "media" array
$media = [];
if ($experience) {
    $stmt = $pdo->prepare("SELECT * FROM Media_experience WHERE id_experience = ?");
    $stmt->execute([$experience['id_experience']]); 
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch evaluations for the artisan and add the client names and show him DESC New to O
$stmt_evaluations = $pdo->prepare("
    SELECT e.*, u.nom AS client_nom, u.prenom AS client_prenom
    FROM Evaluation e
    JOIN Client c ON e.id_client = c.id_client
    JOIN Utilisateur u ON c.id_utilisateur = u.id_utilisateur
    WHERE e.id_prestataire = ?
    ORDER BY e.date_evaluation DESC
");
$stmt_evaluations->execute([$id_prestataire]);
$evaluations = $stmt_evaluations->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating and total reviews
$average_rating = 0;
$total_reviews = count($evaluations);

if ($total_reviews > 0) {
    $total_note = 0;
    foreach ($evaluations as $evaluation) {
        $total_note += $evaluation['note'];
    }
    $average_rating = round($total_note / $total_reviews, 1);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="artisan.css">
    <title>Artisan Profile</title>
</head>
<body>
        <header class="main-header">
        <div class="header-top-bar">
            <div class="header-logo">
                <a href="home.php">
                <img src="img/skilled_logo.svg" class="header-logo-img" />
                </a>
                <span class="header-logo-text">Skilled<span class="header-logo-dot">.</span></span>
            </div>
            <div class="header-search-container">
                <form method="GET" action="search.php" class="header-search-form">
                    <input type="text" name="search" placeholder="What Service are you looking for today..." class="header-search-input" />
                    <div class="header-search-icon-wrapper">
                        <button type="submit" style="background: none; border: none; padding: 0;">
                            <img src="img/search.png" alt="Search" class="header-search-icon" />
                        </button>
                    </div>
                </form>
            </div>

            <!-- Header-Right Section -->
            <div class="header-right-nav">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
                    <a href="client_dashboard.php" class="header-orders">Orders</a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire'): ?>
                    <a href="artisan_dashboard.php" class="header-orders">Orders</a>
                <?php else: ?>
                    <span class="header-orders">Orders</span>
                <?php endif; ?>
                <a href="sign_up_artisan.php" class="header-switch">Switch to Artisans</a>
                <div class="profile-dropdown">
                    <img src="<?= htmlspecialchars($artisan_profile_photo) ?>" class="header-profile-pic" onclick="toggleProfileDropdown(event)" />
                    <div class="dropdown-content">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
                            <a href="client_profile.php">My Profile</a>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire'): ?>
                            <a href="artisan_profile.php">My Artisan Profile</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Slider -->
        <div class="header-line"></div>
        <div class="categories-nav-bar">
            <div class="categories-scroll-slider">
                <?php foreach ($categories as $cat): ?>
                    <span class="category-item">
                        <img src="img/icons/<?= htmlspecialchars($cat['icone']) ?>" alt="" class="category-icon" />
                        <?= htmlspecialchars($cat['nom']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <img src="img/arrow.svg" id="scrollRight" class="category-arrow-icon" />
        </div>
    </header>

    <!-- Artisan Profil -->
    <main class="artisan-profile-layout">
        <section class="profile-info-section">
            <div class="artisan-bio-header">

                <!-- Profil Image -->
                <img src="<?= htmlspecialchars(get_image_path($prestataire['photo'], 'prestataire')) ?>" class="artisan-profile-avatar">
                <div class="artisan-main-details">

                    <!-- artisan name and Tarif Journalier -->
                    <div class="name-and-price-line">
                        <h1 class="artisan-full-name"><?= htmlspecialchars($prestataire['user_prenom'] . ' ' . $prestataire['user_nom']) ?></h1>
                        <span class="artisan-service-price"><?= htmlspecialchars($prestataire['tarif_journalier']) ?> DH/jour</span>
                    </div>

                    <!-- Rating the numbers of the stars and the evaluations-->
                    <div class="artisan-reviews-summary">
                        <img src="img/rating.svg" class="Rating-icon">
                        <div class="star-rating-display" data-rating="<?= htmlspecialchars($average_rating) ?>"></div>
                        <span class="artisan-rating-score"><?= htmlspecialchars(number_format($average_rating, 1)) ?></span>
                        <span class="artisan-total-reviews">(<?= htmlspecialchars($total_reviews) ?>)</span>
                    </div>
                    
                    <!-- Location -->
                    <div class="artisan-location-info">
                        <img src="img/location_icon.svg" class="location-marker-icon">
                        <span class="artisan-location-text"><?= htmlspecialchars($prestataire['ville'] . ', ' . $prestataire['pays']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Categorie , Titre , Description -->
            <h2 class="artisan-service-category"><?= htmlspecialchars($prestataire['categorie_nom']) ?></h2>
            <p class="artisan-short-description"><?= htmlspecialchars($experience['titre_experience'] ?? 'No experience yet') ?></p>
            <p class="artisan-long-description"><?= htmlspecialchars($experience['description'] ?? 'No description provided') ?></p>

            <div class="artisan-media-gallery">

            <!-- Main Gallery Image with Arrows -->
                <div class="main-media-viewer">
                    <img src="<?= htmlspecialchars(get_image_path($media[0]['chemin_fichier'] ?? '', 'media')) ?>" class="main-gallery-image">
                    <img src="img/arrow_slide_left.svg" class="gallery-arrow arrow-left">
                    <img src="img/arrow_slide_right.svg" class="gallery-arrow arrow-right">
                </div>

                <!-- Thumbails Images under the Main Media Image -->
                <div class="thumbnail-media-carousel">
                    <?php foreach ($media as $m): ?>
                        <img src="<?= htmlspecialchars(get_image_path($m['chemin_fichier'], 'media')) ?>" class="gallery-thumbnail">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Redirect the Client to the Experiences for this Specifique artisan -->
            <div class="view-all-experiences-button-container">
                <a href="experiences.php?id=<?= htmlspecialchars($id_prestataire) ?>" class="view-all-experiences-button">View All Experiences</a>
            </div>

            <section class="customer-reviews-section">
                <h3 class="reviews-section-title">Reviews</h3>
                <div class="reviews-list">
                    <?php if ($total_reviews > 0): ?>

                        <?php foreach ($evaluations as $evaluation): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <!-- Client name -->
                                    <span class="reviewer-name"><?= htmlspecialchars($evaluation['client_prenom'] . ' ' . $evaluation['client_nom']) ?></span>
                                    <!-- Number of Evaluations -->
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $evaluation['note']): ?>
                                                <img src="img/rating.svg" alt="star" class="star-icon filled">
                                            <?php else: ?>
                                                <img src="img/empty_star.svg" alt="empty star" class="star-icon">
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="rating-score"><?= htmlspecialchars(number_format($evaluation['note'], 1)) ?></span>
                                    </div>
                                </div>
                                <!-- Commentaire and Date -->
                                <p class="review-comment"><?= htmlspecialchars($evaluation['commentaire']) ?></p>
                                <span class="review-date"><?= htmlspecialchars(date('F j, Y', strtotime($evaluation['date_evaluation']))) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-reviews-message">No reviews yet for this artisan.</p>
                    <?php endif; ?>
                </div>
            </section>
        </section>

        <!-- Demande service Buton if Client -->
                <button class="request-service-action-btn" data-prestataire-id="<?= $id_prestataire ?>" <?= (isset($_SESSION['role']) && $_SESSION['role'] === 'client') ? '' : 'data-requires-login="true"' ?>>Demande De Service</button>
            <script>

                // If the Client Not Log In Redirect to the Login Page
                document.addEventListener('DOMContentLoaded', function() {
                    const requestServiceBtn = document.querySelector('.request-service-action-btn');

                    requestServiceBtn.addEventListener('click', function(event) {
                        event.preventDefault(); // Prevent default button action

                        const prestataireId = this.dataset.prestataireId;
                        const requiresLogin = this.dataset.requiresLogin === 'true';

                        if (requiresLogin) {
                            window.location.href = 'login.php'; // Redirect to login if not logged in
                        } else {
                            let redirectUrl = `demande_service.php?id_prestataire=${prestataireId}`;
                            window.location.href = redirectUrl;
                        }
                    });
                });
            </script>

    </main>


    <footer class="footer">
    <div class="footer-top-line"></div>
  
    <div class="footer-content">

        <div class="footer-column">
        <h3 class="footer-title">Categories</h3>
        <ul>
          <li>Construction</li>
          <li>Carpentry</li>
          <li>Electrical</li>
          <li>Plumbing</li>
          <li>HVAC</li>
          <li>Cleaning</li>
          <li>Metalwork</li>
          <li>Aluminum Work</li>
          <li>Gardening</li>
          <li>Security</li>
          <li>General Handyman</li>
        </ul>
      </div>
  

      <div class="footer-column">
        <h3 class="footer-title">For Client</h3>
        <ul>
          <li>How Skilled Works</li>
          <li>Customer Success Stories</li>
          <li>Trust & Safety</li>
          <li>Quality Guide</li>
          <li>Skilled Guide</li>
          <li>Skilled Faq</li>
        </ul>
      </div>
  

      <div class="footer-column">
        <h3 class="footer-title">For Artisans</h3>
        <ul>
          <li>Become a Skilled Artisans</li>
          <li>Become in Artisans</li>
          <li>Community Hub</li>
          <li>Forum</li>
          <li>Events</li>
        </ul>
      </div>
  

      <div class="footer-column">
        <h3 class="footer-title">Solutions</h3>
        <ul>
          <li>Become a Skilled Artisans</li>
          <li>Become in Artisans</li>
          <li>Community Hub</li>
          <li>Forum</li>
          <li>Events</li>
        </ul>
      </div>
  

      <div class="footer-column">
        <h3 class="footer-title">Company</h3>
        <ul>
          <li>Become a Skilled Artisans</li>
          <li>Become in Artisans</li>
          <li>Community Hub</li>
          <li>Forum</li>
          <li>Events</li>
        </ul>
      </div>
    </div>
  
    <div class="footer-bottom">
      <div class="footer-left">
        <img src="img/Skillid..png" alt="Skilled Logo" class="footer-logo" />
        <span class="footer-copy">© Skilled Ltd .2025</span>
      </div>
      <div class="footer-right">
        <div class="language-selector">
          <img src="img/language.png" class="lang-icon" />
          <span>English</span>
        </div>
        <div class="social-icons">
          <img src="img/tiktok.svg" alt="TikTok" />
          <img src="img/insta.svg" alt="Instagram" />
          <img src="img/link.svg" alt="LinkedIn" />
          <img src="img/fb.svg" alt="Facebook" />
          <img src="img/x.svg" alt="X" />
        </div>
      </div>
    </div>
  </footer>


    <script src="artisan.js"></script>
    
    <style>
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            right: 0; /* Align to the right of the profile picture */
            border-radius: 5px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .profile-dropdown.show .dropdown-content {
            display: block;
        }
    </style>

    <script>

        // If the User Click On the Profil Image the Dropdown will show
        function toggleProfileDropdown(event) {
            event.stopPropagation(); // Prevent click from immediately closing the dropdown
            document.querySelector('.profile-dropdown').classList.toggle('show');
        }

        // Close the dropdown if the user clicks outside of Pic Image
        window.onclick = function(event) {
            if (!event.target.matches('.header-profile-pic')) {
                let dropdowns = document.getElementsByClassName("profile-dropdown");
                for (let i = 0; i < dropdowns.length; i++) {
                    let openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>

