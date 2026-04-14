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
 * Unit tests for report generator classes
 *
 * @package    local_customreports
 * @category   test
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports;

use advanced_testcase;
use local_customreports\report\courseprogress;
use local_customreports\report\studentengagement;
use local_customreports\report\timetracking;

defined('MOODLE_INTERNAL') || die();

/**
 * Report generator test class
 */
class report_generator_test extends advanced_testcase {

    /**
     * Test course progress calculation
     */
    public function test_course_progress_calculation() {
        $this->resetAfterTest();

        // Create course and users.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Enrol users.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Mark user1 as completed.
        $completion = new \completion_completion(['userid' => $user1->id, 'course' => $course->id]);
        $completion->mark_complete();

        // Get report data.
        $report = new courseprogress($course->id);
        $data = $report->get_courses_progress();

        // Verify results.
        $this->assertNotEmpty($data);
        $courseData = reset($data);
        $this->assertEquals($course->id, $courseData->courseid);
        $this->assertEquals(3, $courseData->total_enrolled);
        $this->assertEquals(1, $courseData->completed);
        $this->assertEquals(33.33, round($courseData->completion_rate, 2));
    }

    /**
     * Test student engagement score calculation
     */
    public function test_engagement_score_calculation() {
        $this->resetAfterTest();

        // Create course and user.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Enrol user.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Generate some log entries.
        for ($i = 0; $i < 10; $i++) {
            $this->generate_log_entry($user->id, $course->id);
        }

        // Get engagement data.
        $report = new studentengagement($course->id);
        $data = $report->get_engagement_data();

        // Verify results.
        $this->assertNotEmpty($data);
        $userData = reset($data);
        $this->assertEquals($user->id, $userData['userid']);
        $this->assertGreaterThan(0, $userData['engagement_score']);
        $this->assertContains($userData['engagement_level'], ['HIGH', 'MEDIUM', 'LOW']);
    }

    /**
     * Test time tracking data retrieval
     */
    public function test_time_tracking_data() {
        $this->resetAfterTest();

        // Create course and user.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Enrol user.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Generate log entries.
        for ($i = 0; $i < 5; $i++) {
            $this->generate_log_entry($user->id, $course->id);
        }

        // Get time tracking data.
        $report = new timetracking($course->id, $user->id);
        $data = $report->get_lms_level_data();

        // Verify results.
        $this->assertNotEmpty($data);
        $found = false;
        foreach ($data as $record) {
            if ($record->userid == $user->id) {
                $found = true;
                $this->assertGreaterThan(0, $record->total_events);
                break;
            }
        }
        $this->assertTrue($found, 'User data not found in time tracking');
    }

    /**
     * Test engagement level classification
     */
    public function test_engagement_level_classification() {
        $reflection = new \ReflectionClass(studentengagement::class);
        $method = $reflection->getMethod('classify_engagement_level');
        $method->setAccessible(true);

        $report = new studentengagement();

        // Test HIGH level.
        $level = $method->invoke($report, 85);
        $this->assertEquals('HIGH', $level);

        // Test MEDIUM level.
        $level = $method->invoke($report, 60);
        $this->assertEquals('MEDIUM', $level);

        // Test LOW level.
        $level = $method->invoke($report, 30);
        $this->assertEquals('LOW', $level);

        // Test boundary values.
        $level = $method->invoke($report, 80);
        $this->assertEquals('HIGH', $level);

        $level = $method->invoke($report, 50);
        $this->assertEquals('MEDIUM', $level);
    }

    /**
     * Test dashboard widget data generation
     */
    public function test_dashboard_widgets() {
        $this->resetAfterTest();

        // Create some test data.
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_user();

        $generator = new \local_customreports\utils\dashboard_generator();
        $widgets = $generator->get_all_widgets();

        // Verify widgets exist.
        $this->assertArrayHasKey('site_overview', $widgets);
        $this->assertArrayHasKey('course_progress', $widgets);
        $this->assertArrayHasKey('daily_activities', $widgets);
        $this->assertArrayHasKey('realtime_users', $widgets);
        $this->assertArrayHasKey('inactive_users', $widgets);

        // Verify site overview has required fields.
        $overview = $widgets['site_overview'];
        $this->assertArrayHasKey('title', $overview);
        $this->assertArrayHasKey('data', $overview);
        $this->assertArrayHasKey('metrics', $overview['data']);
    }

    /**
     * Test export functionality
     */
    public function test_csv_export() {
        $data = [
            ['name' => 'John', 'email' => 'john@example.com', 'score' => 85],
            ['name' => 'Jane', 'email' => 'jane@example.com', 'score' => 92]
        ];

        $exporter = new \local_customreports\export\report_exporter($data, 'test', 'csv');
        $csv = $exporter->export();

        // Verify CSV content.
        $this->assertStringContainsString('name,email,score', $csv);
        $this->assertStringContainsString('John', $csv);
        $this->assertStringContainsString('Jane', $csv);
    }

    /**
     * Test JSON export
     */
    public function test_json_export() {
        $data = [
            ['id' => 1, 'value' => 'test1'],
            ['id' => 2, 'value' => 'test2']
        ];

        $exporter = new \local_customreports\export\report_exporter($data, 'test', 'json');
        $json = $exporter->export();

        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertCount(2, $decoded['data']);
    }

    /**
     * Helper function to generate log entry
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     */
    private function generate_log_entry($userid, $courseid) {
        global $DB;

        $log = new \stdClass();
        $log->userid = $userid;
        $log->courseid = $courseid;
        $log->timecreated = time();
        $log->component = 'course';
        $log->action = 'viewed';
        $log->target = 'course';
        $log->contextid = \context_course::instance($courseid)->id;
        
        $DB->insert_record('logstore_standard_log', $log);
    }
}
