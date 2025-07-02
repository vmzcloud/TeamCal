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
    </div>
</body>
</html>
<?php
exit;
endif;

// If logged in, show event management
require_once __DIR__ . '/db.php';

// Handle delete
if (isset($_POST['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$_POST['delete_id']]);
}

// Fetch all events
$events = $db->query("SELECT * FROM events ORDER BY start DESC")->fetchAll(PDO::FETCH_ASSOC);
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
    </style>
</head>
<body>
    <div class="container">
        <form method="post" style="float:right;">
            <button class="logout-btn" name="logout" type="submit">Logout</button>
        </form>
        <h2>Event Management</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Person</th>
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
    </div>
</body>
</html>
