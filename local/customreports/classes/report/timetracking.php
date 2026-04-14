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
 * Time Tracking Report
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\report;

defined('MOODLE_INTERNAL') || die();

/**
 * Time Tracking Report Generator
 */
class timetracking {

    /** @var int Course ID filter */
    protected $courseid;

    /** @var int User ID filter */
    protected $userid;

    /** @var int Start timestamp */
    protected $timestart;

    /** @var int End timestamp */
    protected $timeend;

    /**
     * Constructor
     *
     * @param int $courseid Course ID (0 for all)
     * @param int $userid User ID (0 for all)
     * @param int $timestart Start timestamp
     * @param int $timeend End timestamp
     */
    public function __construct($courseid = 0, $userid = 0, $timestart = null, $timeend = null) {
        $this->courseid = $courseid;
        $this->userid = $userid;
        $this->timestart = $timestart ?? (time() - 30 * DAYSECS);
        $this->timeend = $timeend ?? time();
    }

    /**
     * Get time tracking data at LMS level
     *
     * @return array Time tracking data
     */
    public function get_lms_level_data() {
        global $DB;

        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        $userfilter = $this->userid ? "AND l.userid = :userid" : "";
        $coursefilter = $this->courseid ? "AND l.courseid = :courseid" : "";
        
        if ($this->userid) {
            $params['userid'] = $this->userid;
        }
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        $sql = "SELECT 
                    l.userid,
                    u.firstname,
                    u.lastname,
                    DATE(FROM_UNIXTIME(l.timecreated)) as logdate,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT l.courseid) as courses_visited,
                    MIN(l.timecreated) as first_event,
                    MAX(l.timecreated) as last_event
                FROM {logstore_standard_log} l
                JOIN {user} u ON u.id = l.userid
                WHERE l.timecreated >= :timestart 
                AND l.timecreated <= :timeend
                $userfilter
                $coursefilter
                GROUP BY l.userid, DATE(FROM_UNIXTIME(l.timecreated)), u.firstname, u.lastname
                ORDER BY logdate DESC, total_events DESC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get time tracking data at course level
     *
     * @return array Course-level time data
     */
    public function get_course_level_data() {
        global $DB;

        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        $userfilter = $this->userid ? "AND l.userid = :userid" : "";
        
        if ($this->userid) {
            $params['userid'] = $this->userid;
        }

        $coursefilter = $this->courseid ? "WHERE c.id = :courseid" : "WHERE c.visible = 1";
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        $sql = "SELECT 
                    c.id as courseid,
                    c.fullname as coursename,
                    COUNT(DISTINCT l.userid) as unique_users,
                    COUNT(l.id) as total_events,
                    ROUND(AVG(session_duration.duration), 2) as avg_session_duration,
                    SUM(session_duration.duration) as total_time_seconds
                FROM {course} c
                LEFT JOIN {logstore_standard_log} l ON l.courseid = c.id 
                    AND l.timecreated >= :timestart 
                    AND l.timecreated <= :timeend
                    $userfilter
                LEFT JOIN (
                    SELECT 
                        userid,
                        courseid,
                        DATE(FROM_UNIXTIME(timecreated)) as logdate,
                        (MAX(timecreated) - MIN(timecreated)) as duration
                    FROM {logstore_standard_log}
                    WHERE timecreated >= :timestart2 
                    AND timecreated <= :timeend2
                    $userfilter
                    GROUP BY userid, courseid, DATE(FROM_UNIXTIME(timecreated))
                ) session_duration ON session_duration.courseid = c.id
                $coursefilter
                GROUP BY c.id, c.fullname
                ORDER BY total_time_seconds DESC";

        $params['timestart2'] = $this->timestart;
        $params['timeend2'] = $this->timeend;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get time tracking data at activity level
     *
     * @return array Activity-level time data
     */
    public function get_activity_level_data() {
        global $DB;

        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        $userfilter = $this->userid ? "AND l.userid = :userid" : "";
        $coursefilter = $this->courseid ? "AND cm.course = :courseid" : "";
        
        if ($this->userid) {
            $params['userid'] = $this->userid;
        }
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        $sql = "SELECT 
                    cm.id as cmid,
                    cm.name as activityname,
                    cm.modulename,
                    c.id as courseid,
                    c.fullname as coursename,
                    COUNT(DISTINCT l.userid) as unique_users,
                    COUNT(l.id) as total_events,
                    MIN(l.timecreated) as first_access,
                    MAX(l.timecreated) as last_access
                FROM {course_modules} cm
                JOIN {course} c ON c.id = cm.course
                LEFT JOIN {logstore_standard_log} l ON l.coursemoduleid = cm.id
                    AND l.timecreated >= :timestart 
                    AND l.timecreated <= :timeend
                    $userfilter
                    $coursefilter
                WHERE c.visible = 1
                GROUP BY cm.id, cm.name, cm.modulename, c.id, c.fullname
                HAVING COUNT(l.id) > 0
                ORDER BY total_events DESC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get heatmap data (activity by day/hour)
     *
     * @return array Heatmap data
     */
    public function get_heatmap_data() {
        global $DB;

        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        $userfilter = $this->userid ? "AND l.userid = :userid" : "";
        $coursefilter = $this->courseid ? "AND l.courseid = :courseid" : "";
        
        if ($this->userid) {
            $params['userid'] = $this->userid;
        }
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        $sql = "SELECT 
                    DAYOFWEEK(FROM_UNIXTIME(l.timecreated)) as dayofweek,
                    HOUR(FROM_UNIXTIME(l.timecreated)) as hourofday,
                    COUNT(*) as event_count
                FROM {logstore_standard_log} l
                WHERE l.timecreated >= :timestart 
                AND l.timecreated <= :timeend
                $userfilter
                $coursefilter
                GROUP BY DAYOFWEEK(FROM_UNIXTIME(l.timecreated)), HOUR(FROM_UNIXTIME(l.timecreated))
                ORDER BY dayofweek, hourofday";

        $records = $DB->get_records_sql($sql, $params);
        
        // Transform to heatmap format
        $heatmap = [];
        foreach ($records as $record) {
            if (!isset($heatmap[$record->dayofweek])) {
                $heatmap[$record->dayofweek] = [];
            }
            $heatmap[$record->dayofweek][$record->hourofday] = $record->event_count;
        }

        return $heatmap;
    }

    /**
     * Get daily activity summary
     *
     * @return array Daily summary
     */
    public function get_daily_summary() {
        global $DB;

        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        $userfilter = $this->userid ? "AND l.userid = :userid" : "";
        $coursefilter = $this->courseid ? "AND l.courseid = :courseid" : "";
        
        if ($this->userid) {
            $params['userid'] = $this->userid;
        }
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        $sql = "SELECT 
                    DATE(FROM_UNIXTIME(l.timecreated)) as logdate,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT l.userid) as unique_users,
                    COUNT(DISTINCT l.courseid) as courses_accessed
                FROM {logstore_standard_log} l
                WHERE l.timecreated >= :timestart 
                AND l.timecreated <= :timeend
                $userfilter
                $coursefilter
                GROUP BY DATE(FROM_UNIXTIME(l.timecreated))
                ORDER BY logdate ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get top active users by time spent
     *
     * @param int $limit Number of users to return
     * @return array Top users
     */
    public function get_top_active_users($limit = 10) {
        global $DB;

        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        $coursefilter = $this->courseid ? "AND l.courseid = :courseid" : "";
        
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        $sql = "SELECT 
                    l.userid,
                    u.firstname,
                    u.lastname,
                    u.email,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT l.courseid) as courses_visited,
                    MIN(l.timecreated) as first_activity,
                    MAX(l.timecreated) as last_activity
                FROM {logstore_standard_log} l
                JOIN {user} u ON u.id = l.userid
                WHERE l.timecreated >= :timestart 
                AND l.timecreated <= :timeend
                $coursefilter
                GROUP BY l.userid, u.firstname, u.lastname, u.email
                ORDER BY total_events DESC
                LIMIT :limit";

        $params['limit'] = $limit;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Format seconds to human readable
     *
     * @param int $seconds Seconds
     * @return string Formatted time
     */
    public static function format_time($seconds) {
        if ($seconds < 60) {
            return "$seconds sec";
        } elseif ($seconds < 3600) {
            $mins = floor($seconds / 60);
            $secs = $seconds % 60;
            return "$mins min " . ($secs > 0 ? "$secs sec" : "");
        } else {
            $hours = floor($seconds / 3600);
            $mins = floor(($seconds % 3600) / 60);
            return "$hours hr " . ($mins > 0 ? "$mins min" : "");
        }
    }
}
