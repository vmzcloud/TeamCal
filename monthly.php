<?php
require_once __DIR__ . '/db.php';

// Get month and year from query or default to current
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// First and last day of month
$firstDay = date('Y-m-01', strtotime("$year-$month-01"));
$lastDay = date('Y-m-t', strtotime($firstDay));

// Load title options from title.json
$title_options = [];
if (file_exists(__DIR__ . '/title.json')) {
    $title_options = json_decode(file_get_contents(__DIR__ . '/title.json'), true);
}

// Get selected titles from GET
$selected_titles = isset($_GET['titles']) && is_array($_GET['titles']) ? $_GET['titles'] : [];

// Fetch events for this month, filter by title if selected
$query = "SELECT * FROM events WHERE start BETWEEN ? AND ?";
$params = [$firstDay . ' 00:00:00', $lastDay . ' 23:59:59'];
if (!empty($selected_titles)) {
    $in = implode(',', array_fill(0, count($selected_titles), '?'));
    $query .= " AND title IN ($in)";
    $params = array_merge($params, $selected_titles);
}
$query .= " ORDER BY start ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group events by date, sort each day's events by start time
$eventsByDate = [];
foreach ($events as $ev) {
    $date = substr($ev['start'], 0, 10);
    if (!isset($eventsByDate[$date])) $eventsByDate[$date] = [];
    $eventsByDate[$date][] = $ev;
}
foreach ($eventsByDate as &$evList) {
    usort($evList, function($a, $b) {
        return strcmp($a['start'], $b['start']);
    });
}
unset($evList);

// For navigation
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear = $month == 12 ? $year + 1 : $year;

// Fetch special days for this month
$stmt2 = $db->prepare("SELECT date, description FROM special_day WHERE date BETWEEN ? AND ?");
$stmt2->execute([$firstDay, $lastDay]);
$special_days = [];
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $sd) {
    $special_days[$sd['date']] = $sd['description'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Monthly View - Team Calendar</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; width: 14.28%; min-height: 80px; }
        th { background: #f0f0f0; }
        .event { background: #e0f7fa; margin: 2px 0; padding: 2px 4px; border-radius: 3px; font-size: 0.97em; }
        .today-cell { background: #fffde7 !important; border: 2px solid #ffb300 !important; }
        .holiday-cell { background: #ffebee !important; border: 2px solid #d32f2f !important; }
        .holiday-label { color: #d32f2f; font-weight: bold; font-size: 0.97em; }
        .nav-btn { background: #2196f3; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; cursor: pointer; font-size: 1em; margin: 0 8px; }
        .month-title { font-size: 2em; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .back-btn { background: #888; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-bottom: 16px; }
        .filter-form { margin-bottom: 18px; }
        .filter-form select { font-size: 1em; padding: 4px 8px; min-width: 180px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php"><button class="back-btn">&larr; Back to Weekly View</button></a>
        <form method="get" class="filter-form" style="display:flex;align-items:center;gap:12px;">
            <input type="hidden" name="year" value="<?=htmlspecialchars($year)?>">
            <input type="hidden" name="month" value="<?=htmlspecialchars($month)?>">
            <label for="titles">Filter by Title:</label>
            <select name="titles[]" id="titles" multiple size="1" onchange="this.form.submit()">
                <?php foreach ($title_options as $t): ?>
                    <option value="<?=htmlspecialchars($t)?>"<?= in_array($t, $selected_titles) ? ' selected' : '' ?>><?=htmlspecialchars($t)?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Filter</button></noscript>
            <?php if (!empty($selected_titles)): ?>
                <a href="?year=<?=htmlspecialchars($year)?>&month=<?=htmlspecialchars($month)?>">
                    <button type="button" style="background:#888; color:#fff; border:none; padding:6px 16px; border-radius:4px; cursor:pointer;">Reset Filter</button>
                </a>
            <?php endif; ?>
        </form>
        <div class="month-title">
            <a href="?year=<?=$prevYear?>&month=<?=$prevMonth?><?=!empty($selected_titles)?'&'.http_build_query(['titles'=>$selected_titles]):''?>"><button class="nav-btn">&larr;</button></a>
            <?=date('F Y', strtotime("$year-$month-01"))?>
            <a href="?year=<?=$nextYear?>&month=<?=$nextMonth?><?=!empty($selected_titles)?'&'.http_build_query(['titles'=>$selected_titles]):''?>"><button class="nav-btn">&rarr;</button></a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Sunday</th>
                    <th>Monday</th>
                    <th>Tuesday</th>
                    <th>Wednesday</th>
                    <th>Thursday</th>
                    <th>Friday</th>
                    <th>Saturday</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $firstDayOfWeek = date('w', strtotime($firstDay));
            $daysInMonth = date('t', strtotime($firstDay));
            $today = date('Y-m-d');
            $day = 1;
            for ($row = 0; $day <= $daysInMonth; $row++) {
                echo '<tr>';
                for ($col = 0; $col < 7; $col++) {
                    if (($row == 0 && $col < $firstDayOfWeek) || $day > $daysInMonth) {
                        echo '<td></td>';
                    } else {
                        $cellDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = $cellDate === $today;
                        $isHoliday = isset($special_days[$cellDate]);
                        $tdClass = $isToday ? 'today-cell' : '';
                        if ($isHoliday) $tdClass .= ($tdClass ? ' ' : '') . 'holiday-cell';
                        echo "<td class='$tdClass'>";
                        echo "<div style='text-align:center;'><strong>$day</strong></div>";
                        if ($isHoliday) {
                            echo "<div class='holiday-label'>{$special_days[$cellDate]}</div>";
                        }
                        if (isset($eventsByDate[$cellDate])) {
                            foreach ($eventsByDate[$cellDate] as $ev) {
                                $personName = $ev['person'] ? "<div style='font-size:0.95em;color:#1976d2;'>👤 ".htmlspecialchars($ev['person'])."</div>" : '';
                                $location = $ev['location'] ? "<div style='font-size:0.95em;color:#388e3c;'>📍 ".htmlspecialchars($ev['location'])."</div>" : '';
                                $desc = $ev['description'] ? "<div style='font-size:0.95em;color:#555;'>".htmlspecialchars($ev['description'])."</div>" : '';
                                $editIcon = "<a href='edit_event.php?id=".urlencode($ev['id'])."' title='Edit Event' style='margin-left:6px;vertical-align:middle;'><span style='font-size:1.1em;cursor:pointer;'>&#9998;</span></a>";
                                echo "<div class='event'><div><strong>".htmlspecialchars($ev['title'])."</strong> (".substr($ev['start'],11,5)."-".substr($ev['end'],11,5).") $editIcon</div>$location$desc$personName</div>";
                            }
                        }
                        echo "</td>";
                        $day++;
                    }
                }
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>
