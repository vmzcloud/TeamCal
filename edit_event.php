<?php
require_once __DIR__ . '/db.php';

// Fetch event by id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $db->prepare("SELECT * FROM events WHERE id=?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Event not found.";
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    // Get all checked persons as array and join with comma
    $person = isset($_POST['person']) ? (is_array($_POST['person']) ? implode(', ', $_POST['person']) : $_POST['person']) : '';
    $location = $_POST['location'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';

    $stmt = $db->prepare("UPDATE events SET title=?, description=?, person=?, location=?, start=?, end=? WHERE id=?");
    $stmt->execute([$title, $description, $person, $location, $start, $end, $id]);

    // Insert audit log for update
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO audit_log (event_id, action, ip_address, datetime, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, 'update', $ip, $now, $title, $description]);

    // Change redirect to index.php after update
    header('Location: index.php');
    exit;
}

// Load persons for checkboxes
$persons = [];
if (file_exists(__DIR__ . '/data/persons.json')) {
    $persons = json_decode(file_get_contents(__DIR__ . '/data/persons.json'), true);
}
// Parse selected persons as array
$selected_persons = array_map('trim', explode(',', $event['person'] ?? ''));

// Load title options from title.json
$title_options = [];
if (file_exists(__DIR__ . '/data/title.json')) {
    $title_options = json_decode(file_get_contents(__DIR__ . '/data/title.json'), true);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        label { display: block; margin-top: 12px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 4px; }
        button { background: #2196f3; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 16px; }
        .back-btn { background: #888; margin-right: 8px; }
        .person-checkbox-group label { display: inline-block; margin-right: 12px; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Event</h2>
        <form method="post">
            <label>Title
                <select name="title" required>
                    <option value="">Select Title</option>
                    <?php foreach ($title_options as $t): ?>
                        <option value="<?=htmlspecialchars($t)?>" <?=($event['title'] === $t ? 'selected' : '')?>><?=htmlspecialchars($t)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Description
                <input type="text" name="description" value="<?=htmlspecialchars($event['description'])?>">
            </label>
            <label>Person
                <div class="person-checkbox-group" style="margin-bottom:8px;">
                    <?php foreach ($persons as $p): 
                        $checked = in_array($p['name'], $selected_persons) ? 'checked' : '';
                    ?>
                        <label>
                            <input type="checkbox" name="person[]" value="<?=htmlspecialchars($p['name'])?>" <?=$checked?>> <?=htmlspecialchars($p['name'])?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </label>
            <label>Location
                <input type="text" name="location" value="<?=htmlspecialchars($event['location'])?>">
            </label>
            <label>Start
                <input type="datetime-local" name="start" value="<?=htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['start'])))?>" required>
            </label>
            <label>End
                <input type="datetime-local" name="end" value="<?=htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['end'])))?>" required>
            </label>
            <button type="submit">Update Event</button>
            <a href="index.php" class="back-btn" style="background:#888; color:#fff; padding:8px 16px; border-radius:4px; text-decoration:none; margin-left:8px;">Cancel</a>
        </form>
    </div>
</body>
</html>
