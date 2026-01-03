<?php
session_start();

if (
    !isset($_SESSION['student_id']) ||
    ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'club_president')
) {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

/* fix image path for student folder */
function img_path_student(string $path): string {
    $path = trim($path);
    if ($path === '') return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path; // absolute url
    if ($path[0] === '/') return $path;                    // already absolute from root
    return '../' . ltrim($path, '/');                      // relative -> go up from /student/
}

/**
 * Load a single news row.
 */
function loadNews(mysqli $conn, ?int $newsId): ?array {
    // If a specific ID is given
    if ($newsId && $newsId > 0) {
        $stmt = $conn->prepare("
            SELECT *
            FROM news
            WHERE news_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $newsId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    // Fallback: latest news
    $sql = "SELECT * FROM news ORDER BY created_at DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return null;
}

/* ---------- Read id from URL  ---------- */
$newsIdParam = null;
if (isset($_GET['news_id'])) {
    $newsIdParam = (int)$_GET['news_id'];
} elseif (isset($_GET['id'])) {
    $newsIdParam = (int)$_GET['id'];
}

$news = loadNews($conn, $newsIdParam);

/* ---------- Map data safely ---------- */
$hasNews   = (bool)$news;
$title     = $hasNews ? ($news['title'] ?? 'News article') : 'News article not found';
$category  = $hasNews ? ($news['category'] ?? 'News')       : 'News';

/* which column holds the long text. */
$content = '';
if ($hasNews) {
    if (isset($news['content'])) {
        $content = $news['content'];
    } elseif (isset($news['body'])) {
        $content = $news['body'];
    } elseif (isset($news['description'])) {
        $content = $news['description'];
    } else {
        $content = '';
    }
}

/* hero image from DB with correct path */
$fallbackHero = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=1600&auto=format&fit=crop';

$heroImageRaw = '';
if ($hasNews) {
    // prefer hero_image, but if your DB uses a different column name, add it here
    if (!empty($news['hero_image'])) $heroImageRaw = $news['hero_image'];
    elseif (!empty($news['image']))  $heroImageRaw = $news['image'];
    elseif (!empty($news['photo']))  $heroImageRaw = $news['photo'];
}

$heroImage = $heroImageRaw !== '' ? img_path_student($heroImageRaw) : $fallbackHero;

$createdAt = '';
if ($hasNews && !empty($news['created_at'])) {
    try {
        $dt = new DateTime($news['created_at']);
        $createdAt = $dt->format('M Y');
    } catch (Exception $e) {
        $createdAt = $news['created_at'];
    }
} else {
    $createdAt = '—';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — News Article</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751;
    --royal:#4871db;
    --lightBlue:#a9bff8;
    --gold:#e5b758;
    --paper:#eef2f7;
    --ink:#0e1228;
    --card:#ffffff;
    --shadow:0 18px 38px rgba(12,22,60,.16);
    --radius:22px;
    --maxw:1100px;
  }

  *{box-sizing:border-box}
  html,body{margin:0;padding:0}

  body{
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:linear-gradient(180deg,#f5f7fb,#eef2f7);
    color:var(--ink);
  }

  .wrap{
    max-width:var(--maxw);
    margin:40px auto 0px;
    padding:0 20px;
  }
  .content{
    max-width:var(--maxw);
    margin:40px auto 60px;
    padding:0 20px;
  }

  .headline{
    margin:10px 0 16px;
    font-weight:800;
    line-height:1.1;
    font-size:clamp(32px, 4.7vw, 52px);
    color:var(--navy);
  }

  .meta{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:22px;
    color:#666c85;
    font-weight:700;
    flex-wrap:wrap;
  }

  .badge{
    background:var(--royal);
    color:#fff;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
  }

  .dot{
    width:6px;height:6px;border-radius:50%;background:#c5c9d7;
  }

  .hero{
    position:relative;
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:var(--shadow);
    background:#d0d8ff;
    aspect-ratio: 16 / 9;
    margin-bottom:6px;
  }
  .hero img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }
  .credit{
    position:absolute;right:12px;bottom:10px;
    background:rgba(0,0,0,.55);
    color:#fff;
    font-size:12px;
    padding:6px 10px;
    border-radius:999px;
  }

  article{
    margin-top:28px;
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:30px;
  }
  article p{
    margin:0 0 18px;
    line-height:1.75;
    font-size:18px;
  }
  article p.lead{
    font-size:19px;
    font-weight:600;
  }

  .not-found{
    margin:40px 0 0;
    font-size:17px;
    color:#4b5168;
  }

  footer{ margin-top:0 !important; }
</style>
</head>

<body>

<?php include('header.php'); ?>

<main class="wrap">

  <h1 class="headline">
    <?php echo htmlspecialchars($title); ?>
  </h1>

  <div class="meta">
    <span class="badge"><?php echo htmlspecialchars($category); ?></span>
    <span class="dot"></span>
    <span>UNIHIVE • <?php echo htmlspecialchars($createdAt); ?></span>
  </div>

  <?php if ($hasNews): ?>
    <figure class="hero">
      <img src="<?php echo htmlspecialchars($heroImage); ?>"
           alt="<?php echo htmlspecialchars($title); ?>"
           onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackHero); ?>';">
      <figcaption class="credit">Photo: CCH Media</figcaption>
    </figure>

    <article class="content">
      <?php
        echo nl2br(htmlspecialchars($content));
      ?>
    </article>
  <?php else: ?>
    <article class="content">
      <p class="not-found">
        We couldn’t find this news article. It might have been removed or the link is incorrect.
      </p>
    </article>
  <?php endif; ?>

</main>

<?php include('footer.php'); ?>

</body>
</html>
