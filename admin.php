<?php
session_start();

$ADMIN_USER = 'admin';
$ADMIN_PASS = 'password123'; // Change this to a secure password

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle login
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Invalid username or password";
    }
}

if (empty($_SESSION['admin_logged_in'])):
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Team Calendar</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; }
        .login-box { background: #fff; padding: 32px; margin: 80px auto; width: 320px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        .login-box input { width: 100%; margin-bottom: 12px; padding: 8px; }
        .login-box button { width: 100%; padding: 8px; background: #2196f3; color: #fff; border: none; border-radius: 4px; }
        .error { color: #d32f2f; margin-bottom: 12px; }
        .back-btn { background: #2196f3; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-bottom: 16px; width: 100%; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if (!empty($error)): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="index.php"><button type="button" class="back-btn" style="margin-top:8px;">&larr; Back to Calendar</button></a>
    </div>
</body>
</html>
<?php
exit;
endif;

// If logged in, show event management
require_once __DIR__ . '/db.php';

// Add audit log function
function insert_audit_log($event_id, $action, $title = '', $description = '') {
    global $db;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO audit_log (event_id, action, ip_address, datetime, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$event_id, $action, $ip, $now, $title, $description]);
}

// Handle delete
if (isset($_POST['delete_id'])) {
    $event_id = $_POST['delete_id'];
    // Fetch event info for audit
    $stmt = $db->prepare("SELECT title, description FROM events WHERE id=?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    $title = $event['title'] ?? '';
    $description = $event['description'] ?? '';
    insert_audit_log($event_id, 'delete', $title, $description);
    $stmt = $db->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$event_id]);
}

// Handle add special day
if (isset($_POST['special_date']) && isset($_POST['special_desc'])) {
    $date = $_POST['special_date'];
    $desc = $_POST['special_desc'];
    if ($date) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO special_day (date, description) VALUES (?, ?)");
        $stmt->execute([$date, $desc]);
    }
}

// Handle delete special day
if (isset($_POST['delete_special_id'])) {
    $stmt = $db->prepare("DELETE FROM special_day WHERE id=?");
    $stmt->execute([$_POST['delete_special_id']]);
}

// Handle ICS import for holidays
if (isset($_FILES['ics_file']) && $_FILES['ics_file']['error'] === UPLOAD_ERR_OK) {
    $icsData = file_get_contents($_FILES['ics_file']['tmp_name']);
    $lines = explode("\n", $icsData);
    $date = '';
    $desc = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'DTSTART;VALUE=DATE:') === 0) {
            $date = substr($line, 19);
            $date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        }
        if (strpos($line, 'SUMMARY:') === 0) {
            $desc = substr($line, 8);
        }
        if ($line === 'END:VEVENT' && $date && $desc) {
            $stmt = $db->prepare("INSERT OR IGNORE INTO special_day (date, description) VALUES (?, ?)");
            $stmt->execute([$date, $desc]);
            $date = '';
            $desc = '';
        }
    }
    $import_message = "ICS holidays imported successfully.";
}

// --- Month filter logic ---
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_start = $selected_month . '-01 00:00:00';
$month_end = date('Y-m-t 23:59:59', strtotime($selected_month . '-01'));

// Get all months with events for dropdown
$months_stmt = $db->query("SELECT DISTINCT strftime('%Y-%m', start) as ym FROM events ORDER BY ym DESC");
$months = $months_stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch events for selected month
$stmt = $db->prepare("SELECT * FROM events WHERE start >= ? AND start <= ? ORDER BY start DESC");
$stmt->execute([$month_start, $month_end]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all special days
$special_days = $db->query("SELECT * FROM special_day ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

// Pagination for audit log
$audit_page = isset($_GET['audit_page']) ? max(1, intval($_GET['audit_page'])) : 1;
$audit_limit = 10;
$audit_offset = ($audit_page - 1) * $audit_limit;
$audit_total = $db->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
$audit_logs = $db->query("SELECT * FROM audit_log ORDER BY datetime DESC LIMIT $audit_limit OFFSET $audit_offset")->fetchAll(PDO::FETCH_ASSOC);
$audit_has_next = ($audit_page * $audit_limit) < $audit_total;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Events</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f0f0f0; }
        .logout-btn { float: right; background: #d32f2f; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .delete-btn { background: #d32f2f; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; }
        .back-btn { background: #2196f3; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-bottom: 16px; }
        .audit-table { margin-top: 40px; }
        .audit-table th, .audit-table td { font-size: 0.95em; }
        .audit-nav { margin-top: 10px; text-align: right; }
        .audit-nav button { background: #2196f3; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; cursor: pointer; }
        .month-select-form { margin-bottom: 18px; }
        .month-select-form select { font-size: 1em; padding: 4px 8px; }
        .holiday-row { background: #ffebee; }
        .holiday-label { color: #d32f2f; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <form method="post" style="float:right;">
            <button class="logout-btn" name="logout" type="submit">Logout</button>
        </form>
        <a href="index.php"><button type="button" class="back-btn">&larr; Back to Calendar</button></a>
        <h2>Event Management</h2>
        <form method="get" class="month-select-form">
            <label for="month">Show events for month:</label>
            <select name="month" id="month" onchange="this.form.submit()">
                <?php foreach ($months as $ym): ?>
                    <option value="<?=htmlspecialchars($ym)?>"<?= $ym === $selected_month ? ' selected' : '' ?>>
                        <?=date('F Y', strtotime($ym . '-01'))?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Person</th>
                    <th>Location</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?=htmlspecialchars($ev['id'])?></td>
                    <td><?=htmlspecialchars($ev['title'])?></td>
                    <td><?=htmlspecialchars($ev['description'])?></td>
                    <td><?=htmlspecialchars($ev['person'])?></td>
                    <td><?=htmlspecialchars($ev['location'])?></td>
                    <td><?=htmlspecialchars($ev['start'])?></td>
                    <td><?=htmlspecialchars($ev['end'])?></td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this event?');">
                            <input type="hidden" name="delete_id" value="<?=htmlspecialchars($ev['id'])?>">
                            <button class="delete-btn" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="margin-top:40px;">Audit Log</h2>
        <table class="audit-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event ID</th>
                    <th>Action</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Date Time</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($audit_logs as $log): ?>
                <tr>
                    <td><?=htmlspecialchars($log['id'])?></td>
                    <td><?=htmlspecialchars($log['event_id'])?></td>
                    <td><?=htmlspecialchars($log['action'])?></td>
                    <td><?=htmlspecialchars($log['title'])?></td>
                    <td><?=htmlspecialchars($log['description'])?></td>
                    <td><?=htmlspecialchars($log['ip_address'])?></td>
                    <td><?=htmlspecialchars($log['datetime'])?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="audit-nav">
            <?php if ($audit_page > 1): ?>
                <a href="?audit_page=<?=($audit_page-1)?>"><button>&larr; Previous</button></a>
            <?php endif; ?>
            <?php if ($audit_has_next): ?>
                <a href="?audit_page=<?=($audit_page+1)?>"><button>Next &rarr;</button></a>
            <?php endif; ?>
        </div>

        <h2>Special Day Management (Holiday)</h2>
        <?php
        // Get all years with special days for dropdown
        $years_stmt = $db->query("SELECT DISTINCT strftime('%Y', date) as y FROM special_day ORDER BY y DESC");
        $years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
        $selected_year = isset($_GET['special_year']) ? $_GET['special_year'] : date('Y');
        // Fetch special days for selected year
        $special_days = $db->prepare("SELECT * FROM special_day WHERE strftime('%Y', date) = ? ORDER BY date ASC");
        $special_days->execute([$selected_year]);
        $special_days = $special_days->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <form method="get" style="margin-bottom:12px;">
            <label for="special_year">Show holidays for year:</label>
            <select name="special_year" id="special_year" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?=htmlspecialchars($y)?>"<?= $y == $selected_year ? ' selected' : '' ?>>
                        <?=htmlspecialchars($y)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <form method="post" style="margin-bottom:16px;">
            <input type="date" name="special_date" required>
            <input type="text" name="special_desc" placeholder="Description" required>
            <button type="submit" style="background:#d32f2f; color:#fff; border:none; padding:6px 12px; border-radius:4px;">Add Holiday</button>
        </form>
        <h2>Import Holidays (ICS)</h2>
        <form method="post" enctype="multipart/form-data" style="margin-bottom:24px;">
            <input type="file" name="ics_file" accept=".ics" required>
            <button type="submit" style="background:#2196f3; color:#fff; border:none; padding:8px 16px; border-radius:4px; cursor:pointer;">Import ICS</button>
        </form>
        <?php if (!empty($import_message)): ?>
            <div style="color:#388e3c; margin-bottom:16px;"><?=htmlspecialchars($import_message)?></div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($special_days as $sd): ?>
                <tr class="holiday-row">
                    <td><?=htmlspecialchars($sd['date'])?></td>
                    <td><?=htmlspecialchars($sd['description'])?></td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this holiday?');">
                            <input type="hidden" name="delete_special_id" value="<?=htmlspecialchars($sd['id'])?>">
                            <button class="delete-btn" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
    </div>
</body>
</html>
</body>
</html>
