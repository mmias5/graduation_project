<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Edit Club (Admin)</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --navy: #242751;
      --coral: #ff5c5c;

      --pageBg: #e9ecf1;
      --mainBg: #f5f6fb;

      --card: #ffffff;
      --ink: #0e1228;
      --muted: #6b7280;

      --radius: 18px;
      --radiusLg: 24px;
      --shadowSoft: 0 20px 40px rgba(15,23,42,.10);
      --shadowLight: 0 12px 30px rgba(15,23,42,.08);

      --sidebarWidth: 230px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      min-height:100vh;
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:var(--ink);
      background:var(--pageBg);
    }

    /* ========= MAIN LAYOUT ========= */
    .main{
      margin-left:var(--sidebarWidth);
      padding:28px 40px 40px;
      min-height:100vh;
      background:var(--mainBg);
      box-shadow:-18px 0 40px rgba(15,23,42,.06);
    }

    .page-header{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:16px;
      margin-bottom:24px;
    }

    .page-title{
      font-size:1.4rem;
      font-weight:800;
      color:var(--navy);
    }

    .page-sub{
      font-size:.9rem;
      color:var(--muted);
      margin-top:4px;
    }

    .page-tag{
      padding:6px 12px;
      border-radius:999px;
      background:#ffe1e1;
      color:var(--coral);
      font-size:.8rem;
      font-weight:700;
      align-self:flex-start;
    }

    /* ========= CARD CONTAINERS ========= */
    .card{
      background:var(--card);
      border-radius:var(--radiusLg);
      box-shadow:var(--shadowLight);
      border:1px solid rgba(148,163,184,.22);
      padding:22px 22px 24px;
    }

    .card + .card{
      margin-top:24px;
    }

    .card-title-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:18px;
    }

    .card-title{
      font-size:1.02rem;
      font-weight:800;
      color:var(--navy);
    }

    .card-hint{
      font-size:.85rem;
      color:var(--muted);
    }

    /* ========= FORM LAYOUT ========= */
    .form-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:18px 20px;
    }

    @media (max-width:900px){
      .main{
        margin-left:0;
        padding:22px 18px 28px;
        box-shadow:none;
      }
      .form-grid{
        grid-template-columns:1fr;
      }
      .page-header{
        flex-direction:column;
        align-items:flex-start;
      }
    }

    .field{
      display:flex;
      flex-direction:column;
      gap:6px;
    }

    .label{
      font-weight:800;
      font-size:.9rem;
      color:var(--navy);
    }

    .hint{
      font-size:.8rem;
      color:var(--muted);
    }

    .input,
    .textarea{
      width:100%;
      border-radius:12px;
      border:1px solid #e5e7eb;
      padding:11px 13px;
      font-size:.9rem;
      background:#fff;
      outline:none;
      transition:border-color .12s ease, box-shadow .12s ease, background .12s;
    }

    .textarea{
      min-height:120px;
      resize:vertical;
    }

    .input:focus,
    .textarea:focus{
      border-color:var(--coral);
      box-shadow:0 0 0 3px rgba(255,92,92,.15);
      background:#fff;
    }

    .input[disabled]{
      background:#f3f4f6;
      color:#9ca3af;
      cursor:not-allowed;
    }

    /* ========= MINI CLUB SUMMARY ROW ========= */
    .club-summary{
      display:flex;
      align-items:center;
      gap:14px;
      padding:14px 16px;
      border-radius:14px;
      background:#f9fafb;
      border:1px solid rgba(148,163,184,.25);
      margin-bottom:18px;
    }

    .club-avatar-lg{
      width:54px;
      height:54px;
      border-radius:50%;
      overflow:hidden;
      border:2px solid rgba(255,255,255,.9);
      box-shadow:0 8px 20px rgba(15,23,42,.18);
      background:#e5e7eb;
      flex-shrink:0;
    }

    .club-avatar-lg img{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .club-summary-main{
      display:flex;
      flex-direction:column;
      gap:2px;
    }

    .club-summary-name{
      font-weight:800;
      font-size:1rem;
      color:var(--navy);
    }

    .club-summary-meta{
      font-size:.8rem;
      color:var(--muted);
    }

    .club-summary-meta strong{
      color:var(--coral);
    }

    /* ========= UPLOADERS ========= */
    .uploader{
      display:flex;
      gap:14px;
      align-items:center;
      flex-wrap:wrap;
      border:1px dashed #d1d5db;
      border-radius:14px;
      padding:12px;
      background:#f9fafb;
    }

    .thumb{
      width:84px;
      height:84px;
      border-radius:12px;
      background:#f2f5ff;
      overflow:hidden;
      display:grid;
      place-items:center;
      border:1px solid #e5e7eb;
    }

    .thumb.wide{
      width:150px;
      height:84px;
    }

    .thumb img{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .uploader input[type=file]{
      display:none;
    }

    .pick{
      display:inline-block;
      padding:9px 12px;
      border-radius:10px;
      background:#fef2f2;
      color:var(--coral);
      font-weight:800;
      font-size:.85rem;
      cursor:pointer;
      border:1px solid rgba(248,113,113,.4);
    }

    /* ========= CUSTOM SELECT (CATEGORY) ========= */
    .cch-select{
      position:relative;
      font-size:.9rem;
    }

    .cch-select__btn{
      width:100%;
      display:flex;
      align-items:center;
      justify-content:space-between;
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:12px;
      padding:11px 13px;
      font-weight:700;
      color:var(--ink);
      cursor:pointer;
      transition:border-color .12s, box-shadow .12s, background .12s;
    }

    .cch-select__btn:focus,
    .cch-select.open .cch-select__btn{
      outline:none;
      border-color:var(--coral);
      box-shadow:0 0 0 3px rgba(255,92,92,.15);
      background:#fff;
    }

    .cch-select__chev{
      width:20px;
      height:20px;
      color:var(--coral);
      transition:transform .18s;
    }

    .cch-select.open .cch-select__chev{
      transform:rotate(180deg);
    }

    .cch-select__menu{
      position:absolute;
      left:0;
      right:0;
      top:calc(100% + 8px);
      background:#fff;
      border:1px solid #dee6f5;
      border-radius:12px;
      box-shadow:0 16px 34px rgba(10,23,60,.16);
      list-style:none;
      margin:0;
      padding:6px;
      max-height:260px;
      overflow:auto;
      display:none;
      z-index:30;
    }

    .cch-select.open .cch-select__menu{
      display:block;
    }

    .cch-select__option{
      padding:10px 12px;
      border-radius:10px;
      cursor:pointer;
      font-weight:600;
      font-size:.9rem;
    }

    .cch-select__option:hover,
    .cch-select__option[aria-selected="true"]{
      background:#fff1f1;
      color:#7f1d1d;
    }

    /* ========= ACTION BUTTONS ========= */
    .actions{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      margin-top:20px;
      flex-wrap:wrap;
    }

    .btn{
      appearance:none;
      border:0;
      border-radius:999px;
      padding:10px 18px;
      font-weight:800;
      font-size:.9rem;
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
    }

    .btn.primary{
      background:var(--coral);
      color:#fff;
      box-shadow:0 14px 30px rgba(248,113,113,.4);
    }

    .btn.ghost{
      background:#e5e7eb;
      color:#111827;
    }

  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

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

  <main class="main">
    <!-- PAGE HEADER -->
    <header class="page-header">
      <div>
        <h1 class="page-title">Edit Club</h1>
        <p class="page-sub">Update club details, images, and social links from the admin panel.</p>
      </div>
      <div class="page-tag">Admin console</div>
    </header>

    <!-- CLUB SUMMARY CARD -->
    <section class="card" style="margin-bottom:24px;">
      <div class="card-title-row">
        <h2 class="card-title">Club overview</h2>
        <p class="card-hint">Quick snapshot of how this club appears across UniHive.</p>
      </div>

      <div class="club-summary">
        <div class="club-avatar-lg">
          <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Club logo">
        </div>
        <div class="club-summary-main">
          <div class="club-summary-name"><?php echo htmlspecialchars($club_name); ?></div>
          <div class="club-summary-meta">
            Category: <strong><?php echo htmlspecialchars($category ?: 'Not set'); ?></strong> ·
            Sponsor: <strong><?php echo htmlspecialchars($sponsor_name); ?></strong>
          </div>
          <div class="club-summary-meta">
            Contact email: <?php echo htmlspecialchars($contact_email); ?>
          </div>
        </div>
      </div>
    </section>

    <!-- MAIN EDIT FORM -->
    <form class="card" id="editClubForm" action="update_club.php" method="POST" enctype="multipart/form-data" novalidate>
      <div class="card-title-row">
        <h2 class="card-title">Club details</h2>
      </div>

      <input type="hidden" name="club_id" value="<?php echo (int)$club_id; ?>">

      <div class="form-grid">
        <!-- Club name -->
        <div class="field">
          <label class="label" for="club_name">Club name</label>
          <input class="input" id="club_name" name="club_name" required maxlength="255"
                 value="<?php echo htmlspecialchars($club_name); ?>" />
          <span class="hint">Official club display name as it appears to students and sponsors.</span>
        </div>

        <!-- Category (custom select) -->
        <div class="field">
          <label class="label">Category</label>
          <div class="cch-select" id="categorySelect">
            <button type="button" class="cch-select__btn" aria-haspopup="listbox" aria-expanded="false">
              <span class="cch-select__value"><?php echo htmlspecialchars($category ?: 'Select category'); ?></span>
              <svg class="cch-select__chev" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M7 10l5 5 5-5"/>
              </svg>
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
          <span class="hint">Choose the best-fit category for this club.</span>
        </div>

        <!-- Contact email -->
        <div class="field">
          <label class="label" for="contact_email">Contact email</label>
          <input class="input" id="contact_email" name="contact_email" type="email" required
                 value="<?php echo htmlspecialchars($contact_email); ?>" />
          <span class="hint">Used for student and sponsor inquiries.</span>
        </div>

        <!-- Sponsor name (read-only here) -->
        <div class="field">
          <label class="label" for="sponsor_name_display">Sponsor name</label>
          <input class="input" id="sponsor_name_display" value="<?php echo htmlspecialchars($sponsor_name); ?>" disabled>
          <input type="hidden" name="sponsor_name" value="<?php echo htmlspecialchars($sponsor_name); ?>">
          <span class="hint">Managed by Super Admin. Sponsor cannot be changed from this page.</span>
        </div>

        <!-- About -->
        <div class="field" style="grid-column:1 / -1">
          <label class="label" for="description">About the club</label>
          <textarea class="textarea" id="description" name="description" maxlength="1000" required><?php echo htmlspecialchars($description); ?></textarea>
          <span class="hint">Short description displayed on the club profile for students and sponsors.</span>
        </div>
      </div>

      <!-- IMAGES CARD -->
      <div class="card-title-row" style="margin-top:26px;">
        <h2 class="card-title">Images</h2>
      </div>

      <div class="form-grid">
        <div class="field">
          <span class="label">Logo</span>
          <div class="uploader">
            <div class="thumb">
              <img id="logoPreview" src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo preview">
            </div>
            <div>
              <label class="pick" for="logo">Choose file</label>
              <input id="logo" name="logo" type="file" accept="image/*">
              <div class="hint">PNG/JPG. Square ~512×512 recommended.</div>
            </div>
          </div>
        </div>

        <div class="field">
          <span class="label">Cover image</span>
          <div class="uploader">
            <div class="thumb wide">
              <img id="coverPreview" src="<?php echo htmlspecialchars($cover_url); ?>" alt="Cover preview">
            </div>
            <div>
              <label class="pick" for="cover">Choose file</label>
              <input id="cover" name="cover" type="file" accept="image/*">
              <div class="hint">Wide ~1200×600 looks best on the club page.</div>
            </div>
          </div>
        </div>

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

      <!-- SOCIAL LINKS CARD -->
      <div class="card-title-row" style="margin-top:26px;">
        <h2 class="card-title">Social links</h2>
      </div>

      <div class="form-grid">
        <div class="field">
          <label class="label" for="instagram">Instagram</label>
          <input class="input" id="instagram" name="instagram" type="url"
                 placeholder="https://www.instagram.com/yourclub"
                 value="<?php echo htmlspecialchars($instagram); ?>">
        </div>

        <div class="field">
          <label class="label" for="facebook">Facebook</label>
          <input class="input" id="facebook" name="facebook" type="url"
                 placeholder="https://www.facebook.com/yourclub"
                 value="<?php echo htmlspecialchars($facebook); ?>">
        </div>

        <div class="field">
          <label class="label" for="linkedin">LinkedIn</label>
          <input class="input" id="linkedin" name="linkedin" type="url"
                 placeholder="https://www.linkedin.com/company/yourclub"
                 value="<?php echo htmlspecialchars($linkedin); ?>">
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="actions">
        <a class="btn ghost" href="club.php?club_id=<?php echo urlencode($club_id); ?>">Cancel</a>
        <button class="btn primary" type="submit" id="saveBtn">Save changes</button>
      </div>
    </form>
  </main>

  <script>
    // ===== Custom dropdown (category) =====
    (function(){
      const sel = document.getElementById('categorySelect');
      if(!sel) return;
      const btn = sel.querySelector('.cch-select__btn');
      const valEl = sel.querySelector('.cch-select__value');
      const menu = sel.querySelector('.cch-select__menu');
      const opts = Array.from(menu.querySelectorAll('.cch-select__option'));
      const hidden = sel.querySelector('input[type="hidden"]');

      function open(){ sel.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
      function close(){ sel.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
      function set(v, t){
        hidden.value = v;
        valEl.textContent = t;
        opts.forEach(o=>o.removeAttribute('aria-selected'));
        const chosen = opts.find(o=>o.dataset.value===v);
        if(chosen) chosen.setAttribute('aria-selected','true');
      }

      btn.addEventListener('click', e=>{
        e.stopPropagation();
        sel.classList.contains('open') ? close() : open();
      });

      opts.forEach(o=>o.addEventListener('click', ()=>{
        set(o.dataset.value, o.textContent.trim());
        close();
      }));

      document.addEventListener('click', e=>{ if(!sel.contains(e.target)) close(); });
    })();

    // ===== Image previews (logo + cover) =====
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
    });
  </script>
</body>
</html>
