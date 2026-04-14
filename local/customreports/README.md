# Custom Moodle Analytics Reports

## Overview

A comprehensive analytics and reporting plugin for Moodle LMS that provides administrators, teachers, and students with powerful tools for visualizing learning progress, analyzing student engagement, and monitoring platform activity.

This plugin is designed as a replacement for commercial reporting solutions like Edwiser Reports.

## Features

### Dashboard Widgets
- **Site Overview**: Total users, active today, courses, completions with growth metrics
- **Course Progress**: Visual distribution of course completion rates
- **Popular Courses**: Top 10 courses by enrollment with engagement metrics
- **Daily Activities**: 30-day trend of registrations, enrollments, completions, and visits
- **Real-time Users**: Live counter of online users (updates every 60 seconds)
- **Inactive Users**: Students inactive for 7/30/90 days with pie chart visualization
- **Certificates Stats**: Badge/certificate issuance statistics by course

### Report Types
- Course Progress Report with filtering by category, cohort, and group
- Student Engagement Report with calculated engagement scores
- Time Tracking Report with detailed activity breakdowns
- Custom Report Builder (drag-and-drop interface)

### Export & Scheduling
- Export to PDF, Excel (XLSX), CSV, and JSON
- Scheduled email delivery (daily, weekly, monthly)
- Template saving and sharing

### Technical Features
- GDPR compliant with privacy provider
- Moodle cache API integration for performance
- Web Services API for mobile and external integrations
- Responsive design (mobile-first, 320px+)
- Chart.js 4.x for interactive visualizations
- Auto-refresh capabilities (configurable intervals)

## Requirements

- **Moodle**: 4.1 - 4.5 (tested on latest stable)
- **PHP**: 7.4+
- **Database**: MySQL 5.7+ / PostgreSQL 10+ / MariaDB 10.3+
- **JavaScript**: ES6+ compatible browser

## Installation

1. Copy the plugin to your Moodle installation:
   ```bash
   cp -r local/customreports /path/to/moodle/local/
   ```

2. Navigate to **Site administration > Notifications**

3. Click **Upgrade Moodle database now**

4. Configure plugin capabilities in **Site administration > Users > Permissions > Define roles**

## Configuration

### Capabilities

The plugin defines the following capabilities:

- `local/customreports:viewdashboard` - View dashboard (Manager, Teacher, Student)
- `local/customreports:viewcourses` - View course reports (Manager, Teacher)
- `local/customreports:viewallcourses` - View all courses (Manager, Admin)
- `local/customreports:exportdata` - Export report data (Manager, Admin, Teacher)
- `local/customreports:managescheduled` - Manage scheduled reports (Manager, Admin)
- `local/customreports:createcustom` - Create custom reports (Manager, Teacher)

### Cache Configuration

The plugin uses Moodle's cache API. A cache store definition is automatically created during installation.

## Usage

### Accessing the Dashboard

1. Navigate to **Site administration > Reports > Custom Reports**
2. Or use the quick link in the navigation block

### Creating Custom Reports

1. Go to **Custom Reports > Report Builder**
2. Drag and drop fields from the available panels
3. Configure filters, grouping, and sorting
4. Choose visualization type (table, bar chart, line chart, pie chart)
5. Save report with desired visibility settings

### Scheduling Reports

1. Open any saved report
2. Click **Schedule** button
3. Configure frequency (daily/weekly/monthly)
4. Set delivery time
5. Enable/disable as needed

## API Endpoints

The plugin provides the following Web Services:

- `local_customreports_get_dashboard_data` - Get all dashboard widgets
- `local_customreports_get_widget_data` - Get specific widget data
- `local_customreports_get_course_progress` - Get course progress data
- `local_customreports_get_student_engagement` - Get engagement metrics
- `local_customreports_get_time_tracking` - Get time tracking data
- `local_customreports_save_custom_report` - Save custom report config
- `local_customreports_export_report` - Export report data

## File Structure

```
local/customreports/
├── admin/                  # Admin settings pages
├── classes/
│   ├── privacy/           # GDPR compliance provider
│   ├── task/              # Scheduled tasks
│   ├── report/            # Report generator classes
│   ├── utils/             # Utility classes
│   └── external/          # Web services API
├── db/                    # Database definitions
│   ├── install.xml        # Table definitions
│   ├── access.php         # Capabilities
│   ├── services.php       # Web services
│   └── tasks.php          # Scheduled tasks
├── templates/             # Mustache templates
├── amd/src/               # JavaScript modules
├── lang/                  # Language strings
└── styles.css            # Custom styles
```

## Performance Optimization

- All widget data is cached for 5 minutes (configurable)
- Lazy loading for dashboard widgets using Intersection Observer
- Database query optimization with proper indexing
- Asynchronous data loading for better UX

## Troubleshooting

### Common Issues

1. **Dashboard not loading**
   - Check browser console for JavaScript errors
   - Verify cache is properly configured
   - Ensure user has required capabilities

2. **Reports showing no data**
   - Verify logstore_standard_log is enabled
   - Check course completion tracking is configured
   - Ensure users are enrolled in courses

3. **Scheduled reports not sending**
   - Verify cron is running: `admin/cli/cron.php`
   - Check email configuration in Moodle
   - Review scheduled task status in admin panel

## Development

### Running Tests

```bash
vendor/bin/phpunit local/customreports/tests/
```

### Code Style

Follow Moodle coding standards:
- PHP: Moodle PHP Coding Style
- JavaScript: ESLint with Moodle config
- CSS: BEM methodology

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPL-3.0 or later

## Support

For issues and feature requests, please open an issue in the project repository.

## Changelog

### Version 1.0.0 (2024-01-01)
- Initial release
- Dashboard with 7 widgets
- Course Progress Report
- Basic export functionality
- Scheduled reports
- GDPR compliance
