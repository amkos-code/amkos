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
 * External API for Custom Analytics Reports Dashboard
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;

class dashboard extends external_api {
    
    public static function get_data_parameters() {
        return new external_function_parameters(array(
            'widgetid' => new external_value(PARAM_ALPHANUMEXT, 'Widget identifier', VALUE_REQUIRED),
        ));
    }
    
    public static function get_data($widgetid) {
        global $DB;
        
        $params = self::validate_parameters(self::get_data_parameters(), array('widgetid' => $widgetid));
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/customreports:viewdashboard', $context);
        
        $result = array();
        
        switch ($params['widgetid']) {
            case 'site-overview':
                $result = self::get_site_overview_data();
                break;
            case 'course-progress':
                $result = self::get_course_progress_data();
                break;
            case 'popular-courses':
                $result = self::get_popular_courses_data();
                break;
            case 'daily-activities':
                $result = self::get_daily_activities_data();
                break;
            case 'realtime-users':
                $result = self::get_realtime_users_data();
                break;
            case 'inactive-users':
                $result = self::get_inactive_users_data();
                break;
            case 'certificates-stats':
                $result = self::get_certificates_stats_data();
                break;
        }
        
        return $result;
    }
    
    private static function get_site_overview_data() {
        global $DB;
        
        $totalusers = $DB->count_records('user', array('deleted' => 0, 'suspended' => 0));
        $activecourses = $DB->count_records('course', array('visible' => 1));
        
        // Active today (last 24 hours)
        $dayago = time() - DAYSECS;
        $activetoday = $DB->count_records_select('user', "lastaccess > ?", array($dayago));
        
        // Completed courses (simple count from course_completions if exists)
        $completed = 0;
        if ($DB->get_manager()->table_exists('course_completions')) {
            $completed = $DB->count_records('course_completions', array('completionstate' => COMPLETION_COMPLETE));
        }
        
        // Growth calculation (users created in last 7 days)
        $weekago = time() - WEEKSECS;
        $newusers = $DB->count_records_select('user', "timecreated > ?", array($weekago));
        $growth = $totalusers > 0 ? round(($newusers / $totalusers) * 100, 1) : 0;
        
        return array(
            'stats' => array(
                'total_users' => $totalusers,
                'active_courses' => $activecourses,
                'active_today' => $activetoday,
                'completed_courses' => $completed,
                'users_growth' => $growth,
            ),
        );
    }
    
    private static function get_course_progress_data() {
        global $DB;
        
        $labels = array('0-20%', '20-40%', '40-60%', '60-80%', '80-100%', '100%');
        $values = array(0, 0, 0, 0, 0, 0);
        
        $courses = $DB->get_records('course', array('visible' => 1), '', 'id,fullname');
        
        foreach ($courses as $course) {
            if ($course->id == 1) continue; // Skip front page
            
            $enrolled = $DB->count_records_select('user_enrolments ue', 
                'ue.enrolid IN (SELECT e.id FROM {enrol} e WHERE e.courseid = ?)', 
                array($course->id), 
                'COUNT(DISTINCT ue.userid)');
            
            if ($enrolled == 0) continue;
            
            $completed = 0;
            if ($DB->get_manager()->table_exists('course_completions')) {
                $completed = $DB->count_records('course_completions', 
                    array('course' => $course->id, 'completionstate' => COMPLETION_COMPLETE));
            }
            
            $percentage = $enrolled > 0 ? ($completed / $enrolled) * 100 : 0;
            
            if ($percentage >= 100) {
                $values[5]++;
            } elseif ($percentage >= 80) {
                $values[4]++;
            } elseif ($percentage >= 60) {
                $values[3]++;
            } elseif ($percentage >= 40) {
                $values[2]++;
            } elseif ($percentage >= 20) {
                $values[1]++;
            } else {
                $values[0]++;
            }
        }
        
        return array(
            'labels' => $labels,
            'values' => $values,
        );
    }
    
    private static function get_popular_courses_data() {
        global $DB;
        
        $sql = "SELECT c.id, c.fullname as name, COUNT(DISTINCT ue.userid) as enrolled
                FROM {course} c
                LEFT JOIN {enrol} e ON e.courseid = c.id
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE c.visible = 1 AND c.id != 1
                GROUP BY c.id, c.fullname
                ORDER BY enrolled DESC
                LIMIT 10";
        
        $courses = $DB->get_records_sql($sql);
        
        return array(
            'courses' => array_values($courses),
        );
    }
    
    private static function get_daily_activities_data() {
        global $DB;
        
        $labels = array();
        $values = array();
        
        for ($i = 6; $i >= 0; $i--) {
            $daystart = strtotime('-' . $i . ' days', strtotime('today'));
            $dayend = $daystart + DAYSECS;
            
            $label = date('D', $daystart);
            $labels[] = $label;
            
            if ($DB->get_manager()->table_exists('logstore_standard_log')) {
                $count = $DB->count_records_select('logstore_standard_log', 
                    'timecreated >= ? AND timecreated < ?', 
                    array($daystart, $dayend));
                $values[] = $count;
            } else {
                $values[] = 0;
            }
        }
        
        return array(
            'labels' => $labels,
            'values' => $values,
        );
    }
    
    private static function get_realtime_users_data() {
        global $DB;
        
        $now = time();
        $hourago = $now - HOURSECS;
        $dayago = $now - DAYSECS;
        
        $onlinenow = $DB->count_records_select('user', "lastaccess > ?", array($now - 300)); // 5 minutes
        $lasthour = $DB->count_records_select('user', "lastaccess > ?", array($hourago));
        $today = $DB->count_records_select('user', "lastaccess > ?", array($dayago));
        
        return array(
            'online_now' => $onlinenow,
            'last_hour' => $lasthour,
            'today' => $today,
        );
    }
    
    private static function get_inactive_users_data() {
        global $DB;
        
        $now = time();
        $weekago = $now - (7 * DAYSECS);
        $monthago = $now - (30 * DAYSECS);
        $quarterago = $now - (90 * DAYSECS);
        
        $inactive7 = $DB->count_records_select('user', 
            "lastaccess > 0 AND lastaccess < ? AND deleted = 0", 
            array($weekago));
        
        $inactive30 = $DB->count_records_select('user', 
            "lastaccess > 0 AND lastaccess < ? AND deleted = 0", 
            array($monthago));
        
        $inactive90 = $DB->count_records_select('user', 
            "lastaccess > 0 AND lastaccess < ? AND deleted = 0", 
            array($quarterago));
        
        return array(
            'labels' => array('7 days', '30 days', '90 days'),
            'values' => array($inactive7, $inactive30, $inactive90),
        );
    }
    
    private static function get_certificates_stats_data() {
        global $DB;
        
        $total = 0;
        $thismonth = 0;
        
        // Check for badges table
        if ($DB->get_manager()->table_exists('badge_issued')) {
            $total = $DB->count_records('badge_issued');
            
            $monthstart = strtotime('first day of this month');
            $thismonth = $DB->count_records_select('badge_issued', 
                'dateissued >= ?', 
                array($monthstart));
        }
        
        return array(
            'total' => $total,
            'this_month' => $thismonth,
        );
    }
    
    public static function get_data_returns() {
        return new external_value(PARAM_RAW, 'Widget data');
    }
}
