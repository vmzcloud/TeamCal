<?php
require_once __DIR__ . '/db.php';

// Fetch events for a week only
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');
    // Weekly view (default)
    $weekStart = (new DateTime($date))->modify('Sunday this week')->format('Y-m-d');
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
?>
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
