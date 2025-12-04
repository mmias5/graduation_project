<?php
require_once __DIR__ . '/../config.php';

/**
 * يجيب كل الكلابز الـ active (ونتجاهل "No Club / Not Assigned")
 */
function sponsor_get_discover_clubs(): array {
    global $pdo;

    $sql = "
        SELECT 
            club_id,
            club_name,
            description,
            category,
            logo,
            member_count,
            points
        FROM club
        WHERE status = 'active'
          AND club_id <> 1
        ORDER BY points DESC, club_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * ترتيب الكلابز حسب جدول ranking
 */
function sponsor_get_clubs_ranking(): array {
    global $pdo;

    $sql = "
        SELECT 
            r.rank_position,
            c.club_id,
            c.club_name,
            c.logo,
            c.member_count,
            c.points
        FROM ranking r
        JOIN club c ON c.club_id = r.club_id
        ORDER BY r.period_start DESC, r.period_end DESC, r.rank_position ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * الأحداث القادمة (Upcoming Events)
 */
function sponsor_get_upcoming_events(): array {
    global $pdo;

    $sql = "
        SELECT 
            e.*,
            c.club_name
        FROM event e
        JOIN club c ON c.club_id = e.club_id
        WHERE e.starting_date >= NOW()
        ORDER BY e.starting_date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * الأحداث السابقة (Past Events)
 */
function sponsor_get_past_events(): array {
    global $pdo;

    $sql = "
        SELECT 
            e.*,
            c.club_name
        FROM event e
        JOIN club c ON c.club_id = e.club_id
        WHERE e.starting_date < NOW()
        ORDER BY e.starting_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * تفاصيل event واحد (لو بدنا نستخدمها لاحقًا في eventpage.php)
 */
function sponsor_get_event_by_id(int $event_id): ?array {
    global $pdo;

    $sql = "
        SELECT 
            e.*,
            c.club_name,
            c.club_id
        FROM event e
        JOIN club c ON c.club_id = e.club_id
        WHERE e.event_id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $event_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
/**
 * تفاصيل club واحد (لو بدنا نربط clubpage.php بالـ DB)
 */
function sponsor_get_club_by_id(int $club_id): ?array {
    global $pdo;

    $sql = "
        SELECT 
            *
        FROM club
        WHERE club_id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $club_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
function require_sponsor_login(): void {
    if (empty($_SESSION['sponsor_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * رجع بيانات السبونسر الحالي (أو null لو مش لاقيه)
 */
function get_logged_in_sponsor(): ?array {
    global $conn;

    if (empty($_SESSION['sponsor_id'])) {
        return null;
    }

    $sponsorId = (int) $_SESSION['sponsor_id'];

    $sql  = "SELECT * FROM sponsor WHERE sponsor_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $sponsorId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

/* ========== DATA HELPERS FOR HOME PAGE ========== */

/**
 * Best of Campus this Month — توب كلَبز للـ homepage
 * تستعملها sponsor/index.php
 *
 * نعتمد على جدول ranking + club + sponsor_club_support + sponsor
 */
function get_top_clubs_for_home(int $limit = 3): array {
    global $conn;

    // أحدث فترة في جدول ranking
    $periodSql = "SELECT MAX(period_start) AS latest_period FROM ranking";
    $periodRes = $conn->query($periodSql);
    $periodRow = $periodRes ? $periodRes->fetch_assoc() : null;
    $latestPeriod = $periodRow && $periodRow['latest_period'] ? $periodRow['latest_period'] : null;

    if (!$latestPeriod) {
        return [];
    }

    $sql = "
        SELECT 
            c.club_id,
            c.club_name,
            c.logo,
            c.category,
            r.total_points,
            r.rank_position,
            -- ممكن يكون أكتر من سبونسر لنفس الكلب، بناخد أي واحد (بسبب GROUP BY)
            s.company_name AS sponsor_name
        FROM ranking r
        JOIN club c 
              ON r.club_id = c.club_id
        LEFT JOIN sponsor_club_support sc 
              ON sc.club_id = c.club_id
        LEFT JOIN sponsor s
              ON s.sponsor_id = sc.sponsor_id
        WHERE r.period_start = ?
        ORDER BY r.rank_position ASC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('si', $latestPeriod, $limit);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

/**
 * Highlighted / Upcoming Events للـ homepage
 * نعتمد على جدول event + club
 */
function get_upcoming_events(int $limit = 6): array {
    global $conn;

    $sql = "
        SELECT 
            e.event_id,
            e.event_name,
            e.description,
            e.event_location,
            e.max_attendees,
            e.starting_date,
            e.ending_date,
            e.banner_image,
            c.club_name
        FROM event e
        JOIN club c ON e.club_id = c.club_id
        WHERE e.starting_date >= NOW()
        ORDER BY e.starting_date ASC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

/**
 * Clubs to Watch — قائمة كلَبز أكتيف للـ discover
 * نعتمد على جدول club + event لعدد الإيفينتات
 */
function get_active_clubs_for_discover(int $limit = 6): array {
    global $conn;

    $sql = "
        SELECT 
            c.club_id,
            c.club_name,
            c.description,
            c.category,
            c.logo,
            c.member_count,
            c.points,
            COUNT(e.event_id) AS events_count
        FROM club c
        LEFT JOIN event e 
               ON e.club_id = c.club_id
        WHERE c.status = 'active'
        GROUP BY c.club_id
        ORDER BY events_count DESC, c.points DESC, c.club_name ASC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

/* ========== EXTRA HELPERS FOR OTHER SPONSOR PAGES ========== */

/**
 * Club Ranking page — تستخدم جدول ranking + club
 */
function get_club_ranking(int $limit = 50): array {
    global $conn;

    // نفس فكرة latest period
    $periodSql = "SELECT MAX(period_start) AS latest_period FROM ranking";
    $periodRes = $conn->query($periodSql);
    $periodRow = $periodRes ? $periodRes->fetch_assoc() : null;
    $latestPeriod = $periodRow && $periodRow['latest_period'] ? $periodRow['latest_period'] : null;

    if (!$latestPeriod) {
        return [];
    }

    $sql = "
        SELECT 
            r.ranking_id,
            r.club_id,
            r.period_start,
            r.period_end,
            r.total_points,
            r.rank_position,
            c.club_name,
            c.logo,
            c.category,
            c.member_count,
            c.points
        FROM ranking r
        JOIN club c ON r.club_id = c.club_id
        WHERE r.period_start = ?
        ORDER BY r.rank_position ASC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('si', $latestPeriod, $limit);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

/**
 * Upcoming events list page (upcoming_events.php)
 */
function get_upcoming_events_list(int $limit = 50): array {
    return get_upcoming_events($limit);
}

/**
 * Past events list page (past_events.php)
 */
function get_past_events_list(int $limit = 50): array {
    global $conn;

    $sql = "
        SELECT 
            e.event_id,
            e.event_name,
            e.description,
            e.event_location,
            e.max_attendees,
            e.starting_date,
            e.ending_date,
            e.banner_image,
            e.attendees_count,
            c.club_name
        FROM event e
        JOIN club c ON e.club_id = c.club_id
        WHERE e.starting_date < NOW()
        ORDER BY e.starting_date DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

/**
 * تفاصيل كلَب معيّن (club_page.php)
 */
function get_club_details(int $clubId): ?array {
    global $conn;

    $sql  = "
        SELECT 
            club_id,
            club_name,
            description,
            category,
            social_media_link,
            facebook_url,
            instagram_url,
            linkedin_url,
            logo,
            creation_date,
            status,
            contact_email,
            member_count,
            points
        FROM club
        WHERE club_id = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $clubId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

/**
 * أحداث تابعة لكلَب معيّن (تستخدم في club_page.php)
 */
function get_events_for_club(int $clubId): array {
    global $conn;

    $sql  = "
        SELECT 
            event_id,
            event_name,
            description,
            event_location,
            max_attendees,
            starting_date,
            ending_date,
            attendees_count,
            banner_image
        FROM event
        WHERE club_id = ?
        ORDER BY starting_date DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $clubId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

/**
 * تفاصيل حدث معيّن (event_details.php)
 */
function get_event_details(int $eventId): ?array {
    global $conn;

    $sql = "
        SELECT 
            e.*,
            c.club_name,
            c.logo AS club_logo,
            c.category
        FROM event e
        JOIN club c ON e.club_id = c.club_id
        WHERE e.event_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}
