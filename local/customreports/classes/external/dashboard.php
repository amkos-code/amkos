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
 * Dashboard external API
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class dashboard extends \external_api {
    
    /**
     * Get dashboard data parameters
     *
     * @return \external_function_parameters
     */
    public static function get_data_parameters() {
        return new \external_function_parameters([]);
    }
    
    /**
     * Get all dashboard widget data
     *
     * @return array Dashboard data
     */
    public static function get_data() {
        global $USER;
        
        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewdashboard', $context);
        
        $generator = new \local_customreports\utils\dashboard_generator();
        $data = $generator->get_all_widgets_data();
        
        return [
            'status' => 'success',
            'data' => [
                'widgets' => $data
            ],
            'timestamp' => time(),
            'cache_ttl' => 300
        ];
    }
    
    /**
     * Get dashboard data return value
     *
     * @return \external_single_structure
     */
    public static function get_data_returns() {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_ALPHANUM, 'Status'),
            'data' => new \external_single_structure([
                'widgets' => new \external_multiple_structure(
                    new \external_single_structure([
                        'widget_id' => new \external_value(PARAM_ALPHANUMEXT, 'Widget ID'),
                        'title' => new \external_value(PARAM_TEXT, 'Widget title'),
                        'data' => new \external_value(PARAM_RAW, 'Widget data'),
                        'type' => new \external_value(PARAM_ALPHANUM, 'Widget type')
                    ])
                )
            ]),
            'timestamp' => new \external_value(PARAM_INT, 'Timestamp'),
            'cache_ttl' => new \external_value(PARAM_INT, 'Cache TTL in seconds')
        ]);
    }
    
    /**
     * Get specific widget data parameters
     *
     * @return \external_function_parameters
     */
    public static function get_widget_data_parameters() {
        return new \external_function_parameters([
            'widgetid' => new \external_value(PARAM_ALPHANUMEXT, 'Widget ID', VALUE_REQUIRED)
        ]);
    }
    
    /**
     * Get specific widget data
     *
     * @param string $widgetid Widget ID
     * @return array Widget data
     */
    public static function get_widget_data($widgetid) {
        global $USER;
        
        // Validate parameters
        $params = self::validate_parameters(self::get_widget_data_parameters(), [
            'widgetid' => $widgetid
        ]);
        
        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewdashboard', $context);
        
        $generator = new \local_customreports\utils\dashboard_generator();
        $data = $generator->get_widget_data($widgetid);
        
        return [
            'status' => 'success',
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get widget data return value
     *
     * @return \external_single_structure
     */
    public static function get_widget_data_returns() {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_ALPHANUM, 'Status'),
            'data' => new \external_value(PARAM_RAW, 'Widget data'),
            'timestamp' => new \external_value(PARAM_INT, 'Timestamp')
        ]);
    }
}
