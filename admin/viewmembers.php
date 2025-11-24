<?php
// Dummy data – later load by club_id from DB
$members = [
    [
        "id" => 1,
        "name" => "Lina",
        "email" => "member1@university.edu",
        "major" => "CS",
        "student_id" => "0225757",
        "joined" => "2025-01-01",
        "avatar" => "assets/members/lina.jpg",
        "role" => "President"
    ],
    [
        "id" => 2,
        "name" => "Omar",
        "email" => "member2@university.edu",
        "major" => "IT",
        "student_id" => "0225758",
        "joined" => "2025-02-04",
        "avatar" => "assets/members/omar.jpg",
        "role" => "Member"
    ],
    [
        "id" => 3,
        "name" => "Sara",
        "email" => "member3@university.edu",
        "major" => "Business",
        "student_id" => "0225759",
        "joined" => "2025-03-07",
        "avatar" => "assets/members/sara.jpg",
        "role" => "Member"
    ],
    [
        "id" => 4,
        "name" => "Mustafa",
        "email" => "member4@university.edu",
        "major" => "Design",
        "student_id" => "0225760",
        "joined" => "2025-04-10",
        "avatar" => "assets/members/mustafa.jpg",
        "role" => "Member"
    ],
    [
        "id" => 5,
        "name" => "Noor",
        "email" => "member5@university.edu",
        "major" => "CS",
        "student_id" => "0225761",
        "joined" => "2025-05-13",
        "avatar" => "assets/members/noor.jpg",
        "role" => "Member"
    ],
    [
        "id" => 6,
        "name" => "Jad",
        "email" => "member6@university.edu",
        "major" => "IT",
        "student_id" => "0225762",
        "joined" => "2025-06-16",
        "avatar" => "assets/members/jad.jpg",
        "role" => "Member"
    ],
    [
        "id" => 7,
        "name" => "Maya",
        "email" => "member7@university.edu",
        "major" => "Business",
        "student_id" => "0225763",
        "joined" => "2025-07-19",
        "avatar" => "assets/members/maya.jpg",
        "role" => "Member"
    ],
    [
        "id" => 8,
        "name" => "Hiba",
        "email" => "member8@university.edu",
        "major" => "Design",
        "student_id" => "0225764",
        "joined" => "2025-08-22",
        "avatar" => "assets/members/hiba.jpg",
        "role" => "Member"
    ],
];

$totalMembers = count($members);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Club Members</title>
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

  --sidebarWidth:260px; /* for sidebar.php */
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

/* ===== Main content (sidebar is in sidebar.php) ===== */
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}

.header-row{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  margin-bottom:20px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  color:var(--ink);
}

.total-count{
  font-size:.95rem;
  color:var(--muted);
}

/* Search bar */
.search-wrapper{
  background:#ffffff;
  padding:14px 16px;
  border-radius:999px;
  box-shadow:0 10px 26px rgba(15,23,42,.18);
  margin-bottom:26px;
}

.search-input{
  width:100%;
  border:none;
  outline:none;
  font-size:.96rem;
  color:var(--ink);
}

.search-input::placeholder{
  color:#9ca3af;
}

/* Members grid */
.members-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(360px,1fr));
  gap:18px 20px;
}

/* Member card */
.member-card{
  background:var(--card);
  border-radius:20px;
  box-shadow:0 16px 34px rgba(15,23,42,.16);
  padding:16px 18px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.member-left{
  display:flex;
  gap:14px;
  align-items:flex-start;
}

.avatar{
  width:52px;
  height:52px;
  border-radius:50%;
  object-fit:cover;
  background:#e5e7eb;
  flex-shrink:0;
}

.member-info{
  display:flex;
  flex-direction:column;
  gap:3px;
}

.member-name{
  font-weight:800;
  font-size:1rem;
  color:var(--ink);
}

.member-email{
  font-size:.9rem;
  color:var(--muted);
}

.member-meta{
  font-size:.86rem;
  color:var(--muted);
}

/* Role badge */
.role-badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:5px 12px;
  border-radius:999px;
  font-size:.8rem;
  font-weight:600;
  margin-top:6px;
}

.role-president{
  background:var(--coral);
  color:#ffffff;
}

.role-member{
  background:#e5e7eb;
  color:#374151;
}

/* Right side button */
.member-right{
  display:flex;
  align-items:center;
}

.make-president-btn{
  padding:9px 16px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.88rem;
  font-weight:600;
  background:#242751;
  color:#ffffff;
  transition:background .15s ease, transform .1s ease, opacity .1s ease;
}

.make-president-btn:hover{
  background:#181b3b;
  transform:translateY(-1px);
}

/* Style when this member is president (but still clickable) */
.make-president-btn.is-president{
  background:#ffffff;
  color:var(--navy);
  border:2px solid var(--navy);
}

@media(max-width:900px){
  .members-grid{
    grid-template-columns:1fr;
  }
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">

  <div class="header-row">
    <div class="page-title">Club Members</div>
    <div class="total-count"><?= $totalMembers ?> total</div>
  </div>

  <div class="search-wrapper">
    <input
      type="text"
      id="searchMembers"
      class="search-input"
      placeholder="Search by name..."
    >
  </div>

  <div class="members-grid" id="membersGrid">
    <?php foreach($members as $m): ?>
      <div
        class="member-card"
        data-name="<?= strtolower($m['name']); ?>"
        data-role="<?= strtolower($m['role']); ?>"
        data-id="<?= $m['id']; ?>"
      >
        <div class="member-left">
          <img src="<?= $m['avatar']; ?>" alt="Avatar" class="avatar">

          <div class="member-info">
            <div class="member-name"><?= $m['name']; ?></div>
            <div class="member-email"><?= $m['email']; ?></div>
            <div class="member-meta">
              <?= $m['major']; ?> · <?= $m['student_id']; ?> · Joined <?= $m['joined']; ?>
            </div>

            <?php if($m['role'] === 'President'): ?>
              <span class="role-badge role-president">President</span>
            <?php else: ?>
              <span class="role-badge role-member">Member</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="member-right">
          <button
            class="make-president-btn <?= $m['role'] === 'President' ? 'is-president' : '' ?>"
            type="button"
            data-id="<?= $m['id']; ?>"
          >
            Make President
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
// ===== Search by name =====
const searchInput = document.getElementById('searchMembers');
const memberCards = document.querySelectorAll('.member-card');

searchInput.addEventListener('input', () => {
  const q = searchInput.value.toLowerCase().trim();

  memberCards.forEach(card => {
    const name = card.dataset.name;
    card.style.display = !q || name.includes(q) ? 'flex' : 'none';
  });
});

// ===== Make President logic (front-end) =====
function getCurrentPresidentCard() {
  return document.querySelector('.member-card[data-role="president"]');
}

function demote(card) {
  if (!card) return;

  card.dataset.role = 'member';

  const badge = card.querySelector('.role-badge');
  if (badge) {
    badge.textContent = 'Member';
    badge.classList.remove('role-president');
    badge.classList.add('role-member');
  }

  const btn = card.querySelector('.make-president-btn');
  if (btn) {
    btn.classList.remove('is-president');
  }
}

function promote(card) {
  if (!card) return;

  card.dataset.role = 'president';

  const badge = card.querySelector('.role-badge');
  if (badge) {
    badge.textContent = 'President';
    badge.classList.remove('role-member');
    badge.classList.add('role-president');
  }

  const btn = card.querySelector('.make-president-btn');
  if (btn) {
    btn.classList.add('is-president');
  }
}

// Attach one handler to all buttons
document.querySelectorAll('.make-president-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const newCard = btn.closest('.member-card');
    if (!newCard) return;

    const current = getCurrentPresidentCard();

    // If clicked on the same president, do nothing
    if (current === newCard) return;

    // Demote old president (if exists), promote new one
    demote(current);
    promote(newCard);

    // TODO: send AJAX / form request to backend to persist change
  });
});
</script>

</body>
</html>
