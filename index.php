<?php
// ...existing code...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Weekly Calendar</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; width: 14%; }
        th { background: #f0f0f0; }
        .event { background: #e0f7fa; margin: 2px 0; padding: 2px 4px; border-radius: 3px; }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 20px;
        }
        .arrow-btn {
            background: none;
            border: none;
            font-size: 2em;
            cursor: pointer;
            width: 48px;
            height: 48px;
            line-height: 1;
        }
        .calendar-title {
            flex: 1;
            text-align: center;
            font-size: 2em;
            font-weight: bold;
        }
        .today-btn {
            margin-left: 16px;
            background: #43a047;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .show-hide-btn {
            position: absolute;
            right: 0;
            top: 0;
            background: #2196f3;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            z-index: 2;
        }
        #add-event-section {
            margin-bottom: 24px;
            background: #f9f9f9;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .today-cell {
            background: #fffde7 !important;
            border: 2px solid #ffb300 !important;
        }
    </style>
</head>
<body>
    <div style="position: relative; height: 0;">
        <button class="show-hide-btn" id="toggle-add-event" type="button" onclick="toggleAddEvent()">Show Add Event</button>
        <a href="admin.php" style="position:absolute; left:0; top:0; z-index:2;">
            <button type="button" style="background:#ff9800; color:#fff; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-size:1em;">
                Admin Page
            </button>
        </a>
    </div>
    <div id="add-event-section" style="display:none;">
        <h2>Add Event</h2>
        <form id="event-form">
            <input type="text" name="title" placeholder="Title" required>
            <input type="datetime-local" name="start" required>
            <input type="datetime-local" name="end" required>
            <input type="text" name="location" placeholder="Location">
            <input type="text" name="description" placeholder="Description">
            <select name="person" id="person-select" required>
                <option value="">Select Person</option>
            </select>
            <button type="submit">Add</button>
        </form>
    </div>
    <div class="header-row">
        <button class="arrow-btn" onclick="changeWeek(-1)" title="Previous Week">&#8592;</button>
        <div class="calendar-title">
            Team Calendar
            <div id="week-range" style="font-size:1em; color:#555; margin-top:4px;"></div>
            <button class="today-btn" onclick="goToToday()" type="button">Today</button>
        </div>
        <button class="arrow-btn" id="right-arrow-btn" onclick="changeWeek(1)" title="Next Week">&#8594;</button>
    </div>
    <div id="calendar-container">
        <table id="calendar">
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
                <tr id="week-row">
                    <!-- Days will be filled by JS -->
                </tr>
            </tbody>
        </table>
    </div>
    <script>
        let currentDate = new Date();
        let persons = [];

        // Load persons from JSON file and populate the select
        function loadPersons() {
            fetch('persons.json')
                .then(res => res.json())
                .then(data => {
                    persons = data;
                    const select = document.getElementById('person-select');
                    select.innerHTML = '<option value="">Select Person</option>';
                    persons.forEach(person => {
                        // Save person name as value (not id)
                        opt = document.createElement('option');
                        opt.value = person.name;
                        opt.textContent = person.name;
                        select.appendChild(opt);
                    });
                });
        }

        function getWeekDates(date) {
            const d = new Date(date);
            const day = d.getDay();
            const sunday = new Date(d);
            sunday.setDate(d.getDate() - day);
            let days = [];
            for (let i = 0; i < 7; i++) {
                let dt = new Date(sunday);
                dt.setDate(sunday.getDate() + i);
                days.push(dt);
            }
            return days;
        }

        function renderCalendar() {
            const weekDates = getWeekDates(currentDate);
            const row = document.getElementById('week-row');
            row.innerHTML = '';
            const today = new Date();
            // Set week range under title
            const weekRange = document.getElementById('week-range');
            const start = weekDates[0];
            const end = weekDates[6];
            weekRange.textContent = `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;
            weekDates.forEach(dt => {
                const isToday =
                    dt.getFullYear() === today.getFullYear() &&
                    dt.getMonth() === today.getMonth() &&
                    dt.getDate() === today.getDate();
                const td = document.createElement('td');
                if (isToday) td.classList.add('today-cell');
                td.innerHTML = `<div style="text-align:center;">
                    <strong>${dt.toLocaleDateString('en-US', { weekday: 'long' })}</strong><br>
                    <span style="font-size:0.95em;color:#555;">${dt.toLocaleDateString()}</span>
                </div>
                <div class="events"></div>`;
                row.appendChild(td);
            });
            fetchEvents(weekDates[0]);
        }

        function fetchEvents(sunday) {
            fetch('calendar.php?date=' + sunday.toISOString().slice(0,10))
                .then(res => res.json())
                .then(events => {
                    const weekDates = getWeekDates(currentDate);
                    const row = document.getElementById('week-row');
                    for (let i = 0; i < 7; i++) {
                        const day = weekDates[i].toISOString().slice(0,10);
                        const cell = row.children[i].querySelector('.events');
                        cell.innerHTML = '';
                        events.filter(ev => ev.start.slice(0,10) === day).forEach(ev => {
                            const personName = ev.person ? `<div style="font-size:0.95em;color:#1976d2;">👤 ${getPersonName(ev.person)}</div>` : '';
                            const location = ev.location ? `<div style="font-size:0.95em;color:#388e3c;">📍 ${ev.location}</div>` : '';
                            const desc = ev.description ? `<div style="font-size:0.95em;color:#555;">${ev.description}</div>` : '';
                            const div = document.createElement('div');
                            div.className = 'event';
                            div.innerHTML = 
                                `<div><strong>${ev.title}</strong> (${ev.start.slice(11,16)}-${ev.end.slice(11,16)})</div>
                                 ${location}
                                 ${desc}
                                 ${personName}`;
                            cell.appendChild(div);
                        });
                    }
                });
        }

        // Helper to get person name from id or value
        function getPersonName(personValue) {
            if (!persons || persons.length === 0) return personValue;
            // Try to match by id or name
            const found = persons.find(p => p.id == personValue || p.name == personValue);
            return found ? found.name : personValue;
        }

        function changeWeek(offset) {
            currentDate.setDate(currentDate.getDate() + offset * 7);
            renderCalendar();
        }

        document.getElementById('event-form').onsubmit = function(e) {
            e.preventDefault();
            const form = e.target;
            const start = new Date(form.start.value);
            const end = new Date(form.end.value);
            if (end < start) {
                alert('End date/time cannot be earlier than start date/time.');
                return;
            }
            const data = {
                title: form.title.value,
                start: form.start.value,
                end: form.end.value,
                location: form.location.value,
                description: form.description.value,
                person: form.person.value
            };
            fetch('calendar.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            }).then(() => {
                renderCalendar();
                form.reset();
            });
        };

        // Update goToToday for both views
        function goToToday() {
            currentDate = new Date();
            renderCalendar();
        }

        function toggleAddEvent() {
            const section = document.getElementById('add-event-section');
            const btn = document.getElementById('toggle-add-event');
            const rightArrow = document.getElementById('right-arrow-btn');
            if (section.style.display === 'none') {
                section.style.display = '';
                btn.textContent = 'Hide Add Event';
                rightArrow.style.display = '';
            } else {
                section.style.display = 'none';
                btn.textContent = 'Show Add Event';
                rightArrow.style.display = '';
            }
        }

        loadPersons();
        renderCalendar();
    </script>
</body>
</html>