<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

  $currentPage = basename($_SERVER['PHP_SELF']);

  // Replace with DB fetch
  $newsId = $_GET['id'] ?? 1;
  $news = [
    "title"    => "Campus Clubs Hub expands cross-university activities with new analytics tools",
    "category" => "News",          // can be News / Announcement / Update
    "date"     => "2025-11-01",
    "body"     => "This is the body text of the news article...",
    "image"    => ""
  ];

  $currentCategory = $news['category'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Edit News</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========================================
   UNI HIVE ADMIN BRAND SYSTEM
======================================== */
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
  font-family:"Raleway",system-ui,sans-serif;
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

/* ========== CUSTOM CATEGORY DROPDOWN ========== */

.custom-select{
  position:relative;
  width:100%;
  margin-bottom:20px;
  font-family:"Raleway",system-ui,sans-serif;
}

.custom-select-trigger{
  width:100%;
  padding:12px 16px;
  border-radius:16px;
  background:#ffffff;
  box-shadow:0 0 0 1px rgba(0,0,0,0.08);
  font-size:.92rem;
  color:var(--ink);
  display:flex;
  align-items:center;
  justify-content:space-between;
  cursor:pointer;
  transition:.15s ease;
}

.custom-select-trigger span{
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.custom-select-arrow{
  border-style:solid;
  border-width:5px 4px 0 4px;
  border-color:var(--muted) transparent transparent transparent;
  margin-left:10px;
  transition:transform .15s ease;
}

.custom-select.open .custom-select-arrow{
  transform:rotate(180deg);
}

.custom-select-trigger:hover{
  box-shadow:0 0 0 1px rgba(72,113,219,.40);
}
.custom-select.open .custom-select-trigger{
  box-shadow:0 0 0 2px rgba(72,113,219,.25);
}

.custom-options{
  position:absolute;
  top:100%;
  left:0;
  right:0;
  margin-top:6px;
  background:#ffffff;
  border-radius:16px;
  box-shadow:0 18px 38px rgba(12,22,60,.16);
  padding:6px 0;
  z-index:10;
  display:none;
}

.custom-select.open .custom-options{
  display:block;
}

.custom-option{
  padding:9px 16px;
  font-size:.9rem;
  color:var(--ink);
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:space-between;
}

.custom-option:hover{
  background:rgba(72,113,219,.06);
}

.custom-option.selected{
  font-weight:600;
  color:var(--coral);
}

.custom-option-pill{
  font-size:.7rem;
  text-transform:uppercase;
  letter-spacing:.06em;
  padding:3px 9px;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.6);
  color:var(--muted);
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
  font-family:"Raleway",system-ui,sans-serif;
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
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
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

      <!-- CATEGORY (custom dropdown) -->
      <label class="form-label">Category</label>

      <!-- hidden input actually submitted to PHP -->
      <input type="hidden" name="category" id="categoryValue"
             value="<?php echo htmlspecialchars($currentCategory); ?>">

      <div class="custom-select" id="categorySelect">
        <div class="custom-select-trigger">
          <span id="categoryLabel">
            <?php echo htmlspecialchars($currentCategory); ?>
          </span>
          <div class="custom-select-arrow"></div>
        </div>
        <div class="custom-options">
          <div class="custom-option <?php echo $currentCategory=='News'?'selected':''; ?>" data-value="News">
            <span>News</span>
            <span class="custom-option-pill">Default</span>
          </div>
          <div class="custom-option <?php echo $currentCategory=='Announcement'?'selected':''; ?>" data-value="Announcement">
            <span>Announcement</span>
            <span class="custom-option-pill">Announcement</span>
          </div>
          <div class="custom-option <?php echo $currentCategory=='Update'?'selected':''; ?>" data-value="Update">
            <span>Update</span>
            <span class="custom-option-pill">Update</span>
          </div>
        </div>
      </div>

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
        <a href="news.php" class="btn btn-ghost">Cancel</a>
      </div>

    </form>
  </div>

</main>

<script>
// ===== Custom Category Dropdown Logic (same as Add News) =====
(function(){
  const selectEl   = document.getElementById('categorySelect');
  const trigger    = selectEl.querySelector('.custom-select-trigger');
  const labelSpan  = document.getElementById('categoryLabel');
  const optionsBox = selectEl.querySelector('.custom-options');
  const options    = selectEl.querySelectorAll('.custom-option');
  const hiddenInput= document.getElementById('categoryValue');

  trigger.addEventListener('click', function(){
    selectEl.classList.toggle('open');
  });

  options.forEach(opt => {
    opt.addEventListener('click', function(){
      const value = this.getAttribute('data-value');
      const text  = this.querySelector('span').innerText;

      labelSpan.textContent = text;
      hiddenInput.value = value;

      options.forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');

      selectEl.classList.remove('open');
    });
  });

  document.addEventListener('click', function(e){
    if(!selectEl.contains(e.target)){
      selectEl.classList.remove('open');
    }
  });
})();
</script>

</body>
</html>
