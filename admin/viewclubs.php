<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

/* =========================
   Optional flash message
========================= */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* =========================
   Handle POST Actions (DB)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['club_id'])) {
    $action = $_POST['action'];
    $clubId = (int)$_POST['club_id'];

    if ($clubId <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid club id.'];
        header("Location: viewclubs.php");
        exit;
    }

    /* ===== Toggle status ===== */
    if ($action === 'toggle_status') {
        $current = strtolower(trim($_POST['current_status'] ?? ''));
        $newStatus = ($current === 'active') ? 'inactive' : 'active';

        $stmt = $conn->prepare("UPDATE club SET status = ? WHERE club_id = ? LIMIT 1");
        $stmt->bind_param("si", $newStatus, $clubId);
        $stmt->execute();
        $stmt->close();

        header("Location: viewclubs.php");
        exit;
    }

    /* ===== Delete club ===== */
    if ($action === 'delete_club') {

        if ($clubId === 1) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'You cannot delete the default club (ID = 1).'];
            header("Location: viewclubs.php");
            exit;
        }

        $conn->begin_transaction();

        try {
            // 1) Delete ranking rows
            $stmt = $conn->prepare("DELETE FROM ranking WHERE club_id = ?");
            $stmt->bind_param("i", $clubId);
            $stmt->execute();
            $stmt->close();

            // 2) Delete events for this club
            $stmt = $conn->prepare("DELETE FROM event WHERE club_id = ?");
            $stmt->bind_param("i", $clubId);
            $stmt->execute();
            $stmt->close();

            // 3) Move students to default club_id = 1
            $defaultClubId = 1;
            $stmt = $conn->prepare("UPDATE student SET club_id = ? WHERE club_id = ?");
            $stmt->bind_param("ii", $defaultClubId, $clubId);
            $stmt->execute();
            $stmt->close();

            // 4) Delete the club
            $stmt = $conn->prepare("DELETE FROM club WHERE club_id = ? LIMIT 1");
            $stmt->bind_param("i", $clubId);
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                $stmt->close();
                throw new Exception("Club not found or could not be deleted.");
            }
            $stmt->close();

            $conn->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Club deleted successfully.'];

        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Delete failed: ' . $e->getMessage()];
        }

        header("Location: viewclubs.php");
        exit;
    }
}

/* =========================
   Fetch clubs from DB
========================= */
$clubs = [];
$categories = [];

$sql = "
    SELECT 
        c.club_id,
        c.club_name,
        c.description,
        c.category,
        c.logo,
        c.status,
        c.contact_email,
        c.member_count,
        c.sponsor_id,
        COALESCE(sp.company_name, '') AS sponsor_name,
        COALESCE(ev.total_events, 0) AS total_events,
        COALESCE((
            SELECT r2.total_points
            FROM ranking r2
            WHERE r2.club_id = c.club_id
            ORDER BY r2.period_end DESC, r2.period_start DESC, r2.ranking_id DESC
            LIMIT 1
        ), 0) AS ranking_points
    FROM club c
    LEFT JOIN sponsor sp
        ON sp.sponsor_id = c.sponsor_id
    LEFT JOIN (
        SELECT club_id, COUNT(*) AS total_events
        FROM event
        WHERE ending_date IS NOT NULL
          AND ending_date < NOW()
        GROUP BY club_id
    ) ev
        ON ev.club_id = c.club_id
    ORDER BY c.club_name ASC
";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {

        $status = strtolower(trim($row['status'] ?? ''));
        $isActive = ($status === 'active');

        $sponsorName = trim($row['sponsor_name'] ?? '');
        if ($sponsorName === '') {
            $sponsorName = "No sponsor yet";
        }

        $logoFromDb = trim((string)($row['logo'] ?? ''));
        if ($logoFromDb === '') {
            $logoFromDb = 'assets/club_placeholder.png';
        }

        $club = [
            "id"          => (int)$row['club_id'],
            "name"        => $row['club_name'],
            "category"    => ($row['category'] && trim($row['category']) !== '') ? $row['category'] : 'Uncategorized',
            "sponsor"     => $sponsorName,
            "description" => $row['description'] ?? '',
            "members"     => (int)($row['member_count'] ?? 0),
            "events"      => (int)($row['total_events'] ?? 0),
            "points"      => (int)($row['ranking_points'] ?? 0),
            "logo"        => $logoFromDb,
            "active"      => $isActive,
            "status"      => $isActive ? 'active' : 'inactive',
        ];

        $clubs[] = $club;

        if (!empty($club['category'])) {
            $categories[] = $club['category'];
        }
    }
}

$categories = array_values(array_unique($categories));
sort($categories);

/**
 * Helper: make image src correct from admin folder
 * - If it's "uploads/..." => use "/uploads/..." (absolute from root)
 * - Else (assets/...) => keep as-is (relative to admin)
 */
function uiImgSrc(string $path): string {
    $path = trim($path);

    // placeholder داخل admin
    if ($path === '') {
        return 'assets/club_placeholder.png';
    }

    // uploads from project root
    if (strpos($path, 'uploads/') === 0) {
        return '/graduation_project/' . ltrim($path, '/');
    }

    // any other relative asset
    return $path;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — View Clubs</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --coral:#ff5e5e;
  --paper:#eef2f7;
  --card:#ffffff;
  --ink:#0e1228;
  --muted:#6b7280;
  --radius:22px;
  --shadow:0 14px 34px rgba(10,23,60,.12);
  --sidebarWidth:260px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{margin:0;background:var(--paper);font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
.content{margin-left:var(--sidebarWidth);padding:40px 50px 60px;}
.page-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.page-title{font-size:2rem;font-weight:800;color:var(--ink);}
.filters-row{display:flex;gap:14px;align-items:center;margin-bottom:24px;}
.search-input{flex:1;min-width:260px;padding:11px 16px;border-radius:999px;border:none;outline:none;font-size:.95rem;background:#ffffff;box-shadow:0 8px 22px rgba(15,23,42,.12);color:var(--ink);}
.search-input::placeholder{color:#9ca3af;}
.category-filter{position:relative;}
.category-btn{min-width:190px;padding:11px 16px;border-radius:999px;border:none;background:#ffffff;box-shadow:0 8px 22px rgba(15,23,42,.12);display:flex;justify-content:space-between;align-items:center;cursor:pointer;font-size:.95rem;color:var(--ink);}
.category-btn span:first-child{font-weight:500;}
.category-arrow{font-size:.9rem;opacity:.7;}
.category-menu{position:absolute;top:110%;left:0;width:100%;background:#ffffff;border-radius:18px;box-shadow:0 16px 38px rgba(15,23,42,.18);padding:8px 0;display:none;z-index:10;}
.category-menu.open{display:block;}
.category-option{width:100%;padding:9px 16px;text-align:left;border:none;background:transparent;font-size:.93rem;cursor:pointer;color:var(--ink);transition:background .15s ease;}
.category-option:hover{background:#f3f4f6;}
.cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:22px;}
.club-card{background:var(--card);border-radius:20px;box-shadow:0 18px 40px rgba(15,23,42,.14);padding:18px 20px 16px;border:2px solid rgba(36,39,81,0.07);position:relative;cursor:pointer;}
.card-link{position:absolute;top:0;left:0;width:100%;height:100%;z-index:1;display:block;}
.actions-row,.actions-row *{position:relative;z-index:5;}
.club-card.active{border-color:var(--navy);}
.card-top{display:flex;justify-content:space-between;gap:12px;margin-bottom:10px;}
.card-main{display:flex;gap:14px;}
.club-logo{width:52px;height:52px;border-radius:50%;background:#e5e7eb;object-fit:cover;flex-shrink:0;}
.club-text{display:flex;flex-direction:column;gap:2px;}
.club-name{font-weight:800;font-size:1.05rem;color:var(--ink);}
.club-sponsor{font-size:.9rem;color:var(--muted);}
.club-sponsor span{color:var(--coral);font-weight:600;}
.category-pill{align-self:flex-start;padding:6px 14px;border-radius:999px;border:2px solid var(--navy);color:var(--navy);font-size:.86rem;font-weight:600;background:#e3e4f4;}
.club-desc{font-size:.93rem;color:var(--muted);margin-bottom:10px;}
.stats-row{display:flex;gap:18px;font-size:.88rem;font-weight:600;color:var(--ink);margin-bottom:12px;}
.stats-row span{color:var(--muted);font-weight:500;}
.actions-row{display:flex;justify-content:space-between;gap:10px;align-items:center;}
.btn-group-left{display:flex;gap:8px;}
.card-btn{padding:8px 14px;border-radius:999px;font-size:.86rem;font-weight:600;border:none;cursor:pointer;transition:background .15s ease, color .15s ease, transform .1s ease;}
.btn-delete{background:#ffe3e3;color:#b91c1c;}
.btn-delete:hover{background:var(--coral);color:#ffffff;transform:translateY(-1px);}
.btn-status{background:#dfe2f0;color:var(--navy);}
.btn-status.inactive{background:#f3f4f6;color:#6b7280;}
.btn-status:hover{background:var(--navy);color:#ffffff;transform:translateY(-1px);}
.btn-view-members{background:var(--navy);color:#ffffff;text-decoration:none;}
.btn-view-members:hover{background:#191c3a;transform:translateY(-1px);}
.inline-form{display:inline;}
.flash{margin-bottom:16px;padding:12px 16px;border-radius:14px;font-weight:700;box-shadow:0 10px 24px rgba(15,23,42,.12);}
.flash.success{background:#e9f9ee;color:#166534;border:1px solid #86efac;}
.flash.error{background:#ffecec;color:#991b1b;border:1px solid #fecaca;}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-header">
    <div class="page-title">View Clubs</div>
  </div>

  <?php if ($flash && !empty($flash['msg'])): ?>
    <div class="flash <?= htmlspecialchars($flash['type'] ?? 'success'); ?>">
      <?= htmlspecialchars($flash['msg']); ?>
    </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="filters-row">
    <input type="text" id="searchClubs" class="search-input" placeholder="Search clubs...">

    <div class="category-filter">
      <button type="button" id="categoryBtn" class="category-btn">
        <span id="categoryLabel">All categories</span>
        <span class="category-arrow">▾</span>
      </button>

      <div id="categoryMenu" class="category-menu">
        <button class="category-option" data-category="all">All categories</button>
        <?php foreach($categories as $cat): ?>
          <button class="category-option" data-category="<?= htmlspecialchars($cat); ?>">
            <?= htmlspecialchars($cat); ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Cards grid -->
  <div class="cards-grid" id="cardsGrid">
    <?php foreach($clubs as $club): ?>
      <div 
        class="club-card <?= $club['active'] ? 'active' : '' ?>"
        data-name="<?= htmlspecialchars(strtolower($club['name'])); ?>"
        data-category="<?= htmlspecialchars($club['category']); ?>"
      >
        <a href="clubpage.php?club_id=<?= (int)$club['id']; ?>" class="card-link"></a>

        <div class="card-top">
          <div class="card-main">
            <img src="<?= htmlspecialchars(uiImgSrc($club['logo'])); ?>" alt="Club logo" class="club-logo">
            <div class="club-text">
              <div class="club-name"><?= htmlspecialchars($club['name']); ?></div>
              <div class="club-sponsor">
                Sponsored by <span><?= htmlspecialchars($club['sponsor']); ?></span>
              </div>
            </div>
          </div>
          <div class="category-pill"><?= htmlspecialchars($club['category']); ?></div>
        </div>

        <div class="club-desc">
          <?= nl2br(htmlspecialchars($club['description'])); ?>
        </div>

        <div class="stats-row">
          <div><span>Members:</span> <?= (int)$club['members']; ?></div>
          <div><span>Events:</span> <?= (int)$club['events']; ?></div>
          <div><span>Points:</span> <?= (int)$club['points']; ?></div>
        </div>

        <div class="actions-row">
          <div class="btn-group-left">

            <form class="inline-form delete-form" method="POST" action="viewclubs.php">
              <input type="hidden" name="action" value="delete_club">
              <input type="hidden" name="club_id" value="<?= (int)$club['id']; ?>">
              <button class="card-btn btn-delete" type="submit">Delete</button>
            </form>

            <form class="inline-form status-form" method="POST" action="viewclubs.php">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="club_id" value="<?= (int)$club['id']; ?>">
              <input type="hidden" name="current_status" value="<?= htmlspecialchars($club['status']); ?>">
              <button class="card-btn btn-status <?= $club['active'] ? '' : 'inactive'; ?>" type="submit">
                <?= $club['active'] ? 'Inactivate' : 'Activate'; ?>
              </button>
            </form>

          </div>

          <a href="viewmembers.php?club_id=<?= (int)$club['id']; ?>" class="card-btn btn-view-members">
            View members
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
const categoryBtn = document.getElementById('categoryBtn');
const categoryMenu = document.getElementById('categoryMenu');
const categoryLabel = document.getElementById('categoryLabel');

categoryBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  categoryMenu.classList.toggle('open');
});
document.addEventListener('click', () => categoryMenu.classList.remove('open'));

const searchInput = document.getElementById('searchClubs');
const cards = document.querySelectorAll('.club-card');
let selectedCategory = 'all';

function applyFilters() {
  const query = searchInput.value.toLowerCase().trim();
  cards.forEach(card => {
    const name = card.dataset.name || '';
    const category = card.dataset.category || '';
    const matchesName = !query || name.includes(query);
    const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
    card.style.display = (matchesName && matchesCategory) ? 'block' : 'none';
  });
}
searchInput.addEventListener('input', applyFilters);

document.querySelectorAll('.category-option').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    selectedCategory = btn.dataset.category;
    categoryLabel.textContent = selectedCategory === 'all' ? 'All categories' : btn.textContent;
    categoryMenu.classList.remove('open');
    applyFilters();
  });
});

document.querySelectorAll('.actions-row, .actions-row *').forEach(el => {
  el.addEventListener('click', (e) => e.stopPropagation());
});

document.querySelectorAll('.status-form').forEach(form => {
  form.addEventListener('submit', (e) => {
    const btn = form.querySelector('button');
    const txt = btn ? btn.textContent.trim().toLowerCase() : 'change status';
    if (!confirm(`Are you sure you want to ${txt} this club?`)) e.preventDefault();
  });
});

document.querySelectorAll('.delete-form').forEach(form => {
  form.addEventListener('submit', (e) => {
    const clubId = form.querySelector('input[name="club_id"]')?.value || '';
    if (clubId === '1') {
      alert('You cannot delete the default club (ID = 1).');
      e.preventDefault();
      return;
    }
    if (!confirm('Are you sure you want to delete this club? This will delete its events & ranking and move its students to the default club.')) {
      e.preventDefault();
    }
  });
});
</script>

</body>
</html>
