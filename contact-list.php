<?php
$persons = [];
$json_path = __DIR__ . '/data/persons.json';
if (file_exists($json_path)) {
    $persons = json_decode(file_get_contents($json_path), true);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Contact List</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        .back-btn { background: #2196f3; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-bottom: 16px; }
        .contact-list { list-style: none; padding: 0; }
        .contact-item { background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; margin-bottom: 12px; padding: 16px; }
        .contact-name { font-weight: bold; font-size: 1.1em; }
        .contact-tel { color: #1976d2; margin-top: 4px; }
        .contact-title { color: #388e3c; margin-top: 4px; }
        .contact-location { color: #555; margin-top: 4px; }
        .search-box { margin-bottom: 18px; width: 100%; }
        .search-input { width: 80%; padding: 8px; font-size: 1em; border-radius: 4px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php"><button type="button" class="back-btn">&larr; Back to Calendar</button></a>
        <h2>Contact List</h2>
        <div class="search-box">
            <input type="text" id="search-input" class="search-input" placeholder="Search by name, title, location, or tel...">
        </div>
        <ul class="contact-list" id="contact-list">
            <?php foreach ($persons as $person): ?>
                <li class="contact-item">
                    <div class="contact-name"><?=htmlspecialchars($person['name'])?></div>
                    <div class="contact-title"><?=htmlspecialchars($person['Title'] ?? '')?></div>
                    <div class="contact-tel"><?=htmlspecialchars($person['office_tel'] ?? '')?></div>
                    <div class="contact-location"><?=htmlspecialchars($person['Location'] ?? '')?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <script>
        // Simple client-side search
        const input = document.getElementById('search-input');
        const list = document.getElementById('contact-list');
        const items = Array.from(list.getElementsByClassName('contact-item'));

        input.addEventListener('input', function() {
            const val = input.value.trim().toLowerCase();
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(val) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
