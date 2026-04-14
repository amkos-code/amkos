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
 * Student Engagement Report
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Student Engagement Report Generator
 */
class studentengagement {

    /** @var int Default course ID */
    protected $courseid;

    /** @var int Start timestamp */
    protected $timestart;

    /** @var int End timestamp */
    protected $timeend;

    /** @var array Engagement weights */
    protected $weights = [
        'time_spent' => 0.25,
        'course_visits' => 0.20,
        'activities_completed' => 0.30,
        'forum_activity' => 0.15,
        'assignment_submissions' => 0.10
    ];

    /**
     * Constructor
     *
     * @param int $courseid Course ID (0 for all courses)
     * @param int $timestart Start timestamp
     * @param int $timeend End timestamp
     */
    public function __construct($courseid = 0, $timestart = null, $timeend = null) {
        $this->courseid = $courseid;
        $this->timestart = $timestart ?? (time() - 30 * DAYSECS);
        $this->timeend = $timeend ?? time();
    }

    /**
     * Get engagement data for all students
     *
     * @return array Engagement data
     */
    public function get_engagement_data() {
        global $DB;

        $coursefilter = $this->courseid ? "AND l.courseid = :courseid" : "";
        $params = ['timestart' => $this->timestart, 'timeend' => $this->timeend];
        if ($this->courseid) {
            $params['courseid'] = $this->courseid;
        }

        // Get all enrolled users in the course(s).
        $coursefilterSQL = $this->courseid ? "WHERE c.id = :courseid" : "";
        $enrolledusers = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, c.id as courseid, c.fullname as coursename
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            $coursefilterSQL
            AND u.deleted = 0
        ", $this->courseid ? ['courseid' => $this->courseid] : []);

        $engagementdata = [];
        foreach ($enrolledusers as $user) {
            $metrics = $this->calculate_user_metrics($user->id, $user->courseid);
            $score = $this->calculate_engagement_score($metrics);
            $level = $this->classify_engagement_level($score);

            $engagementdata[] = [
                'userid' => $user->id,
                'fullname' => fullname($user),
                'email' => $user->email,
                'courseid' => $user->courseid,
                'coursename' => $user->coursename,
                'metrics' => $metrics,
                'engagement_score' => round($score, 2),
                'engagement_level' => $level,
                'last_access' => userdate($metrics['last_access'] ?? 0)
            ];
        }

        return $engagementdata;
    }

    /**
     * Calculate metrics for a specific user
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return array User metrics
     */
    protected function calculate_user_metrics($userid, $courseid) {
        global $DB;

        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
            'timestart' => $this->timestart,
            'timeend' => $this->timeend
        ];

        // Time spent in system (from lastaccess)
        $user = $DB->get_record('user', ['id' => $userid], 'id, firstaccess, lastaccess');
        $timespent = max(0, ($user->lastaccess ?? 0) - ($user->firstaccess ?? 0));

        // Course visits count
        $coursevisitsql = "SELECT COUNT(*) FROM {logstore_standard_log} 
                           WHERE userid = :userid AND courseid = :courseid 
                           AND timecreated >= :timestart AND timecreated <= :timeend";
        $coursevisits = $DB->count_records_sql($coursevisitsql, $params);

        // Activities completed
        $activitiessql = "SELECT COUNT(*) FROM {course_modules_completion} cmc
                          JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                          WHERE cmc.userid = :userid AND cm.course = :courseid
                          AND cmc.completionstate = 1";
        $activitiescompleted = $DB->count_records_sql($activitiessql, $params);

        // Forum posts
        $forumsql = "SELECT COUNT(*) FROM {forum_posts} fp
                     JOIN {forum_discussions} fd ON fd.id = fp.discussion
                     JOIN {forum} f ON f.id = fd.forum
                     JOIN {course_modules} cm ON cm.instance = f.id
                     WHERE fp.userid = :userid AND cm.course = :courseid
                     AND fp.created >= :timestart AND fp.created <= :timeend";
        $forumposts = $DB->count_records_sql($forumsql, $params);

        // Assignment submissions
        $assignsql = "SELECT COUNT(*) FROM {assign_submission} asub
                      JOIN {assign} a ON a.id = asub.assignment
                      JOIN {course_modules} cm ON cm.instance = a.id
                      WHERE asub.userid = :userid AND cm.course = :courseid
                      AND asub.timecreated >= :timestart AND asub.timecreated <= :timeend";
        $assignmentsubmissions = $DB->count_records_sql($assignsql, $params);

        // Quiz attempts
        $quizsql = "SELECT COUNT(*) FROM {quiz_attempts} qa
                    JOIN {quiz} q ON q.id = qa.quiz
                    JOIN {course_modules} cm ON cm.instance = q.id
                    WHERE qa.userid = :userid AND cm.course = :courseid
                    AND qa.timefinish >= :timestart AND qa.timefinish <= :timeend";
        $quizattempts = $DB->count_records_sql($quizsql, $params);

        return [
            'time_spent_seconds' => $timespent,
            'time_spent_hours' => round($timespent / 3600, 2),
            'course_visits' => $coursevisits,
            'activities_completed' => $activitiescompleted,
            'forum_posts' => $forumposts,
            'assignment_submissions' => $assignmentsubmissions,
            'quiz_attempts' => $quizattempts,
            'total_interactions' => $coursevisits + $activitiescompleted + $forumposts + $assignmentsubmissions + $quizattempts,
            'last_access' => $user->lastaccess ?? 0
        ];
    }

    /**
     * Calculate engagement score (0-100)
     *
     * @param array $metrics User metrics
     * @return float Engagement score
     */
    protected function calculate_engagement_score($metrics) {
        // Normalize each metric (0-100 scale)
        $timenormalized = min(100, ($metrics['time_spent_hours'] / 50) * 100); // Max 50 hours
        $visitsnormalized = min(100, ($metrics['course_visits'] / 100) * 100); // Max 100 visits
        $activitiesnormalized = min(100, ($metrics['activities_completed'] / 50) * 100); // Max 50 activities
        $forumnormalized = min(100, ($metrics['forum_posts'] / 30) * 100); // Max 30 posts
        $assignmentnormalized = min(100, ($metrics['assignment_submissions'] / 20) * 100); // Max 20 submissions

        // Calculate weighted score
        $score = (
            $timenormalized * $this->weights['time_spent'] +
            $visitsnormalized * $this->weights['course_visits'] +
            $activitiesnormalized * $this->weights['activities_completed'] +
            $forumnormalized * $this->weights['forum_activity'] +
            $assignmentnormalized * $this->weights['assignment_submissions']
        );

        return $score;
    }

    /**
     * Classify engagement level
     *
     * @param float $score Engagement score
     * @return string Engagement level
     */
    protected function classify_engagement_level($score) {
        if ($score >= 80) {
            return 'HIGH';
        } elseif ($score >= 50) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Get engagement summary statistics
     *
     * @return array Summary statistics
     */
    public function get_summary() {
        $data = $this->get_engagement_data();

        if (empty($data)) {
            return [
                'total_students' => 0,
                'high_engagement' => 0,
                'medium_engagement' => 0,
                'low_engagement' => 0,
                'average_score' => 0
            ];
        }

        $total = count($data);
        $high = count(array_filter($data, fn($u) => $u['engagement_level'] === 'HIGH'));
        $medium = count(array_filter($data, fn($u) => $u['engagement_level'] === 'MEDIUM'));
        $low = count(array_filter($data, fn($u) => $u['engagement_level'] === 'LOW'));
        $avg = array_sum(array_column($data, 'engagement_score')) / $total;

        return [
            'total_students' => $total,
            'high_engagement' => $high,
            'medium_engagement' => $medium,
            'low_engagement' => $low,
            'average_score' => round($avg, 2),
            'high_percentage' => round(($high / $total) * 100, 2),
            'medium_percentage' => round(($medium / $total) * 100, 2),
            'low_percentage' => round(($low / $total) * 100, 2)
        ];
    }

    /**
     * Get top engaged students
     *
     * @param int $limit Number of students to return
     * @return array Top students
     */
    public function get_top_students($limit = 10) {
        $data = $this->get_engagement_data();
        usort($data, fn($a, $b) => $b['engagement_score'] <=> $a['engagement_score']);
        return array_slice($data, 0, $limit);
    }

    /**
     * Get at-risk students (low engagement)
     *
     * @param int $limit Number of students to return
     * @return array At-risk students
     */
    public function get_at_risk_students($limit = 10) {
        $data = $this->get_engagement_data();
        $atrisk = array_filter($data, fn($u) => $u['engagement_level'] === 'LOW');
        usort($atrisk, fn($a, $b) => $a['engagement_score'] <=> $b['engagement_score']);
        return array_slice($atrisk, 0, $limit);
    }
}
