<?php
// staff/news.php - News management page
require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

$success = '';
$error = '';

// Handle news submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = sanitize($_POST['title'] ?? '');
    $category = sanitize($_POST['category'] ?? 'general');
    $content = $_POST['content'] ?? '';
    $featured_image = sanitize($_POST['featured_image'] ?? '');
    $tags = sanitize($_POST['tags'] ?? '');
    
    if ($action === 'publish') {
        if (!$title || !$content) {
            $error = "Title and content are required";
        } else {
            $news_id = db_insert('news', [
                'title' => $title,
                'category' => strtolower($category),
                'content' => $content,
                'featured_image' => $featured_image,
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user_id']
            ]);
            
            if ($news_id) {
                log_activity($_SESSION['user_id'], 'news_published', "Published news: $title");
                $success = "News published successfully!";
                // Clear form data
                $_POST = [];
            } else {
                $error = "Failed to publish news";
            }
        }
    } elseif ($action === 'save_draft') {
        if (!$title) {
            $error = "Title is required for draft";
        } else {
            $news_id = db_insert('news', [
                'title' => $title,
                'category' => strtolower($category),
                'content' => $content,
                'featured_image' => $featured_image,
                'status' => 'draft',
                'created_by' => $_SESSION['user_id']
            ]);
            
            if ($news_id) {
                log_activity($_SESSION['user_id'], 'news_draft_saved', "Saved draft: $title");
                $success = "Draft saved successfully!";
                $_POST = [];
            } else {
                $error = "Failed to save draft";
            }
        }
    }
}

// Get recent drafts and published news
$recent_news = db_fetch_all(
    "SELECT n.*, u.full_name as author_name 
     FROM news n
     LEFT JOIN users u ON n.created_by = u.user_id
     ORDER BY n.created_at DESC LIMIT 10"
);

$draft_count = db_count('news', "status = 'draft' AND created_by = " . $_SESSION['user_id']);
$published_count = db_count('news', "status = 'published'");

// Get current user info
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Create Update | The Academic Nexus</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/news.css" />
    
    <style>
        .logout-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #059669;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 4px solid #059669;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 4px solid #dc2626;
        }
        
        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
        }
        
        .character-count {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .character-count.warning {
            color: #f59e0b;
        }
        
        .character-count.danger {
            color: #dc2626;
        }
        
        .back-link-custom {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="app-header">
      <div class="container header-content">
        <div class="header-left">
          <a class="back-link back-link-custom group" href="dashboard.php">
            <div class="icon-box dark transition-transform">
              <span class="material-symbols-outlined text-white">arrow_back</span>
            </div>
            <div class="back-text">
              <span class="title">Back to Dashboard</span>
              <span class="subtitle">Return Home</span>
            </div>
          </a>
          <div class="divider-v hidden-sm"></div>
          <div class="status-indicator hidden-md">
            <div class="dot bg-teal"></div>
            <span>Post Center News</span>
          </div>
        </div>

        <div class="header-right">
          <div class="user-profile">
            <button class="icon-btn hover-bg">
              <span class="material-symbols-outlined">notifications</span>
            </button>
            <div class="user-info hidden-lg">
              <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
              <p class="user-role"><?php echo ucfirst($user_role); ?></p>
            </div>
            <div class="avatar" style="background: var(--teal-600); display: flex; align-items: center; justify-content: center; color: white;">
              <?php echo substr($_SESSION['user_name'], 0, 2); ?>
            </div>
          </div>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="save_draft">
            <button type="submit" class="btn btn-text" name="save_draft_btn">Save Draft</button>
          </form>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="publish">
            <button type="submit" class="btn btn-primary shadow-teal">
              <span class="material-symbols-outlined icon-left">send</span>
              Publish Now
            </button>
          </form>
        </div>
      </div>
    </header>

    <main class="main-wrapper">
      <div class="container">
        <?php if ($success): ?>
        <div class="alert-success">
          <span class="material-symbols-outlined">check_circle</span>
          <span><?php echo $success; ?></span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-error">
          <span class="material-symbols-outlined">error</span>
          <span><?php echo $error; ?></span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>

        <div class="editor-grid">
          <div class="editor-main space-y">
            <div class="focus-card p-large">
              <label class="tracking-label mb-md">Article Headline</label>
              <input class="title-input" id="newsTitle" name="title" placeholder="A compelling title for the nexus..." type="text" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" />
            </div>

            <div class="focus-card editor-body">
              <div class="toolbar">
                <div class="toolbar-actions">
                  <button type="button" class="icon-btn hover-bg" onclick="formatText('bold')">
                    <span class="material-symbols-outlined">format_bold</span>
                  </button>
                  <button type="button" class="icon-btn hover-bg" onclick="formatText('italic')">
                    <span class="material-symbols-outlined">format_italic</span>
                  </button>
                  <div class="divider-v-small"></div>
                  <button type="button" class="icon-btn hover-bg" onclick="formatText('list')">
                    <span class="material-symbols-outlined">format_list_bulleted</span>
                  </button>
                  <button type="button" class="icon-btn hover-bg" onclick="formatText('link')">
                    <span class="material-symbols-outlined">link</span>
                  </button>
                </div>
                <div class="draft-status">
                  <span class="dot bg-teal"></span>
                  Drafting
                </div>
              </div>

              <div class="editor-textarea-wrapper">
                <textarea class="editor-textarea custom-scrollbar" id="newsContent" name="content" placeholder="Start your story here..." rows="15"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
              </div>

              <div class="editor-footer">
                <div class="stats">
                  <span class="stat-item" id="readTime">
                    <span class="material-symbols-outlined icon-small">timer</span>
                    0 min read
                  </span>
                  <span class="stat-item" id="charCount">
                    <span class="material-symbols-outlined icon-small">text_fields</span>
                    0 characters
                  </span>
                </div>
                <span class="save-status" id="saveStatus">Draft in progress</span>
              </div>
            </div>
          </div>

          <aside class="editor-sidebar space-y">
            <div class="side-card">
              <div class="card-header mb-md">
                <label class="tracking-label">Cover Media</label>
                <span class="material-symbols-outlined text-muted">image_search</span>
              </div>
              <div class="upload-area group" onclick="document.getElementById('imageUrlInput').click()">
                <div class="upload-icon-box transition-transform">
                  <span class="material-symbols-outlined">cloud_upload</span>
                </div>
                <span class="upload-title">Drop featured image</span>
                <span class="upload-subtitle">Recommended 1200x675px</span>
                <input type="text" id="imageUrlInput" name="featured_image" style="display: none;" placeholder="Image URL" />
              </div>
              <div class="image-preview" id="imagePreview" style="margin-top: 1rem; display: none;">
                <img id="previewImg" src="" alt="Preview" style="width: 100%; border-radius: 0.5rem;" />
              </div>
            </div>

            <div class="side-card">
              <label class="tracking-label mb-md block">Article Metadata</label>
              <div class="form-group space-y">
                <div>
                  <span class="form-label mb-sm block">Target Category</span>
                  <div class="input-wrapper">
                    <select class="form-select custom-scrollbar" name="category">
                      <option value="announcement">Announcement</option>
                      <option value="research">Research</option>
                      <option value="achievement">Achievement</option>
                      <option value="event">Event</option>
                      <option value="general" selected>General</option>
                    </select>
                    <span class="material-symbols-outlined select-icon">unfold_more</span>
                  </div>
                </div>
                <div>
                  <span class="form-label mb-sm block">Keywords</span>
                  <div class="tags-container mb-sm" id="tagsContainer">
                    <span class="tag">
                      Innovation
                      <button type="button" class="tag-close" onclick="removeTag(this)">
                        <span class="material-symbols-outlined icon-xs">close</span>
                      </button>
                    </span>
                    <span class="tag">
                      Robotics
                      <button type="button" class="tag-close" onclick="removeTag(this)">
                        <span class="material-symbols-outlined icon-xs">close</span>
                      </button>
                    </span>
                  </div>
                  <div class="input-wrapper">
                    <input class="form-input" id="tagInput" placeholder="Add tag..." type="text" />
                    <button class="input-action-btn text-teal" type="button" onclick="addTag()">
                      <span class="material-symbols-outlined icon-md">add_circle</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="review-card group">
              <div class="glow-effect transition-bg"></div>
              <div class="review-content">
                <h4 class="review-title">
                  <span class="material-symbols-outlined icon-teal">task_alt</span>
                  Ready to go?
                </h4>
                <p class="review-desc">
                  Preview your content to see how it looks for the MJIIT community.
                </p>
                <button class="btn btn-outline-white full-width transition-colors" onclick="previewNews()">
                  <span class="material-symbols-outlined icon-left">visibility</span>
                  Live Preview
                </button>
              </div>
            </div>
          </aside>
        </div>

        <footer class="app-footer">
          <div class="footer-left">
            <div class="footer-item">
              <span class="material-symbols-outlined icon-sm">verified_user</span>
              <span>End-to-end Encrypted</span>
            </div>
            <div class="footer-item">
              <span class="material-symbols-outlined icon-sm">history</span>
              <span>Version History</span>
            </div>
          </div>
          <div class="footer-right">
            <button class="footer-btn hover-red text-muted" onclick="discardDraft()">Discard Draft</button>
            <div class="dot bg-slate-200"></div>
            <button class="footer-btn hover-dark text-slate-400">Editor Preferences</button>
          </div>
        </footer>
      </div>
    </main>

    <!-- Preview Modal -->
    <div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
      <div style="background: white; border-radius: 1rem; padding: 2rem; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <h3>Preview</h3>
          <button onclick="closePreview()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="previewContent"></div>
      </div>
    </div>

    <script>
      // Auto-save draft every 30 seconds
      let autoSaveInterval;
      
      document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('newsTitle');
        const contentTextarea = document.getElementById('newsContent');
        
        // Update character count and read time
        function updateStats() {
          const content = contentTextarea.value;
          const charCount = content.length;
          const wordCount = content.trim().split(/\s+/).length;
          const readTime = Math.max(1, Math.ceil(wordCount / 200));
          
          document.getElementById('charCount').innerHTML = `<span class="material-symbols-outlined icon-small">text_fields</span> ${charCount} characters`;
          document.getElementById('readTime').innerHTML = `<span class="material-symbols-outlined icon-small">timer</span> ${readTime} min read`;
        }
        
        contentTextarea.addEventListener('input', updateStats);
        titleInput.addEventListener('input', function() {
          document.getElementById('saveStatus').innerHTML = 'Unsaved changes';
          document.getElementById('saveStatus').style.color = '#f59e0b';
        });
        contentTextarea.addEventListener('input', function() {
          document.getElementById('saveStatus').innerHTML = 'Unsaved changes';
          document.getElementById('saveStatus').style.color = '#f59e0b';
          updateStats();
        });
        
        updateStats();
        
        // Auto-save draft
        autoSaveInterval = setInterval(function() {
          const title = titleInput.value;
          const content = contentTextarea.value;
          if (title || content) {
            // Auto-save via AJAX would go here
            document.getElementById('saveStatus').innerHTML = 'Auto-saving...';
            setTimeout(() => {
              document.getElementById('saveStatus').innerHTML = 'Draft saved';
              document.getElementById('saveStatus').style.color = 'var(--teal-600)';
            }, 500);
          }
        }, 30000);
      });
      
      function formatText(type) {
        const textarea = document.getElementById('newsContent');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        
        let formattedText = '';
        if (type === 'bold') {
          formattedText = `**${selectedText}**`;
        } else if (type === 'italic') {
          formattedText = `*${selectedText}*`;
        } else if (type === 'list') {
          formattedText = `\n- ${selectedText}`;
        } else if (type === 'link') {
          const url = prompt('Enter URL:', 'https://');
          if (url) {
            formattedText = `[${selectedText || 'link'}](${url})`;
          }
        }
        
        if (formattedText) {
          textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
          textarea.focus();
        }
      }
      
      function addTag() {
        const input = document.getElementById('tagInput');
        const tag = input.value.trim();
        if (tag) {
          const container = document.getElementById('tagsContainer');
          const tagSpan = document.createElement('span');
          tagSpan.className = 'tag';
          tagSpan.innerHTML = `${tag} <button type="button" class="tag-close" onclick="removeTag(this)"><span class="material-symbols-outlined icon-xs">close</span></button>`;
          container.appendChild(tagSpan);
          input.value = '';
        }
      }
      
      function removeTag(btn) {
        btn.closest('.tag').remove();
      }
      
      function previewNews() {
        const title = document.getElementById('newsTitle').value || 'Untitled';
        const content = document.getElementById('newsContent').value || 'No content';
        const previewDiv = document.getElementById('previewContent');
        
        previewDiv.innerHTML = `
          <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem;">${escapeHtml(title)}</h1>
          <div style="line-height: 1.8; font-size: 1rem;">${escapeHtml(content).replace(/\n/g, '<br>')}</div>
        `;
        
        document.getElementById('previewModal').style.display = 'flex';
      }
      
      function closePreview() {
        document.getElementById('previewModal').style.display = 'none';
      }
      
      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }
      
      function discardDraft() {
        if (confirm('Discard current draft? All unsaved changes will be lost.')) {
          document.getElementById('newsTitle').value = '';
          document.getElementById('newsContent').value = '';
          document.getElementById('saveStatus').innerHTML = 'Draft discarded';
        }
      }
      
      // Image URL preview
      document.getElementById('imageUrlInput').addEventListener('change', function() {
        const url = this.value;
        if (url) {
          document.getElementById('previewImg').src = url;
          document.getElementById('imagePreview').style.display = 'block';
        }
      });
    </script>
</body>
</html>