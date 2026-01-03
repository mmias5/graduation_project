<?php
session_start();

if (!isset($_SESSION['student_id']) || ($_SESSION['role'] ?? '') !== 'club_president') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$studentId      = (int)$_SESSION['student_id'];
$redeemError    = '';
$redeemSuccess  = '';

/* ========= helpers ========= */

function escapeH(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* fix image paths without changing DB values */
function img_path($path){
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path; // full URL
    if ($path[0] === '/') return $path;                     // absolute path
    return '../' . ltrim($path, '/');                       // make uploads/... work from /president/
}

function getStudentPointsFromStudent(mysqli $conn, int $studentId): int {
    $sql = "SELECT COALESCE(total_points,0) AS total_points FROM student WHERE student_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total_points'] ?? 0);
}

/* deduct points */
function updateStudentPoints(mysqli $conn, int $studentId, int $cost): bool {
    $sql = "UPDATE student
            SET total_points = total_points - ?
            WHERE student_id = ? AND total_points >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $cost, $studentId, $cost);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

/* add redemption row */
function addRedemptionRow(mysqli $conn, int $studentId, int $itemId, int $pointsSpent): bool {
    $sql = "INSERT INTO redemption (student_id, item_id, redeemed_at, points_spent)
            VALUES (?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $studentId, $itemId, $pointsSpent);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

/* ========= handle redeem POST ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_submit'])) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $redeemError = 'Database connection error.';
    } else {
        $itemId = isset($_POST['redeem_item_id']) ? (int)$_POST['redeem_item_id'] : 0;
        $code   = trim($_POST['redeem_code'] ?? '');

        if ($itemId <= 0 || $code === '') {
            $redeemError = 'Please choose a reward and enter its code.';
        } else {
            // fetch active rewards
            $sql = "SELECT item_id, item_name, value, code, picture
                    FROM items_to_redeem
                    WHERE item_id = ? AND is_active = 1
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$item) {
                $redeemError = 'Reward not found (or it is no longer available).';
            } elseif (strcasecmp($code, $item['code']) !== 0) {
                $redeemError = 'Invalid code for this reward.';
            } else {
                $costPoints    = (int)$item['value'];
                $currentPoints = getStudentPointsFromStudent($conn, $studentId);

                if ($costPoints <= 0) {
                    $redeemError = 'This reward is not configured correctly.';
                } elseif ($currentPoints < $costPoints) {
                    $redeemError = 'You do not have enough points to redeem this reward.';
                } else {
                    // deduction + redemption in one transaction
                    try {
                        $conn->begin_transaction();

                        if (!updateStudentPoints($conn, $studentId, $costPoints)) {
                            throw new Exception('Could not update your points.');
                        }

                        if (!addRedemptionRow($conn, $studentId, $itemId, $costPoints)) {
                            throw new Exception('Could not save redemption record.');
                        }

                        $conn->commit();
                        $redeemSuccess = 'Reward redeemed successfully!';
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $redeemError = $e->getMessage();
                    }
                }
            }
        }
    }
}

/* ========= fetch current points & rewards list ========= */

$currentPoints = isset($conn) && $conn instanceof mysqli
    ? getStudentPointsFromStudent($conn, $studentId)
    : 0;

$progressTarget = 250;
$progressRatio  = $progressTarget > 0 ? min(1, $currentPoints / $progressTarget) : 0.0;

$rewards = [];
if (isset($conn) && $conn instanceof mysqli) {
    //  ACTIVE ONLY
    $sql = "SELECT item_id, item_name, value, code, picture
            FROM items_to_redeem
            WHERE is_active = 1
            ORDER BY value ASC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rewards[] = $row;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Rewards</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --c-navy:   #2B3751;
    --c-blue:   #4871DB;
    --c-yellow: #F6E578;
    --c-red:    #FF5C5E;
    --c-ice:    #E9ECEF;

    --radius-xl: 20px;
    --shadow: 0 10px 28px rgba(16,24,40,.10);
    --shadow-soft: 0 6px 18px rgba(16,24,40,.08);
  }

  @font-face{
    font-family: "Extenda 90 Exa";
    src: url("assets/fonts/Extenda90Exa.woff2") format("woff2"),
         url("assets/fonts/Extenda90Exa.woff") format("woff");
    font-weight: 700; font-style: normal; font-display: swap;
  }

  body{
    margin:0;
    background:
      radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
      radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
      #eef2f7;
    font-family: "Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    color: var(--c-navy);
  }

  .loyalty, .loyalty *{ box-sizing: border-box; }
  .loyalty{
    background: inherit;
    margin-top: -1px;
    padding: 24px 0 56px;
  }
  .loyalty .container{ width:min(1120px, 92%); margin-inline:auto; }

  .lp-headline{ text-align:center; margin-bottom: 18px; }
  .lp-headline h2{
    margin:0 0 8px;
    font-family: "Extenda 90 Exa","Raleway",sans-serif;
    font-weight:700;
    font-size: clamp(22px, 5vw, 34px);
    letter-spacing:.2px;
    color: var(--c-navy);
  }
  .lp-total{ font-weight:800; font-size: 16px; opacity:.85; margin-bottom: 12px; }

  .loyalty-meter{
    --progress: .35;
    --h: 38px;
    position: relative;
    margin: 0 auto 18px;
    width: min(620px, 92%);
    height: var(--h);
    border-radius: calc(var(--h) / 2);
    background: linear-gradient(90deg, #E0E6F7 0%, #D4DCF1 100%);
    box-shadow: inset 0 2px 8px rgba(30,42,76,.12);
    overflow: hidden;
  }
  .loyalty-meter .fill{
    position:absolute; inset:0;
    width: calc(var(--progress) * 100%);
    border-radius: inherit;
    background: linear-gradient(90deg, var(--c-blue) 0%, var(--c-yellow) 100%);
  }

  .flash{
    width:min(1120px,92%);
    margin:0 auto 10px;
    font-size:14px;
    padding:10px 14px;
    border-radius:12px;
    box-shadow:var(--shadow-soft);
  }
  .flash.error{
    background:#ffe5e8;
    color:#7f1d1d;
    border:1px solid #fecaca;
  }
  .flash.success{
    background:#e7f8ea;
    color:#14532d;
    border:1px solid #bbf7d0;
  }

  /*  Search bar */
  .search-wrap{
    width:min(520px, 92%);
    margin: 0 auto 22px;
    display:flex;
    gap:10px;
    align-items:center;
    justify-content:center;
  }
  .search-input{
    width:100%;
    height: 44px;
    border-radius: 999px;
    border: 2px solid #E5E9F5;
    outline: none;
    padding: 0 14px;
    font-size: 14px;
    box-shadow: var(--shadow-soft);
    background:#fff;
  }
  .search-input:focus{
    border-color: rgba(72,113,219,.65);
  }
  .search-clear{
    height:44px;
    padding:0 14px;
    border-radius:999px;
    border:none;
    cursor:pointer;
    font-weight:800;
    background:#EEF2FA;
    color:#1f2a45;
    box-shadow: var(--shadow-soft);
  }
  .search-clear:hover{ filter:brightness(.98); }

  .rewards{ display:grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }

  .reward-card{
    background:#fff; border-radius: var(--radius-xl); overflow:hidden;
    box-shadow: var(--shadow);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .reward-card:hover{ transform: translateY(-2px); box-shadow: 0 14px 34px rgba(16,24,40,.12); }

  .rc-photo{
    position:relative;
    width:100%;
    aspect-ratio: 16/9;
    background:#f2f4f8;
    overflow:hidden;
  }
  .rc-photo img{
    width:100%; height:100%; object-fit:cover; display:block;
    transform:scale(1.02);
  }

  .rc-body{ background: linear-gradient(180deg, #ffffff 0%, #F5F7FB 100%); padding: 14px 16px 16px; }
  .rc-title{ font-weight:800; color:var(--c-navy); margin:0 0 6px; line-height:1.2; }
  .rc-sub{ margin:0 0 12px; font-weight:700; color:#65708A; }

  .rc-cta{
    display:inline-flex; align-items:center; justify-content:center;
    padding:10px 16px; border-radius:12px; border:none; cursor:pointer;
    font-weight:700; background: #F6E578;
    color: var(--c-navy); box-shadow: inset 0 -2px 0 rgba(0,0,0,.06);
  }
  .rc-cta:hover{ filter:saturate(1.05);background:#4871DB; color:#fff; }
  .rc-cta:active{ transform: translateY(1px); }

  .no-results{
    grid-column: 1 / -1;
    background: rgba(255,255,255,.75);
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px;
    text-align:center;
    color:#65708A;
    box-shadow: var(--shadow-soft);
    display:none;
  }

  .modal-backdrop{
    position: fixed; inset: 0; display:none;
    background: rgba(10, 16, 28, .5);
    z-index: 1000;
  }
  .modal{
    position: fixed; inset:0; display:none; place-items:center; z-index:1001;
    padding: 20px;
  }
  .modal.active, .modal-backdrop.active{ display:grid; }
  .modal-card{
    width: min(440px, 94%);
    background: #fff; border-radius: 16px; box-shadow: var(--shadow);
    overflow:hidden;
  }
  .modal-head{
    background: var(--c-blue);
    color:#fff; padding:14px 18px; font-weight:800;
    font-family:"Extenda 90 Exa","Raleway",sans-serif;
  }
  .modal-body{ padding:18px; }
  .modal-body p{ margin:0 0 12px; color:#2b3751; font-weight:600; }
  .form-row{ display:grid; gap:10px; }
  .form-row input{
    height: 44px; border-radius: 10px; border: 2px solid #E5E9F5; outline: none;
    padding: 0 12px; font-size: 15px;
  }
  .form-actions{ margin-top: 12px; display:flex; gap:10px; justify-content:flex-end; }
  .btn{
    height: 42px; padding: 0 16px; border-radius: 12px; border:none; cursor:pointer; font-weight:800;
  }
  .btn-cancel{ background:#EEF2FA; color:#1f2a45; }
  .btn-submit{
    background: var(--c-yellow);
    color: var(--c-navy);
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="loyalty" aria-labelledby="loyalty-heading">
  <div class="container">

    <?php if ($redeemError): ?>
      <div class="flash error"><?php echo escapeH($redeemError); ?></div>
    <?php elseif ($redeemSuccess): ?>
      <div class="flash success"><?php echo escapeH($redeemSuccess); ?></div>
    <?php endif; ?>

    <div class="lp-headline">
      <h2 id="loyalty-heading">Your Loyalty points!</h2>
      <div class="lp-total"><?php echo (int)$currentPoints; ?> pts</div>
    </div>

    <div class="loyalty-meter" style="--progress:<?php echo escapeH(number_format($progressRatio,3,'.','')); ?>" aria-label="Loyalty progress">
      <div class="fill"></div>
    </div>

    <!--  Search bar -->
    <div class="search-wrap">
      <input id="rewardSearch" class="search-input" type="text" placeholder="Search reward by name..." autocomplete="off">
      <button id="clearSearch" class="search-clear" type="button">Clear</button>
    </div>

    <!-- Rewards grid -->
    <div class="rewards" id="rewardsGrid">
      <?php if (empty($rewards)): ?>
        <div class="no-results" style="display:block;">No rewards available right now.</div>
      <?php else: ?>
        <?php foreach ($rewards as $r): ?>
          <?php
            $cost = (int)$r['value'];
            $picRaw  = (string)($r['picture'] ?? '');
            $imgSrc  = img_path($picRaw);
          ?>
          <article class="reward-card" data-name="<?php echo escapeH(mb_strtolower((string)$r['item_name'])); ?>">
            <div class="rc-photo">
              <?php if ($imgSrc !== ''): ?>
                <img src="<?php echo escapeH($imgSrc); ?>" alt="<?php echo escapeH($r['item_name']); ?>" loading="lazy">
              <?php else: ?>
                <img src="../assets/rewards/default_reward.jpg" alt="<?php echo escapeH($r['item_name']); ?>" loading="lazy" onerror="this.style.display='none'">
              <?php endif; ?>
            </div>
            <div class="rc-body">
              <h3 class="rc-title"><?php echo escapeH($r['item_name']); ?></h3>
              <p class="rc-sub"><?php echo $cost; ?> Points</p>
              <button
                class="rc-cta redeem-btn"
                type="button"
                data-reward="<?php echo escapeH($r['item_name']); ?>"
                data-item-id="<?php echo (int)$r['item_id']; ?>"
              >
                Redeem
              </button>
            </div>
          </article>
        <?php endforeach; ?>
        <div class="no-results" id="noResults">No rewards match your search.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Modal + Backdrop -->
<div class="modal-backdrop" id="redeemBackdrop"></div>
<div class="modal" id="redeemModal" role="dialog" aria-modal="true" aria-labelledby="redeemTitle">
  <form class="modal-card" method="post">
    <div class="modal-head" id="redeemTitle">Redeem Reward</div>
    <div class="modal-body">
      <p id="rewardName">Enter the code for: <strong>—</strong></p>

      <input type="hidden" name="redeem_item_id" id="redeem_item_id" value="">
      <input type="hidden" name="redeem_submit" value="1">

      <div class="form-row">
        <input id="redeemCode" name="redeem_code" type="text" placeholder="Enter your code" autocomplete="one-time-code" />
      </div>
      <div class="form-actions">
        <button class="btn btn-cancel" id="btnCancel" type="button">Cancel</button>
        <button class="btn btn-submit" id="btnSubmit" type="submit">Submit</button>
      </div>
    </div>
  </form>
</div>

<script>
  // ===== Redeem modal =====
  const modal = document.getElementById('redeemModal');
  const backdrop = document.getElementById('redeemBackdrop');
  const rewardNameEl = document.getElementById('rewardName').querySelector('strong');
  const codeInput = document.getElementById('redeemCode');
  const itemInput = document.getElementById('redeem_item_id');

  function openModal(name, itemId){
    rewardNameEl.textContent = name;
    codeInput.value = '';
    itemInput.value = itemId || '';
    modal.classList.add('active');
    backdrop.classList.add('active');
    setTimeout(() => codeInput.focus(), 0);
  }
  function closeModal(){
    modal.classList.remove('active');
    backdrop.classList.remove('active');
  }

  document.querySelectorAll('.redeem-btn').forEach(btn=>{
    btn.addEventListener('click', () => {
      openModal(btn.dataset.reward || 'Selected Reward', btn.dataset.itemId || '');
    });
  });

  document.getElementById('btnCancel').addEventListener('click', closeModal);
  backdrop.addEventListener('click', closeModal);
  window.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

  // ===== Search filter =====
  const searchInput = document.getElementById('rewardSearch');
  const clearBtn = document.getElementById('clearSearch');
  const cards = Array.from(document.querySelectorAll('.reward-card'));
  const noResults = document.getElementById('noResults');

  function applyFilter(){
    const q = (searchInput.value || '').trim().toLowerCase();
    let shown = 0;

    cards.forEach(card => {
      const name = (card.dataset.name || '');
      const match = (q === '' || name.includes(q));
      card.style.display = match ? '' : 'none';
      if (match) shown++;
    });

    if (noResults) {
      noResults.style.display = (shown === 0 && cards.length > 0) ? 'block' : 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilter);
  }
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      searchInput.value = '';
      applyFilter();
      searchInput.focus();
    });
  }
</script>

<?php include 'footer.php'; ?>
</body>
</html>
