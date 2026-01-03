<?php
session_start();

if (!isset($_SESSION['student_id']) || ($_SESSION['role'] ?? '') !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$presidentId = (int)$_SESSION['student_id'];

/* =========================
   Helpers
========================= */
function esc($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
 * Make a usable URL for stored paths (NO file_exists, NO changing DB value)
 */
function media_url(?string $path): string {
    $p = trim((string)$path);
    if ($p === '') return '';
    if (preg_match('~^https?://~i', $p)) return $p;
    if (strpos($p, '/') === 0) return $p;
    return '../' . ltrim($p, '/');
}

/* =========================
   Get president club_id
========================= */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$myClubId = (int)($stmt->get_result()->fetch_assoc()['club_id'] ?? 0);
$stmt->close();

// ✅ FIX: This is "not assigned", NOT "submitted successfully"
if ($myClubId <= 1) {
    echo "<script>alert('You are not assigned to any club yet.'); location.href='index.php';</script>";
    exit;
}

/* =========================
   Fetch club (ONLY his club)
========================= */
$stmt = $conn->prepare("SELECT * FROM club WHERE club_id=? LIMIT 1");
$stmt->bind_param("i", $myClubId);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$club_id        = (int)$myClubId;
$club_name      = $club['club_name'] ?? '';
$category       = $club['category'] ?? '';
$contact_email  = $club['contact_email'] ?? '';
$description    = $club['description'] ?? '';
$logo_db        = $club['logo'] ?? '';
$instagram      = $club['instagram_url'] ?? '';
$facebook       = $club['facebook_url'] ?? '';
$linkedin       = $club['linkedin_url'] ?? '';

// keep DB value as-is if exists 
$cover_db = trim((string)(
    $club['cover_image']   ??   // لو عندك cover_image
    $club['cover']         ??   // أو cover
    $club['banner_image']  ??   // أو banner_image
    $club['banner']        ??   // أو banner
    ''                     // إذا ما في عمود
));
// placeholder if db is empty
$cover_url = ($cover_db !== '')
    ? media_url($cover_db)
    : media_url('tools/pics/social_life.png');

// placeholder if db is empty
$logo_url = (trim((string)$logo_db) !== '')
    ? media_url($logo_db)
    : media_url('tools/pics/social_life.png');

$sponsor_name = '—';
$sponsor_logo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Amazon_logo.svg/1200px-Amazon_logo.svg.png';

/* =========================
   Pending request check
========================= */
$pendingMsg = '';
try {
    $stmt = $conn->prepare("SELECT request_id FROM club_edit_request WHERE club_id=? AND reviewed_at IS NULL ORDER BY submitted_at DESC LIMIT 1");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) $pendingMsg = "You already have a pending edit request (Request #".$r['request_id']."). Wait for admin review.";
} catch (Throwable $e) {
    // لو عمود status مش موجود، ما نوقف الصفحة
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

.section{padding:15px 20px}
.wrap{max-width:1100px; margin:0 auto}

.hero{ padding:0 0 28px 0; }
.hero-card{ position:relative; overflow:hidden; border-radius:28px; box-shadow:var(--shadow); min-height:320px; display:flex; align-items:flex-end; background:none; }
.hero-card::before{
  content:""; position:absolute; inset:0;
  background-image: var(--hero-bg, url("../tools/pics/social_life.png"));
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

.h-title{ font-size:34px; letter-spacing:.35em; text-transform:uppercase; margin:34px 0 12px; text-align:left; color:#2b2f55; }
.hr{ height:3px; width:280px; background:#2b2f55; opacity:.35; border-radius:3px; margin:10px 0 24px; }

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

.actions{ display:flex; justify-content:flex-end; gap:12px; margin-top:18px; flex-wrap:wrap }
.btn{ appearance:none; border:0; border-radius:12px; padding:12px 16px; font-weight:800; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
.btn.primary{ background:var(--royal); color:#fff; box-shadow:var(--shadow); }
.btn.ghost{ background:#eef2ff; color:#1f2a6b; }

.uploader{ display:flex; gap:14px; align-items:center; flex-wrap:wrap; border:1px dashed #d1d5db; border-radius:14px; padding:12px; }
.thumb{ width:84px; height:84px; border-radius:12px; background:#f2f5ff; overflow:hidden; display:grid; place-items:center; border:1px solid #e5e7eb; }
.thumb.wide{ width:144px; height:84px; }
.thumb img{ width:100%; height:100%; object-fit:cover; }
.uploader input[type=file]{ display:none; }
.pick{ display:inline-block; padding:10px 12px; border-radius:10px; background:#f3f4ff; color:#1f2a6b; font-weight:800; cursor:pointer; }

.notice{
  background:#fff7d6;border:1px solid #f1dc92;color:#604d11;
  padding:12px 14px;border-radius:14px;margin:10px 0 18px;font-weight:700;
}
</style>
</head>

<body>
  <?php include 'header.php'; ?>
  <div class="underbar"></div>

  <section class="section hero">
    <div class="wrap">

      <!-- cover stays DB value as-is , fallback only if DB empty -->
      <div class="hero-card" style="--hero-bg: url('<?php echo esc($cover_url); ?>');">
        <div class="hero-top">
          <h1>EDIT CLUB</h1>
          <div class="tag">President Console</div>
        </div>

        <div class="hero-pillrow">
          <div class="pill">
            <img src="<?php echo esc($logo_url); ?>" alt="Club Logo"
              style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.8)" />
            <div>
              <div style="font-size:12px;opacity:.8">club name</div>
              <strong id="clubs"><?php echo esc($club_name); ?></strong>
            </div>
          </div>

          <div class="pill">
            <div class="circle">SP</div>
            <div>
              <div style="font-size:12px;opacity:.8">sponsor name</div>
              <strong id="sponsorNameHero"><?php echo esc($sponsor_name); ?></strong>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <h3 class="h-title">Club Details</h3>
      <div class="hr"></div>

      <?php if ($pendingMsg): ?>
        <div class="notice"><?php echo esc($pendingMsg); ?></div>
      <?php endif; ?>

      <form class="card" id="editClubForm" action="update_club.php" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="club_id" value="<?php echo (int)$club_id; ?>">

        <div class="form-grid">
          <div class="field">
            <label class="label" for="club_name">Club name</label>
            <input class="input" id="club_name" name="club_name" required maxlength="255"
                   value="<?php echo esc($club_name); ?>" />
            <span class="hint">Your official club display name.</span>
          </div>

          <div class="field">
            <label class="label" for="category">Category</label>
            <input class="input" id="category" name="category" maxlength="80"
                   value="<?php echo esc($category); ?>" />
            <span class="hint">Write a category (or later we can keep your custom dropdown).</span>
          </div>

          <div class="field">
            <label class="label" for="contact_email">Contact email</label>
            <input class="input" id="contact_email" name="contact_email" type="email" required
                   value="<?php echo esc($contact_email); ?>" />
            <span class="hint">For student & sponsor inquiries.</span>
          </div>

          <div class="field">
            <label class="label" for="sponsor_name_display">Sponsor name</label>
            <input class="input" id="sponsor_name_display" value="<?php echo esc($sponsor_name); ?>" disabled>
            <input type="hidden" name="sponsor_name" value="<?php echo esc($sponsor_name); ?>">
            <span class="hint">Assigned by admin.</span>
          </div>

          <div class="field" style="grid-column:1 / -1">
            <label class="label" for="description">About the club</label>
            <textarea class="textarea" id="description" name="description" maxlength="1000" required><?php echo esc($description); ?></textarea>
            <span class="hint">Short and clear.</span>
          </div>
        </div>

        <h3 class="h-title" style="font-size:20px; letter-spacing:.2em; margin-top:24px">Images</h3>
        <div class="hr" style="width:180px"></div>

        <div class="form-grid">
          <div class="field">
            <span class="label">Logo</span>
            <div class="uploader">
              <div class="thumb">
                <img id="logoPreview" src="<?php echo esc($logo_url); ?>" alt="Logo preview">
              </div>
              <div>
                <label class="pick" for="logo">Choose file</label>
                <input id="logo" name="logo" type="file" accept="image/*">
                <div class="hint">PNG/JPG recommended.</div>
              </div>
            </div>
          </div>

          <div class="field">
            <span class="label">Cover</span>
            <div class="uploader">
              <!-- preview shows DB value (even if file missing) -->
              <div class="thumb wide">
                <img id="coverPreview" src="<?php echo esc($cover_url); ?>" alt="Cover preview">
              </div>
              <div>
                <label class="pick" for="cover">Choose file</label>
                <input id="cover" name="cover" type="file" accept="image/*">
                <div class="hint">Preview only unless your update_club.php saves it.</div>
              </div>
            </div>
          </div>

          <div class="field">
            <span class="label">Sponsor logo</span>
            <div class="uploader">
              <div class="thumb">
                <img id="sponsorLogoPreview" src="<?php echo esc($sponsor_logo); ?>" alt="Sponsor logo preview">
              </div>
              <div class="hint">Managed by admin.</div>
            </div>
            <input type="hidden" name="sponsor_logo" value="<?php echo esc($sponsor_logo); ?>">
          </div>
        </div>

        <h3 class="h-title" style="font-size:20px; letter-spacing:.2em; margin-top:24px">Social Links</h3>
        <div class="hr" style="width:210px"></div>

        <div class="form-grid">
          <div class="field">
            <label class="label" for="instagram">Instagram</label>
            <input class="input" id="instagram" name="instagram" type="url"
                   value="<?php echo esc($instagram); ?>">
          </div>
          <div class="field">
            <label class="label" for="facebook">Facebook</label>
            <input class="input" id="facebook" name="facebook" type="url"
                   value="<?php echo esc($facebook); ?>">
          </div>
          <div class="field">
            <label class="label" for="linkedin">LinkedIn</label>
            <input class="input" id="linkedin" name="linkedin" type="url"
                   value="<?php echo esc($linkedin); ?>">
          </div>
        </div>

        <div class="actions">
          <a class="btn ghost" href="clubpage.php">Cancel</a>
          <button class="btn primary" type="submit" id="saveBtn">Request change</button>
        </div>
      </form>
    </div>
  </section>

  <?php include 'footer.php'; ?>

<script>
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
    if (this.files && this.files[0]) {
      document.querySelector('.hero-card')?.style.setProperty('--hero-bg', `url('${URL.createObjectURL(this.files[0])}')`);
    }
  });
</script>
</body>
</html>
