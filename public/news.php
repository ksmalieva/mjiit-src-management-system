<?php
// public/news.php - Integrated news page with dynamic content
require_once '../config.php';

// Get filter parameters
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$where_conditions = ["status = 'published'"];
$params = [];

if ($category !== 'all') {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM news WHERE $where_clause";
$total_result = db_fetch_one($count_sql, $params);
$total_news = $total_result['total'] ?? 0;
$total_pages = ceil($total_news / $per_page);

// Get news for current page
$sql = "SELECT * FROM news WHERE $where_clause ORDER BY published_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$news_items = db_fetch_all($sql, $params);

// Get featured news (latest published)
$featured_news = db_fetch_one(
    "SELECT * FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 1"
);

// Category mapping for badges
$category_badge_class = [
    'research' => 'research',
    'industry_talk' => 'industry',
    'innovation' => 'innovation',
    'announcement' => 'research',
    'event' => 'industry',
    'achievement' => 'innovation',
    'general' => 'research'
];

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
    <title>News & Announcements | The Academic Nexus</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles/publicnews.css" />
    
    <style>
        /* Additional styles for dynamic content */
        .no-results {
            text-align: center;
            padding: 4rem;
            background: var(--surface-container-lowest);
            border-radius: 1rem;
            grid-column: 1 / -1;
        }
        
        .no-results .material-symbols-outlined {
            font-size: 4rem;
            color: var(--on-surface-variant);
            margin-bottom: 1rem;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-form input {
            background: white;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            width: 320px;
        }
        
        .search-form button {
            background: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }
        
        .active-filter {
            background: white !important;
            color: var(--primary) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                gap: 1rem;
            }
            .search-form input {
                width: 100%;
            }
            .featured-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
      <div class="nav-container">
        <div class="nav-left">
          <div class="nav-links">
            <a href="index.php">About Us</a>
            <a href="#" class="active">News</a>
            <a href="collaborations.php">Collaborations</a>
          </div>
        </div>
        <div class="nav-right">
          <div class="search-box">
            <form method="GET" action="" class="search-form">
              <input type="text" name="search" placeholder="Search insights..." value="<?php echo htmlspecialchars($search); ?>" />
              <button type="submit">Search</button>
            </form>
          </div>
          <a href="../auth/login.php"><button class="btn-primary">Connect</button></a>
        </div>
      </div>
    </nav>

    <main class="content-wrapper">
      <header class="page-header">
        <div class="breadcrumbs">
          <a href="index.php" style="text-decoration: none; color: inherit;">HOME</a>
          <span class="material-symbols-outlined">chevron_right</span>
          <span class="active-crumb">NEWS & INSIGHTS</span>
        </div>
        <h1>Latest from SRC</h1>
        <p class="subtitle">
          Exploring the intersection of Japanese engineering excellence and
          Malaysian industrial innovation. Discover our latest breakthroughs,
          research milestones, and corporate partnerships.
        </p>
      </header>

      <?php if ($featured_news): ?>
      <section class="featured-card">
        <div class="featured-image">
          <img
            src="<?php echo $featured_news['featured_image'] ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuAv5y0pb8k_Dmf_fhwTKnDryGqppBiyjICbDDUrIO2LA-kEVL43bGYTCvjWRZbq4tx2iLKXsazqfuBCwTxPIQAQLd-avYsDseaT11DLhwsMK_BRGSAYwieIh2xxgQMd2WLlhNyIyWcUvGuN9lVUlBgD0CImP2ZHG7Z3pvLU7ASTw0Kw6aIPcK8FyfQl8JscqQl5u755A7bOVRFpbx0d5MzyNfSQtOaUQJpKH8JZwmZONeKj4nhxI6WmUkah1exYYdAcnEgJhUNtnw0'; ?>"
            alt="<?php echo htmlspecialchars($featured_news['title']); ?>"
          />
        </div>
        <div class="featured-content">
          <div class="badge">
            <span class="material-symbols-outlined">verified</span> FEATURED STORY
          </div>
          <h2><?php echo htmlspecialchars($featured_news['title']); ?></h2>
          <p><?php echo htmlspecialchars(substr($featured_news['content'], 0, 200)); ?>...</p>
          <div class="author-block">
            <?php
            $author = get_user($featured_news['created_by']);
            $avatar_url = 'https://lh3.googleusercontent.com/aida-public/AB6AXuBgto4H_65ZjuNtmoGyNBzb3kfghIfp81Yh1q4PEfJ2Ov7sa9-rRQzJd1KkwHUgJijTqvd-_xWjypYaGxjmW8uuMLVWFXc5E6314dTVzt-GOFU2EhBX7duPyOCjjdThAI1UeAirLUoFtJFxTZA5tN0fACcdmTWzJvqjZPDRm7o_4bgN5R2Ff5E244GFlpqJ9gRmrsZ5oTHnY5cwrjpMJzosiFufsBUtZYePBcOodkgjJ2D0mmzV6JIODHSKDvAZ8Fos_KcgwyzPsD8';
            ?>
            <img src="<?php echo $avatar_url; ?>" alt="Author" class="avatar" />
            <div class="author-info">
              <strong><?php echo htmlspecialchars($author['full_name'] ?? 'SRC Editor'); ?></strong>
              <span><?php echo date('F j, Y', strtotime($featured_news['published_at'])); ?></span>
            </div>
          </div>
          <a href="news-detail.php?id=<?php echo $featured_news['news_id']; ?>" class="read-more">
            Read Full Insight
            <span class="material-symbols-outlined">arrow_forward</span>
          </a>
        </div>
      </section>
      <?php endif; ?>

      <div class="filter-bar">
        <div class="filter-tabs">
          <a href="?category=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="tab <?php echo $category == 'all' ? 'active' : ''; ?>">All News</button>
          </a>
          <a href="?category=research<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="tab <?php echo $category == 'research' ? 'active' : ''; ?>">Research</button>
          </a>
          <a href="?category=industry_talk<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="tab <?php echo $category == 'industry_talk' ? 'active' : ''; ?>">Industry Talk</button>
          </a>
          <a href="?category=innovation<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="tab <?php echo $category == 'innovation' ? 'active' : ''; ?>">Innovation</button>
          </a>
        </div>
        <div class="filter-search">
          <form method="GET" action="" style="display: flex; gap: 0.5rem;">
            <?php if ($category !== 'all'): ?>
              <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Filter by keywords..." value="<?php echo htmlspecialchars($search); ?>" />
            <button type="submit" style="background: none; border: none; cursor: pointer;">
              <span class="material-symbols-outlined">tune</span>
            </button>
            <?php if (!empty($search) || $category !== 'all'): ?>
              <a href="?category=all" style="text-decoration: none;">
                <button type="button" style="background: var(--surface-container-low); border: none; padding: 0.5rem 1rem; border-radius: 12px; cursor: pointer;">Clear</button>
              </a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="news-grid">
        <?php if (!empty($news_items)): ?>
          <?php foreach ($news_items as $news): ?>
            <?php 
            $news_category = $news['category'] ?? 'general';
            $badge_class = $category_badge_class[$news_category] ?? 'research';
            $display_category = $category_display[$news_category] ?? strtoupper($news_category);
            ?>
            <article class="news-card">
              <div class="card-img-wrapper">
                <img
                  src="<?php echo $news['featured_image'] ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuAtNAjkIxlIh2ciXsjp6geVzKGP-49YTRJhU38Phx9ki0pIC08Foqn-Wh5At0eKGI3Y9jN3c4P0jRm1B6HISU7jR35KV3vJQrJMErNagUdK1pH5EPKei_VmhKxz8F5uxpoZCXGHNxNYTN44PIjflspyBtcsVtRd_iUkXgWeyvIh1GR7boPEgAkSUNAqmgTg6C1hspW_SZk-Ph7uOsEVM62CTMtNIaxlkAcGInBdOuNnqFvG9OyBHp3qXa5YfFl57VpQsI0h3t5WAgg'; ?>"
                  alt="<?php echo htmlspecialchars($news['title']); ?>"
                />
                <span class="card-tag <?php echo $badge_class; ?>"><?php echo $display_category; ?></span>
              </div>
              <div class="card-body">
                <div class="card-meta">
                  <span class="material-symbols-outlined">calendar_today</span> 
                  <?php echo date('M d, Y', strtotime($news['published_at'])); ?>
                </div>
                <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                <p><?php echo htmlspecialchars(substr($news['content'], 0, 120)); ?>...</p>
                <a href="news-detail.php?id=<?php echo $news['news_id']; ?>" class="card-link">
                  Read More
                  <span class="material-symbols-outlined">north_east</span>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-results">
            <span class="material-symbols-outlined">newspaper</span>
            <h3>No news found</h3>
            <p>Try adjusting your search or filter criteria.</p>
            <a href="?category=all"><button class="btn-primary" style="margin-top: 1rem;">View all news</button></a>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
            <button class="pg-btn">
              <span class="material-symbols-outlined">chevron_left</span>
            </button>
          </a>
        <?php endif; ?>
        
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1): ?>
          <a href="?page=1&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
            <button class="pg-btn">1</button>
          </a>
          <?php if ($start_page > 2): ?>
            <span class="dots">...</span>
          <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
          <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
            <button class="pg-btn <?php echo $i == $page ? 'pg-active' : ''; ?>"><?php echo $i; ?></button>
          </a>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
          <?php if ($end_page < $total_pages - 1): ?>
            <span class="dots">...</span>
          <?php endif; ?>
          <a href="?page=<?php echo $total_pages; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
            <button class="pg-btn"><?php echo $total_pages; ?></button>
          </a>
        <?php endif; ?>
        
        <?php if ($page < $total_pages): ?>
          <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">
            <button class="pg-btn">
              <span class="material-symbols-outlined">chevron_right</span>
            </button>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
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
    
    <script>
    // Update active tab based on URL parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        
        if (category && category !== 'all') {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                if (tab.textContent.toLowerCase().replace(' ', '_') === category) {
                    tab.classList.add('active');
                } else if (category === 'all' && tab.textContent === 'All News') {
                    tab.classList.add('active');
                }
            });
        }
    });
    </script>
</body>
</html>