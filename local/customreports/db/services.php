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
 * Web service definitions for Custom Reports plugin
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_customreports_get_dashboard_data' => array(
        'classname'   => 'local_customreports\external\dashboard',
        'methodname'  => 'get_data',
        'description' => 'Get dashboard widgets data',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'local_customreports_get_course_progress' => array(
        'classname'   => 'local_customreports\external\courseprogress',
        'methodname'  => 'get_progress',
        'description' => 'Get course progress data',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_customreports_get_student_engagement' => array(
        'classname'   => 'local_customreports\external\engagement',
        'methodname'  => 'get_engagement',
        'description' => 'Get student engagement data',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_customreports_get_time_tracking' => array(
        'classname'   => 'local_customreports\external\timetracking',
        'methodname'  => 'get_time_data',
        'description' => 'Get time tracking data',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_customreports_save_custom_report' => array(
        'classname'   => 'local_customreports\external\customreport',
        'methodname'  => 'save',
        'description' => 'Save custom report configuration',
        'type'        => 'write',
        'ajax'        => true
    ),
    'local_customreports_export_report' => array(
        'classname'   => 'local_customreports\external\export',
        'methodname'  => 'export',
        'description' => 'Export report to PDF/Excel/CSV',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_customreports_get_widget_data' => array(
        'classname'   => 'local_customreports\external\dashboard',
        'methodname'  => 'get_widget_data',
        'description' => 'Get specific widget data',
        'type'        => 'read',
        'ajax'        => true
    )
);

$services = array(
    'Custom Reports Service' => array(
        'functions' => array(
            'local_customreports_get_dashboard_data',
            'local_customreports_get_course_progress',
            'local_customreports_get_student_engagement',
            'local_customreports_get_time_tracking',
            'local_customreports_save_custom_report',
            'local_customreports_export_report'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_customreports_service'
    )
);
