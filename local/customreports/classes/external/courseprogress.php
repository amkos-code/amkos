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
 * Course Progress external API
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class courseprogress extends \external_api {
    
    /**
     * Get course progress parameters
     *
     * @return \external_function_parameters
     */
    public static function get_progress_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID (optional)', VALUE_DEFAULT, null),
            'categoryid' => new \external_value(PARAM_INT, 'Category ID filter (optional)', VALUE_DEFAULT, null)
        ]);
    }
    
    /**
     * Get course progress data
     *
     * @param int|null $courseid Course ID
     * @param int|null $categoryid Category ID
     * @return array Course progress data
     */
    public static function get_progress($courseid = null, $categoryid = null) {
        global $USER;
        
        // Validate parameters
        $params = self::validate_parameters(self::get_progress_parameters(), [
            'courseid' => $courseid,
            'categoryid' => $categoryid
        ]);
        
        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewcourses', $context);
        
        $filters = [];
        if ($params['categoryid']) {
            $filters['categoryid'] = $params['categoryid'];
        }
        
        $generator = new \local_customreports\report\courseprogress();
        $data = $generator->get_data($params['courseid'], $filters);
        $distribution = $generator->get_progress_distribution($data);
        
        return [
            'status' => 'success',
            'data' => [
                'courses' => $data,
                'distribution' => $distribution
            ],
            'timestamp' => time()
        ];
    }
    
    /**
     * Get course progress return value
     *
     * @return \external_single_structure
     */
    public static function get_progress_returns() {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_ALPHANUM, 'Status'),
            'data' => new \external_single_structure([
                'courses' => new \external_multiple_structure(
                    new \external_single_structure([
                        'course_id' => new \external_value(PARAM_INT, 'Course ID'),
                        'course_name' => new \external_value(PARAM_TEXT, 'Course name'),
                        'total_enrolled' => new \external_value(PARAM_INT, 'Total enrolled'),
                        'completed' => new \external_value(PARAM_INT, 'Completed count'),
                        'completion_rate' => new \external_value(PARAM_FLOAT, 'Completion rate percentage'),
                        'progress_level' => new \external_value(PARAM_ALPHANUM, 'Progress level'),
                        'color_class' => new \external_value(PARAM_ALPHANUM, 'Bootstrap color class')
                    ])
                ),
                'distribution' => new \external_single_structure([
                    'labels' => new \external_multiple_structure(new \external_value(PARAM_TEXT, 'Label')),
                    'values' => new \external_multiple_structure(new \external_value(PARAM_INT, 'Value')),
                    'colors' => new \external_multiple_structure(new \external_value(PARAM_TEXT, 'Color'))
                ])
            ]),
            'timestamp' => new \external_value(PARAM_INT, 'Timestamp')
        ]);
    }
}
