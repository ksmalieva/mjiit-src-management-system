<?php
// public/news-detail.php - Single news article view
require_once '../config.php';

$news_id = (int)($_GET['id'] ?? 0);

if ($news_id <= 0) {
    header('Location: news.php');
    exit();
}

$news = db_fetch_one(
    "SELECT n.*, u.full_name as author_name 
     FROM news n
     LEFT JOIN users u ON n.created_by = u.user_id
     WHERE n.news_id = ? AND n.status = 'published'",
    [$news_id]
);

if (!$news) {
    header('Location: news.php');
    exit();
}

// Get related news (same category)
$related_news = db_fetch_all(
    "SELECT * FROM news WHERE category = ? AND news_id != ? AND status = 'published' LIMIT 3",
    [$news['category'], $news_id]
);

$category_display = [
    'research' => 'RESEARCH',
    'industry_talk' => 'INDUSTRY TALK',
    'innovation' => 'INNOVATION',
    'announcement' => 'ANNOUNCEMENT',
    'event' => 'EVENT',
    'achievement' => 'ACHIEVEMENT',
    'general' => 'GENERAL'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($news['title']); ?> | The Academic Nexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Styles/publicnews.css" />
    <style>
        .article-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .article-header {
            margin-bottom: 2rem;
        }
        .article-title {
            font-size: 2.5rem;
            color: var(--primary);
            margin: 1rem 0;
        }
        .article-meta {
            color: var(--on-surface-variant);
            font-size: 0.875rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .article-image {
            width: 100%;
            border-radius: 1rem;
            margin: 2rem 0;
        }
        .article-content {
            font-size: 1.125rem;
            line-height: 1.8;
            color: var(--on-surface);
        }
        .article-content p {
            margin-bottom: 1.5rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--primary);
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .related-section {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .related-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s;
        }
        .related-card:hover {
            transform: translateY(-5px);
        }
        .related-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .related-card h4 {
            padding: 1rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="nav-links">
                    <a href="index.php">About Us</a>
                    <a href="news.php" class="active">News</a>
                    <a href="collaborations.php">Collaborations</a>
                </div>
            </div>
            <div class="nav-right">
                <a href="../auth/login.php"><button class="btn-primary">Connect</button></a>
            </div>
        </div>
    </nav>

    <main class="content-wrapper">
        <div class="article-container">
            <a href="news.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to News
            </a>
            
            <article>
                <div class="article-header">
                    <div class="badge" style="background: var(--secondary-container); width: fit-content;">
                        <?php echo $category_display[$news['category']] ?? strtoupper($news['category']); ?>
                    </div>
                    <h1 class="article-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                    <div class="article-meta">
                        <span><?php echo date('F j, Y', strtotime($news['published_at'])); ?></span>
                        <span>•</span>
                        <span>By <?php echo htmlspecialchars($news['author_name'] ?? 'SRC Editor'); ?></span>
                    </div>
                </div>
                
                <?php if ($news['featured_image']): ?>
                <img src="<?php echo htmlspecialchars($news['featured_image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="article-image" />
                <?php endif; ?>
                
                <div class="article-content">
                    <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                </div>
            </article>
            
            <?php if (!empty($related_news)): ?>
            <div class="related-section">
                <h3>Related Articles</h3>
                <div class="related-grid">
                    <?php foreach ($related_news as $related): ?>
                    <a href="news-detail.php?id=<?php echo $related['news_id']; ?>" class="related-card">
                        <img src="<?php echo $related['featured_image'] ?? 'https://via.placeholder.com/300x150?text=News'; ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" />
                        <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-copy">
                <strong>The Academic Nexus</strong>
                <p>© 2024 MJIIT Sangaku Renkei Center. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
                <a href="#">MJIIT Official</a>
            </div>
        </div>
    </footer>
</body>
</html>