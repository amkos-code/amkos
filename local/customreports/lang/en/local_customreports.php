<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for Custom Reports
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General strings
$string['pluginname'] = 'Custom Reports';
$string['customreports'] = 'Custom Reports';
$string['dashboard'] = 'Dashboard';
$string['dashboarddesc'] = 'Interactive analytics dashboard with real-time data visualization';
$string['reports'] = 'Reports';
$string['settings'] = 'Settings';
$string['all'] = 'All';
$string['refresh'] = 'Refresh';
$string['export'] = 'Export';
$string['loading'] = 'Loading...';
$string['lastupdated'] = 'Last updated';
$string['viewall'] = 'View all';
$string['course'] = 'Course';
$string['cohort'] = 'Cohort';
$string['datefrom'] = 'From date';
$string['dateto'] = 'To date';
$string['enrolled'] = 'Enrolled';
$string['issued'] = 'Issued';
$string['days'] = 'days';
$string['onlinenow'] = 'Online now';
$string['lasthour'] = 'Last hour';
$string['today'] = 'Today';
$string['autoupdating'] = 'Auto-updating';

// Capabilities
$string['local/customreports:viewdashboard'] = 'View dashboard';
$string['local/customreports:viewcourses'] = 'View course reports';
$string['local/customreports:viewallcourses'] = 'View all courses reports';
$string['local/customreports:exportdata'] = 'Export report data';
$string['local/customreports:managescheduled'] = 'Manage scheduled reports';
$string['local/customreports:createcustom'] = 'Create custom reports';

// Widget titles
$string['siteoverview'] = 'Site Overview';
$string['courseprogress'] = 'Course Progress';
$string['popularcourses'] = 'Popular Courses';
$string['dailyactivities'] = 'Daily Activities';
$string['realtimeusers'] = 'Real-time Users';
$string['inactiveusers'] = 'Inactive Users';
$string['certificatesstats'] = 'Certificates & Badges';

// Statistics labels
$string['totalusers'] = 'Total Users';
$string['activetoday'] = 'Active Today';
$string['totalcourses'] = 'Total Courses';
$string['completedcourses'] = 'Completed Courses';

// Settings
$string['generalsettings'] = 'General Settings';
$string['generalsettings_desc'] = 'Configure general settings for Custom Reports plugin';
$string['widgetsettings'] = 'Widget Settings';
$string['widgetsettings_desc'] = 'Enable or disable dashboard widgets';
$string['exportsettings'] = 'Export Settings';
$string['exportsettings_desc'] = 'Configure export options and formats';
$string['performancesettings'] = 'Performance Settings';
$string['performancesettings_desc'] = 'Configure caching and performance options';

$string['refreshinterval'] = 'Dashboard Refresh Interval';
$string['refreshinterval_desc'] = 'How often the dashboard should automatically refresh (in seconds)';

$string['enable_site_overview'] = 'Enable Site Overview';
$string['enable_site_overview_desc'] = 'Show site overview statistics widget';

$string['enable_course_progress'] = 'Enable Course Progress';
$string['enable_course_progress_desc'] = 'Show course progress distribution widget';

$string['enable_popular_courses'] = 'Enable Popular Courses';
$string['enable_popular_courses_desc'] = 'Show popular courses widget';

$string['enable_daily_activities'] = 'Enable Daily Activities';
$string['enable_daily_activities_desc'] = 'Show daily activities chart widget';

$string['enable_realtime_users'] = 'Enable Real-time Users';
$string['enable_realtime_users_desc'] = 'Show real-time users online widget';

$string['enable_inactive_users'] = 'Enable Inactive Users';
$string['enable_inactive_users_desc'] = 'Show inactive users analysis widget';

$string['enable_certificates_stats'] = 'Enable Certificates Stats';
$string['enable_certificates_stats_desc'] = 'Show certificates and badges statistics widget';

$string['defaultexportformat'] = 'Default Export Format';
$string['defaultexportformat_desc'] = 'Select the default format for exporting reports';

$string['enablescheduledreports'] = 'Enable Scheduled Reports';
$string['enablescheduledreports_desc'] = 'Allow scheduling automatic report delivery via email';

$string['cachettl'] = 'Cache Time To Live';
$string['cachettl_desc'] = 'How long to cache report data before refreshing';

$string['maxpopularcourses'] = 'Max Popular Courses';
$string['maxpopularcourses_desc'] = 'Maximum number of courses to show in popular courses widget';

$string['activitytrackingdays'] = 'Activity Tracking Days';
$string['activitytrackingdays_desc'] = 'Number of days to track for activity statistics';

// Export messages
$string['exportsuccess'] = 'Report exported successfully';
$string['schedulesuccess'] = 'Report scheduled successfully';
$string['html2canvasnotloaded'] = 'Image export library not loaded';

// Tasks
$string['task_scheduled_reports'] = 'Send scheduled reports';
$string['task_cache_cleanup'] = 'Clean up expired cache';

// Privacy
$string['privacy:metadata:local_customreports_saved'] = 'Saved custom reports';
$string['privacy:metadata:local_customreports_saved:name'] = 'Report name';
$string['privacy:metadata:local_customreports_saved:description'] = 'Report description';
$string['privacy:metadata:local_customreports_saved:config'] = 'Report configuration';
$string['privacy:metadata:local_customreports_saved:visibility'] = 'Visibility setting';
$string['privacy:metadata:local_customreports_saved:timecreated'] = 'Time created';
$string['privacy:metadata:local_customreports_saved:timemodified'] = 'Time modified';

$string['privacy:metadata:local_customreports_scheduled'] = 'Scheduled reports';
$string['privacy:metadata:local_customreports_scheduled:reportid'] = 'Report ID';
$string['privacy:metadata:local_customreports_scheduled:frequency'] = 'Frequency';
$string['privacy:metadata:local_customreports_scheduled:schedule_time'] = 'Schedule time';
$string['privacy:metadata:local_customreports_scheduled:schedule_day'] = 'Schedule day';
$string['privacy:metadata:local_customreports_scheduled:lastsent'] = 'Last sent';
$string['privacy:metadata:local_customreports_scheduled:enabled'] = 'Enabled';

$string['privacysavedreports'] = 'Saved Reports';
$string['privacyscheduledreports'] = 'Scheduled Reports';

// Scheduled reports
$string['scheduled_report_subject'] = 'Your scheduled report: {$a}';
$string['scheduled_report_body'] = 'Hello,\n\nYour scheduled report "{$a->name}" has been generated on {$a->generated}.\n\nPlease find the report attached.\n\nBest regards,\nMoodle Analytics System';

// Messages
$string['report_generated'] = 'Report generated successfully';
$string['report_saved'] = 'Report saved successfully';
$string['report_deleted'] = 'Report deleted successfully';
$string['error_generating_report'] = 'Error generating report';
$string['no_data_available'] = 'No data available';
