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
 * Custom Reports plugin settings
 *
 * @package    local_customreports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_customreports', new lang_string('pluginname', 'local_customreports'));

    if ($ADMIN->fulltree) {
        
        // General settings section
        $settings->add(new admin_setting_heading(
            'local_customreports/generalsettings',
            new lang_string('generalsettings', 'local_customreports'),
            new lang_string('generalsettings_desc', 'local_customreports')
        ));

        // Dashboard refresh interval
        $settings->add(new admin_setting_configduration(
            'local_customreports/refresh_interval',
            new lang_string('refreshinterval', 'local_customreports'),
            new lang_string('refreshinterval_desc', 'local_customreports'),
            300, // Default 5 minutes
            MINSECS, null, [MINSECS, HOURSECS]
        ));

        // Enable/disable widgets
        $settings->add(new admin_setting_heading(
            'local_customreports/widgetsettings',
            new lang_string('widgetsettings', 'local_customreports'),
            new lang_string('widgetsettings_desc', 'local_customreports')
        ));

        // Site Overview widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_site_overview',
            new lang_string('enable_site_overview', 'local_customreports'),
            new lang_string('enable_site_overview_desc', 'local_customreports'),
            1
        ));

        // Course Progress widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_course_progress',
            new lang_string('enable_course_progress', 'local_customreports'),
            new lang_string('enable_course_progress_desc', 'local_customreports'),
            1
        ));

        // Popular Courses widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_popular_courses',
            new lang_string('enable_popular_courses', 'local_customreports'),
            new lang_string('enable_popular_courses_desc', 'local_customreports'),
            1
        ));

        // Daily Activities widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_daily_activities',
            new lang_string('enable_daily_activities', 'local_customreports'),
            new lang_string('enable_daily_activities_desc', 'local_customreports'),
            1
        ));

        // Real-time Users widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_realtime_users',
            new lang_string('enable_realtime_users', 'local_customreports'),
            new lang_string('enable_realtime_users_desc', 'local_customreports'),
            1
        ));

        // Inactive Users widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_inactive_users',
            new lang_string('enable_inactive_users', 'local_customreports'),
            new lang_string('enable_inactive_users_desc', 'local_customreports'),
            1
        ));

        // Certificates Stats widget
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_certificates_stats',
            new lang_string('enable_certificates_stats', 'local_customreports'),
            new lang_string('enable_certificates_stats_desc', 'local_customreports'),
            1
        ));

        // Export settings section
        $settings->add(new admin_setting_heading(
            'local_customreports/exportsettings',
            new lang_string('exportsettings', 'local_customreports'),
            new lang_string('exportsettings_desc', 'local_customreports')
        ));

        // Default export format
        $formats = [
            'pdf' => 'PDF',
            'excel' => 'Excel (XLSX)',
            'csv' => 'CSV',
            'json' => 'JSON'
        ];

        $settings->add(new admin_setting_configselect(
            'local_customreports/default_export_format',
            new lang_string('defaultexportformat', 'local_customreports'),
            new lang_string('defaultexportformat_desc', 'local_customreports'),
            'pdf',
            $formats
        ));

        // Enable scheduled reports
        $settings->add(new admin_setting_configcheckbox(
            'local_customreports/enable_scheduled_reports',
            new lang_string('enablescheduledreports', 'local_customreports'),
            new lang_string('enablescheduledreports_desc', 'local_customreports'),
            1
        ));

        // Performance settings section
        $settings->add(new admin_setting_heading(
            'local_customreports/performancesettings',
            new lang_string('performancesettings', 'local_customreports'),
            new lang_string('performancesettings_desc', 'local_customreports')
        ));

        // Cache TTL
        $settings->add(new admin_setting_configduration(
            'local_customreports/cache_ttl',
            new lang_string('cachettl', 'local_customreports'),
            new lang_string('cachettl_desc', 'local_customreports'),
            300, // Default 5 minutes
            MINSECS, null, [MINSECS, DAYSECS]
        ));

        // Max courses in popular courses
        $settings->add(new admin_setting_configtext(
            'local_customreports/max_popular_courses',
            new lang_string('maxpopularcourses', 'local_customreports'),
            new lang_string('maxpopularcourses_desc', 'local_customreports'),
            10,
            PARAM_INT
        ));

        // Days for activity tracking
        $settings->add(new admin_setting_configtext(
            'local_customreports/activity_tracking_days',
            new lang_string('activitytrackingdays', 'local_customreports'),
            new lang_string('activitytrackingdays_desc', 'local_customreports'),
            30,
            PARAM_INT
        ));
    }
}
