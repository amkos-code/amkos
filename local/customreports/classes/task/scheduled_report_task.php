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
 * Scheduled report task - sends reports via email on schedule
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\task;

defined('MOODLE_INTERNAL') || die();

class scheduled_report_task extends \core\task\scheduled_task {
    
    /**
     * Get a descriptive name for this task, shown to admin users.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_scheduled_reports', 'local_customreports');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        mtrace('Starting scheduled report task...');
        
        $now = time();
        
        // Get all enabled scheduled reports
        $scheduled = $DB->get_records('local_customreports_scheduled', ['enabled' => 1]);
        
        foreach ($scheduled as $schedule) {
            if (!$this->should_run($schedule, $now)) {
                continue;
            }
            
            try {
                mtrace("Processing scheduled report ID: {$schedule->id}");
                
                // Get the saved report configuration
                $report = $DB->get_record('local_customreports_saved', ['id' => $schedule->reportid]);
                if (!$report) {
                    mtrace("Report not found for schedule ID: {$schedule->id}");
                    continue;
                }
                
                // Generate report data
                $data = $this->generate_report_data($report);
                
                // Convert to PDF
                $pdf = $this->convert_to_pdf($data, $report);
                
                // Send email
                $this->send_email($schedule->userid, $report, $pdf);
                
                // Update last sent time
                $DB->set_field('local_customreports_scheduled', 'lastsent', $now, ['id' => $schedule->id]);
                
                mtrace("Successfully sent report ID: {$schedule->id}");
                
            } catch (\Exception $e) {
                mtrace("Error processing schedule ID {$schedule->id}: " . $e->getMessage());
                // Log error but continue with other reports
            }
        }
        
        mtrace('Scheduled report task completed.');
    }
    
    /**
     * Check if a scheduled report should run now
     *
     * @param object $schedule Schedule record
     * @param int $now Current timestamp
     * @return bool
     */
    private function should_run($schedule, $now) {
        $currenttime = userdate($now, 'H:i');
        $currentday = (int)userdate($now, 'u'); // 1 (Monday) to 7 (Sunday)
        $currentdate = (int)userdate($now, 'j'); // Day of month
        
        switch ($schedule->frequency) {
            case 'daily':
                return $schedule->schedule_time === $currenttime;
                
            case 'weekly':
                return $schedule->schedule_time === $currenttime && 
                       $schedule->schedule_day == $currentday;
                
            case 'monthly':
                return $schedule->schedule_time === $currenttime && 
                       $schedule->schedule_day == $currentdate;
                
            default:
                return false;
        }
    }
    
    /**
     * Generate report data based on configuration
     *
     * @param object $report Report configuration
     * @return array Report data
     */
    private function generate_report_data($report) {
        $config = json_decode($report->config, true);
        
        // Use appropriate report generator based on type
        $reporttype = $config['reporttype'] ?? 'dashboard';
        
        switch ($reporttype) {
            case 'courseprogress':
                $generator = new \local_customreports\report\courseprogress();
                return $generator->get_data();
                
            case 'studentengagement':
                // TODO: Implement engagement report
                return [];
                
            case 'timetracking':
                // TODO: Implement time tracking report
                return [];
                
            default:
                // Dashboard data
                $generator = new \local_customreports\utils\dashboard_generator();
                return $generator->get_all_widgets_data();
        }
    }
    
    /**
     * Convert report data to PDF
     *
     * @param array $data Report data
     * @param object $report Report configuration
     * @return string PDF content
     */
    private function convert_to_pdf($data, $report) {
        // TODO: Implement PDF generation using TCPDF or mPDF
        // For now, return placeholder
        return '';
    }
    
    /**
     * Send email with report attachment
     *
     * @param int $userid Recipient user ID
     * @param object $report Report configuration
     * @param string $attachment PDF attachment content
     */
    private function send_email($userid, $report, $attachment) {
        global $USER;
        
        $user = \core_user::get_user($userid);
        if (!$user) {
            return;
        }
        
        // Prepare email
        $subject = get_string('scheduled_report_subject', 'local_customreports', $report->name);
        $body = get_string('scheduled_report_body', 'local_customreports', [
            'name' => $report->name,
            'generated' => userdate(time())
        ]);
        
        // Send email
        email_to_user($user, $USER, $subject, strip_tags($body), $body);
        
        // TODO: Add PDF attachment when PDF generation is implemented
    }
}
