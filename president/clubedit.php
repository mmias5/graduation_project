<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Clubs Hub — Edit Your Club</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========= Brand Tokens ========= */
:root{
  --navy: #242751;
  --royal: #4871db;
  --light: #a9bff8;
  --paper: #eef2f7;
  --ink: #0e1228;
  --gold: #f4df6d;
  --white: #ffffff;
  --muted: #6b7280;
  --shadow:0 14px 34px rgba(10, 23, 60, .18);
  --radius:18px;
}

*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:"Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
  line-height:1.5;
}

/* ========= Helpers ========= */
.section{padding:15px 20px}
.wrap{max-width:1100px; margin:0 auto}

/* ========= Hero ========= */
.hero{ padding:0 0 28px 0; }
.hero-card{ position:relative; overflow:hidden; border-radius:28px; box-shadow:var(--shadow); min-height:320px; display:flex; align-items:flex-end; background:none; }
.hero-card::before{
  content:""; position:absolute; inset:0;
  background-image: var(--hero-bg, url("https://images.unsplash.com/photo-1531189611190-3c6c6b3c3d57?q=80&w=1650&auto=format&fit=crop"));
  background-size: cover; background-position: center; background-repeat: no-repeat;
  filter: grayscale(.12) contrast(1.03); opacity: .95;
}
.hero-card::after{
  content:""; position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(36,39,81,.15) 0%,
                                      rgba(36,39,81,.35) 60%,
                                      rgba(36,39,81,.55) 100%);
  pointer-events:none;
}
.hero-top{ position:absolute; left:24px; right:24px; top:20px; display:flex; justify-content:space-between; align-items:center; color:#fff; text-shadow:0 8px 26px rgba(0,0,0,.35); }
.hero-top h1{margin:0; letter-spacing:.35em; font-size:32px}
.tag{ background:rgba(244,223,109,.95); color:#2b2f55; font-weight:800; padding:8px 14px; border-radius:999px; font-size:12px; }
.hero-pillrow{ position:relative; width:100%; padding:18px; display:flex; gap:18px; flex-wrap:wrap; }
.pill{ flex:1 1 260px; display:flex; align-items:center; gap:14px; backdrop-filter: blur(6px); background:rgba(255,255,255,.82); border:1px solid rgba(255,255,255,.7); border-radius:20px; padding:12px 14px; color:#1d244d; }
.circle{ width:42px;height:42px;border-radius:50%; background:radial-gradient(circle at 30% 30%, #fff, #b9ccff); display:grid; place-items:center; font-weight:800; font-size:14px; color:#1d244d; border:2px solid rgba(255,255,255,.8); }

/* ========= Headings ========= */
.h-title{ font-size:34px; letter-spacing:.35em; text-transform:uppercase; margin:34px 0 12px; text-align:left; color:#2b2f55; }
.hr{ height:3px; width:280px; background:#2b2f55; opacity:.35; border-radius:3px; margin:10px 0 24px; }

/* ========= Form ========= */
.card{ background:#fff; border-radius:18px; box-shadow:0 14px 34px rgba(10,23,60,.12); padding:20px; border:1px solid #e6e8f2; }
.form-grid{ display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media (max-width:900px){ .form-grid{ grid-template-columns:1fr; } }
.field{ display:flex; flex-direction:column; gap:8px; }
.label{ font-weight:800; color:var(--navy); font-size:14px; }
.hint{ font-size:12px; color:var(--muted); }
.input, .textarea{
  width:100%; border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; font-size:14px; background:#fff; outline:none;
  transition:border-color .12s ease, box-shadow .12s ease;
}
.textarea{ min-height:120px; resize:vertical; }
.input:focus, .textarea:focus{ border-color:var(--royal); box-shadow:0 0 0 3px rgba(72,113,219,.15); }

/* ===== Buttons ===== */
.actions{ display:flex; justify-content:flex-end; gap:12px; margin-top:18px; flex-wrap:wrap }
.btn{ appearance:none; border:0; border-radius:12px; padding:12px 16px; font-weight:800; cursor:pointer; }
.btn.primary{ background:var(--royal); color:#fff; box-shadow:var(--shadow); }
.btn.ghost{ background:#eef2ff; color:#1f2a6b; }

/* ===== Uploaders ===== */
.uploader{ display:flex; gap:14px; align-items:center; flex-wrap:wrap; border:1px dashed #d1d5db; border-radius:14px; padding:12px; }
.thumb{ width:84px; height:84px; border-radius:12px; background:#f2f5ff; overflow:hidden; display:grid; place-items:center; border:1px solid #e5e7eb; }
.thumb.wide{ width:144px; height:84px; }
.thumb img{ width:100%; height:100%; object-fit:cover; }
.uploader input[type=file]{ display:none; }
.pick{ display:inline-block; padding:10px 12px; border-radius:10px; background:#f3f4ff; color:#1f2a6b; font-weight:800; cursor:pointer; }

/* ===== Custom Category Dropdown (same size as inputs) ===== */
.cch-select{position:relative;font-size:14px;}
.cch-select__btn{
  width:100%;display:flex;align-items:center;justify-content:space-between;
  background:#fff;border:1px solid #e5e7eb;border-radius:12px;
  padding:12px 14px;font-weight:700;color:var(--ink);
  cursor:pointer;transition:.12s;
}
.cch-select__btn:focus,
.cch-select.open .cch-select__btn{
  outline:none;border-color:var(--royal);
  box-shadow:0 0 0 3px rgba(72,113,219,.15);
}
.cch-select__chev{width:20px;height:20px;color:var(--royal);transition:transform .18s;}
.cch-select.open .cch-select__chev{transform:rotate(180deg);}
.cch-select__menu{
  position:absolute;left:0;right:0;top:calc(100% + 8px);
  background:#fff;border:1px solid #dee6f5;border-radius:12px;
  box-shadow:0 16px 34px rgba(10,23,60,.16);
  list-style:none;margin:0;padding:6px;
  max-height:260px;overflow:auto;display:none;z-index:30;
}
.cch-select.open .cch-select__menu{display:block;}
.cch-select__option{padding:10px 12px;border-radius:10px;cursor:pointer;font-weight:600;}
.cch-select__option:hover,
.cch-select__option[aria-selected="true"]{
  background:linear-gradient(180deg,#f5f8ff,#eef3ff);color:#1a2a5a;
}

/* Responsive bits preserved */
@media (max-width:900px){
  .nav-links{display:none}
}
</style>
</head>

<body>
  <?php include 'header.php'; ?>
  <div class="underbar"></div>

  <?php
    // ===== Prefill (replace with your real fetch) =====
    $club_id        = isset($club['club_id']) ? (int)$club['club_id'] : 1;
    $club_name      = isset($club['club_name']) ? $club['club_name'] : 'Birds';
    $sponsor_name   = isset($club['sponsor_name']) ? $club['sponsor_name'] : 'Amazone';
    $sponsor_logo   = isset($club['sponsor_logo']) ? $club['sponsor_logo'] : 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Amazon_logo.svg/1200px-Amazon_logo.svg.png';
    $category       = isset($club['category']) ? $club['category'] : 'Technology';
    $contact_email  = isset($club['contact_email']) ? $club['contact_email'] : 'club@example.edu';
    $description    = isset($club['description']) ? $club['description'] : 'Description about the club goes here. It can be two to three sentences long.';
    $logo_url       = isset($club['logo']) ? $club['logo'] : 'https://img.freepik.com/free-vector/bird-colorful-gradient-design-vector_343694-2506.jpg?semt=ais_hybrid&w=740&q=80';
    $cover_url      = isset($club['cover']) ? $club['cover'] : 'tools/pics/social_life.png';
    $instagram      = isset($club['instagram']) ? $club['instagram'] : '';
    $facebook       = isset($club['facebook']) ? $club['facebook'] : '';
    $linkedin       = isset($club['linkedin']) ? $club['linkedin'] : '';
  ?>

  <!-- ========== HERO ========== -->
  <section class="section hero">
    <div class="wrap">
      <div class="hero-card" style="--hero-bg: url('<?php echo htmlspecialchars($cover_url); ?>');">
        <div class="hero-top">
          <h1>EDIT CLUB</h1>
          <div class="tag">President Console</div>
        </div>

        <div class="hero-pillrow">
          <div class="pill">
            <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Club Logo"
              style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.8)" />
            <div>
              <div style="font-size:12px;opacity:.8">club name</div>
              <strong id="clubs"><?php echo htmlspecialchars($club_name); ?></strong>
            </div>
          </div>

          <div class="pill">
            <div class="circle">SP</div>
            <div>
              <div style="font-size:12px;opacity:.8">sponsor name</div>
              <strong id="sponsorNameHero"><?php echo htmlspecialchars($sponsor_name); ?></strong>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ========== EDIT FORM ========== -->
  <section class="section">
    <div class="wrap">
      <h3 class="h-title">Club Details</h3>
      <div class="hr"></div>

      <form class="card" id="editClubForm" action="update_club.php" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="club_id" value="<?php echo (int)$club_id; ?>">

        <div class="form-grid">
          <!-- Club name -->
          <div class="field">
            <label class="label" for="club_name">Club name</label>
            <input class="input" id="club_name" name="club_name" required maxlength="255"
                   value="<?php echo htmlspecialchars($club_name); ?>" />
            <span class="hint">Your official club display name.</span>
          </div>

          <!-- Category (custom) -->
          <div class="field">
            <label class="label">Category</label>
            <div class="cch-select" id="categorySelect">
              <button type="button" class="cch-select__btn" aria-haspopup="listbox" aria-expanded="false">
                <span class="cch-select__value"><?php echo htmlspecialchars($category ?: 'Select category'); ?></span>
                <svg class="cch-select__chev" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 10l5 5 5-5"/></svg>
              </button>
              <ul class="cch-select__menu" role="listbox">
                <?php
                  $cats = ['Technology','Business','Arts','Sports','Culture','Community','Science','Other'];
                  foreach($cats as $c){
                    $sel = ($category === $c) ? 'aria-selected="true"' : '';
                    echo '<li class="cch-select__option" '.$sel.' data-value="'.htmlspecialchars($c).'">'.htmlspecialchars($c).'</li>';
                  }
                ?>
              </ul>
              <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            </div>
            <span class="hint">Choose the best-fit category.</span>
          </div>

          <!-- Contact email -->
          <div class="field">
            <label class="label" for="contact_email">Contact email</label>
            <input class="input" id="contact_email" name="contact_email" type="email" required
                   value="<?php echo htmlspecialchars($contact_email); ?>" />
            <span class="hint">For student & sponsor inquiries.</span>
          </div>

          <!-- Sponsor name (FIXED - Super Admin only) -->
          <div class="field">
            <label class="label" for="sponsor_name_display">Sponsor name</label>
            <input class="input" id="sponsor_name_display" value="<?php echo htmlspecialchars($sponsor_name); ?>" disabled>
            <input type="hidden" name="sponsor_name" value="<?php echo htmlspecialchars($sponsor_name); ?>">
            <span class="hint">Assigned by the Super Admin. You can’t change the sponsor from this page.</span>
          </div>

          <!-- About -->
          <div class="field" style="grid-column:1 / -1">
            <label class="label" for="description">About the club</label>
            <textarea class="textarea" id="description" name="description" maxlength="1000" required><?php echo htmlspecialchars($description); ?></textarea>
            <span class="hint">Short and clear. Appears on the public club page.</span>
          </div>
        </div>

        <!-- Images -->
        <h3 class="h-title" style="font-size:20px; letter-spacing:.2em; margin-top:24px">Images</h3>
        <div class="hr" style="width:180px"></div>
        <div class="form-grid">
          <div class="field">
            <span class="label">Logo</span>
            <div class="uploader">
              <div class="thumb"><img id="logoPreview" src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo preview"></div>
              <div>
                <label class="pick" for="logo">Choose file</label>
                <input id="logo" name="logo" type="file" accept="image/*">
                <div class="hint">PNG/JPG. Square ~512×512 recommended.</div>
              </div>
            </div>
          </div>

          <div class="field">
            <span class="label">Cover</span>
            <div class="uploader">
              <div class="thumb wide"><img id="coverPreview" src="<?php echo htmlspecialchars($cover_url); ?>" alt="Cover preview"></div>
              <div>
                <label class="pick" for="cover">Choose file</label>
                <input id="cover" name="cover" type="file" accept="image/*">
                <div class="hint">Wide ~1200×600 works well.</div>
              </div>
            </div>
          </div>

          <!-- Sponsor logo (FIXED - Super Admin only) -->
          <div class="field">
            <span class="label">Sponsor logo</span>
            <div class="uploader">
              <div class="thumb">
                <img id="sponsorLogoPreview" src="<?php echo htmlspecialchars($sponsor_logo); ?>" alt="Sponsor logo preview">
              </div>
              <div class="hint">
                Sponsor branding is managed by the Super Admin and can’t be changed from here.
              </div>
            </div>
            <input type="hidden" name="sponsor_logo" value="<?php echo htmlspecialchars($sponsor_logo); ?>">
          </div>
        </div>

        <!-- Social links -->
        <h3 class="h-title" style="font-size:20px; letter-spacing:.2em; margin-top:24px">Social Links</h3>
        <div class="hr" style="width:210px"></div>
        <div class="form-grid">
          <div class="field">
            <label class="label" for="instagram">Instagram</label>
            <input class="input" id="instagram" name="instagram" type="url" placeholder="https://www.instagram.com/yourclub"
                   value="<?php echo htmlspecialchars($instagram); ?>">
          </div>
          <div class="field">
            <label class="label" for="facebook">Facebook</label>
            <input class="input" id="facebook" name="facebook" type="url" placeholder="https://www.facebook.com/yourclub"
                   value="<?php echo htmlspecialchars($facebook); ?>">
          </div>
          <div class="field">
            <label class="label" for="linkedin">LinkedIn</label>
            <input class="input" id="linkedin" name="linkedin" type="url" placeholder="https://www.linkedin.com/company/yourclub"
                   value="<?php echo htmlspecialchars($linkedin); ?>">
          </div>
        </div>

        <div class="actions">
          <a class="btn ghost" href="club.php?club_id=<?php echo urlencode($club_id); ?>">Cancel</a>
          <button class="btn primary" type="submit" id="saveBtn">Save changes</button>
        </div>
      </form>
    </div>
  </section>

  <?php include 'footer.php'; ?>

<script>
  // ===== Custom dropdown =====
  (function(){
    const sel = document.getElementById('categorySelect');
    const btn = sel.querySelector('.cch-select__btn');
    const valEl = sel.querySelector('.cch-select__value');
    const menu = sel.querySelector('.cch-select__menu');
    const opts = Array.from(menu.querySelectorAll('.cch-select__option'));
    const hidden = sel.querySelector('input[type="hidden"]');

    function open(){ sel.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
    function close(){ sel.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
    function set(v, t){
      hidden.value = v; valEl.textContent = t;
      opts.forEach(o=>o.removeAttribute('aria-selected'));
      const chosen = opts.find(o=>o.dataset.value===v);
      if(chosen) chosen.setAttribute('aria-selected','true');
    }
    btn.addEventListener('click', e=>{
      e.stopPropagation();
      sel.classList.contains('open') ? close() : open();
    });
    opts.forEach(o=>o.addEventListener('click', ()=>{
      set(o.dataset.value, o.textContent.trim()); close();
    }));
    document.addEventListener('click', e=>{ if(!sel.contains(e.target)) close(); });
  })();

  // ===== Image previews (club logo + cover only) =====
  function previewImage(inputEl, imgEl){
    const f = inputEl.files && inputEl.files[0];
    if(!f) return;
    const reader = new FileReader();
    reader.onload = () => { imgEl.src = reader.result; };
    reader.readAsDataURL(f);
  }
  document.getElementById('logo')?.addEventListener('change', function(){
    previewImage(this, document.getElementById('logoPreview'));
  });
  document.getElementById('cover')?.addEventListener('change', function(){
    previewImage(this, document.getElementById('coverPreview'));
    document.querySelector('.hero-card').style.setProperty('--hero-bg', `url('${URL.createObjectURL(this.files[0])}')`);
  });

  // ===== Live preview for description (optional if you have aboutText somewhere) =====
  const descEl = document.getElementById('description');
  const aboutText = document.getElementById('aboutText');
  if(aboutText && descEl){
    ['input','change'].forEach(ev=>descEl.addEventListener(ev, ()=> aboutText.textContent = descEl.value));
  }
</script>
</body>
</html>
