<?php
require_once __DIR__ . '/db.php';

// Helper: get start of week (Sunday)
function getWeekStart($date) {
    $dt = new DateTime($date);
    $dt->modify('Sunday this week');
    return $dt->format('Y-m-d');
}

// Fetch events for a week
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $weekStart = getWeekStart($date);
    $weekEnd = (new DateTime($weekStart))->modify('+6 days')->format('Y-m-d 23:59:59');
    $stmt = $db->prepare("SELECT * FROM events WHERE start BETWEEN ? AND ?");
    $stmt->execute([$weekStart . ' 00:00:00', $weekEnd]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}

// Add event (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Debug: log received data
    // file_put_contents('debug.log', print_r($data, true), FILE_APPEND);

    $stmt = $db->prepare("INSERT INTO events (title, description, start, end, person) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['title'],
        $data['description'] ?? '',
        $data['start'],
        $data['end'],
        $data['person'] ?? ''
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Edit event (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Debug: log received data
    // file_put_contents('debug.log', print_r($data, true), FILE_APPEND);

    $stmt = $db->prepare("UPDATE events SET title=?, description=?, start=?, end=?, person=? WHERE id=?");
    $stmt->execute([
        $data['title'],
        $data['description'] ?? '',
        $data['start'],
        $data['end'],
        $data['person'] ?? '',
        $data['id']
    ]);
    echo json_encode(['success' => true]);
    exit;
}
?>
