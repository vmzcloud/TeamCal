# Team Calendar

A comprehensive PHP-based team calendar application for managing events, holidays, and team schedules with both weekly and monthly views.

## Features

### Calendar Views
- **Weekly View**: Default view showing a week-by-week calendar with detailed event information
- **Monthly View**: Overview of the entire month with event filtering capabilities
- **Today Navigation**: Quick jump to current date
- **Date Navigation**: Go to any specific date using the date picker

### Event Management
- **Add Events**: Create events with title, description, start/end times, location, and assigned persons
- **Edit Events**: Modify existing events with full edit capabilities
- **Multi-day Events**: Support for events spanning multiple days
- **Event Types**: Predefined event titles (Online Meeting, On Leave, Onsite Support, Meeting, Training)
- **Person Assignment**: Assign multiple team members to events with "Select All" functionality

### Holiday Management
- **Special Days**: Add and manage holidays/special days
- **ICS Import**: Import holidays from ICS calendar files
- **Holiday Display**: Visual highlighting of holidays in both calendar views

### Contact Management
- **Contact List**: Searchable directory of team members
- **Contact Information**: Names, titles, phone numbers, and locations
- **Search Functionality**: Filter contacts by name, title, location, or phone number

### Administration
- **Admin Panel**: Secure admin interface for event and holiday management
- **Event Filtering**: Filter events by month in admin view
- **Audit Log**: Track all event deletions with IP addresses and timestamps
- **Bulk Operations**: Manage multiple events and holidays efficiently

## Installation

### Requirements
- PHP 7.4 or higher
- SQLite support (included in most PHP installations)
- Web server (Apache, Nginx, or built-in PHP server)

### Setup

1. **Clone or download** the application files to your web server directory

2. **Set permissions** for the data directory:
   ```bash
   chmod 755 data/
   chmod 666 data/calendar.sqlite
   ```

3. **Configure admin credentials** in `admin.php`:
   ```php
   $ADMIN_USER = 'admin';
   $ADMIN_PASS = 'your_secure_password'; // Change this!
   ```

4. **Access the application** through your web browser:
   ```
   http://your-domain/path-to-calendar/
   ```

### First Run
On first run, the application will automatically:
- Create the SQLite database with required tables
- Generate sample contact data (`data/persons.json`)
- Create default event types (`data/title.json`)

## File Structure

```
team-calendar/
├── index.php              # Main weekly calendar view
├── monthly.php            # Monthly calendar view
├── admin.php              # Admin panel
├── edit_event.php         # Event editing interface
├── contact-list.php       # Contact directory
├── calendar.php           # Calendar API endpoints
├── api_event.php          # Event API (alternative endpoint)
├── db.php                 # Database configuration and setup
├── style.css              # Main stylesheet
├── data/
│   ├── calendar.sqlite    # SQLite database (auto-created)
│   ├── persons.json       # Team member data
│   └── title.json         # Event type definitions
└── README.md              # This file
```

## Usage

### Adding Events
1. Click "Show Add Event" button on the main calendar
2. Select event type from dropdown
3. Set start and end date/time
4. Choose location and team members
5. Add description if needed
6. Click "Add" to save

### Managing Holidays
1. Access the admin panel (`admin.php`)
2. Use the "Special Day Management" section
3. Add individual holidays or import from ICS files
4. Holidays appear highlighted in red on calendars

### Viewing Contacts
1. Click the hamburger menu (☰) in the top-left
2. Select "Contact List"
3. Use the search box to filter contacts
4. View contact details including phone and location

### Admin Functions
1. Navigate to `admin.php`
2. Login with admin credentials
3. View and delete events by month
4. Manage holidays and special days
5. Review audit logs for deleted events

## Configuration

### Adding Team Members
Edit `data/persons.json`:
```json
[
    {
        "id": 1,
        "name": "John Doe",
        "office_tel": "1234 5678",
        "Title": "Manager",
        "Location": "Main Office"
    }
]
```

### Customizing Event Types
Edit `data/title.json`:
```json
[
    "Meeting",
    "Training",
    "Conference",
    "Vacation"
]
```

### Database Schema
The application uses SQLite with these main tables:
- `events`: Event storage with title, description, dates, person, location
- `special_day`: Holiday and special day definitions
- `audit_log`: Deletion tracking for events

## Security Features

- **Admin Authentication**: Password-protected admin panel
- **Audit Logging**: Track all event deletions with IP addresses
- **Input Sanitization**: All user inputs are properly escaped
- **Session Management**: Secure admin session handling

## Browser Compatibility

- Modern browsers with JavaScript enabled
- Responsive design works on desktop and mobile devices
- Date/time inputs require HTML5 support

## Troubleshooting

### Common Issues

**Database not created:**
- Check PHP SQLite extension is enabled
- Verify write permissions on the `data/` directory

**Events not displaying:**
- Check browser console for JavaScript errors
- Verify `calendar.php` is accessible
- Ensure database file exists and is readable

**Admin login not working:**
- Verify credentials in `admin.php`
- Check if sessions are enabled in PHP
- Clear browser cookies and try again

### File Permissions
```bash
# Set directory permissions
chmod 755 data/

# Set database file permissions (if exists)
chmod 666 data/calendar.sqlite

# Set JSON file permissions
chmod 666 data/persons.json
chmod 666 data/title.json
```

## Development

### Adding New Features
1. Database changes should be added to `db.php`
2. API endpoints go in `calendar.php` or `api_event.php`
3. UI changes in respective PHP files
4. Styling updates in `style.css`

### API Endpoints
- `GET calendar.php?date=YYYY-MM-DD`: Fetch week events
- `POST calendar.php`: Create new event
- `PUT calendar.php`: Update existing event
- `DELETE calendar.php`: Remove event

## License

This project is open source. Feel free to modify and distribute according to your needs.

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the file permissions
3. Verify PHP and SQLite are properly configured
4. Check browser console for JavaScript errors