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
 * External API for Export functionality
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
 * Export API
 */
class export extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function report_parameters() {
        return new external_function_parameters([
            'reporttype' => new external_value(PARAM_TEXT, 'Report type (dashboard|courseprogress|engagement|timetracking)'),
            'format' => new external_value(PARAM_TEXT, 'Export format (csv|excel|pdf|json)'),
            'params' => new external_value(PARAM_RAW, 'Additional parameters (JSON)', VALUE_DEFAULT, '{}'),
            'filename' => new external_value(PARAM_TEXT, 'Filename', VALUE_DEFAULT, 'report')
        ]);
    }

    /**
     * Export report data
     *
     * @param string $reporttype Report type
     * @param string $format Export format
     * @param string $params Additional parameters (JSON)
     * @param string $filename Filename
     * @return array Export result
     */
    public static function report($reporttype, $format, $params = '{}', $filename = 'report') {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::report_parameters(), [
            'reporttype' => $reporttype,
            'format' => $format,
            'params' => $params,
            'filename' => $filename
        ]);

        // Check capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:exportdata', $context);

        // Parse additional params.
        $extraparams = json_decode($params['params'], true) ?: [];

        // Get report data based on type.
        $data = self::get_report_data($params['reporttype'], $extraparams);

        if (empty($data)) {
            throw new \moodle_exception('nodata', 'local_customreports');
        }

        // Export data.
        $exporter = new \local_customreports\export\report_exporter(
            $data,
            $params['filename'],
            $params['format']
        );

        $content = $exporter->export();
        $mimetype = \local_customreports\export\report_exporter::get_mime_type($params['format']);

        // For AJAX, return base64 encoded content
        return [
            'status' => 'success',
            'filename' => $params['filename'] . '.' . $params['format'],
            'mimetype' => $mimetype,
            'content' => base64_encode($content),
            'size' => strlen($content),
            'timestamp' => time()
        ];
    }

    /**
     * Get report data based on type
     *
     * @param string $type Report type
     * @param array $params Additional parameters
     * @return array Report data
     */
    protected static function get_report_data($type, $params) {
        switch ($type) {
            case 'dashboard':
                return self::get_dashboard_data($params);
            
            case 'courseprogress':
                return self::get_course_progress_data($params);
            
            case 'engagement':
                return self::get_engagement_data($params);
            
            case 'timetracking':
                return self::get_timetracking_data($params);
            
            default:
                throw new \moodle_exception('invalid_report_type', 'local_customreports');
        }
    }

    /**
     * Get dashboard data for export
     *
     * @param array $params Parameters
     * @return array Dashboard data
     */
    protected static function get_dashboard_data($params) {
        $generator = new \local_customreports\utils\dashboard_generator();
        $widgets = $generator->get_all_widgets();
        
        // Flatten widget data for export
        $exportdata = [];
        foreach ($widgets as $widgetid => $widget) {
            if (isset($widget['data']) && is_array($widget['data'])) {
                foreach ($widget['data'] as $item) {
                    $item['widget'] = $widget['title'];
                    $exportdata[] = $item;
                }
            }
        }
        
        return $exportdata;
    }

    /**
     * Get course progress data for export
     *
     * @param array $params Parameters
     * @return array Course progress data
     */
    protected static function get_course_progress_data($params) {
        $courseid = $params['courseid'] ?? 0;
        $report = new \local_customreports\report\courseprogress($courseid);
        return $report->get_courses_progress();
    }

    /**
     * Get engagement data for export
     *
     * @param array $params Parameters
     * @return array Engagement data
     */
    protected static function get_engagement_data($params) {
        $courseid = $params['courseid'] ?? 0;
        $timestart = $params['timestart'] ?? 0;
        $timeend = $params['timeend'] ?? 0;
        
        $report = new \local_customreports\report\studentengagement($courseid, $timestart, $timeend);
        return $report->get_engagement_data();
    }

    /**
     * Get time tracking data for export
     *
     * @param array $params Parameters
     * @return array Time tracking data
     */
    protected static function get_timetracking_data($params) {
        $courseid = $params['courseid'] ?? 0;
        $userid = $params['userid'] ?? 0;
        $timestart = $params['timestart'] ?? 0;
        $timeend = $params['timeend'] ?? 0;
        
        $report = new \local_customreports\report\timetracking($courseid, $userid, $timestart, $timeend);
        return $report->get_lms_level_data();
    }

    /**
     * Returns description of method result
     *
     * @return external_single_structure
     */
    public static function report_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status'),
            'filename' => new external_value(PARAM_TEXT, 'Filename'),
            'mimetype' => new external_value(PARAM_TEXT, 'MIME type'),
            'content' => new external_value(PARAM_RAW, 'Base64 encoded content'),
            'size' => new external_value(PARAM_INT, 'File size in bytes'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp')
        ]);
    }

    /**
     * Returns description of method parameters for direct download
     *
     * @return external_function_parameters
     */
    public static function download_parameters() {
        return new external_function_parameters([
            'reporttype' => new external_value(PARAM_TEXT, 'Report type'),
            'format' => new external_value(PARAM_TEXT, 'Export format'),
            'params' => new external_value(PARAM_RAW, 'Additional parameters (JSON)', VALUE_DEFAULT, '{}'),
            'filename' => new external_value(PARAM_TEXT, 'Filename', VALUE_DEFAULT, 'report')
        ]);
    }

    /**
     * Download report directly (redirects to file)
     *
     * @param string $reporttype Report type
     * @param string $format Export format
     * @param string $params Additional parameters
     * @param string $filename Filename
     */
    public static function download($reporttype, $format, $params = '{}', $filename = 'report') {
        global $USER;

        $params = self::validate_parameters(self::download_parameters(), [
            'reporttype' => $reporttype,
            'format' => $format,
            'params' => $params,
            'filename' => $filename
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:exportdata', $context);

        $extraparams = json_decode($params['params'], true) ?: [];
        $data = self::get_report_data($params['reporttype'], $extraparams);

        if (empty($data)) {
            throw new \moodle_exception('nodata', 'local_customreports');
        }

        $exporter = new \local_customreports\export\report_exporter(
            $data,
            $params['filename'],
            $params['format']
        );

        $content = $exporter->export();
        $mimetype = \local_customreports\export\report_exporter::get_mime_type($params['format']);
        $fullfilename = $params['filename'] . '.' . $params['format'];

        \local_customreports\export\report_exporter::send_download($content, $fullfilename, $mimetype);
    }

    /**
     * Returns description of method result for download
     *
     * @return null
     */
    public static function download_returns() {
        return null; // This method outputs directly
    }
}
