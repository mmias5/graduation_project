<?php
  // create_club_request.php
  // session_start();
  // $studentId = $_SESSION['student_id'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Create Club</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ====== COLORS ====== */
:root{
  --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8;
  --gold:#e5b758; --paper:#eef2f7; --ink:#0e1228; --card:#ffffff;
  --shadow:0 14px 34px rgba(10,23,60,.12); --radius:22px;
}

*{box-sizing:border-box;}
html,body{margin:0;padding:0;}
body{
  font-family:"Raleway", sans-serif;
  background:linear-gradient(180deg,#f9fbff 0%, #eef2f7 100%);
  color:var(--ink);
}

/* ====== CARD ====== */
.wrapper{
  max-width:970px;
  margin:32px auto 90px;
  padding:0 16px;
}
.card{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:34px;
  border:1px solid #e1e6f0;
}

/* ====== TITLE ====== */
.title-box{
  margin-bottom:26px;
}
.title{
  font-size:30px;
  font-weight:800;
  margin-bottom:4px;
  letter-spacing:0.3px;
}
.subtitle{
  opacity:.8;
  font-size:15px;
}

/* ====== GRID ====== */
.row{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
  margin-bottom:20px;
}
.row.full{grid-template-columns:1fr;}
label{
  font-weight:700;
  font-size:14px;
  margin-bottom:6px;
  display:block;
}

/* ====== INPUTS ====== */
input[type="text"],
input[type="email"],
input[type="url"],
textarea{
  width:100%;
  border:1px solid #d5dbea;
  border-radius:18px;
  padding:14px 16px;
  font-size:15px;
  outline:none;
  background:#fff;
  transition:0.2s;
}
textarea{min-height:135px; resize:vertical;}
input:focus,
textarea:focus{
  border-color:var(--royal);
  box-shadow:0 0 0 4px rgba(72,113,219,.15);
}

/* ====== CUSTOM DROPDOWN ====== */
.select {
  position: relative;
  font-size: 15px;
}
.select-toggle{
  width:100%;
  display:flex; align-items:center; justify-content:space-between;
  background:#fff;
  border:1px solid #d5dbea;
  border-radius:18px;
  padding:14px 16px;
  font-weight:700;
  color:var(--ink);
  cursor:pointer;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
  transition:.2s;
}
.select-toggle:focus,
.select.open .select-toggle{
  outline:none;
  border-color:var(--royal);
  box-shadow:0 0 0 4px rgba(72,113,219,.15);
}
.select .chev{
  flex:0 0 20px;
  width:20px; height:20px;
  transition:transform .18s ease;
  color:var(--royal);
}
.select.open .chev{ transform:rotate(180deg); }

.select-menu{
  position:absolute; left:0; right:0; top:calc(100% + 8px);
  background:#fff; border:1px solid #dee6f5; border-radius:16px;
  box-shadow:0 16px 34px rgba(10,23,60,.16);
  padding:6px;
  list-style:none; margin:0;
  display:none; max-height:260px; overflow:auto;
  z-index:30;
}
.select.open .select-menu{ display:block; }

.select-menu li{
  padding:12px 12px;
  border-radius:12px;
  cursor:pointer;
  font-weight:600;
}
.select-menu li:hover,
.select-menu li[aria-selected="true"]{
  background:linear-gradient(180deg,#f5f8ff,#eef3ff);
  color:#1a2a5a;
}

/* error hint */
.select.error .select-toggle{
  border-color:#ff5e5e;
  box-shadow:0 0 0 4px rgba(255,94,94,.15);
}
.small-hint{
  font-size:12px; color:#ff5e5e; margin-top:6px; display:none;
}
.select.error + .small-hint{ display:block; }

/* ====== FILE UPLOAD ====== */
.file-upload{
  border:1px dashed #cad2e3;
  border-radius:18px;
  padding:16px;
  display:flex;
  align-items:center;
  gap:14px;
}
.file-upload svg{opacity:.55}

/* ====== SOCIAL INPUT ====== */
.social-box label{font-weight:800;}

.social-wrap{
  position:relative;
  background:#fff;
  border:1px solid #d8dfec;
  border-radius:22px;
  height:54px;
  display:flex;
  align-items:center;
  padding-left:62px;
  box-shadow:0 1px 3px rgba(0,0,0,.05);
  transition:.25s;
}

.social-wrap:focus-within{
  border-color:var(--royal);
  box-shadow:0 0 0 5px rgba(72,113,219,.12);
}

.social-icon{
  width:28px;
  height:28px;
  position:absolute;
  left:18px;
  top:50%;
  transform:translateY(-50%);
  object-fit:contain;
}

.social-wrap input{
  width:100%;
  border:none;
  outline:none;
  background:transparent;
  font-size:15px;
  color:#4a4a4a;
}
.social-wrap input::placeholder{
  color:#9aa5b6;
}

/* ====== BUTTONS ====== */
.actions{
  margin-top:28px;
  display:flex;
  justify-content:flex-end;
  gap:12px;
}
.btn{
  border:none;
  cursor:pointer;
  font-weight:800;
  padding:14px 26px;
  font-size:15px;
  border-radius:22px;
}
.btn.secondary{
  background:#fff;
  border:1px solid #dce3f4;
  color:var(--royal);
}
.btn.primary{
  background:linear-gradient(135deg,#5d7ff2,#3664e9);
  color:#fff;
  box-shadow:0 8px 20px rgba(54,100,233,.22);
}
.btn.primary:hover{
  background:linear-gradient(135deg,#4d70ee,#2958e0);
}
</style>
</head>
<body>

<?php include('header.php'); ?>

<div class="wrapper">
  <div class="card">

    <div class="title-box">
      <div class="title">Create a Club</div>
      <div class="subtitle">Fill out the details below and submit your request for review.</div>
    </div>

    <form action="" method="post" enctype="multipart/form-data">

      <input type="hidden" name="president_id" value="<?php /* echo $studentId */ ?>">

      <!-- Club Name + Category -->
      <div class="row">
        <div>
          <label>Club Name</label>
          <input type="text" name="club_name" placeholder="e.g., Debate Club" required>
        </div>

        <div>
          <label>Category</label>
          <!-- Custom dropdown -->
          <div class="select" id="categorySelect" data-name="category">
            <button type="button" class="select-toggle" aria-haspopup="listbox" aria-expanded="false">
              <span class="select-value">Select category</span>
              <svg class="chev" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M7 10l5 5 5-5"></path>
              </svg>
            </button>
            <ul class="select-menu" role="listbox">
              <li role="option" data-value="Technology">Technology</li>
              <li role="option" data-value="Sports">Sports</li>
              <li role="option" data-value="Arts">Arts</li>
              <li role="option" data-value="Community">Community</li>
              <li role="option" data-value="Entrepreneurship">Entrepreneurship</li>
            </ul>
            <input type="hidden" name="category" value="">
          </div>
          <div class="small-hint" id="categoryHint">Please choose a category.</div>
        </div>
      </div>

      <!-- Description -->
      <div class="row full">
        <div>
          <label>Description</label>
          <textarea name="description" placeholder="Describe the purpose, activities, and goals of your club..." required></textarea>
        </div>
      </div>

      <!-- Email + Logo -->
      <div class="row">
        <div>
          <label>Contact Email</label>
          <input type="email" name="contact_email" placeholder="name@university.edu" required>
        </div>

        <div>
          <label>Logo (Optional)</label>
          <div class="file-upload">
            <svg width="22" height="22" fill="#777"><path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14h18ZM5 5h14v9l-4-4-5 6-2-2-3 3V5Z"/></svg>
            <input type="file" name="logo" accept="image/*">
          </div>
        </div>
      </div>

      <!-- SOCIALS -->
      <div class="row">
        <div class="social-box">
          <label>LinkedIn (optional)</label>
          <div class="social-wrap">
            <img class="social-icon" src="https://cdn-icons-png.flaticon.com/512/174/174857.png">
            <input type="url" name="linkedin_url" placeholder="https://www.linkedin.com/company/yourclub">
          </div>
        </div>

        <div class="social-box">
          <label>Facebook (optional)</label>
          <div class="social-wrap">
            <img class="social-icon" src="https://cdn-icons-png.flaticon.com/512/733/733547.png">
            <input type="url" name="facebook_url" placeholder="https://www.facebook.com/yourclub">
          </div>
        </div>
      </div>

      <div class="row full">
        <div class="social-box">
          <label>Instagram (optional)</label>
          <div class="social-wrap">
            <img class="social-icon" src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png">
            <input type="url" name="instagram_url" placeholder="https://www.instagram.com/yourclub">
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="actions">
        <button type="reset" class="btn secondary">Reset</button>
        <button type="submit" class="btn primary">Submit Request</button>
      </div>
    </form>

  </div>
</div>

<?php include('footer.php'); ?>

<!-- JS for custom dropdown -->
<script>
(function(){
  const sel     = document.getElementById('categorySelect');
  const toggle  = sel.querySelector('.select-toggle');
  const valueEl = sel.querySelector('.select-value');
  const menu    = sel.querySelector('.select-menu');
  const opts    = Array.from(menu.querySelectorAll('li'));
  const hidden  = sel.querySelector('input[type="hidden"]');
  const hint    = document.getElementById('categoryHint');

  function open(){ sel.classList.add('open'); toggle.setAttribute('aria-expanded','true'); }
  function close(){ sel.classList.remove('open'); toggle.setAttribute('aria-expanded','false'); }
  function set(val, text){
    hidden.value = val;
    valueEl.textContent = text;
    opts.forEach(li => li.removeAttribute('aria-selected'));
    const chosen = opts.find(li => li.dataset.value === val);
    if (chosen) chosen.setAttribute('aria-selected','true');
    sel.classList.remove('error');
    if (hint) hint.style.display = 'none';
  }

  toggle.addEventListener('click', (e)=>{
    e.stopPropagation();
    sel.classList.contains('open') ? close() : open();
  });

  opts.forEach(li=>{
    li.addEventListener('click', (e)=>{
      set(li.dataset.value, li.textContent.trim());
      close();
    });
  });

  document.addEventListener('click', (e)=>{
    if (!sel.contains(e.target)) close();
  });

  // validate on submit
  const form = sel.closest('form');
  form.addEventListener('submit', (e)=>{
    if (!hidden.value){
      e.preventDefault();
      sel.classList.add('error');
      if (hint) hint.style.display = 'block';
      open();
    }
  });
})();
</script>

</body>
</html>
