<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Insert event
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("INSERT INTO events (title, description, start, end, person, location) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['title'] ?? '',
        $data['description'] ?? '',
        $data['start'] ?? '',
        $data['end'] ?? '',
        $data['person'] ?? '',
        $data['location'] ?? ''
    ]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Query events, support optional date range
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;
    if ($start && $end) {
        $stmt = $db->prepare("SELECT * FROM events WHERE start >= ? AND end <= ? ORDER BY start ASC");
        $stmt->execute([$start, $end]);
    } else {
        $stmt = $db->query("SELECT * FROM events ORDER BY start ASC");
    }
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['events' => $events]);
    exit;
}

echo json_encode(['error' => 'Unsupported method']);
?>
