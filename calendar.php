<?php
require_once __DIR__ . '/db.php';

// Fetch events for a week only
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $weekStartObj = (new DateTime($date))->modify('Sunday this week');
    $weekStart = $weekStartObj->format('Y-m-d');
    $weekEndObj = (clone $weekStartObj)->modify('+6 days');
    $weekEnd = $weekEndObj->format('Y-m-d');

    // Fetch all events that overlap with the week (not just those starting in the week)
    $stmt = $db->prepare("SELECT * FROM events WHERE (date(start) <= ? AND date(end) >= ?)");
    $stmt->execute([$weekEnd, $weekStart]);
    $eventsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Expand multi-day events to each day in the week (including Sunday and Saturday correctly)
    $events = [];
    foreach ($eventsRaw as $ev) {
        $evStart = new DateTime($ev['start']);
        $evEnd = new DateTime($ev['end']);
        $cur = clone $weekStartObj;
        $last = clone $weekEndObj;
        while ($cur <= $last) {
            $curDate = $cur->format('Y-m-d');
            // Only add event for days that are within the event's start and end (inclusive)
            if ($curDate >= $evStart->format('Y-m-d') && $curDate <= $evEnd->format('Y-m-d')) {
                $eventCopy = $ev;
                // Use event's start time on first day, event's end time on last day, 00:00/23:59 otherwise
                $startTime = ($curDate === $evStart->format('Y-m-d')) ? substr($ev['start'], 11, 5) : '00:00';
                $endTime = ($curDate === $evEnd->format('Y-m-d')) ? substr($ev['end'], 11, 5) : '23:59';
                $eventCopy['start'] = $curDate . ' ' . $startTime . ':00';
                $eventCopy['end'] = $curDate . ' ' . $endTime . ':00';
                $events[] = $eventCopy;
            }
            $cur->modify('+1 day');
        }
    }

    // Fetch special days for this week
    $stmt2 = $db->prepare("SELECT date, description FROM special_day WHERE date BETWEEN ? AND ?");
    $stmt2->execute([$weekStart, $weekEnd]);
    $special_days = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'events' => $events,
        'special_days' => $special_days
    ]);
    exit;
}

function insert_audit_log($event_id, $action, $title = '', $description = '') {
    global $db;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO audit_log (event_id, action, ip_address, datetime, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$event_id, $action, $ip, $now, $title, $description]);
}

// Add event (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Multi-day event support: split into daily events if start and end are not the same date
    $start = isset($data['start']) ? $data['start'] : '';
    $end = isset($data['end']) ? $data['end'] : '';
    if ($start && $end && substr($start, 0, 10) !== substr($end, 0, 10)) {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $current = clone $startDate;
        while ($current <= $endDate) {
            $eventStart = $current->format('Y-m-d') . ($current->format('Y-m-d') === substr($start, 0, 10) ? substr($start, 10) : ' 00:00:00');
            $eventEnd = $current->format('Y-m-d') . ($current->format('Y-m-d') === substr($end, 0, 10) ? substr($end, 10) : ' 23:59:59');
            $stmt = $db->prepare("INSERT INTO events (title, description, start, end, person, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $eventStart,
                $eventEnd,
                $data['person'] ?? '',
                $data['location'] ?? ''
            ]);
            $current->modify('+1 day');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Single-day event
    $stmt = $db->prepare("INSERT INTO events (title, description, start, end, person, location) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['title'],
        $data['description'] ?? '',
        $data['start'],
        $data['end'],
        $data['person'] ?? '',
        $data['location'] ?? ''
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Edit event (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    $stmt = $db->prepare("UPDATE events SET title=?, description=?, start=?, end=?, person=?, location=? WHERE id=?");
    $stmt->execute([
        $data['title'],
        $data['description'] ?? '',
        $data['start'],
        $data['end'],
        $data['person'] ?? '',
        $data['location'] ?? '',
        $data['id']
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Delete event (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $event_id = $data['id'] ?? null;
    $title = '';
    $description = '';
    if ($event_id) {
        // Fetch event info for audit before delete
        $stmt = $db->prepare("SELECT title, description FROM events WHERE id=?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        $title = $event['title'] ?? '';
        $description = $event['description'] ?? '';
        insert_audit_log($event_id, 'delete', $title, $description);
    }
    $stmt = $db->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$event_id]);
    echo json_encode(['success' => true]);
    exit;
}
?>
}
?>
