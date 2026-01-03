<?php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($newsId <= 0) {
    header('Location: news.php');
    exit;
}

// first get existing news data
$sql = "SELECT * FROM news WHERE news_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $newsId);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

if (!$news) {
    $_SESSION['flash_error'] = 'News item not found.';
    header('Location: news.php');
    exit;
}

// update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'News');
    $body     = trim($_POST['body'] ?? '');
    $date     = $_POST['date'] ?? '';

    if ($title === '' || $body === '') {
        $_SESSION['flash_error'] = 'Title and body are required.';
        header('Location: editnews.php?id=' . $newsId);
        exit;
    }

    // determine created_at
    if ($date) {
        $createdAt = $date . ' 00:00:00';
    } else {
        $createdAt = $news['created_at']; // if empty use existing
    }

    $updatedAt = date('Y-m-d H:i:s');

    // current image path
    $imagePath = $news['image'];

    // if new image uploaded
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/news/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmp  = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $newName  = 'news_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target   = $uploadDir . $newName;

        if (move_uploaded_file($fileTmp, $target)) {
            // delete old image file
            if (!empty($imagePath) && file_exists('../' . $imagePath)) {
                @unlink('../' . $imagePath);
            }
            $imagePath = 'assets/news/' . $newName;
        }
    }

    $updateSql = "UPDATE news 
                  SET title = ?, body = ?, category = ?, image = ?, created_at = ?, updated_at = ?
                  WHERE news_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ssssssi", $title, $body, $category, $imagePath, $createdAt, $updatedAt, $newsId);

    if ($updateStmt->execute()) {
        $_SESSION['flash_success'] = 'News updated successfully.';
        header('Location: news.php');
        exit;
    } else {
        $_SESSION['flash_error'] = 'Error updating news: ' . $updateStmt->error;
        header('Location: editnews.php?id=' . $newsId);
        exit;
    }
}

$createdDateValue = $news['created_at'] ? substr($news['created_at'], 0, 10) : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Edit News</title>
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
  --radius-card:22px;
  --shadow-card:0 18px 38px rgba(12,22,60,.14);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;
  background:var(--paper);
  font-family:"Raleway",system-ui,sans-serif;
  color:var(--ink);
}
.admin-main{
  margin-left:var(--sidebarWidth);
  padding:30px 34px 40px;
}
@media(max-width:900px){
  .admin-main{margin-left:0;padding:24px 20px}
}
.page-title{
  font-size:1.7rem;
  font-weight:800;
  color:var(--navy);
  margin-bottom:12px;
}
.page-sub{
  font-size:.95rem;
  color:var(--muted);
  margin-bottom:26px;
}
.card{
  background:var(--white);
  padding:26px 26px 30px;
  border-radius:var(--radius-card);
  box-shadow:var(--shadow-card);
  max-width:100%;
}
.form-label{
  font-size:.88rem;
  font-weight:600;
  margin-bottom:6px;
  display:block;
  color:var(--navy);
}
.input-field,
.textarea-field{
  width:100%;
  border:none;
  outline:none;
  background:#ffffff;
  padding:12px 16px;
  font-size:.92rem;
  border-radius:16px;
  box-shadow:0 0 0 1px rgba(0,0,0,0.08);
  margin-bottom:20px;
  font-family:"Raleway",system-ui,sans-serif;
}
.textarea-field{
  height:160px;
  resize:vertical;
}
.input-field:focus,
.textarea-field:focus{
  box-shadow:0 0 0 2px rgba(72,113,219,.25);
}
.btn{
  border:none;
  outline:none;
  cursor:pointer;
  padding:11px 22px;
  border-radius:999px;
  font-size:.92rem;
  font-weight:600;
  transition:.15s;
  font-family:"Raleway",system-ui,sans-serif;
}
.btn-primary{
  background:var(--coral);
  color:#fff;
}
.btn-primary:hover{
  background:#e44c4c;
  transform:translateY(-1px);
}
.btn-ghost{
  background:transparent;
  border:1px solid rgba(148,163,184,.55);
  color:var(--navy);
  display:inline-flex;
  align-items:center;
  justify-content:center;
  text-decoration:none;
}
.btn-ghost:hover{
  background:#e5e7eb;
}
.actions{
  margin-top:24px;
  display:flex;
  gap:14px;
}
.flash{
  margin-bottom:16px;
  padding:10px 14px;
  border-radius:12px;
  font-size:.9rem;
}
.flash.error{
  background:#fee2e2;
  color:#991b1b;
}
.current-image{
  margin-bottom:16px;
}
.current-image img{
  max-width:260px;
  border-radius:14px;
  display:block;
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="admin-main">
  <h1 class="page-title">Edit News Article</h1>
  <p class="page-sub">Update the content of this news item.</p>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash error">
      <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form action="editnews.php?id=<?php echo $newsId; ?>" method="post" enctype="multipart/form-data">

      <label class="form-label">Title</label>
      <input type="text" name="title" class="input-field"
             value="<?php echo htmlspecialchars($news['title']); ?>" required>

      <label class="form-label">Category</label>
      <select name="category" class="input-field" style="padding-right:36px;">
        <option value="News" <?php echo $news['category']=='News'?'selected':''; ?>>News</option>
        <option value="Announcement" <?php echo $news['category']=='Announcement'?'selected':''; ?>>Announcement</option>
        <option value="Update" <?php echo $news['category']=='Update'?'selected':''; ?>>Update</option>
      </select>

      <label class="form-label">Date</label>
      <input type="date" name="date" class="input-field"
             value="<?php echo htmlspecialchars($createdDateValue); ?>" required>

      <?php if (!empty($news['image'])): ?>
        <div class="current-image">
          <label class="form-label">Current Header Image</label>
          <img src="../<?php echo htmlspecialchars($news['image']); ?>" alt="Current image">
        </div>
      <?php endif; ?>

      <label class="form-label">Change Header Image (optional)</label>
      <input type="file" name="image" class="input-field" accept="image/*">

      <label class="form-label">Body Content</label>
      <textarea name="body" class="textarea-field" required><?php
        echo htmlspecialchars($news['body']);
      ?></textarea>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a href="news.php" class="btn btn-ghost">Cancel</a>
      </div>

    </form>
  </div>
</main>

</body>
</html>
