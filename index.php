<?php
// ...existing code...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Weekly Calendar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Hidden menu button and menu -->
    <div style="position: relative; height: 0;">
        <button class="show-hide-btn" id="toggle-add-event" type="button" onclick="toggleAddEvent()">Show Add Event</button>
        <!-- Hamburger menu button -->
        <button id="menu-btn" type="button" style="position:absolute; left:0; top:0; z-index:3; background:#333; color:#fff; border:none; padding:8px 14px; border-radius:4px; cursor:pointer; font-size:1.5em;">
            &#9776;
        </button>
        <!-- Hidden menu -->
        <div id="side-menu" style="display:none; position:absolute; left:0; top:44px; z-index:10; background:#fff; border:1px solid #ccc; border-radius:6px; box-shadow:0 2px 8px #aaa; min-width:180px;">
            <a href="admin.php" style="display:block; padding:12px 18px; color:#333; text-decoration:none; border-bottom:1px solid #eee;">Admin Page</a>
            <a href="contact-list.php" style="display:block; padding:12px 18px; color:#333; text-decoration:none; border-bottom:1px solid #eee;">Contact List</a>
            <a href="monthly.php" style="display:block; padding:12px 18px; color:#333; text-decoration:none;">Monthly View</a>
        </div>
    </div>
    <div id="add-event-section" style="display:none;">
        <br>
        <h2>Add Event</h2>
        <form id="event-form">
            <select name="title" id="title-select" required style="margin-bottom:8px;">
                <option value="">Select Title</option>
            </select>
            <input type="datetime-local" name="start" required>
            <input type="datetime-local" name="end" required>
            <input type="text" name="location" placeholder="Location">
            <div id="person-checkbox-group" style="margin-bottom:8px;"></div>
            <textarea name="description" placeholder="Description" rows="3" style="width:30%;margin-bottom:8px;"></textarea>
            <br>
            <button type="submit" class="add-btn">Add</button>
        </form>
    </div>
    <div class="header-row">
        <button class="arrow-btn" onclick="changeWeek(-1)" title="Previous Week">&#8592;</button>
        <div class="calendar-title">
            Team Calendar
            <div id="week-range" style="font-size:1em; color:#555; margin-top:4px;"></div>
            <div id="week-dates" style="font-size:1em; color:#888; margin-top:2px;"></div>
            <button class="today-btn" onclick="goToToday()" type="button">Today</button>
            <!-- Goto date input and button -->
            <input type="date" id="goto-date-input" style="margin-left:12px; font-size:1em; padding:4px 8px;">
            <button class="today-btn" type="button" onclick="gotoDate()" style="margin-left:4px;">Go To</button>
        </div>
        <button class="arrow-btn" id="right-arrow-btn" onclick="changeWeek(1)" title="Next Week">&#8594;</button>
    </div>
    <div id="calendar-container">
        <table id="calendar">
            <thead>
                <tr id="calendar-header-row">
                    <!-- Day and date will be filled by JS -->
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

        // Load persons from JSON file and populate the checkboxes with "All" option
        function loadPersons() {
            fetch('persons.json?ts=' + new Date().getTime()) // prevent cache
                .then(res => res.json())
                .then(data => {
                    persons = data;
                    const group = document.getElementById('person-checkbox-group');
                    group.innerHTML = '<label style="font-weight:bold;">Person:</label><br>';
                    // Add "All" checkbox first
                    group.innerHTML += `
                        <label style="margin-right:12px;">
                            <input type="checkbox" id="person_all" onchange="toggleAllPersons(this)" class="all-checkbox"> All
                        </label>
                    `;
                    persons.forEach(person => {
                        const id = 'person_' + person.name.replace(/\s+/g, '_');
                        group.innerHTML += `
                            <label style="margin-right:12px;">
                                <input type="checkbox" name="person" value="${person.name}" id="${id}" class="person-checkbox"> ${person.name}
                            </label>
                        `;
                    });
                });
        }

        // Toggle all person checkboxes when "All" is checked/unchecked
        function toggleAllPersons(allCheckbox) {
            const checkboxes = document.querySelectorAll('#person-checkbox-group .person-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = allCheckbox.checked;
            });
        }

        // If any person checkbox is unchecked, uncheck "All". If all are checked, check "All".
        document.addEventListener('change', function(e) {
            if (e.target.classList && e.target.classList.contains('person-checkbox')) {
                const allCheckbox = document.getElementById('person_all');
                const checkboxes = document.querySelectorAll('#person-checkbox-group .person-checkbox');
                const allChecked = Array.from(checkboxes).length > 0 && Array.from(checkboxes).every(cb => cb.checked);
                allCheckbox.checked = allChecked;
            }
        });

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
            // Render header with day and date
            const headerRow = document.getElementById('calendar-header-row');
            headerRow.innerHTML = '';
            weekDates.forEach((dt, idx) => {
                const th = document.createElement('th');
                const isSunday = idx === 0;
                th.innerHTML = `<div>
                    <div><strong${isSunday ? ' style="color:#d32f2f;"' : ''}>${dt.toLocaleDateString('en-US', { weekday: 'long' })}</strong></div>
                    <div style="font-size:0.95em;color:#555;">${dt.toLocaleDateString()}</div>
                </div>`;
                headerRow.appendChild(th);
            });

            // Show week start/end date under the title
            const weekDatesDiv = document.getElementById('week-dates');
            const start = weekDates[0];
            const end = weekDates[6];
            weekDatesDiv.textContent = `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;

            const row = document.getElementById('week-row');
            row.innerHTML = '';
            const today = new Date();
            weekDates.forEach(dt => {
                const isToday =
                    dt.getFullYear() === today.getFullYear() &&
                    dt.getMonth() === today.getMonth() &&
                    dt.getDate() === today.getDate();
                const td = document.createElement('td');
                if (isToday) td.classList.add('today-cell');
                td.innerHTML = `<div class="events"></div>`;
                row.appendChild(td);
            });
            fetchEvents(weekDates[0], weekDates);
        }

        // Update fetchEvents to add edit icon
        function fetchEvents(sunday, weekDates) {
            fetch('calendar.php?date=' + sunday.toISOString().slice(0,10))
                .then(res => res.json())
                .then(data => {
                    const events = data.events || [];
                    // Sort events by start time ascending
                    events.sort((a, b) => a.start.localeCompare(b.start));
                    const special_days = data.special_days || [];
                    const specialMap = {};
                    special_days.forEach(sd => {
                        specialMap[sd.date] = sd.description;
                    });

                    const row = document.getElementById('week-row');
                    for (let i = 0; i < 7; i++) {
                        const dt = weekDates[i];
                        const day = dt.getFullYear() + '-' +
                                    String(dt.getMonth() + 1).padStart(2, '0') + '-' +
                                    String(dt.getDate()).padStart(2, '0');
                        const cell = row.children[i].querySelector('.events');
                        const td = row.children[i];
                        cell.innerHTML = '';
                        if (specialMap[day]) {
                            td.style.background = '#ffebee';
                            td.style.border = '2px solid #d32f2f';
                            cell.innerHTML += `<div style="color:#d32f2f;font-weight:bold;margin-bottom:4px;">${specialMap[day]}</div>`;
                        } else {
                            td.style.background = '';
                            td.style.border = '';
                        }
                        // Filter and sort events for this day
                        const dayEvents = events.filter(ev => {
                            const evDate = new Date(ev.start);
                            const evDay = evDate.getFullYear() + '-' +
                                          String(evDate.getMonth() + 1).padStart(2, '0') + '-' +
                                          String(evDate.getDate()).padStart(2, '0');
                            return evDay === day;
                        }).sort((a, b) => a.start.localeCompare(b.start));
                        dayEvents.forEach(ev => {
                            const personName = ev.person ? `<div style="font-size:0.95em;color:#1976d2;">👤 ${getPersonName(ev.person)}</div>` : '';
                            const location = ev.location ? `<div style="font-size:0.95em;color:#388e3c;">📍 ${ev.location}</div>` : '';
                            const desc = ev.description ? `<div style="font-size:0.95em;color:#555;">${ev.description}</div>` : '';
                            const editIcon = `<a href="edit_event.php?id=${encodeURIComponent(ev.id)}" title="Edit Event" style="margin-left:6px;vertical-align:middle;"><span style="font-size:1.1em;cursor:pointer;">&#9998;</span></a>`;
                            const div = document.createElement('div');
                            div.className = 'event';
                            div.innerHTML =
                                `<div>
                                    <strong>${ev.title}</strong> (${ev.start.slice(11,16)}-${ev.end.slice(11,16)})
                                    ${editIcon}
                                 </div>
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
            // Get checked persons as array and join with comma, but ignore the "All" checkbox
            const checked = Array.from(document.querySelectorAll('#person-checkbox-group input.person-checkbox:checked')).map(cb => cb.value);
            const data = {
                title: form.title.value,
                start: form.start.value,
                end: form.end.value,
                location: form.location.value,
                description: form.description.value,
                person: checked.join(', ')
            };
            fetch('calendar.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            }).then(() => {
                renderCalendar();
                form.reset();
                // Uncheck all checkboxes after submit
                document.querySelectorAll('#person-checkbox-group input[type="checkbox"]').forEach(cb => cb.checked = false);
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

        function gotoDate() {
            const input = document.getElementById('goto-date-input');
            if (input.value) {
                currentDate = new Date(input.value);
                renderCalendar();
            } else {
                alert('Please select a date.');
            }
        }

        function loadTitles() {
            fetch('title.json?ts=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    const select = document.getElementById('title-select');
                    select.innerHTML = '<option value="">Select Title</option>';
                    data.forEach(title => {
                        const opt = document.createElement('option');
                        opt.value = title;
                        opt.textContent = title;
                        select.appendChild(opt);
                    });
                });
        }

        // Call loadTitles in addition to loadPersons
        loadTitles();
        loadPersons();
        renderCalendar();

        // Hamburger menu toggle logic
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('menu-btn');
            const sideMenu = document.getElementById('side-menu');
            document.addEventListener('click', function(e) {
                if (menuBtn.contains(e.target)) {
                    sideMenu.style.display = sideMenu.style.display === 'block' ? 'none' : 'block';
                } else if (!sideMenu.contains(e.target)) {
                    sideMenu.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>