<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

/* =========================================================
   ✅ UniHive Image Helpers (works across admin/student/sponsor)
   DB stores: uploads/...
   Project URL base: /graduation_project
   ========================================================= */
define('APP_BASE', '/graduation_project'); // مهم

function clean_upload_rel(?string $rel): string {
    $p = trim((string)$rel);
    if ($p === '') return '';
    $p = str_replace('\\', '/', $p);
    $p = preg_replace('~^\./+~', '', $p);
    if (strpos($p, '..') !== false) return '';
    if (stripos($p, 'uploads/') !== 0) return '';
    return $p;
}

function upload_url(?string $rel): string {
    $p = clean_upload_rel($rel);
    if ($p === '') return '';
    return rtrim(APP_BASE, '/') . '/' . $p;
}

function upload_exists(?string $rel): bool {
    $p = clean_upload_rel($rel);
    if ($p === '') return false;

    $abs = rtrim($_SERVER['DOCUMENT_ROOT'], '/')
         . rtrim(APP_BASE, '/')
         . '/'
         . $p;

    return is_file($abs);
}

function svg_placeholder_datauri(string $label = 'UH'): string {
    $label = htmlspecialchars(mb_strtoupper(mb_substr(trim($label), 0, 2)), ENT_QUOTES, 'UTF-8');

    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 64 64'>
      <defs>
        <linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>
          <stop offset='0' stop-color='#eef2f7'/>
          <stop offset='1' stop-color='#dbe6ff'/>
        </linearGradient>
      </defs>
      <rect x='0' y='0' width='64' height='64' rx='14' fill='url(#g)'/>
      <text x='32' y='39' text-anchor='middle' font-family='Arial' font-size='18' font-weight='800' fill='#242751'>{$label}</text>
    </svg>";

    return "data:image/svg+xml;charset=UTF-8," . rawurlencode($svg);
}

function img_src_or_placeholder(?string $rel, string $fallbackLabel = 'UH'): string {
    $p = clean_upload_rel($rel);
    if ($p === '' || !upload_exists($p)) {
        return svg_placeholder_datauri($fallbackLabel);
    }
    // لاحظ: هون برجع URL (مش encoded) لأننا بنستخدمه داخل HTML
    return upload_url($p);
}

/* =========================
   Helper: initials for sponsor pill
========================= */
function makeInitials(string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '' || strtolower($name) === 'no sponsor yet') return 'SP';
    $parts = explode(' ', $name);
    $first = mb_substr($parts[0], 0, 1, 'UTF-8');
    $second = '';
    if (count($parts) > 1) {
        $second = mb_substr($parts[1], 0, 1, 'UTF-8');
    } else {
        $second = mb_substr($parts[0], 1, 1, 'UTF-8');
    }
    $ini = mb_strtoupper($first . $second, 'UTF-8');
    return $ini !== '' ? $ini : 'SP';
}

// ======= Get club id from URL =======
$clubId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($clubId <= 0) {
    header('Location: discoverclubs.php');
    exit;
}

// ======= Fetch club from DB (WITH sponsor + total_events + hero_image) =======
$sql = "
    SELECT 
        c.club_id,
        c.club_name,
        c.category,
        c.description,
        c.logo,
        c.cover,
        c.member_count,
        c.points,
        c.contact_email,
        c.instagram_url,
        c.facebook_url,
        c.linkedin_url,
        c.sponsor_id,
        sp.company_name AS club_sponsor_name,
        COALESCE(e.total_events, 0) AS total_events
    FROM club c
    LEFT JOIN sponsor sp
        ON sp.sponsor_id = c.sponsor_id
    LEFT JOIN (
        SELECT club_id, COUNT(*) AS total_events
        FROM event
        GROUP BY club_id
    ) e ON e.club_id = c.club_id
    WHERE c.club_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('SQL error (prepare clubpage): ' . $conn->error);
}
$stmt->bind_param('i', $clubId);
$stmt->execute();
$result = $stmt->get_result();
$club = $result->fetch_assoc();
$stmt->close();

if (!$club) {
    die('Club not found.');
}

// ======= Map values with fallbacks =======
$name          = $club['club_name']       ?? 'Club';
$category      = $club['category']        ?? 'General';
$description   = $club['description']     ?? 'No description available yet for this club.';
$logo          = $club['logo']            ?? '';
$heroImage     = $club['cover']      ?? '';

$memberCount   = isset($club['member_count']) ? (int)$club['member_count'] : 0;
$points        = isset($club['points'])        ? (int)$club['points']        : 0;
$totalEvents   = isset($club['total_events'])  ? (int)$club['total_events']  : 0;

$university    = 'University of Jordan'; // placeholder
$contactEmail  = $club['contact_email']   ?? '';

$instagramUrl  = $club['instagram_url']   ?? '';
$facebookUrl   = $club['facebook_url']    ?? '';
$linkedinUrl   = $club['linkedin_url']    ?? '';

// ✅ Sponsor for THIS CLUB from DB
$clubSponsorName = $club['club_sponsor_name'] ?? '';
if (trim($clubSponsorName) === '') {
    $clubSponsorName = 'No sponsor yet';
}
$sponsorInitials = makeInitials($clubSponsorName);

// ✅ placeholder initials للّوغو
$clubInitials = mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'));

// ✅ HERO background src (URL or placeholder data uri)
$heroBgSrc = img_src_or_placeholder($heroImage, $clubInitials);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Clubs Hub — <?php echo htmlspecialchars($name); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy: #242751;
  --royal: #4871db;
  --light: #a9bff8;
  --paper: #eef2f7;
  --ink: #0e1228;
  --gold: #e5b758;
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
  background:var(--paper);
  line-height:1.5;
}

/* ========= Container helpers ========= */
.section{padding:24px 20px}
.wrap{max-width:1100px; margin:0 auto}

/* ========= HERO ========= */
.hero{ padding-top:36px; padding-bottom:28px; }
.hero-card{
  position:relative; overflow:hidden; border-radius:28px;
  box-shadow:var(--shadow);
  min-height:320px;
  display:flex; align-items:flex-end;
  background:none;
}

/* ✅ IMPORTANT: background from DB via CSS variable */
.hero-card::before{
  content:"";
  position:absolute; inset:0;
  background-image: var(--hero-bg, url("tools/pics/social_life.png"));
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  filter: grayscale(.12) contrast(1.03);
  opacity: .95;
}

.hero-card::after{
  content:"";
  position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(36,39,81,.15) 0%, rgba(36,39,81,.35) 60%, rgba(36,39,81,.55) 100%);
  pointer-events:none;
}
.hero-top{
  position:absolute; left:24px; right:24px; top:20px;
  display:flex; justify-content:space-between; align-items:center;
  color:#fff; text-shadow:0 8px 26px rgba(0,0,0,.35);
}
.tag{
  background:var(--gold);
  color:#2b2f55;
  font-weight:800;
  padding:8px 14px;
  border-radius:999px;
  font-size:12px;
}
.hero-pillrow{
  position:relative;
  width:100%;
  padding:18px;
  display:flex;
  gap:18px;
  flex-wrap:wrap;
}
.pill{
  flex:1 1 260px;
  display:flex;
  align-items:center;
  gap:14px;
  backdrop-filter: blur(6px);
  background:rgba(255,255,255,.82);
  border:1px solid rgba(255,255,255,.7);
  border-radius:20px;
  padding:12px 14px;
  color:#1d244d;
}
.circle{
  width:42px;height:42px;border-radius:50%;
  background:radial-gradient(circle at 30% 30%, #fff, #b9ccff);
  display:flex;align-items:center;justify-content:center;
  font-weight:900;color:#1d244d;
  border:2px solid rgba(255,255,255,.8);
}

/* ========= Headings ========= */
.h-title{
  font-size:34px;
  letter-spacing:.35em;
  text-transform:uppercase;
  margin:34px 0 12px;
  text-align:left;
  color:var(--navy);
}
.hr{
  height:3px;
  width:280px;
  background:var(--gold);
  opacity:.9;
  border-radius:3px;
  margin:10px 0 24px;
}

/* ========= ABOUT CARD ========= */
.about{
  color: var(--navy);
  background: linear-gradient(#e5b758);
  margin-top:18px;
  border-radius:26px;
  padding:26px;
  box-shadow:var(--shadow);
}
.about p{
  max-width:800px;
  font-size:18px;
  margin:0 0 18px;
}

.contact-strip{
  background:#ffffff;
  border-radius:18px;
  padding:14px 18px;
  display:flex;
  align-items:center;
  gap:12px;
  margin:8px 0 24px;
  color:var(--navy);
  box-shadow:0 10px 24px rgba(15,23,42,0.16);
}
.contact-strip svg{flex:0 0 22px}
.contact-strip a{
  color:var(--navy);
  text-decoration:none;
  border-bottom:1px dashed rgba(36,39,81,.4);
}
.contact-strip a:hover{border-bottom-style:solid}

.link-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:20px;
  max-width:720px;
  margin-top:10px;
}
.link-tile{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 14px;
  border-radius:14px;
  background:#fff;
  color:var(--navy);
  border:1px solid #e6e8f2;
  text-decoration:none;
}
.link-tile svg{flex:0 0 22px}
.links{font-weight:700;color:var(--navy)}
.link-tile:hover{
  background: var(--gold);
  transform: translateY(-10px);
  border-color:var(--royal);
}

/* ========= STATS ========= */
.stats{
  margin-top:18px;
  margin-bottom:60px;
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:18px;
}
.stat{
  background:#fff;
  border-radius:18px;
  padding:18px;
  border:1px solid #e6e8f2;
  text-align:center;
  box-shadow:0 10px 24px rgba(10,23,60,.06);
}
.stat h5{
  margin:0 0 6px;
  letter-spacing:.25em;
  text-transform:uppercase;
  color: var(--navy);
  font-size:13px
}
.kpi{
  background:var(--gold);
  border:2px solid #e5c94a;
  border-radius:14px;
  display:inline-block;
  padding:10px 18px;
  font-weight:900;
  font-size:22px;
  letter-spacing:.2em;
  margin-top:6px;
}

/* ========= PAST EVENTS BUTTON ========= */
.past-events-container{
  margin:0 0 6px;
  text-align:center;
  font-size:14px;
  color:var(--navy);
  font-weight:600;
}
.past-events-btn{
  display:block;
  width:100%;
  max-width:360px;
  margin:10px auto 12px;
  padding:14px 24px;
  border-radius:999px;
  border:2px solid var(--gold);
  background:var(--navy);
  color:var(--gold);
  font-size:15px;
  font-weight:800;
  text-decoration:none;
  text-align:center;
  box-shadow:0 12px 28px rgba(10,23,60,.35);
  cursor:pointer;
}
.past-events-btn:hover{
  transform:translateY(-2px);
  box-shadow:0 16px 34px rgba(10,23,60,.45);
}

@media (max-width:900px){
  .link-grid{grid-template-columns:1fr 1fr}
  .stats{grid-template-columns:1fr}
}
@media (max-width:520px){
  .link-grid{grid-template-columns:1fr}
  .pill{flex:1 1 100%}
}
</style>
</head>
<body>

<?php include 'header.php'; ?>
<div class="underbar"></div>

<!-- ========== HERO ========== -->
<section class="section hero">
  <div class="wrap">

    <!-- ✅ هنا الربط: الHero background من DB -->
    <div class="hero-card" style="--hero-bg: url('<?php echo htmlspecialchars($heroBgSrc, ENT_QUOTES, 'UTF-8'); ?>');">
      <div class="hero-top">
        <div class="tag">
          <?php echo htmlspecialchars($university); ?>
        </div>
      </div>

      <div class="hero-pillrow">

        <!-- Club pill -->
        <div class="pill">
          <img
            src="<?php echo htmlspecialchars(img_src_or_placeholder($logo, $clubInitials), ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?php echo htmlspecialchars($name); ?> logo"
            style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.8)"
          />
          <div>
            <div style="font-size:12px;opacity:.8">club name</div>
            <strong id="clubs"><?php echo htmlspecialchars($name); ?></strong>
          </div>
        </div>

        <!-- Sponsor pill -->
        <div class="pill">
          <div class="circle"><?php echo htmlspecialchars($sponsorInitials); ?></div>
          <div>
            <div style="font-size:12px;opacity:.8">sponsor name</div>
            <strong><?php echo htmlspecialchars($clubSponsorName); ?></strong>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ========== ABOUT ========== -->
<section class="section">
  <div class="wrap">
    <h3 class="h-title" id="about">About Club</h3>
    <div class="hr"></div>

    <div class="about">
      <p><?php echo nl2br(htmlspecialchars($description)); ?></p>

      <div class="contact-strip">
        <svg viewBox='0 0 24 24' width='22' height='22' fill='none' stroke='#242751' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
          <path d='M4 4h16v16H4z' stroke='none'/>
          <path d='M4 4l8 8l8-8'/>
        </svg>
        <div>
          <div style="font-size:12px;opacity:.9;font-weight:600;text-transform:uppercase;">
            Sponsorship & Partnerships
          </div>
          <div style="font-size:13px; margin-top:4px;">
            For any <strong>sponsorship, collaboration, or partnership</strong> with this club, you can contact the club president at:
          </div>
          <strong>
            <?php if (!empty($contactEmail)): ?>
              <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>">
                <?php echo htmlspecialchars($contactEmail); ?>
              </a>
            <?php else: ?>
              <span>No contact email provided yet</span>
            <?php endif; ?>
          </strong>
        </div>
      </div>

      <h4 style="letter-spacing:.4em; text-transform:uppercase; margin:8px 0 8px; color: var(--navy)">
        Links
      </h4>

      <div class="link-grid">
        <?php if (!empty($linkedinUrl)): ?>
          <a class="link-tile" href="<?php echo htmlspecialchars($linkedinUrl); ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="#0a66c2" aria-hidden="true">
              <path d="M20.447 20.452h-3.555V14.86c0-1.333-.027-3.045-1.856-3.045-1.858 0-2.142 1.45-2.142 2.95v5.688H9.338V9h3.414v1.561h.048c.476-.9 1.637-1.85 3.369-1.85 3.602 0 4.268 2.371 4.268 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM6.99 20.452H3.68V9h3.31v11.452z"/>
            </svg>
            <span class="links">LinkedIn</span>
          </a>
        <?php endif; ?>

        <?php if (!empty($instagramUrl)): ?>
          <a class="link-tile" href="<?php echo htmlspecialchars($instagramUrl); ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" style="color:#E4405F">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor" stroke-width="2"/>
              <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
            </svg>
            <span class="links">Instagram</span>
          </a>
        <?php endif; ?>

        <?php if (!empty($facebookUrl)): ?>
          <a class="link-tile" href="<?php echo htmlspecialchars($facebookUrl); ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="#1877f2" aria-hidden="true">
              <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.08 5.66 21.2 10.44 22v-7.02H7.9v-2.92h2.54v-2.2c0-2.5 1.5-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.92h-2.34V22C18.34 21.2 22 17.08 22 12.06z"/>
            </svg>
            <span class="links">Facebook</span>
          </a>
        <?php endif; ?>

        <?php if (empty($linkedinUrl) && empty($instagramUrl) && empty($facebookUrl)): ?>
          <p style="font-size:13px; color:var(--navy); margin:4px 0 0;">
            This club has not added any social links yet.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="past-events-container">Want to see what this club has already done?</div>
    <a href="pastevents.php?club_id=<?php echo $clubId; ?>" class="past-events-btn">📅 View Past Events</a>

    <div class="stats">
      <div class="stat">
        <h5>Events done</h5>
        <div class="kpi"><?php echo $totalEvents; ?></div>
      </div>
      <div class="stat">
        <h5>Member</h5>
        <div class="kpi"><?php echo $memberCount; ?></div>
      </div>
      <div class="stat">
        <h5>Earned points</h5>
        <div class="kpi"><?php echo $points; ?></div>
      </div>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>
