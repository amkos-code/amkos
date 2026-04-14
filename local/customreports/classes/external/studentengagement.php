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
 * External API for Student Engagement Report
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
 * Student Engagement API
 */
class studentengagement extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_data_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            'timestart' => new external_value(PARAM_INT, 'Start timestamp', VALUE_DEFAULT, 0),
            'timeend' => new external_value(PARAM_INT, 'End timestamp', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Get student engagement data
     *
     * @param int $courseid Course ID
     * @param int $timestart Start timestamp
     * @param int $timeend End timestamp
     * @return array Engagement data
     */
    public static function get_data($courseid = 0, $timestart = 0, $timeend = 0) {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::get_data_parameters(), [
            'courseid' => $courseid,
            'timestart' => $timestart,
            'timeend' => $timeend
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

        // Get engagement data.
        $report = new \local_customreports\report\studentengagement(
            $params['courseid'],
            $params['timestart'],
            $params['timeend']
        );

        $data = $report->get_engagement_data();
        $summary = $report->get_summary();
        $topstudents = $report->get_top_students(10);
        $atrisk = $report->get_at_risk_students(10);

        return [
            'status' => 'success',
            'data' => $data,
            'summary' => $summary,
            'top_students' => $topstudents,
            'at_risk_students' => $atrisk,
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
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                    'metrics' => new external_single_structure([
                        'time_spent_seconds' => new external_value(PARAM_INT, 'Time spent in seconds'),
                        'time_spent_hours' => new external_value(PARAM_FLOAT, 'Time spent in hours'),
                        'course_visits' => new external_value(PARAM_INT, 'Course visits'),
                        'activities_completed' => new external_value(PARAM_INT, 'Activities completed'),
                        'forum_posts' => new external_value(PARAM_INT, 'Forum posts'),
                        'assignment_submissions' => new external_value(PARAM_INT, 'Assignment submissions'),
                        'quiz_attempts' => new external_value(PARAM_INT, 'Quiz attempts'),
                        'total_interactions' => new external_value(PARAM_INT, 'Total interactions'),
                        'last_access' => new external_value(PARAM_INT, 'Last access timestamp')
                    ]),
                    'engagement_score' => new external_value(PARAM_FLOAT, 'Engagement score'),
                    'engagement_level' => new external_value(PARAM_TEXT, 'Engagement level'),
                    'last_access' => new external_value(PARAM_TEXT, 'Last access formatted')
                ])
            ),
            'summary' => new external_single_structure([
                'total_students' => new external_value(PARAM_INT, 'Total students'),
                'high_engagement' => new external_value(PARAM_INT, 'High engagement count'),
                'medium_engagement' => new external_value(PARAM_INT, 'Medium engagement count'),
                'low_engagement' => new external_value(PARAM_INT, 'Low engagement count'),
                'average_score' => new external_value(PARAM_FLOAT, 'Average score'),
                'high_percentage' => new external_value(PARAM_FLOAT, 'High percentage'),
                'medium_percentage' => new external_value(PARAM_FLOAT, 'Medium percentage'),
                'low_percentage' => new external_value(PARAM_FLOAT, 'Low percentage')
            ]),
            'top_students' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'engagement_score' => new external_value(PARAM_FLOAT, 'Engagement score'),
                    'engagement_level' => new external_value(PARAM_TEXT, 'Engagement level')
                ])
            ),
            'at_risk_students' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'engagement_score' => new external_value(PARAM_FLOAT, 'Engagement score'),
                    'engagement_level' => new external_value(PARAM_TEXT, 'Engagement level')
                ])
            ),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp')
        ]);
    }

    /**
     * Returns description of method parameters for summary
     *
     * @return external_function_parameters
     */
    public static function get_summary_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Get engagement summary
     *
     * @param int $courseid Course ID
     * @return array Summary data
     */
    public static function get_summary($courseid = 0) {
        global $USER;

        $params = self::validate_parameters(self::get_summary_parameters(), ['courseid' => $courseid]);

        $context = $params['courseid'] ? \context_course::instance($params['courseid']) : \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewcourses', $context);

        $report = new \local_customreports\report\studentengagement($params['courseid']);
        $summary = $report->get_summary();

        return [
            'status' => 'success',
            'summary' => $summary,
            'timestamp' => time()
        ];
    }

    /**
     * Returns description of method result for summary
     *
     * @return external_single_structure
     */
    public static function get_summary_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status'),
            'summary' => new external_single_structure([
                'total_students' => new external_value(PARAM_INT, 'Total students'),
                'high_engagement' => new external_value(PARAM_INT, 'High engagement count'),
                'medium_engagement' => new external_value(PARAM_INT, 'Medium engagement count'),
                'low_engagement' => new external_value(PARAM_INT, 'Low engagement count'),
                'average_score' => new external_value(PARAM_FLOAT, 'Average score'),
                'high_percentage' => new external_value(PARAM_FLOAT, 'High percentage'),
                'medium_percentage' => new external_value(PARAM_FLOAT, 'Medium percentage'),
                'low_percentage' => new external_value(PARAM_FLOAT, 'Low percentage')
            ]),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp')
        ]);
    }
}
