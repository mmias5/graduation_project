<?php
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Add News</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   UNI HIVE ADMIN BRAND SYSTEM
======================================== */
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

/* MAIN LAYOUT */
.admin-main{
  margin-left:var(--sidebarWidth);
  padding:30px 34px 40px;
}
@media(max-width:900px){
  .admin-main{margin-left:0;padding:24px 20px}
}

/* PAGE TITLE */
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

/* CARD */
.card{
  background:var(--white);
  padding:26px 26px 30px;
  border-radius:var(--radius-card);
  box-shadow:var(--shadow-card);
  max-width:900px;
}

/* INPUT LABEL */
.form-label{
  font-size:.88rem;
  font-weight:600;
  margin-bottom:6px;
  display:block;
  color:var(--navy);
}

/* INPUTS */
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

/* BUTTONS */
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
  background:#e44c4c;
  transform:translateY(-1px);
}

.btn-ghost{
  background:transparent;
  border:1px solid rgba(148,163,184,.55);
  color:var(--navy);
}
.btn-ghost:hover{
  background:#e5e7eb;
}

/* BUTTON ROW */
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

  <h1 class="page-title">Add News Article</h1>
  <p class="page-sub">Create a new news post that will appear on the student news page.</p>

  <div class="card">
    <form action="addnews_process.php" method="post" enctype="multipart/form-data">

      <label class="form-label">Title</label>
      <input type="text" name="title" class="input-field" required>

      <label class="form-label">Category</label>
      <select name="category" class="select-field" required>
        <option value="News">News</option>
        <option value="Announcement">Announcement</option>
        <option value="Update">Update</option>
      </select>

      <label class="form-label">Date</label>
      <input type="date" name="date" class="input-field" required>

      <label class="form-label">Header Image</label>
      <input type="file" name="image" class="input-field" accept="image/*" required>

      <label class="form-label">Body Content</label>
      <textarea name="body" class="textarea-field" required></textarea>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Publish News</button>
        <a href="viewnews.php" class="btn btn-ghost">Cancel</a>
      </div>

    </form>
  </div>

</main>

</body>
</html>
