<?php
// admin/news.php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// جلب الأخبار
$sql = "SELECT news_id, title, body, category, image, created_at, updated_at, admin_id
        FROM news
        ORDER BY created_at DESC";
$result = $conn->query($sql);

$newsItems = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $newsItems[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Manage News</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px;
      --navy:#242751;
      --royal:#4871db;
      --coral:#ff5e5e;
      --gold:#e5b758;
      --paper:#eef2f7;
      --white:#ffffff;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow-card:0 18px 38px rgba(12,22,60,.14);
      --radius-card:22px;
    }

    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
    }

    body{
      min-height:100vh;
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,sans-serif;
      background:var(--paper);
      color:var(--ink);
    }

    a{
      text-decoration:none;
      color:inherit;
    }

    .admin-main{
      margin-left:var(--sidebarWidth);
      min-height:100vh;
      padding:28px 32px 40px;
      background:var(--paper);
    }
    @media(max-width:960px){
      .admin-main{
        margin-left:0;
        padding:20px 16px 28px;
      }
    }

    .page-header{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom:28px;
    }

    .page-title-block h1{
      font-size:1.6rem;
      font-weight:800;
      letter-spacing:.03em;
      color:var(--navy);
      margin-bottom:6px;
    }
    .page-title-block p{
      font-size:.93rem;
      color:var(--muted);
    }

    .page-actions{
      display:flex;
      align-items:center;
      gap:12px;
    }

    .search-wrapper{
      background:var(--white);
      border-radius:999px;
      padding:7px 14px;
      display:flex;
      align-items:center;
      gap:8px;
      box-shadow:var(--shadow-card);
    }
    .search-icon{
      font-size:.95rem;
      color:var(--muted);
    }
    .search-input{
      border:none;
      outline:none;
      background:transparent;
      font-size:.9rem;
      width:210px;
      color:var(--ink);
    }
    .search-input::placeholder{
      color:var(--muted);
    }
    @media(max-width:720px){
      .page-header{
        flex-direction:column;
        align-items:flex-start;
      }
      .page-actions{
        width:100%;
        justify-content:space-between;
      }
      .search-wrapper{
        flex:1;
      }
      .search-input{
        width:100%;
      }
    }

    .btn{
      border:none;
      outline:none;
      cursor:pointer;
      font-family:inherit;
      font-size:.9rem;
      font-weight:600;
      padding:9px 18px;
      border-radius:999px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      transition:background-color .15s ease, border-color .15s ease, color .15s ease, transform .1s ease;
      box-shadow:none;
    }
    .btn-primary{
      background:var(--coral);
      color:#ffffff;
      border:1px solid var(--coral);
    }
    .btn-primary:hover{
      background:#e44c4c;
      border-color:#e44c4c;
      transform:translateY(-1px);
    }

    .btn-ghost{
      background:transparent;
      color:var(--navy);
      border:1px solid rgba(148,163,184,.55);
    }
    .btn-ghost:hover{
      background:#e5e7eb;
      border-color:#cbd5f5;
      transform:translateY(-1px);
    }

    .btn-small{
      padding:7px 13px;
      font-size:.82rem;
    }

    .btn-outline-coral{
      background:transparent;
      color:var(--coral);
      border:1px solid rgba(255,94,94,.75);
    }
    .btn-outline-coral:hover{
      background:rgba(255,94,94,.06);
      border-color:var(--coral);
      transform:translateY(-1px);
    }

    .content-card{
      background:var(--white);
      border-radius:var(--radius-card);
      box-shadow:var(--shadow-card);
      padding:22px 22px 24px;
      margin-bottom:26px;
    }
    .content-card-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:18px;
    }
    .content-card-title{
      font-size:1.02rem;
      font-weight:700;
      color:var(--navy);
    }
    .content-card-subtitle{
      font-size:.86rem;
      color:var(--muted);
      margin-top:3px;
    }
    @media(max-width:720px){
      .content-card{
        padding:18px 16px 20px;
      }
      .content-card-header{
        flex-direction:column;
        align-items:flex-start;
      }
    }

    .news-list{
      display:flex;
      flex-direction:column;
      gap:14px;
    }

    .news-card{
      background:var(--white);
      border-radius:18px;
      box-shadow:0 10px 24px rgba(15,23,42,.10);
      padding:14px 16px 14px;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:14px;
      border:1px solid rgba(226,232,240,.9);
    }
    .news-main{
      display:flex;
      flex-direction:column;
      gap:6px;
      max-width:100%;
    }
    .news-title-row{
      display:flex;
      flex-wrap:wrap;
      align-items:center;
      gap:8px;
    }
    .news-title{
      font-size:1rem;
      font-weight:700;
      color:var(--navy);
    }
    .news-category-pill{
      font-size:.72rem;
      text-transform:uppercase;
      letter-spacing:.06em;
      padding:4px 10px;
      border-radius:999px;
      background:rgba(72,113,219,.07);
      color:var(--royal);
      border:1px solid rgba(72,113,219,.45);
      white-space:nowrap;
    }
    .news-meta{
      font-size:.8rem;
      color:var(--muted);
    }
    .news-meta strong{
      color:var(--ink);
      font-weight:600;
    }

    .news-actions{
      display:flex;
      flex-direction:column;
      align-items:flex-end;
      gap:8px;
      white-space:nowrap;
      padding-top:2px;
    }
    .news-status-pill{
      font-size:.72rem;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.06em;
      padding:4px 10px;
      border-radius:999px;
      border:1px solid transparent;
    }
    .status-published{
      background:rgba(34,197,94,.08);
      color:#15803d;
      border-color:rgba(34,197,94,.55);
    }

    .news-actions-buttons{
      display:flex;
      gap:8px;
    }

    @media(max-width:768px){
      .news-card{
        flex-direction:column;
        align-items:flex-start;
      }
      .news-actions{
        width:100%;
        flex-direction:row;
        align-items:center;
        justify-content:space-between;
      }
      .news-actions-buttons{
        justify-content:flex-end;
        flex:1;
      }
    }
    @media(max-width:480px){
      .news-actions{
        flex-direction:column;
        align-items:flex-start;
      }
      .news-actions-buttons{
        width:100%;
        justify-content:flex-start;
        flex-wrap:wrap;
      }
    }

    .empty-state{
      padding:18px 10px 6px;
      text-align:center;
      font-size:.9rem;
      color:var(--muted);
    }

    .flash{
      margin-bottom:16px;
      padding:10px 14px;
      border-radius:12px;
      font-size:.9rem;
    }
    .flash.success{
      background:#dcfce7;
      color:#166534;
    }
    .flash.error{
      background:#fee2e2;
      color:#991b1b;
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="admin-main">

  <header class="page-header">
    <div class="page-title-block">
      <h1>News Management</h1>
      <p>View, search, and manage all news articles displayed to students.</p>
    </div>

    <div class="page-actions">
      <div class="search-wrapper">
        <span class="search-icon">🔍</span>
        <input
          type="text"
          id="searchInput"
          class="search-input"
          placeholder="Search news by title..."
        />
      </div>

      <a href="addnews.php" class="btn btn-primary">
        + Add News
      </a>
    </div>
  </header>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="flash success">
      <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash error">
      <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
  <?php endif; ?>

  <section class="content-card">
    <div class="content-card-header">
      <div>
        <div class="content-card-title">All News Articles</div>
        <div class="content-card-subtitle">
          <?php echo count($newsItems); ?> total news item(s).
        </div>
      </div>
    </div>

    <div class="news-list">
      <div id="emptyState" class="empty-state" style="display:none;">
        No news found for your search.
      </div>

      <?php if (empty($newsItems)): ?>
        <div class="empty-state">
          No news has been created yet. Click <strong>&ldquo;Add News&rdquo;</strong>
          to publish your first article.
        </div>
      <?php else: ?>
        <?php foreach ($newsItems as $news): ?>
          <?php
            $id       = (int)$news['news_id'];
            $title    = $news['title'];
            $category = $news['category'];
            $date     = $news['created_at'];
          ?>
          <article class="news-card" data-title="<?php echo strtolower($title); ?>">
            <div class="news-main">
              <div class="news-title-row">
                <h2 class="news-title">
                  <?php echo htmlspecialchars($title); ?>
                </h2>
                <span class="news-category-pill">
                  <?php echo htmlspecialchars($category); ?>
                </span>
              </div>

              <div class="news-meta">
                <span><strong>UniHive Admin</strong></span>
                <span> • </span>
                <span><?php echo htmlspecialchars($date); ?></span>
              </div>
            </div>

            <div class="news-actions">
              <span class="news-status-pill status-published">
                Published
              </span>
              <div class="news-actions-buttons">
                <a
                  href="editnews.php?id=<?php echo $id; ?>"
                  class="btn btn-small btn-ghost"
                >
                  Edit
                </a>
                <form
                  action="deletenews.php"
                  method="post"
                  onsubmit="return confirm('Are you sure you want to delete this news item?');"
                >
                  <input type="hidden" name="id" value="<?php echo $id; ?>">
                  <button type="submit" class="btn btn-small btn-outline-coral">
                    Delete
                  </button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</main>

<script>
document.getElementById("searchInput").addEventListener("input", function () {
    const query = this.value.toLowerCase();
    const cards = document.querySelectorAll(".news-card");
    let anyVisible = false;

    cards.forEach(card => {
        const title = card.getAttribute("data-title") || "";
        if (title.includes(query)) {
            card.style.display = "flex";
            anyVisible = true;
        } else {
            card.style.display = "none";
        }
    });

    const emptyState = document.getElementById("emptyState");
    emptyState.style.display = anyVisible ? "none" : "block";
});
</script>

</body>
</html>
