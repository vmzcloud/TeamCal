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
    person TEXT
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
?>
