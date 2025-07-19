<?php
// Ensure data folder exists
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Auto-generate sample persons.json if not exists
$personsFile = $dataDir . '/persons.json';
if (!file_exists($personsFile)) {
    file_put_contents($personsFile, json_encode([
        [ "id"=>1, "name"=>"Gordon Wong", "office_tel"=>"1234 5678", "Title"=>"Manager", "Location"=>"Mong Kok" ],
        [ "id"=>2, "name"=>"David Cheng", "office_tel"=>"2345 6789", "Title"=>"Developer", "Location"=>"Central" ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Auto-generate sample title.json if not exists
$titleFile = $dataDir . '/title.json';
if (!file_exists($titleFile)) {
    file_put_contents($titleFile, json_encode([
        "Online Meeting",
        "On Leave",
        "Onsite Support",
        "Meeting",
        "Training"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Use SQLite in data folder
$db = new PDO('sqlite:' . $dataDir . '/calendar.sqlite');
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

// Create special_day table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS special_day (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date TEXT NOT NULL UNIQUE,
    description TEXT
)");
?>
