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
 * External API for Time Tracking Report
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_multiple_structure;

/**
 * Time Tracking API
 */
class timetracking extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_data_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
            'timestart' => new external_value(PARAM_INT, 'Start timestamp', VALUE_DEFAULT, 0),
            'timeend' => new external_value(PARAM_INT, 'End timestamp', VALUE_DEFAULT, 0),
            'level' => new external_value(PARAM_TEXT, 'Data level (lms|course|activity)', VALUE_DEFAULT, 'lms')
        ]);
    }

    /**
     * Get time tracking data
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param int $timestart Start timestamp
     * @param int $timeend End timestamp
     * @param string $level Data level
     * @return array Time tracking data
     */
    public static function get_data($courseid = 0, $userid = 0, $timestart = 0, $timeend = 0, $level = 'lms') {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::get_data_parameters(), [
            'courseid' => $courseid,
            'userid' => $userid,
            'timestart' => $timestart,
            'timeend' => $timeend,
            'level' => $level
        ]);

        // Check capabilities.
        $context = $params['courseid'] ? \context_course::instance($params['courseid']) : \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewcourses', $context);

        // Set default timestamps.
        if (empty($params['timestart'])) {
            $params['timestart'] = time() - 30 * DAYSECS;
        }
        if (empty($params['timeend'])) {
            $params['timeend'] = time();
        }

        // Get time tracking data.
        $report = new \local_customreports\report\timetracking(
            $params['courseid'],
            $params['userid'],
            $params['timestart'],
            $params['timeend']
        );

        $data = [];
        switch ($params['level']) {
            case 'course':
                $data = $report->get_course_level_data();
                break;
            case 'activity':
                $data = $report->get_activity_level_data();
                break;
            case 'heatmap':
                $data = $report->get_heatmap_data();
                break;
            case 'daily':
                $data = $report->get_daily_summary();
                break;
            case 'topusers':
                $data = $report->get_top_active_users(10);
                break;
            default:
                $data = $report->get_lms_level_data();
        }

        return [
            'status' => 'success',
            'level' => $params['level'],
            'data' => array_values($data),
            'timestamp' => time()
        ];
    }

    /**
     * Returns description of method result
     *
     * @return external_single_structure
     */
    public static function get_data_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status'),
            'level' => new external_value(PARAM_TEXT, 'Data level'),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
                    'firstname' => new external_value(PARAM_TEXT, 'First name', VALUE_OPTIONAL),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name', VALUE_OPTIONAL),
                    'email' => new external_value(PARAM_TEXT, 'Email', VALUE_OPTIONAL),
                    'logdate' => new external_value(PARAM_TEXT, 'Log date', VALUE_OPTIONAL),
                    'total_events' => new external_value(PARAM_INT, 'Total events'),
                    'courses_visited' => new external_value(PARAM_INT, 'Courses visited', VALUE_OPTIONAL),
                    'first_event' => new external_value(PARAM_INT, 'First event', VALUE_OPTIONAL),
                    'last_event' => new external_value(PARAM_INT, 'Last event', VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_OPTIONAL),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name', VALUE_OPTIONAL),
                    'unique_users' => new external_value(PARAM_INT, 'Unique users', VALUE_OPTIONAL),
                    'avg_session_duration' => new external_value(PARAM_FLOAT, 'Avg session duration', VALUE_OPTIONAL),
                    'total_time_seconds' => new external_value(PARAM_INT, 'Total time seconds', VALUE_OPTIONAL),
                    'cmid' => new external_value(PARAM_INT, 'Course module ID', VALUE_OPTIONAL),
                    'activityname' => new external_value(PARAM_TEXT, 'Activity name', VALUE_OPTIONAL),
                    'modulename' => new external_value(PARAM_TEXT, 'Module name', VALUE_OPTIONAL),
                    'first_access' => new external_value(PARAM_INT, 'First access', VALUE_OPTIONAL),
                    'last_access' => new external_value(PARAM_INT, 'Last access', VALUE_OPTIONAL),
                    'dayofweek' => new external_value(PARAM_INT, 'Day of week', VALUE_OPTIONAL),
                    'hourofday' => new external_value(PARAM_INT, 'Hour of day', VALUE_OPTIONAL),
                    'event_count' => new external_value(PARAM_INT, 'Event count', VALUE_OPTIONAL),
                    'courses_accessed' => new external_value(PARAM_INT, 'Courses accessed', VALUE_OPTIONAL)
                ], '', VALUE_OPTIONAL)
            ),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp')
        ]);
    }

    /**
     * Returns description of method parameters for heatmap
     *
     * @return external_function_parameters
     */
    public static function get_heatmap_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            'days' => new external_value(PARAM_INT, 'Number of days', VALUE_DEFAULT, 30)
        ]);
    }

    /**
     * Get heatmap data formatted for Chart.js
     *
     * @param int $courseid Course ID
     * @param int $days Number of days
     * @return array Heatmap data
     */
    public static function get_heatmap($courseid = 0, $days = 30) {
        global $USER;

        $params = self::validate_parameters(self::get_heatmap_parameters(), [
            'courseid' => $courseid,
            'days' => $days
        ]);

        $context = $params['courseid'] ? \context_course::instance($params['courseid']) : \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewcourses', $context);

        $timestart = time() - ($params['days'] * DAYSECS);
        
        $report = new \local_customreports\report\timetracking($params['courseid'], 0, $timestart, time());
        $heatmap = $report->get_heatmap_data();

        // Format for Chart.js
        $formatted = [];
        for ($day = 1; $day <= 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $value = isset($heatmap[$day][$hour]) ? $heatmap[$day][$hour] : 0;
                $formatted[] = [
                    'x' => $hour,
                    'y' => $day,
                    'v' => $value
                ];
            }
        }

        return [
            'status' => 'success',
            'data' => $formatted,
            'days' => $params['days'],
            'timestamp' => time()
        ];
    }

    /**
     * Returns description of method result for heatmap
     *
     * @return external_single_structure
     */
    public static function get_heatmap_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status'),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'x' => new external_value(PARAM_INT, 'Hour'),
                    'y' => new external_value(PARAM_INT, 'Day'),
                    'v' => new external_value(PARAM_INT, 'Value')
                ])
            ),
            'days' => new external_value(PARAM_INT, 'Days'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp')
        ]);
    }
}
