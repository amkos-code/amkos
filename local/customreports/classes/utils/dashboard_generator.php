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
 * Dashboard data generator
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\utils;

defined('MOODLE_INTERNAL') || die();

class dashboard_generator {
    
    /**
     * Get all widgets data for dashboard
     *
     * @return array All widget data
     */
    public function get_all_widgets_data() {
        $widgets = [];
        
        // Widget 1: Site Overview
        $widgets[] = $this->get_site_overview();
        
        // Widget 2: Course Progress Distribution
        $widgets[] = $this->get_course_progress_widget();
        
        // Widget 3: Popular Courses
        $widgets[] = $this->get_popular_courses();
        
        // Widget 4: Daily Activities
        $widgets[] = $this->get_daily_activities();
        
        // Widget 5: Real-time Users
        $widgets[] = $this->get_realtime_users();
        
        // Widget 6: Inactive Users
        $widgets[] = $this->get_inactive_users();
        
        // Widget 7: Certificates Stats
        $widgets[] = $this->get_certificates_stats();
        
        return $widgets;
    }
    
    /**
     * Get specific widget data
     *
     * @param string $widgetid Widget ID
     * @return array Widget data
     */
    public function get_widget_data($widgetid) {
        switch ($widgetid) {
            case 'site-overview':
                return $this->get_site_overview();
            case 'course-progress':
                return $this->get_course_progress_widget();
            case 'popular-courses':
                return $this->get_popular_courses();
            case 'daily-activities':
                return $this->get_daily_activities();
            case 'realtime-users':
                return $this->get_realtime_users();
            case 'inactive-users':
                return $this->get_inactive_users();
            case 'certificates-stats':
                return $this->get_certificates_stats();
            default:
                return ['error' => 'Widget not found'];
        }
    }
    
    /**
     * Widget 1: Site Overview
     *
     * @return array Site overview data
     */
    private function get_site_overview() {
        global $DB;
        
        $now = time();
        $today_start = strtotime('today midnight');
        
        // Total users
        $total_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
        
        // Active today
        $active_today = $DB->count_records_select('user', 
            "lastaccess > :today AND deleted = 0", 
            ['today' => $today_start]
        );
        
        // Total courses
        $total_courses = $DB->count_records('course', ['visible' => 1]);
        
        // Completed courses (approximation via course_completions)
        $completed_courses = $DB->count_records('course_completions', ['completionstate' => 1]);
        
        // Growth calculations (previous period)
        $period_start = strtotime('-30 days midnight');
        $prev_users = $DB->count_records_select('user', 
            "timecreated < :period AND deleted = 0", 
            ['period' => $period_start]
        );
        $user_growth = $prev_users > 0 ? round(($total_users - $prev_users) / $prev_users * 100, 2) : 0;
        
        return [
            'widget_id' => 'site-overview',
            'title' => get_string('widget_site_overview', 'local_customreports'),
            'type' => 'stats-cards',
            'data' => [
                'total_users' => [
                    'value' => $total_users,
                    'label' => get_string('total_users', 'local_customreports'),
                    'icon' => 'fa-users',
                    'growth' => $user_growth,
                    'color' => 'primary'
                ],
                'active_today' => [
                    'value' => $active_today,
                    'label' => get_string('active_today', 'local_customreports'),
                    'icon' => 'fa-user-check',
                    'growth' => null,
                    'color' => 'success'
                ],
                'total_courses' => [
                    'value' => $total_courses,
                    'label' => get_string('total_courses', 'local_customreports'),
                    'icon' => 'fa-book',
                    'growth' => null,
                    'color' => 'info'
                ],
                'completed_courses' => [
                    'value' => $completed_courses,
                    'label' => get_string('completed_courses', 'local_customreports'),
                    'icon' => 'fa-graduation-cap',
                    'growth' => null,
                    'color' => 'warning'
                ]
            ],
            'last_updated' => $now
        ];
    }
    
    /**
     * Widget 2: Course Progress Distribution
     *
     * @return array Course progress data
     */
    private function get_course_progress_widget() {
        $generator = new \local_customreports\report\courseprogress();
        $distribution = $generator->get_progress_distribution();
        
        return [
            'widget_id' => 'course-progress',
            'title' => get_string('widget_course_progress', 'local_customreports'),
            'type' => 'bar-chart',
            'data' => $distribution,
            'last_updated' => time()
        ];
    }
    
    /**
     * Widget 3: Popular Courses
     *
     * @return array Popular courses data
     */
    private function get_popular_courses() {
        global $DB;
        
        $sql = "SELECT c.id, c.fullname, c.shortname,
                       COUNT(DISTINCT ue.id) as enrollments,
                       COUNT(DISTINCT l.id) as visits,
                       ROUND(COUNT(DISTINCT CASE WHEN cc.completionstate = 1 THEN ue.userid END) * 100.0 / 
                             NULLIF(COUNT(DISTINCT ue.userid), 0), 2) as completion_rate
                FROM {course} c
                LEFT JOIN {enrol} e ON e.courseid = c.id
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                LEFT JOIN {logstore_standard_log} l ON l.courseid = c.id
                LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
                WHERE c.visible = 1
                GROUP BY c.id, c.fullname, c.shortname
                ORDER BY enrollments DESC
                LIMIT 10";
        
        $courses = $DB->get_records_sql($sql);
        
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'course_id' => $course->id,
                'course_name' => $course->fullname,
                'shortname' => $course->shortname,
                'enrollments' => $course->enrollments,
                'visits' => $course->visits,
                'completion_rate' => (float)$course->completion_rate
            ];
        }
        
        return [
            'widget_id' => 'popular-courses',
            'title' => get_string('widget_popular_courses', 'local_customreports'),
            'type' => 'table-chart',
            'data' => $data,
            'last_updated' => time()
        ];
    }
    
    /**
     * Widget 4: Daily Activities
     *
     * @return array Daily activities data
     */
    private function get_daily_activities() {
        global $DB;
        
        $days = 30; // Last 30 days
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $day_start = strtotime("-{$i} days midnight");
            $day_end = $day_start + DAYSECS;
            
            // Registrations
            $registrations = $DB->count_records_select('user', 
                "timecreated >= :start AND timecreated < :end AND deleted = 0",
                ['start' => $day_start, 'end' => $day_end]
            );
            
            // Enrollments
            $enrollments = $DB->count_records_select('user_enrolments', 
                "timecreated >= :start AND timecreated < :end",
                ['start' => $day_start, 'end' => $day_end]
            );
            
            // Activity completions
            $completions = $DB->count_records_select('course_modules_completion', 
                "timecreated >= :start AND timecreated < :end",
                ['start' => $day_start, 'end' => $day_end]
            );
            
            // Course completions
            $course_completions = $DB->count_records_select('course_completions', 
                "timecompleted >= :start AND timecompleted < :end",
                ['start' => $day_start, 'end' => $day_end]
            );
            
            // Log entries (visits)
            $visits = $DB->count_records_select('logstore_standard_log', 
                "timecreated >= :start AND timecreated < :end",
                ['start' => $day_start, 'end' => $day_end]
            );
            
            $data[] = [
                'date' => userdate($day_start, '%Y-%m-%d'),
                'registrations' => $registrations,
                'enrollments' => $enrollments,
                'activity_completions' => $completions,
                'course_completions' => $course_completions,
                'visits' => $visits
            ];
        }
        
        return [
            'widget_id' => 'daily-activities',
            'title' => get_string('widget_daily_activities', 'local_customreports'),
            'type' => 'line-chart',
            'data' => $data,
            'last_updated' => time()
        ];
    }
    
    /**
     * Widget 5: Real-time Users
     *
     * @return array Real-time users data
     */
    private function get_realtime_users() {
        global $DB;
        
        $now = time();
        $hour_ago = $now - HOURSECS;
        $today_start = strtotime('today midnight');
        
        // Currently online (last 5 minutes)
        $online_now = $DB->count_records_select('user', 
            "lastaccess > :now AND deleted = 0",
            ['now' => $now - 300]
        );
        
        // Last hour
        $online_hour = $DB->count_records_select('user', 
            "lastaccess > :hour AND deleted = 0",
            ['hour' => $hour_ago]
        );
        
        // Today
        $online_today = $DB->count_records_select('user', 
            "lastaccess > :today AND deleted = 0",
            ['today' => $today_start]
        );
        
        return [
            'widget_id' => 'realtime-users',
            'title' => get_string('widget_realtime_users', 'local_customreports'),
            'type' => 'live-counter',
            'data' => [
                'online_now' => $online_now,
                'online_hour' => $online_hour,
                'online_today' => $online_today
            ],
            'last_updated' => $now,
            'auto_refresh' => 60
        ];
    }
    
    /**
     * Widget 6: Inactive Users
     *
     * @return array Inactive users data
     */
    private function get_inactive_users() {
        global $DB;
        
        $now = time();
        $days_7 = $now - (7 * DAYSECS);
        $days_30 = $now - (30 * DAYSECS);
        $days_90 = $now - (90 * DAYSECS);
        
        // Inactive 7+ days
        $inactive_7 = $DB->count_records_select('user', 
            "lastaccess > 0 AND lastaccess < :days AND deleted = 0",
            ['days' => $days_7]
        );
        
        // Inactive 30+ days
        $inactive_30 = $DB->count_records_select('user', 
            "lastaccess > 0 AND lastaccess < :days AND deleted = 0",
            ['days' => $days_30]
        );
        
        // Inactive 90+ days
        $inactive_90 = $DB->count_records_select('user', 
            "lastaccess > 0 AND lastaccess < :days AND deleted = 0",
            ['days' => $days_90]
        );
        
        // Get list of inactive users (30+ days)
        $inactive_list = $DB->get_records_select('user', 
            "lastaccess > 0 AND lastaccess < :days AND deleted = 0",
            ['days' => $days_30],
            'lastaccess ASC',
            'id, firstname, lastname, email, lastaccess',
            0, 10
        );
        
        $users = [];
        foreach ($inactive_list as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => fullname($user),
                'email' => $user->email,
                'last_access' => userdate($user->lastaccess)
            ];
        }
        
        return [
            'widget_id' => 'inactive-users',
            'title' => get_string('widget_inactive_users', 'local_customreports'),
            'type' => 'pie-chart-list',
            'data' => [
                'chart' => [
                    'labels' => ['7+ days', '30+ days', '90+ days'],
                    'values' => [$inactive_7, $inactive_30, $inactive_90],
                    'colors' => ['#ffc107', '#fd7e14', '#dc3545']
                ],
                'list' => $users
            ],
            'last_updated' => $now
        ];
    }
    
    /**
     * Widget 7: Certificates Stats
     *
     * @return array Certificates stats data
     */
    private function get_certificates_stats() {
        global $DB;
        
        $now = time();
        $month_start = strtotime('first day of this month midnight');
        
        $total_certs = 0;
        $month_certs = 0;
        
        // Check if certificate module exists
        if ($DB->count_records('modules', ['name' => 'certificate'])) {
            $total_certs = $DB->count_records('certificate_issues');
            $month_certs = $DB->count_records_select('certificate_issues', 
                "timecreated >= :month",
                ['month' => $month_start]
            );
        }
        
        // Use badges as alternative
        $total_badges = $DB->count_records('badge_issued');
        $month_badges = $DB->count_records_select('badge_issued', 
            "dateissued >= :month",
            ['month' => $month_start]
        );
        $total_certs = max($total_certs, $total_badges);
        $month_certs = max($month_certs, $month_badges);
        
        // By course
        $by_course = $DB->get_records_sql(
            "SELECT c.id, c.fullname, COUNT(bi.id) as count
             FROM {course} c
             LEFT JOIN {badge} b ON b.courseid = c.id
             LEFT JOIN {badge_issued} bi ON bi.badgeid = b.id
             WHERE c.visible = 1
             GROUP BY c.id, c.fullname
             HAVING COUNT(bi.id) > 0
             ORDER BY count DESC
             LIMIT 10"
        );
        
        $course_data = [];
        foreach ($by_course as $course) {
            $course_data[] = [
                'course_id' => $course->id,
                'course_name' => $course->fullname,
                'count' => $course->count
            ];
        }
        
        return [
            'widget_id' => 'certificates-stats',
            'title' => get_string('widget_certificates_stats', 'local_customreports'),
            'type' => 'bar-chart-table',
            'data' => [
                'total' => $total_certs,
                'this_month' => $month_certs,
                'by_course' => $course_data
            ],
            'last_updated' => $now
        ];
    }
}
