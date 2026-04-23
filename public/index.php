<?php
// public/index.php - Integrated public landing page
require_once '../config.php';

// Fetch dynamic data from database
$latest_news = db_fetch_all(
    "SELECT * FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 3"
);

$active_collaborations = db_fetch_all(
    "SELECT * FROM collaborations WHERE status = 'active' ORDER BY created_at DESC LIMIT 6"
);

// Check if user is logged in for navbar display
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SRC Public - MJIIT UTM Sangaku Renkei Center</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@500;700;800&display=swap"
      rel="stylesheet"
    />

    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/styles.css" />
    
    <style>
        /* Additional styles for dynamic content */
        .no-data-message {
            text-align: center;
            padding: 3rem;
            color: var(--on-surface-variant);
            background: var(--surface-container-low);
            border-radius: 0.75rem;
        }
        
        .news-list-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .collab-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .collab-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .btn-link {
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar glass-header">
      <div class="nav-brand">
        <img alt="SRC Logo" class="logo" src="../logo/SRC_logo.png" />
      </div>
      <div class="nav-links">
        <a href="#mission" class="active">About Us</a>
        <a href="news.php">News</a>
        <a href="collaborations.php">Collaborations</a>
      </div>
      <div class="nav-actions">
        <?php if ($is_logged_in): ?>
          <a href="../auth/logout.php"><button class="btn btn-secondary btn-sm">Logout</button></a>
          <?php if ($user_role == 'admin'): ?>
            <a href="../admin/dashboard.php"><button class="btn btn-primary btn-sm">Admin Dashboard</button></a>
          <?php elseif ($user_role == 'staff'): ?>
            <a href="../staff/dashboard.php"><button class="btn btn-primary btn-sm">Staff Dashboard</button></a>
          <?php endif; ?>
        <?php else: ?>
          <a href="../auth/login.php"><button class="btn btn-primary btn-sm" id="loginbtn">Login</button></a>
        <?php endif; ?>
      </div>
    </nav>

    <main class="main-content">
      <section class="hero">
        <div class="hero-bg">
          <div class="hero-overlay"></div>
          <img
            alt="ultra-modern glass university building facade"
            src="pictures/mjiit-building-front-scaled.jpg"
          />
        </div>

        <div class="container hero-content">
          <div class="hero-text-wrapper">
            <span class="badge">MJIIT UTM Sangaku Renkei Center</span>
            <h1 class="hero-title">
              Connecting <br />
              <span class="text-secondary">Academia</span> & <br />
              Industry.
            </h1>
            <p class="hero-description">
              <strong style="color: rgb(11, 0, 3)"
                >Bridging the gap between rigorous academic excellence and fluid
                corporate collaboration. We curate intellectual capital to drive
                global innovation</strong
              >
            </p>
            <div class="hero-buttons">
              <a href="collaborations.php"><button class="btn btn-primary" id="exploreBtn">Explore Projects</button></a>
              <a href="#mission"><button class="btn btn-secondary" id="learnBtn">Learn More</button></a>
            </div>
          </div>
        </div>
        <div class="hero-accent"></div>
      </section>

      <section class="section bg-light">
        <div class="container grid-2 align-center gap-lg">
          <div class="image-wrapper">
            <div class="image-box">
              <img
                alt="professional academic team discussing research data"
                src="pictures/mission.jpg"
              />
            </div>
          </div>

          <div class="about-content" id="mission">
            <h2 class="section-title">Our Vision & Mission</h2>
            <div class="features">
              <div class="feature-item">
                <h3 class="feature-title">
                  <span class="material-symbols-outlined">visibility</span>
                  Vision
                </h3>
                <p class="feature-text">
                  To be the premier global hub for industry-academia linkage,
                  fostering a culture of high-end collaborative research that
                  transcends traditional boundaries.
                </p>
              </div>
              <div class="feature-item">
                <h3 class="feature-title">
                  <span class="material-symbols-outlined">rocket_launch</span>
                  Mission
                </h3>
                <p class="feature-text">
                  We facilitate knowledge transfer, manage strategic academic
                  agreements, and provide researchers with the corporate
                  networking tools necessary to turn theories into tangible
                  solutions.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <div class="section-header space-between">
            <div>
              <h2 class="section-title">Latest News</h2>
              <p class="section-subtitle">
                Insights and activities from our academic nexus.
              </p>
            </div>
            <a href="news.php"><button class="link-btn">
              View All News
              <span class="material-symbols-outlined">arrow_forward</span>
            </button></a>
          </div>

          <div class="news-grid gap-md">
            <?php if (!empty($latest_news)): ?>
              <?php 
              $featured_news = $latest_news[0];
              $other_news = array_slice($latest_news, 1);
              ?>
              <!-- Featured News -->
              <div class="news-featured card hover-scale">
                <img
                  alt="<?php echo htmlspecialchars($featured_news['title']); ?>"
                  src="<?php echo $featured_news['featured_image'] ?? 'pictures/vision.jpg'; ?>"
                />
                <div class="news-featured-overlay"></div>
                <div class="news-featured-content">
                  <span class="tag bg-secondary text-white">
                    <?php echo strtoupper($featured_news['category'] ?? 'ANNOUNCEMENT'); ?>
                  </span>
                  <h3 class="news-title-large"><?php echo htmlspecialchars($featured_news['title']); ?></h3>
                  <p class="news-excerpt"><?php echo htmlspecialchars(substr($featured_news['content'], 0, 100)); ?>...</p>
                  <span class="news-date"><?php echo date('d F Y', strtotime($featured_news['published_at'])); ?></span>
                </div>
              </div>

              <!-- Other News List -->
              <div class="news-list gap-md">
                <?php foreach ($other_news as $news): ?>
                <div class="news-card card hover-bg" onclick="location.href='news.php?id=<?php echo $news['news_id']; ?>'">
                  <div>
                    <span class="news-category"><?php echo strtoupper($news['category'] ?? 'GENERAL'); ?></span>
                    <h4 class="news-title">
                      <?php echo htmlspecialchars($news['title']); ?>
                    </h4>
                  </div>
                  <div class="news-footer">
                    <span class="news-date-dark"><?php echo date('M d, Y', strtotime($news['published_at'])); ?></span>
                    <span class="material-symbols-outlined icon-arrow">arrow_right_alt</span>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="no-data-message">
                <span class="material-symbols-outlined" style="font-size: 3rem;">newspaper</span>
                <p>No news available at the moment. Please check back later.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="section bg-light overflow-hidden">
        <div class="container mb-lg">
          <h2 class="section-title">Current Collaborations</h2>
          <p class="section-subtitle">
            Connecting global leaders with academic brilliance.
          </p>
        </div>
        <div class="carousel no-scrollbar px-lg pb-lg gap-lg">
          <?php if (!empty($active_collaborations)): ?>
            <?php foreach ($active_collaborations as $collab): ?>
            <div class="collab-card card text-center" onclick="location.href='collaborations.php?id=<?php echo $collab['collab_id']; ?>'">
              <div class="icon-box">
                <?php 
                $icon_map = [
                    'industry' => 'corporate_fare',
                    'university' => 'school',
                    'research_institute' => 'science',
                    'government' => 'account_balance'
                ];
                $icon = $icon_map[$collab['partner_type']] ?? 'handshake';
                ?>
                <span class="material-symbols-outlined"><?php echo $icon; ?></span>
              </div>
              <h5><?php echo htmlspecialchars($collab['partner_name']); ?></h5>
              <p><?php echo htmlspecialchars(substr($collab['description'] ?? 'Strategic partnership', 0, 80)); ?></p>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-data-message">
              <span class="material-symbols-outlined" style="font-size: 3rem;">handshake</span>
              <p>No active collaborations at the moment. Please check back later.</p>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="section cta-section">
        <div class="cta-bg-icon">
          <span class="material-symbols-outlined">hub</span>
        </div>
        <div class="container cta-content text-center relative z-10">
          <h2>Ready to Pioneer the Future?</h2>
          <p>
            Join the SRC ecosystem as a researcher or industrial partner. Access
            grants, specialized facilities, and global networks.
          </p>
          <div class="cta-buttons gap-md">
            <a href="../auth/register.php"><button class="btn btn-secondary-solid btn-lg shadow-lg" id="registerBtn">
              Register as Partner
            </button></a>
            <a href="../auth/login.php"><button class="btn btn-outline btn-lg" id="portalLoginBtn">
              Login to Portal
            </button></a>
          </div>
        </div>
      </section>
    </main>

    <footer class="footer space-between align-center px-lg py-md">
      <div class="footer-brand">
        <span class="footer-title">SRC Public</span>
        <p class="footer-text">
          © 2024 MJIIT UTM Sangaku Renkei Center. All rights reserved.
        </p>
      </div>
      <div class="footer-links gap-lg">
        <a href="#">Privacy Policy</a>
        <a href="#">Contact Support</a>
        <a href="#">MJIIT Official</a>
      </div>
    </footer>
    
    <script src="../js/public.js"></script>
    <script>
      // Update JavaScript to work with PHP links
      document.addEventListener('DOMContentLoaded', function() {
        // If login button exists and user is not logged in, it already has href
        // If user is logged in, the button text changes
        <?php if (!$is_logged_in): ?>
        const loginBtn = document.getElementById('loginbtn');
        if (loginBtn) {
          loginBtn.addEventListener('click', function() {
            window.location.href = '../auth/login.php';
          });
        }
        <?php endif; ?>
        
        const exploreBtn = document.getElementById('exploreBtn');
        if (exploreBtn) {
          exploreBtn.addEventListener('click', function() {
            window.location.href = 'collaborations.php';
          });
        }
        
        const learnBtn = document.getElementById('learnBtn');
        if (learnBtn) {
          learnBtn.addEventListener('click', function() {
            document.getElementById('mission').scrollIntoView({ behavior: 'smooth' });
          });
        }
        
        const registerBtn = document.getElementById('registerBtn');
        if (registerBtn) {
          registerBtn.addEventListener('click', function() {
            window.location.href = '../auth/register.php';
          });
        }
        
        const portalLoginBtn = document.getElementById('portalLoginBtn');
        if (portalLoginBtn) {
          portalLoginBtn.addEventListener('click', function() {
            window.location.href = '../auth/login.php';
          });
        }
      });
    </script>
</body>
</html>