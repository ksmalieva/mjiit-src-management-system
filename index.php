<?php
// index.php - Public landing page
require_once 'config.php';

// Get latest news (for future sprint)
$latest_collaborations = db_fetch_all(
    "SELECT * FROM collaborations WHERE status = 'active' ORDER BY created_at DESC LIMIT 6"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Industry Collaboration Hub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Sangaku Renkei Center</h1>
            <p>Bridging Academia and Industry for Innovation</p>
            <div class="hero-buttons">
                <a href="auth/login.php" class="btn btn-primary">Staff Login</a>
                <a href="auth/register.php" class="btn btn-secondary">Register Account</a>
            </div>
        </div>
    </section>
    
    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <h2>About SRC</h2>
            <p>The Sangaku Renkei Center (SRC) at MJIIT facilitates collaborations between the university and industry partners for internships, research, and academic activities.</p>
        </div>
    </section>
    
    <!-- Collaborations Section -->
    <section class="collaborations-section">
        <div class="container">
            <h2>Our Collaborations</h2>
            <div class="collaborations-grid">
                <?php foreach ($latest_collaborations as $collab): ?>
                <div class="collab-card">
                    <h3><?php echo htmlspecialchars($collab['partner_name']); ?></h3>
                    <p class="collab-type"><?php echo ucfirst($collab['partner_type']); ?></p>
                    <p class="collab-desc"><?php echo htmlspecialchars(substr($collab['description'] ?? '', 0, 100)); ?>...</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <style>
    .hero {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        text-align: center;
        padding: 80px 20px;
    }
    
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 20px;
    }
    
    .hero p {
        font-size: 1.2rem;
        margin-bottom: 30px;
    }
    
    .hero-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px;
    }
    
    .about-section {
        background-color: #f8f9fa;
        text-align: center;
    }
    
    .about-section h2 {
        margin-bottom: 20px;
    }
    
    .collaborations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    
    .collab-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    
    .collab-card:hover {
        transform: translateY(-5px);
    }
    
    .collab-card h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }
    
    .collab-type {
        color: #3498db;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .collab-desc {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    @media (max-width: 768px) {
        .hero h1 { font-size: 2rem; }
        .hero-buttons { flex-direction: column; align-items: center; }
        .hero-buttons .btn { width: 200px; }
    }
    </style>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>