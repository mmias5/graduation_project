<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    // لو بدك تخلي الـ president يدخل على صفحة مختلفة
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_president') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Header + Sidebar + Hover Dropdowns</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<?php include 'header.php'; ?>
<!-- ===== CCH • Clubs Ranking (final, with gradient background & sponsor logos) ===== -->
<style>
/* ---------- Scoped theme ---------- */
.cch-ranking{
  --navy: #212153;
  --royal: #4871DB;
  --gold: #E5B758;
  --coral: #FF5E5E;
  --ink: #0E1228;
  --card: #fff;
  --shadow:0 10px 24px rgba(0,0,0,.12);
  --radius:16px;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;

  /* 🎨 Background like your Discover page */
  background:
    radial-gradient(1200px 420px at 50% 0%, rgba(255,255,255,.55) 0%, rgba(255,255,255,0) 60%),
    linear-gradient(180deg, #e9ecef 100%);
}
.cch-ranking .wrap{ max-width:1180px; margin:auto; padding:32px 20px 40px }

/* ---------- Title + Search ---------- */
.cch-ranking .head{ display:flex; align-items:center; gap:14px; margin-bottom:22px }
.cch-ranking .title{
  background:linear-gradient(135deg,var(--royal),var(--gold));
  color:#fff; padding:10px 18px; border-radius:12px; font-weight:800; font-size:28px;
  box-shadow:var(--shadow);
}
.cch-ranking .search{ margin-left:auto; width:min(420px,100%); position:relative }
.cch-ranking .search input{
  width:100%; padding:12px 44px; border-radius:999px; border:1px solid #d9dceb; background:#fff;
  font-size:15px; outline:none; box-shadow:0 4px 12px rgba(72,113,219,.12)
}
.cch-ranking .search svg{ position:absolute; left:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; opacity:.7 }

/* ---------- Sponsor line + logo pill (used in top3 & table) ---------- */
.cch-ranking .sponsor{ display:flex; align-items:center; gap:6px; margin-top:4px; font-size:12px; font-weight:600; color:#6b7090 }
.cch-ranking .sponsor.small{ font-size:11px }
.cch-ranking .sponsor-logo{
  width:20px; height:20px; border-radius:50%; overflow:hidden; display:grid; place-items:center;
  background:#fff; border:2px solid var(--coral)
}
.cch-ranking .sponsor-logo.small{ width:18px; height:18px }
.cch-ranking .sponsor-logo img{ width:100%; height:100%; object-fit:cover }

/* ---------- Top 3 Podium ---------- */
.cch-ranking .podium{
  display:grid; grid-template-columns:1fr 1.15fr 1fr; gap:16px; align-items:end; margin-bottom:26px
}
.cch-ranking .pod{ background:var(--card); border-radius:20px; box-shadow:var(--shadow); padding:18px 14px 14px; text-align:center }
.cch-ranking .pod.s2{ transform:translateY(10px) } .cch-ranking .pod.s3{ transform:translateY(18px) }

/* Club logo circle (image) */
.cch-ranking .medal{
  width:54px; height:54px; border-radius:50%; margin:-42px auto 8px; overflow:hidden;
  background:#fff; display:grid; place-items:center; border:6px solid transparent; box-shadow:0 6px 14px rgba(0,0,0,.12)
}
.cch-ranking .medal.g{ border-color:var(--gold) } .cch-ranking .medal.s{ border-color:#C5CFDF } .cch-ranking .medal.b{ border-color:#D18A57 }
.cch-ranking .medal img{ width:100%; height:100%; object-fit:cover }

.cch-ranking .clubname{ font-weight:800; font-size:18px; margin-top:4px }
.cch-ranking .subpt{ font-size:14px; font-weight:700; color:#5b6384; margin-top:4px }
.cch-ranking .ped{ margin-top:12px; border-radius:12px; font-weight:800; padding:6px 0 }
.cch-ranking .ped.g{ background:#FFE8A3; color:#7c5f00 } .cch-ranking .ped.s{ background:#E8EEF6; color:#465a7c } .cch-ranking .ped.b{ background:#F4E0D4; color:#724f34 }

/* ---------- Table ---------- */
.cch-ranking .card{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden }
.cch-ranking table{ width:100%; border-collapse:separate; border-spacing:0 }
.cch-ranking thead th{ text-align:left; font-size:13px; color:white; padding:12px 16px; background:#4871DB }
.cch-ranking tbody td{ padding:16px 16px; border-top:1px solid #EEF0F6; vertical-align:middle; font-size:15px }
.cch-ranking tbody tr:hover{ background: #FAFBFF }
.cch-ranking .col-rank{ width:56px; text-align:center; font-weight:900; color:#6b7090 }

/* Club cell with image avatar + name + sponsor */
.cch-ranking .clubcell{ display:flex; align-items:center; gap:10px; white-space:nowrap }
.cch-ranking .avatar{
  width:28px; height:28px; border-radius:50%; overflow:hidden; display:grid; place-items:center;
  background:#fff; border:2px solid var(--coral); color:#3556D4; font-weight:700
}
.cch-ranking .avatar img{ width:100%; height:100%; object-fit:cover }

.cch-ranking .points{ display:flex; align-items:center; gap:8px; font-weight:800; white-space:nowrap }
.cch-ranking .points svg{ width:16px; height:16px }
.cch-ranking .pill{ display:grid; place-items:center; min-width:30px; height:22px; padding:0 10px; border-radius:999px; background:#EEF2FF; color:#3556D4; font-weight:700; font-size:12px }

/* ---------- Responsive ---------- */
@media (max-width:900px){
  .cch-ranking .podium{ grid-template-columns:1fr }
  .cch-ranking .pod.s2,.cch-ranking .pod.s3{ transform:none }
  .cch-ranking thead th:nth-child(5), .cch-ranking tbody td:nth-child(5){ display:none } /* hide Members on narrow screens */
}

</style>

<section class="cch-ranking">
  <div class="wrap">

    <!-- Header + Search -->
    <div class="head">
      <div class="title">Clubs Ranking</div>
      <div class="search" role="search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a6 6 0 014.8 9.6l4.3 4.3-1.4 1.4-4.3-4.3A6 6 0 1110 4zm0 2a4 4 0 100 8 4 4 0 000-8z"/></svg>
        <input id="rankSearch" type="text" placeholder="Search club name…" autocomplete="off">
      </div>
    </div>

    <!-- Top 3 -->
    <div class="podium">
      <!-- #2 -->
      <article class="pod s2">
        <div class="medal s"><img src="pics/club-b.png" alt="Club B"></div>
        <div class="clubname">Club B</div>
        <div class="sponsor">
          <span class="sponsor-logo"><img src="pics/sponsor-b.png" alt=""></span>
          Sponsored by <strong>TechCorp</strong>
        </div>
        <div class="subpt">1,750 pt</div>
        <div class="ped s">2</div>
      </article>

      <!-- #1 -->
      <article class="pod">
        <div class="medal g"><img src="pics/club-a.png" alt="Club A"></div>
        <div class="clubname">Club A</div>
        <div class="sponsor">
          <span class="sponsor-logo"><img src="pics/sponsor-a.png" alt=""></span>
          Sponsored by <strong>Samsung</strong>
        </div>
        <div class="subpt">1,950 pt</div>
        <div class="ped g">1</div>
      </article>

      <!-- #3 -->
      <article class="pod s3">
        <div class="medal b"><img src="pics/club-c.png" alt="Club C"></div>
        <div class="clubname">Club C</div>
        <div class="sponsor">
          <span class="sponsor-logo"><img src="pics/sponsor-c.png" alt=""></span>
          Sponsored by <strong>ArtWorks</strong>
        </div>
        <div class="subpt">1,700 pt</div>
        <div class="ped b">3</div>
      </article>
    </div>

    <!-- Table -->
    <div class="card" role="region" aria-label="All clubs">
      <table id="clubsTbl">
        <thead style="background:#4871DB">
        <tr>
          <th class="col-rank" style="color:white">Rank</th>
          <th>Club</th>
          <th>Points</th>
          <th>Events</th>
          <th>Members</th>
        </tr>
        </thead>
        <tbody>
          <tr data-name="club d">
            <td class="col-rank">1</td>
            <td class="clubcell">
              <span class="avatar"><img src="pics/club-d.png" alt=""></span>
              <div>
                <span>Club D</span>
                <div class="sponsor small">
                  <span class="sponsor-logo small"><img src="pics/sponsor-d.png" alt=""></span>
                  Sponsored by <strong>Nike</strong>
                </div>
              </div>
            </td>
            <td><span class="points"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/></svg>1950</span></td>
            <td><span class="pill">22</span></td>
            <td><span class="pill">85</span></td>
          </tr>

          <tr data-name="club b">
            <td class="col-rank">2</td>
            <td class="clubcell">
              <span class="avatar"><img src="pics/club-b.png" alt=""></span>
              <div>
                <span>Club B</span>
                <div class="sponsor small">
                  <span class="sponsor-logo small"><img src="pics/sponsor-b.png" alt=""></span>
                  Sponsored by <strong>Puma</strong>
                </div>
              </div>
            </td>
            <td><span class="points"><svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/></svg>1750</span></td>
            <td><span class="pill">20</span></td>
            <td><span class="pill">88</span></td>
          </tr>

          <tr data-name="club c">
            <td class="col-rank">3</td>
            <td class="clubcell">
              <span class="avatar"><img src="pics/club-c.png" alt=""></span>
              <div>
                <span>Club C</span>
                <div class="sponsor small">
                  <span class="sponsor-logo small"><img src="pics/sponsor-c.png" alt=""></span>
                  Sponsored by <strong>Pepsi</strong>
                </div>
              </div>
            </td>
            <td><span class="points"><svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/></svg>1700</span></td>
            <td><span class="pill">13</span></td>
            <td><span class="pill">72</span></td>
          </tr>

          <tr data-name="club a2">
            <td class="col-rank">4</td>
            <td class="clubcell">
              <span class="avatar"><img src="pics/club-a2.png" alt=""></span>
              <div>
                <span>Club A2</span>
                <div class="sponsor small">
                  <span class="sponsor-logo small"><img src="pics/sponsor-a2.png" alt=""></span>
                  Sponsored by <strong>CarePlus</strong>
                </div>
              </div>
            </td>
            <td><span class="points"><svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/></svg>1580</span></td>
            <td><span class="pill">19</span></td>
            <td><span class="pill">88</span></td>
          </tr>

          <tr data-name="club e">
            <td class="col-rank">5</td>
            <td class="clubcell">
              <span class="avatar"><img src="pics/club-e.png" alt=""></span>
              <div>
                <span>Club E</span>
                <div class="sponsor small">
                  <span class="sponsor-logo small"><img src="pics/sponsor-e.png" alt=""></span>
                  Sponsored by <strong>ArtWorks</strong>
                </div>
              </div>
            </td>
            <td><span class="points"><svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/></svg>1530</span></td>
            <td><span class="pill">22</span></td>
            <td><span class="pill">67</span></td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</section>

<script>
/* Search by club name only (case-insensitive) */
(() => {
  const q = document.getElementById('rankSearch');
  const rows = [...document.querySelectorAll('#clubsTbl tbody tr')];
  q.addEventListener('input', () => {
    const s = q.value.trim().toLowerCase();
    rows.forEach(tr => {
      const name = (tr.dataset.name || '').toLowerCase();
      tr.style.display = name.includes(s) ? '' : 'none';
    });
  });
})();
</script>
<?php include 'footer.php'; ?>

</body>
</html>
