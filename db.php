<?php
$db = new PDO('sqlite:' . __DIR__ . '/calendar.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create events table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    start DATETIME NOT NULL,
    end DATETIME NOT NULL,
    person TEXT,
    location TEXT
)");

// Ensure the 'person' column exists in the events table
$cols = $db->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
$hasPerson = false;
foreach ($cols as $col) {
    if ($col['name'] === 'person') {
        $hasPerson = true;
        break;
    }
}
if (!$hasPerson) {
    $db->exec("ALTER TABLE events ADD COLUMN person TEXT");
}

// Ensure the 'location' column exists in the events table
$cols = $db->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
$hasLocation = false;
foreach ($cols as $col) {
    if ($col['name'] === 'location') {
        $hasLocation = true;
        break;
    }
}
if (!$hasLocation) {
    $db->exec("ALTER TABLE events ADD COLUMN location TEXT");
}

// Create audit_log table if not exists (add title, description columns)
$db->exec("CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER,
    action TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    datetime DATETIME NOT NULL,
    title TEXT,
    description TEXT
)");
?>
