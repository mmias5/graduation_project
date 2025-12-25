<?php
require_once '../config.php';//database connection
require_once 'admin_auth.php';//3ashan yetakad eno admin

$currentPage = basename($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {//bas y3mal form submit befoot el condition
    $title    = trim($_POST['title'] ?? '');//trim to remove spaces
    $category = trim($_POST['category'] ?? 'News');
    $body     = trim($_POST['body'] ?? '');
    $date     = $_POST['date'] ?? ''; // YYYY-MM-DD
    $adminId  = $_SESSION['admin_id'] ?? null;

    if ($title === '' || $body === '' || !$adminId) {
        $_SESSION['flash_error'] = 'Title and body are required.';
        header('Location: addnews.php');
        exit;
    }

    if ($date) {//etha el date mawjood bel form est5dm el date ely akhadtoh etha la hot el current date w time
        $createdAt = $date . ' 00:00:00';
    } else {
        $createdAt = date('Y-m-d H:i:s');
    }
    $updatedAt = $createdAt;

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/news/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmp  = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $newName  = 'news_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target   = $uploadDir . $newName;

        if (move_uploaded_file($fileTmp, $target)) {
            $imagePath = '../uploads/news/' . $newName;
        }
    }
    $sql = "INSERT INTO news (title, body, category, image, created_at, updated_at, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);//prepare statement to prevent SQL injection
    $stmt->bind_param("ssssssi", $title, $body, $category, $imagePath, $createdAt, $updatedAt, $adminId);//ssssssi 6 strings w 1 integer

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = 'News added successfully.';
        header('Location: news.php');
        exit;
    } else {
        $_SESSION['flash_error'] = 'Error while saving news: ' . $stmt->error;
        header('Location: addnews.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Add News</title>

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
.flash.success{
  background:#dcfce7;
  color:#166534;
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="admin-main">

  <h1 class="page-title">Add News Article</h1>
  <p class="page-sub">Create a new news post that will appear on the student news page.</p>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash error">
      <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form action="addnews.php" method="post" enctype="multipart/form-data">

      <label class="form-label">Title</label>
      <input type="text" name="title" class="input-field" required>

      <!-- CATEGORY customized dropdown list(java script) -->
      <label class="form-label">Category</label>

      <input type="hidden" name="category" id="categoryValue" value="News">

      <div class="custom-select" id="categorySelect">
        <div class="custom-select-trigger">
          <span id="categoryLabel">News</span>
          <div class="custom-select-arrow"></div>
        </div>
        <div class="custom-options">
          <div class="custom-option selected" data-value="News">
            <span>News</span>
            <span class="custom-option-pill">Default</span>
          </div>
          <div class="custom-option" data-value="Announcement">
            <span>Announcement</span>
            <span class="custom-option-pill">Announcement</span>
          </div>
          <div class="custom-option" data-value="Update">
            <span>Update</span>
            <span class="custom-option-pill">Update</span>
          </div>
        </div>
      </div>

      <label class="form-label">Date</label>
      <input type="date" name="date" class="input-field">

      <label class="form-label">Header Image</label>
      <input type="file" name="image" class="input-field" accept="uploads/news/*" required>

      <label class="form-label">Body Content</label>
      <textarea name="body" class="textarea-field" required></textarea>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Publish News</button>
        <a href="news.php" class="btn btn-ghost">Cancel</a>
      </div>

    </form>
  </div>

</main>

<script>
(function(){
  const selectEl   = document.getElementById('categorySelect');
  const trigger    = selectEl.querySelector('.custom-select-trigger');
  const labelSpan  = document.getElementById('categoryLabel');
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
