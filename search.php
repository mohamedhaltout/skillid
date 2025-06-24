<?php
session_start();
require 'config.php';


// Get the profil image is Artisan and get his Profil Image of not get a default image
$artisan_profile_photo = get_image_path('', 'prestataire'); // Default profile photo
if (isset($_SESSION['id_utilisateur']) && $_SESSION['role'] === 'prestataire') {
    $id_utilisateur = $_SESSION['id_utilisateur'];
    $stmt_photo = $pdo->prepare("SELECT photo FROM Prestataire WHERE id_utilisateur = ?");
    $stmt_photo->execute([$id_utilisateur]);
    $artisan_data = $stmt_photo->fetch(PDO::FETCH_ASSOC);
    if ($artisan_data && !empty($artisan_data['photo'])) {
        $artisan_profile_photo = get_image_path($artisan_data['photo'], 'prestataire');
    }
}


// Fetch Categories
$categories = $pdo->query("SELECT id_categorie, nom FROM Categories WHERE type = 'standard'")->fetchAll(PDO::FETCH_ASSOC);

// Filters
$search_query = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$country_filter = $_GET['country'] ?? '';
$city_filter = $_GET['city'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$rating_filter = $_GET['rating'] ?? '';



// List Des Prestataire
$sql = "SELECT p.id_prestataire, p.photo, p.tarif_journalier, p.ville, p.pays,
               u.nom AS user_nom, u.prenom AS user_prenom, c.nom AS categorie_nom,
               (SELECT AVG(note) FROM Evaluation e WHERE e.id_prestataire = p.id_prestataire) AS avg_rating,
               (SELECT COUNT(*) FROM Evaluation e WHERE e.id_prestataire = p.id_prestataire) AS review_count,
               (SELECT description FROM Experience_prestataire ep WHERE ep.id_prestataire = p.id_prestataire ORDER BY date_project DESC LIMIT 1) AS latest_experience,
               (SELECT me.chemin_fichier
                FROM Experience_prestataire ep_inner
                JOIN Media_experience me ON ep_inner.id_experience = me.id_experience
                WHERE ep_inner.id_prestataire = p.id_prestataire
                ORDER BY ep_inner.date_project DESC, me.id_media ASC
                LIMIT 1) AS cover_image
       FROM Prestataire p
       JOIN Utilisateur u ON p.id_utilisateur = u.id_utilisateur
       JOIN Categories c ON p.id_categorie = c.id_categorie
       WHERE 1=1";

$params = [];


// Filter and search

if ($search_query) {
    $sql .= " AND (c.nom LIKE ? OR p.ville LIKE ? OR EXISTS (SELECT 1 FROM Experience_prestataire ep WHERE ep.id_prestataire = p.id_prestataire AND ep.description LIKE ?))";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($category_filter) {
    $sql .= " AND p.id_categorie = ?";
    $params[] = $category_filter;
}
if ($country_filter) {
    $sql .= " AND p.pays = ?";
    $params[] = $country_filter;
}
if ($city_filter) {
    $sql .= " AND p.ville = ?";
    $params[] = $city_filter;
}
if ($price_min !== '' && is_numeric($price_min)) {
    $sql .= " AND p.tarif_journalier >= ?";
    $params[] = $price_min;
}
if ($price_max !== '' && is_numeric($price_max)) {
    $sql .= " AND p.tarif_journalier <= ?";
    $params[] = $price_max;
}
if ($rating_filter && in_array($rating_filter, ['3', '4', '5'])) {
    $sql .= " AND (SELECT AVG(note) FROM Evaluation e WHERE e.id_prestataire = p.id_prestataire) >= ?";
    $params[] = $rating_filter;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prestataires = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countries = ['Morocco', 'Spain', 'France', 'Belgium', 'Netherlands', 'United Kingdom'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="search.css">
  <title>Skilled - Search For Artisans</title>
</head>
<body>
  <header class="prestataires-header">
<style>
    .login-button, .signup-button {
      background-color: #3E185B;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      text-decoration: none;
      margin-left: 10px;
    }
    .login-button:hover, .signup-button:hover {
      opacity: 0.9;
    }
  </style>
    <div class="header-top">
      <div class="logo">
        <a href="home.php">
        <img src="img/skilled_logo.svg" alt="Skilled Logo" class="logo-img" />
        </a>
        <span class="logo-text">Skilled<span class="dot">.</span></span>
      </div>


      <!-- Right Header -->
      <div class="header-right">
        <?php if (isset($_SESSION['id_utilisateur'])): ?>

            <!-- Show the Order Button Based On the Role -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
                <a href="client_dashboard.php" class="orders">Orders</a>
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire'): ?>
                <a href="artisan_dashboard.php" class="orders">Orders</a>
            <?php else: ?>
                <span class="orders">Orders</span>
            <?php endif; ?>

            <!-- Switch To Artisan -->
            <a href="sign_up_artisan.php" class="switch">Switch to Artisans</a>

            <!-- Profil -->
            <div class="profile-dropdown">
                <img src="<?= htmlspecialchars($artisan_profile_photo) ?>" class="profile-pic" alt="Profile" onclick="toggleProfileDropdown(this, event)" />
                <div class="dropdown-content">
                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
                    <a href="client_profile.php">My Profile</a>
                  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire'): ?>
                    <a href="artisan_profile.php">My Artisan Profile</a>
                  <?php endif; ?>
                  <a href="logout.php">Logout</a>
                </div>
            </div>

            <!-- If The User Not Log In -->
        <?php else: ?>
            <a href="login.php" class="login-button">Login</a>
            <a href="sign_up_client.php" class="signup-button">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="line"></div>

    <!-- Categories Slider -->
    <div class="categories-bar">
      <div class="categories-slider">
        <?php foreach ($categories as $cat): ?>
          <a href="search.php?category=<?= $cat['id_categorie'] ?>" class="category"> <?= htmlspecialchars($cat['nom']) ?> </a>
        <?php endforeach; ?>
      </div>
      <img src="img/arrow.svg" alt="More" class="arrow-icon" id="scrollRight" />
    </div>
  </header>


  <!-- Search Bar -->
  <section class="search-section">
    <div class="search-texts">
      <h1 class="search-title">Find Your Artisan</h1>
      <p class="search-subtitle">Discover skilled professionals ...</p>
    </div>
    <div class="search-box">
      <form method="GET" action="">
        <input type="text" name="search" placeholder="Search for any service ..." value="<?= htmlspecialchars($search_query) ?>" />
        <div class="search-icon-wrapper">
          <button type="submit" style="background: none; border: none; padding: 0;">
            <img src="img/search.png" alt="Search" class="search-icon" />
          </button>
        </div>
      </form>
    </div>
  </section>

        </div>
    </div>



    <!-- Filters -->
    <section class="cards-filters-section">
        <h2 class="section-title">Artisans</h2>
        <p class="section-description">
            Browse our selection of verified artisans ready to assist with your needs.
        </p>

        <div class="filters-container">
            <div class="filter-card">
                <img src="img/categoryes.svg" alt="Category Icon" class="filter-icon" />
                <span class="filter-title">Category</span>
                <img src="img/down_arrow.svg" alt="Dropdown Arrow" class="filter-arrow" />
                <select name="category" onchange="this.form.submit()" form="filterForm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id_categorie'] ?>" <?= $category_filter == $cat['id_categorie'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-card">
                <img src="img/country.svg" alt="Country Icon" class="filter-icon" />
                <span class="filter-title">Country</span>
                <img src="img/down_arrow.svg" alt="Dropdown Arrow" class="filter-arrow" />
                <select name="country" id="country-select" onchange="updateCities(); this.form.submit()" form="filterForm">
                    <option value="">All Countries</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country) ?>" <?= $country_filter == $country ? 'selected' : '' ?>>
                            <?= htmlspecialchars($country) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-card">
                <img src="img/City.svg" alt="City Icon" class="filter-icon" />
                <span class="filter-title">City</span>
                <img src="img/down_arrow.svg" alt="Dropdown Arrow" class="filter-arrow" />
                <select name="city" id="city-select" onchange="this.form.submit()" form="filterForm">
                    <option value="">All Cities</option>
                    <?php if ($country_filter): ?>
                        <?php
                        $cities = [
                            'Morocco' => ['Tanger', 'Casablanca', 'Rabat', 'Marrakech', 'Fes'],
                            'Spain' => ['Madrid', 'Barcelona', 'Seville'],
                            'France' => ['Paris', 'Lyon', 'Marseille'],
                            'Belgium' => ['Brussels', 'Antwerp', 'Ghent'],
                            'Netherlands' => ['Amsterdam', 'Rotterdam', 'Utrecht'],
                            'United Kingdom' => ['London', 'Manchester', 'Birmingham']
                        ][$country_filter] ?? [];
                        foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= $city_filter == $city ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="filter-card">
                <img src="img/price.svg" alt="Price Icon" class="filter-icon" />
                <span class="filter-title">Price/day</span>
                <img src="img/down_arrow.svg" alt="Dropdown Arrow" class="filter-arrow" />
                <div class="price-range">
                    <input type="number" name="price_min" placeholder="Min" value="<?= htmlspecialchars($price_min) ?>" form="filterForm">
                    <span>-</span>
                    <input type="number" name="price_max" placeholder="Max" value="<?= htmlspecialchars($price_max) ?>" form="filterForm">
                    <button type="submit" form="filterForm"></button>
                </div>
            </div>

            <div class="filter-card">
                <img src="img/rating.svg" alt="Rating Icon" class="filter-icon" />
                <span class="filter-title">Rating</span>
                <img src="img/down_arrow.svg" alt="Dropdown Arrow" class="filter-arrow" />
                <select name="rating" class="filter-select" onchange="this.form.submit()" form="filterForm">
                    <option value="">All Ratings</option>
                    <option value="5" <?= $rating_filter == '5' ? 'selected' : '' ?>>5 Stars & Up</option>
                    <option value="4" <?= $rating_filter == '4' ? 'selected' : '' ?>>4 Stars & Up</option>
                    <option value="3" <?= $rating_filter == '3' ? 'selected' : '' ?>>3 Stars & Up</option>
                </select>
            </div>

        </div>

        <form id="filterForm" method="GET" style="display: none;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        </form>


        <!-- Artisan Card -->
        <div class="cards-container">
  <?php if (empty($prestataires)): ?>
    <p>No artisans found matching your criteria.</p>
  <?php else: ?>
    <?php foreach ($prestataires as $prestataire): ?>
      <div class="prestataire-card">
        <a href="artisan.php?id=<?= $prestataire['id_prestataire'] ?>">
          <img src="<?= htmlspecialchars(get_image_path($prestataire['cover_image'] ?? $prestataire['photo'], $prestataire['cover_image'] ? 'media' : 'prestataire')) ?>" alt="Service Image" class="service-image" />
          <div class="card-content">
            <div class="profile-category">
              <div class="profile-dropdown">
                <img src="<?= htmlspecialchars(get_image_path($prestataire['photo'], 'prestataire')) ?>" alt="Profile" class="profile-photo" onclick="toggleProfileDropdown(this, event)" />
                <div class="dropdown-content">
                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
                    <a href="client_profile.php">My Profile</a>
                  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire'): ?>
                    <a href="artisan_profile.php">My Artisan Profile</a>
                  <?php endif; ?>
                  <a href="logout.php">Logout</a>
                </div>
              </div>
              <div class="name-category">
                <span class="prestataire-name"><?= htmlspecialchars($prestataire['user_prenom'] . ' ' . $prestataire['user_nom']) ?></span>
                <span class="prestataire-category"><?= htmlspecialchars($prestataire['categorie_nom']) ?></span>
              </div>
            </div>
            <p class="service-description">
              <?= htmlspecialchars(truncate_description($prestataire['latest_experience'] ?? 'No experience description available.', 5)) ?>
            </p>
            <div class="reviews-section">
              <img src="img/review_group.svg" alt="Reviews" class="review-icon" />
              <span class="review-score"><?= number_format($prestataire['avg_rating'] ?? 0, 1) ?></span>
              <span class="total-reviews">(<?= $prestataire['review_count'] ?? 0 ?>)</span>
            </div>
            <div class="price-section"><?= htmlspecialchars($prestataire['tarif_journalier']) ?> DH/day</div>
            <div class="location">
              <img src="img/location_icon.svg" alt="Location" class="location-icon" />
              <span class="location-text"><?= htmlspecialchars($prestataire['ville'] . ', ' . $prestataire['pays']) ?></span>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

    </section>

    <footer class="footer">
        <div class="footer-top-line"></div>
        <div class="footer-content">
            <div class="footer-column">
                <h3 class="footer-title">Categories</h3>
                <ul>
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="search.php?category=<?= $cat['id_categorie'] ?>"><?= htmlspecialchars($cat['nom']) ?></a></li>
                    <?php endforeach; ?>
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
                    <li>Become a Skilled Artisan</li>
                    <li>Artisan Community</li>
                    <li>Community Hub</li>
                    <li>Forum</li>
                    <li>Events</li>
                </ul>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Solutions</h3>
                <ul>
                    <li>Skilled for Business</li>
                    <li>Enterprise Solutions</li>
                    <li>Community Hub</li>
                    <li>Forum</li>
                    <li>Events</li>
                    </ul>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Company</h3>
                <ul>
                    <li>About Us</li>
                    <li>Careers</li>
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

    <script>

        // Categories Scrool
        let scrollRightBtn = document.getElementById('scrollRight');
        let slider = document.querySelector('.categories-slider');
        scrollRightBtn.addEventListener('click', () => {
            slider.scrollBy({ left: 200, behavior: 'smooth' });
        });

        // Citys Filter Select
        let citiesByCountry = {
            "Morocco": ["Tanger", "Casablanca", "Rabat", "Marrakech", "Fes"],
            "Spain": ["Madrid", "Barcelona", "Seville"],
            "France": ["Paris", "Lyon", "Marseille"],
            "Belgium": ["Brussels", "Antwerp", "Ghent"],
            "Netherlands": ["Amsterdam", "Rotterdam", "Utrecht"],
            "United Kingdom": ["London", "Manchester", "Birmingham"]
        };


        // Show and Update Citys Based On the Country Selected
        function updateCities() {
            let country = document.getElementById("country-select").value;
            let citySelect = document.getElementById("city-select");
            citySelect.innerHTML = '<option value="">All Cities</option>';

            if (citiesByCountry[country]) {
                citiesByCountry[country].forEach(function(city) {
                    let option = document.createElement("option");
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        }



        // Save the selected City Value if I Change the Page
        <?php if ($country_filter): ?>
            updateCities();
            document.getElementById("city-select").value = "<?= htmlspecialchars($city_filter) ?>";
        <?php endif; ?>



        // Change the filter-card to be active and show the Dropdown
        document.querySelectorAll('.filter-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('select') && !e.target.closest('input')) {
                    card.classList.toggle('active');
                }
            });
        });


        // Submit the Price Range Without Click on submit Button
        document.querySelectorAll('.price-range input').forEach(input => {
            input.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
            });
        });

        // Sticky search visibility
        window.addEventListener('scroll', () => {
            if (window.scrollY > document.querySelector('.search-section').offsetHeight) {
                document.body.classList.add('scrolled');
            } else {
                document.body.classList.remove('scrolled');
            }
        });
    </script>

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
        function toggleProfileDropdown(clickedElement, event) {
            event.stopPropagation(); // Prevent click from immediately closing the dropdown
            let dropdown = clickedElement.closest('.profile-dropdown');
            dropdown.classList.toggle('show');

            // Close other open dropdowns
            let allDropdowns = document.querySelectorAll('.profile-dropdown.show');
            allDropdowns.forEach(function(openDropdown) {
                if (openDropdown !== dropdown) {
                    openDropdown.classList.remove('show');
                }
            });
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                let dropdowns = document.querySelectorAll(".profile-dropdown.show");
                dropdowns.forEach(function(openDropdown) {
                    openDropdown.classList.remove('show');
                });
            }
        }
    </script>
    
</body>
</html>