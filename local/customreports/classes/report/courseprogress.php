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
 * Course Progress Report Generator
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\report;

defined('MOODLE_INTERNAL') || die();

class courseprogress {
    
    /**
     * Get course progress data for all courses or specific course
     *
     * @param int|null $courseid Specific course ID or null for all courses
     * @param array $filters Additional filters (category, cohort, group, etc.)
     * @return array Course progress data
     */
    public function get_data($courseid = null, $filters = []) {
        global $DB;
        
        $params = [];
        $where = ['c.visible = 1'];
        
        if ($courseid) {
            $where[] = 'c.id = :courseid';
            $params['courseid'] = $courseid;
        }
        
        // Category filter
        if (!empty($filters['categoryid'])) {
            $where[] = 'c.category = :categoryid';
            $params['categoryid'] = $filters['categoryid'];
        }
        
        // Build SQL query
        $sql = "SELECT 
                    c.id as courseid,
                    c.fullname as coursename,
                    c.shortname as courseshortname,
                    c.category as categoryid,
                    COUNT(DISTINCT ue.userid) as total_enrolled,
                    COUNT(DISTINCT CASE WHEN cc.completionstate = 1 THEN ue.userid END) as completed,
                    ROUND(COUNT(DISTINCT CASE WHEN cc.completionstate = 1 THEN ue.userid END) * 100.0 / 
                          NULLIF(COUNT(DISTINCT ue.userid), 0), 2) as completion_rate
                FROM {course} c
                LEFT JOIN {user_enrolments} ue ON ue.enrolid IN (
                    SELECT e.id FROM {enrol} e WHERE e.courseid = c.id
                )
                LEFT JOIN {course_completions} cc ON cc.userid = ue.userid AND cc.course = c.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY c.id, c.fullname, c.shortname, c.category
                ORDER BY completion_rate DESC";
        
        $courses = $DB->get_records_sql($sql, $params);
        
        // Format data for charts
        $result = [];
        foreach ($courses as $course) {
            $progresslevel = $this->get_progress_level($course->completion_rate);
            
            $result[] = [
                'course_id' => $course->courseid,
                'course_name' => $course->coursename,
                'course_shortname' => $course->courseshortname,
                'category_id' => $course->categoryid,
                'total_enrolled' => $course->total_enrolled,
                'completed' => $course->completed,
                'in_progress' => $course->total_enrolled - $course->completed,
                'completion_rate' => (float)$course->completion_rate,
                'progress_level' => $progresslevel,
                'color_class' => $this->get_color_class($progresslevel)
            ];
        }
        
        return $result;
    }
    
    /**
     * Get progress distribution for chart visualization
     *
     * @param array $courses Array of course data
     * @return array Distribution data for chart
     */
    public function get_progress_distribution($courses = null) {
        if (!$courses) {
            $courses = $this->get_data();
        }
        
        $distribution = [
            '0-20' => 0,
            '20-40' => 0,
            '40-60' => 0,
            '60-80' => 0,
            '80-100' => 0,
            '100' => 0
        ];
        
        foreach ($courses as $course) {
            $rate = $course['completion_rate'];
            
            if ($rate >= 100) {
                $distribution['100']++;
            } elseif ($rate >= 80) {
                $distribution['80-100']++;
            } elseif ($rate >= 60) {
                $distribution['60-80']++;
            } elseif ($rate >= 40) {
                $distribution['40-60']++;
            } elseif ($rate >= 20) {
                $distribution['20-40']++;
            } else {
                $distribution['0-20']++;
            }
        }
        
        return [
            'labels' => ['0-20%', '20-40%', '40-60%', '60-80%', '80-100%', '100%'],
            'values' => array_values($distribution),
            'colors' => ['#dc3545', '#ffc107', '#fd7e14', '#28a745', '#20c997', '#007bff']
        ];
    }
    
    /**
     * Get progress level classification
     *
     * @param float $rate Completion rate percentage
     * @return string Progress level
     */
    private function get_progress_level($rate) {
        if ($rate >= 100) {
            return 'completed';
        } elseif ($rate >= 75) {
            return 'high';
        } elseif ($rate >= 50) {
            return 'medium';
        } elseif ($rate >= 25) {
            return 'low';
        } else {
            return 'very_low';
        }
    }
    
    /**
     * Get Bootstrap color class for progress level
     *
     * @param string $level Progress level
     * @return string Bootstrap color class
     */
    private function get_color_class($level) {
        $colors = [
            'very_low' => 'danger',
            'low' => 'warning',
            'medium' => 'info',
            'high' => 'success',
            'completed' => 'primary'
        ];
        
        return $colors[$level] ?? 'secondary';
    }
}
