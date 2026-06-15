<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'month_aggregate':
        getMonthAggregate();
        break;
    case 'week_aggregate':
        getWeekAggregate();
        break;
    case 'date_detail':
        getDateDetail();
        break;
    case 'badge_stats':
        getBadgeStats();
        break;
    default:
        jsonResponse(['success' => false, 'message' => '无效的操作'], 400);
}

function getMonthAggregate() {
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
    $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $priorities = isset($_GET['priorities']) ? explode(',', $_GET['priorities']) : [];

    if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
        jsonResponse(['success' => false, 'message' => '日期参数无效'], 400);
    }

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    $conn = getConnection();

    $whereClauses = ["DATE(publish_date) BETWEEN ? AND ?", "status = 'published'"];
    $params = [$startDate, $endDate];
    $types = 'ss';

    if (!empty($priorities)) {
        $validPriorities = ['high', 'medium', 'low'];
        $filteredPriorities = array_values(array_intersect($priorities, $validPriorities));
        if (!empty($filteredPriorities)) {
            $placeholders = implode(',', array_fill(0, count($filteredPriorities), '?'));
            $whereClauses[] = "priority IN ($placeholders)";
            foreach ($filteredPriorities as $p) {
                $params[] = $p;
                $types .= 's';
            }
        }
    }

    $whereSql = implode(' AND ', $whereClauses);

    $sql = "SELECT 
                id, title, priority, publish_date, author,
                DATE(publish_date) as date_only
            FROM notices 
            WHERE $whereSql
            ORDER BY publish_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $days = [];
    for ($d = 1; $d <= intval(date('t', strtotime($startDate))); $d++) {
        $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $days[$dateKey] = [
            'date' => $dateKey,
            'total' => 0,
            'high_count' => 0,
            'medium_count' => 0,
            'low_count' => 0,
            'notices' => []
        ];
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dateKey = $row['date_only'];
            if (!isset($days[$dateKey])) {
                $days[$dateKey] = [
                    'date' => $dateKey,
                    'total' => 0,
                    'high_count' => 0,
                    'medium_count' => 0,
                    'low_count' => 0,
                    'notices' => []
                ];
            }
            $days[$dateKey]['total']++;
            $days[$dateKey][$row['priority'] . '_count']++;

            if (count($days[$dateKey]['notices']) < 3) {
                $days[$dateKey]['notices'][] = [
                    'id' => intval($row['id']),
                    'title' => $row['title'],
                    'priority' => $row['priority'],
                    'author' => $row['author'],
                    'publish_time' => date('H:i', strtotime($row['publish_date']))
                ];
            }
        }
    }

    if (isset($stmt)) $stmt->close();
    closeConnection($conn);

    jsonResponse([
        'success' => true,
        'data' => [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => array_values($days)
        ]
    ]);
}

function getWeekAggregate() {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'));
    $priorities = isset($_GET['priorities']) ? explode(',', $_GET['priorities']) : [];

    $startTs = strtotime($startDate);
    $endTs = strtotime($endDate);

    if (!$startTs || !$endTs || $startTs > $endTs) {
        jsonResponse(['success' => false, 'message' => '日期参数无效'], 400);
    }

    $conn = getConnection();

    $whereClauses = ["DATE(publish_date) BETWEEN ? AND ?", "status = 'published'"];
    $params = [$startDate, $endDate];
    $types = 'ss';

    if (!empty($priorities)) {
        $validPriorities = ['high', 'medium', 'low'];
        $filteredPriorities = array_values(array_intersect($priorities, $validPriorities));
        if (!empty($filteredPriorities)) {
            $placeholders = implode(',', array_fill(0, count($filteredPriorities), '?'));
            $whereClauses[] = "priority IN ($placeholders)";
            foreach ($filteredPriorities as $p) {
                $params[] = $p;
                $types .= 's';
            }
        }
    }

    $whereSql = implode(' AND ', $whereClauses);

    $sql = "SELECT 
                id, title, priority, publish_date, author,
                DATE(publish_date) as date_only
            FROM notices 
            WHERE $whereSql
            ORDER BY publish_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $days = [];
    $currentTs = $startTs;
    while ($currentTs <= $endTs) {
        $dateKey = date('Y-m-d', $currentTs);
        $days[$dateKey] = [
            'date' => $dateKey,
            'weekday' => date('N', $currentTs),
            'total' => 0,
            'high_count' => 0,
            'medium_count' => 0,
            'low_count' => 0,
            'notices' => []
        ];
        $currentTs = strtotime('+1 day', $currentTs);
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dateKey = $row['date_only'];
            if (!isset($days[$dateKey])) {
                $days[$dateKey] = [
                    'date' => $dateKey,
                    'weekday' => intval(date('N', strtotime($dateKey))),
                    'total' => 0,
                    'high_count' => 0,
                    'medium_count' => 0,
                    'low_count' => 0,
                    'notices' => []
                ];
            }
            $days[$dateKey]['total']++;
            $days[$dateKey][$row['priority'] . '_count']++;

            if (count($days[$dateKey]['notices']) < 3) {
                $days[$dateKey]['notices'][] = [
                    'id' => intval($row['id']),
                    'title' => $row['title'],
                    'priority' => $row['priority'],
                    'author' => $row['author'],
                    'publish_time' => date('H:i', strtotime($row['publish_date']))
                ];
            }
        }
    }

    if (isset($stmt)) $stmt->close();
    closeConnection($conn);

    jsonResponse([
        'success' => true,
        'data' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => array_values($days)
        ]
    ]);
}

function getDateDetail() {
    $date = $_GET['date'] ?? date('Y-m-d');
    $priorities = isset($_GET['priorities']) ? explode(',', $_GET['priorities']) : [];

    $ts = strtotime($date);
    if (!$ts) {
        jsonResponse(['success' => false, 'message' => '日期参数无效'], 400);
    }

    $conn = getConnection();

    $whereClauses = ["DATE(publish_date) = ?", "status = 'published'"];
    $params = [$date];
    $types = 's';

    if (!empty($priorities)) {
        $validPriorities = ['high', 'medium', 'low'];
        $filteredPriorities = array_values(array_intersect($priorities, $validPriorities));
        if (!empty($filteredPriorities)) {
            $placeholders = implode(',', array_fill(0, count($filteredPriorities), '?'));
            $whereClauses[] = "priority IN ($placeholders)";
            foreach ($filteredPriorities as $p) {
                $params[] = $p;
                $types .= 's';
            }
        }
    }

    $whereSql = implode(' AND ', $whereClauses);

    $sql = "SELECT 
                id, title, content, priority, publish_date, author, category, views
            FROM notices 
            WHERE $whereSql
            ORDER BY 
                CASE priority 
                    WHEN 'high' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 3 
                    ELSE 4 
                END,
                publish_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $notices = [];
    $stats = ['total' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats['total']++;
            $stats[$row['priority']]++;

            $notices[] = [
                'id' => intval($row['id']),
                'title' => $row['title'],
                'content' => $row['content'],
                'priority' => $row['priority'],
                'publish_time' => date('Y-m-d H:i', strtotime($row['publish_date'])),
                'author' => $row['author'],
                'category' => $row['category'] ?? '',
                'views' => intval($row['views']),
                'excerpt' => mb_substr(strip_tags($row['content']), 0, 100, 'UTF-8')
            ];
        }
    }

    if (isset($stmt)) $stmt->close();
    closeConnection($conn);

    jsonResponse([
        'success' => true,
        'data' => [
            'date' => $date,
            'stats' => $stats,
            'notices' => $notices
        ]
    ]);
}

function getBadgeStats() {
    $type = $_GET['type'] ?? 'month';
    $priorities = isset($_GET['priorities']) ? explode(',', $_GET['priorities']) : [];

    $conn = getConnection();

    if ($type === 'month') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            jsonResponse(['success' => false, 'message' => '日期参数无效'], 400);
        }

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
    } else {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'));

        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if (!$startTs || !$endTs || $startTs > $endTs) {
            jsonResponse(['success' => false, 'message' => '日期参数无效'], 400);
        }
    }

    $whereClauses = ["DATE(publish_date) BETWEEN ? AND ?", "status = 'published'"];
    $params = [$startDate, $endDate];
    $types = 'ss';

    if (!empty($priorities)) {
        $validPriorities = ['high', 'medium', 'low'];
        $filteredPriorities = array_values(array_intersect($priorities, $validPriorities));
        if (!empty($filteredPriorities)) {
            $placeholders = implode(',', array_fill(0, count($filteredPriorities), '?'));
            $whereClauses[] = "priority IN ($placeholders)";
            foreach ($filteredPriorities as $p) {
                $params[] = $p;
                $types .= 's';
            }
        }
    }

    $whereSql = implode(' AND ', $whereClauses);

    $sql = "SELECT 
                DATE(publish_date) as date_only,
                priority,
                COUNT(*) as count
            FROM notices 
            WHERE $whereSql
            GROUP BY DATE(publish_date), priority
            ORDER BY date_only, priority";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $badges = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dateKey = $row['date_only'];
            if (!isset($badges[$dateKey])) {
                $badges[$dateKey] = [
                    'date' => $dateKey,
                    'total' => 0,
                    'high' => 0,
                    'medium' => 0,
                    'low' => 0
                ];
            }
            $badges[$dateKey][$row['priority']] = intval($row['count']);
            $badges[$dateKey]['total'] += intval($row['count']);
        }
    }

    if (isset($stmt)) $stmt->close();
    closeConnection($conn);

    jsonResponse([
        'success' => true,
        'data' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'badges' => array_values($badges)
        ]
    ]);
}
?>
