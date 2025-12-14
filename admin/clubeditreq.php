<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ===============================
   Fetch PENDING club edit requests
   =============================== */

$editRequests = [];

$sql = "
    SELECT
        cer.*,
        c.club_name         AS current_club_name,
        c.category          AS current_category,
        c.contact_email     AS current_contact_email,
        c.description       AS current_description,
        c.social_media_link AS current_social_media_link,
        c.instagram_url     AS current_instagram,
        c.facebook_url      AS current_facebook,
        c.linkedin_url      AS current_linkedin,
        c.logo              AS current_logo,
        s.student_name      AS requested_by_name
    FROM club_edit_request cer
    JOIN club c ON c.club_id = cer.club_id
    LEFT JOIN student s ON s.student_id = cer.requested_by_student_id
    WHERE cer.reviewed_at IS NULL
    AND cer.status = 'Pending'
    ORDER BY cer.submitted_at DESC
";


$res = $conn->query($sql);
while ($res && ($row = $res->fetch_assoc())) {
    $editRequests[] = $row;
}

/* helpers */
function pickValue($newVal, $oldVal){
    if ($newVal === null) return $oldVal;
    if (is_string($newVal) && trim($newVal) === '') return $oldVal;
    return $newVal;
}
function isChanged($newVal, $oldVal){
    $n = trim((string)$newVal);
    $o = trim((string)$oldVal);
    if ($n === '') return false;
    return strcasecmp($n, $o) !== 0;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Club Edit Requests (Admin)</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px;
      --navy:#242751;
      --royal:#4871db;
      --coral:#ff5e5e;
      --paper:#eef2f7;
      --card:#ffffff;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow:0 18px 38px rgba(12,22,60,.16);
      --radius-lg:20px;
      --radius-pill:999px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      margin:0;
      font-family:"Raleway",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:var(--paper);
      color:var(--ink);
    }

    .page-shell{
      margin-left:var(--sidebarWidth);
      min-height:100vh;
      padding:32px 40px 40px;
    }

    .page-header{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:16px;
      margin-bottom:24px;
    }

    .page-title{
      font-size:1.6rem;
      font-weight:800;
      letter-spacing:.02em;
      color:var(--navy);
    }

    .page-subtitle{
      font-size:.97rem;
      color:var(--muted);
      margin-top:4px;
    }

    .badge-pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 12px;
      border-radius:999px;
      font-size:.8rem;
      font-weight:600;
      background:rgba(255,94,94,.06);
      color:var(--coral);
      white-space:nowrap;
    }

    .chip-icon{
      width:6px;
      height:6px;
      border-radius:50%;
      background:var(--coral);
    }

    .toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      margin-bottom:24px;
    }

    .search-input{
      flex:1;
      position:relative;
    }

    .search-input input{
      width:100%;
      padding:10px 14px;
      border-radius:999px;
      border:1px solid rgba(15,23,42,.08);
      background:#f9fafb;
      font-size:.93rem;
      outline:none;
    }

    .search-input input:focus{
      border-color:var(--coral);
      box-shadow:0 0 0 1px rgba(255,94,94,.18);
      background:#ffffff;
    }

    .requests-grid{
      display:flex;
      flex-direction:column;
      gap:18px;
    }

    .request-card{
      background:var(--card);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:18px 20px 16px;
      display:flex;
      flex-direction:column;
      gap:14px;
    }

    .request-header{
      display:flex;
      justify-content:space-between;
      gap:10px;
    }

    .request-title{
      font-size:1.05rem;
      font-weight:700;
      color:var(--navy);
    }

    .request-meta-top{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      font-size:.8rem;
      color:var(--muted);
      margin-top:4px;
    }

    .request-meta-top span strong{
      font-weight:700;
      color:var(--ink);
    }

    .compare-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:14px;
      font-size:.83rem;
    }

    .compare-column{
      background:#f9fafb;
      border-radius:14px;
      padding:10px 12px;
    }

    .column-title{
      font-size:.8rem;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.04em;
      margin-bottom:6px;
      color:var(--muted);
    }

    .field-row{
      padding:5px 6px;
      border-radius:10px;
      margin-bottom:2px;
    }

    .field-label{
      font-size:.78rem;
      font-weight:600;
      color:var(--muted);
      display:block;
      margin-bottom:2px;
    }

    .field-value{
      font-size:.84rem;
      color:var(--ink);
      word-break:break-word;
    }

    .changed-field{
      background:#fff1f2;
      border-left:3px solid var(--coral);
    }

    .actions-row{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      flex-wrap:wrap;
    }

    .btn{
      padding:8px 18px;
      border-radius:var(--radius-pill);
      border:1px solid transparent;
      font-size:.86rem;
      font-weight:600;
      cursor:pointer;
      transition:.18s ease all;
      font-family:inherit;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }

    .btn-view{
      background:var(--coral);
      color:#ffffff;
      box-shadow:0 10px 20px rgba(255,94,94,.35);
    }

    .btn-view:hover{
      transform:translateY(-1px);
      box-shadow:0 12px 26px rgba(255,94,94,.45);
    }

    .empty-state{
      text-align:center;
      margin-top:40px;
      color:var(--muted);
      font-size:.95rem;
    }

    @media (max-width:900px){
      .page-shell{ margin-left:0; padding:20px 16px 28px; }
      .compare-grid{ grid-template-columns:1fr; }
      .actions-row{ justify-content:flex-start; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Club Edit Requests</h1>
      <p class="page-subtitle">Review requested edits to existing clubs and open each request to approve or reject.</p>
    </div>
    <span class="badge-pill"><span class="chip-icon"></span> Pending edit requests</span>
  </header>

  <div class="toolbar">
    <div class="search-input">
      <input type="text" id="searchBox" placeholder="Search by club name or applicant…" onkeyup="filterRequests()">
    </div>
  </div>

  <section class="requests-grid" id="requestsList">
    <?php if (!empty($editRequests)): ?>
      <?php foreach($editRequests as $row): ?>
        <?php
          $clubName    = $row['current_club_name'] ?: '—';
          $requestedBy = $row['requested_by_name'] ?: ('Student #'.$row['requested_by_student_id']);
          $searchText  = strtolower($clubName.' '.$requestedBy);

          // current
          $origName   = $row['current_club_name'];
          $origCat    = $row['current_category'];
          $origEmail  = $row['current_contact_email'];
          $origDesc   = $row['current_description'];
          $origInsta  = $row['current_instagram'];
          $origFb     = $row['current_facebook'];
          $origLi     = $row['current_linkedin'];
          $origLogo   = $row['current_logo'];

          // requested (final preview)
          $propName  = pickValue($row['new_club_name'], $origName);
          $propCat   = pickValue($row['new_category'], $origCat);
          $propEmail = pickValue($row['new_contact_email'], $origEmail);
          $propDesc  = pickValue($row['new_description'], $origDesc);
          $propInsta = pickValue($row['instagram'], $origInsta);
          $propFb    = pickValue($row['facebook'], $origFb);
          $propLi    = pickValue($row['linkedin'], $origLi);
          $propLogo  = pickValue($row['new_logo'], $origLogo);

          // change flags (only if request has value)
          $nameChanged  = isChanged($row['new_club_name'], $origName);
          $catChanged   = isChanged($row['new_category'], $origCat);
          $emailChanged = isChanged($row['new_contact_email'], $origEmail);
          $descChanged  = isChanged($row['new_description'], $origDesc);
          $instaChanged = isChanged($row['instagram'], $origInsta);
          $fbChanged    = isChanged($row['facebook'], $origFb);
          $liChanged    = isChanged($row['linkedin'], $origLi);
          $logoChanged  = isChanged($row['new_logo'], $origLogo);

          $origCatShow   = trim((string)$origCat) === '' ? '—' : $origCat;
          $propCatShow   = trim((string)$propCat) === '' ? '—' : $propCat;

          $origEmailShow = trim((string)$origEmail) === '' ? '—' : $origEmail;
          $propEmailShow = trim((string)$propEmail) === '' ? '—' : $propEmail;

          $origLogoShow  = trim((string)$origLogo) === '' ? '—' : $origLogo;
          $propLogoShow  = trim((string)$propLogo) === '' ? '—' : $propLogo;
        ?>

        <article class="request-card" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES); ?>">
          <div class="request-header">
            <div>
              <div class="request-title"><?php echo htmlspecialchars($clubName); ?></div>
              <div class="request-meta-top">
                <span>Requested by: <strong><?php echo htmlspecialchars($requestedBy); ?></strong></span>
                <span>Requested on: <?php echo htmlspecialchars(date('d M Y', strtotime($row['submitted_at']))); ?></span>
              </div>
            </div>
          </div>

          <div class="compare-grid">
            <!-- Current Club -->
            <div class="compare-column">
              <div class="column-title">Current Club</div>

              <div class="field-row <?php echo $nameChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Club name</span>
                <span class="field-value"><?php echo htmlspecialchars($origName ?: '—'); ?></span>
              </div>

              <div class="field-row <?php echo $catChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Category</span>
                <span class="field-value"><?php echo htmlspecialchars($origCatShow); ?></span>
              </div>

              <div class="field-row <?php echo $emailChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Contact email</span>
                <span class="field-value"><?php echo htmlspecialchars($origEmailShow); ?></span>
              </div>

              <div class="field-row <?php echo $descChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Description</span>
                <span class="field-value"><?php echo nl2br(htmlspecialchars($origDesc ?: '—')); ?></span>
              </div>

              <div class="field-row <?php echo $logoChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Logo path</span>
                <span class="field-value"><?php echo htmlspecialchars($origLogoShow); ?></span>
              </div>

              <div class="field-row <?php echo $instaChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Instagram</span>
                <span class="field-value"><?php echo htmlspecialchars($origInsta ?: '—'); ?></span>
              </div>

              <div class="field-row <?php echo $fbChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Facebook</span>
                <span class="field-value"><?php echo htmlspecialchars($origFb ?: '—'); ?></span>
              </div>

              <div class="field-row <?php echo $liChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">LinkedIn</span>
                <span class="field-value"><?php echo htmlspecialchars($origLi ?: '—'); ?></span>
              </div>
            </div>

            <!-- Requested Changes -->
            <div class="compare-column">
              <div class="column-title">Requested Changes</div>

              <div class="field-row <?php echo $nameChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Club name</span>
                <span class="field-value"><?php echo htmlspecialchars($propName ?: '—'); ?></span>
              </div>

              <div class="field-row <?php echo $catChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Category</span>
                <span class="field-value"><?php echo htmlspecialchars($propCatShow); ?></span>
              </div>

              <div class="field-row <?php echo $emailChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Contact email</span>
                <span class="field-value"><?php echo htmlspecialchars($propEmailShow); ?></span>
              </div>

              <div class="field-row <?php echo $descChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Description</span>
                <span class="field-value"><?php echo nl2br(htmlspecialchars($propDesc ?: '—')); ?></span>
              </div>

              <div class="field-row <?php echo $logoChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Logo path</span>
                <span class="field-value"><?php echo htmlspecialchars($propLogoShow); ?></span>
              </div>

              <div class="field-row <?php echo $instaChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Instagram</span>
                <span class="field-value"><?php echo htmlspecialchars($propInsta ?: '—'); ?></span>
              </div>

              <div class="field-row <?php echo $fbChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">Facebook</span>
                <span class="field-value"><?php echo htmlspecialchars($propFb ?: '—'); ?></span>
              </div>

              <div class="field-row <?php echo $liChanged ? 'changed-field' : ''; ?>">
                <span class="field-label">LinkedIn</span>
                <span class="field-value"><?php echo htmlspecialchars($propLi ?: '—'); ?></span>
              </div>
            </div>
          </div>

          <div class="actions-row">
            <a href="editform.php?id=<?php echo (int)$row['request_id']; ?>" class="btn btn-view">
              Review & Decide
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">There are no pending club edit requests right now.</p>
    <?php endif; ?>
  </section>
</div>

<script>
  function filterRequests(){
    const q = document.getElementById('searchBox').value.toLowerCase();
    const cards = document.querySelectorAll('#requestsList .request-card');
    cards.forEach(card => {
      const text = (card.getAttribute('data-search') || '').toLowerCase();
      card.style.display = text.includes(q) ? 'flex' : 'none';
    });
  }
</script>

</body>
</html>
