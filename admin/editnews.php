<?php
  $currentPage = basename($_SERVER['PHP_SELF']);

  // Replace with DB fetch
  $newsId = $_GET['id'] ?? 1;
  $news = [
    "title"    => "Campus Clubs Hub expands cross-university activities with new analytics tools",
    "category" => "News",
    "date"     => "2025-11-01",
    "body"     => "This is the body text of the news article...",
    "image"    => ""
  ];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Edit News</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* EXACT SAME CSS AS ADDNEWS PAGE */
/* ======================================== */
:root{
  --sidebarWidth:240px;
  --navy: #242751;
  --royal: #4871db;
  --coral: #ff5e5e;
  --gold: #e5b758;
  --paper: #eef2f7;
  --white: #ffffff;
  --ink: #0e1228;
  --muted: #6b7280;
  --radius-card:22px;
  --shadow-card:0 18px 38px rgba(12,22,60,.14);
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  min-height:100vh;
  background:var(--paper);
  font-family:"Raleway";
  color:var(--ink);
}

.admin-main{
  margin-left:var(--sidebarWidth);
  padding:30px 34px;
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
  padding:26px;
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
.textarea-field,
.select-field{
  width:100%;
  border:none;
  outline:none;
  background:#ffffff;
  padding:12px 16px;
  font-size:.92rem;
  border-radius:16px;
  box-shadow:0 0 0 1px rgba(0,0,0,0.08);
  margin-bottom:20px;
}

.textarea-field{
  height:160px;
  resize:vertical;
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
}
.btn-primary{
  background:var(--coral);
  color:#fff;
}
.btn-primary:hover{
  background:white;
  color:var(--coral);
}
.btn-ghost{
  background:transparent;
  border:1px solid rgba(148,163,184,.55);
  color:var(--navy);
}
.btn-ghost:hover{
  background:#e5e7eb;
}

.actions{
  margin-top:24px;
  display:flex;
  gap:14px;
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="admin-main">

  <h1 class="page-title">Edit News Article</h1>
  <p class="page-sub">Modify the content of the existing news item.</p>

  <div class="card">
    <form action="editnews_process.php" method="post" enctype="multipart/form-data">

      <input type="hidden" name="id" value="<?php echo $newsId; ?>">

      <label class="form-label">Title</label>
      <input type="text" name="title" class="input-field"
             value="<?php echo htmlspecialchars($news['title']); ?>" required>

      <label class="form-label">Category</label>
      <select name="category" class="select-field" required>
        <option value="News"        <?php if($news['category']=="News") echo "selected"; ?>>News</option>
        <option value="Announcement"<?php if($news['category']=="Announcement") echo "selected"; ?>>Announcement</option>
        <option value="Update"      <?php if($news['category']=="Update") echo "selected"; ?>>Update</option>
      </select>

      <label class="form-label">Date</label>
      <input type="date" name="date" class="input-field"
             value="<?php echo htmlspecialchars($news['date']); ?>" required>

      <label class="form-label">Header Image (optional)</label>
      <input type="file" name="image" class="input-field" accept="image/*">

      <label class="form-label">Body Content</label>
      <textarea name="body" class="textarea-field" required><?php
        echo htmlspecialchars($news['body']);
      ?></textarea>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a href="viewnews.php" class="btn btn-ghost">Cancel</a>
      </div>

    </form>
  </div>

</main>

</body>
</html>