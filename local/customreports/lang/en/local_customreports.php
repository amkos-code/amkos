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
$string['reports'] = 'Reports';
$string['settings'] = 'Settings';

// Capabilities
$string['local/customreports:viewdashboard'] = 'View dashboard';
$string['local/customreports:viewcourses'] = 'View course reports';
$string['local/customreports:viewallcourses'] = 'View all courses reports';
$string['local/customreports:exportdata'] = 'Export report data';
$string['local/customreports:managescheduled'] = 'Manage scheduled reports';
$string['local/customreports:createcustom'] = 'Create custom reports';

// Widget titles
$string['widget_site_overview'] = 'Site Overview';
$string['widget_course_progress'] = 'Course Progress';
$string['widget_popular_courses'] = 'Popular Courses';
$string['widget_daily_activities'] = 'Daily Activities';
$string['widget_realtime_users'] = 'Real-time Users';
$string['widget_inactive_users'] = 'Inactive Users';
$string['widget_certificates_stats'] = 'Certificates & Badges';

// Statistics labels
$string['total_users'] = 'Total Users';
$string['active_today'] = 'Active Today';
$string['total_courses'] = 'Total Courses';
$string['completed_courses'] = 'Completed Courses';

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
