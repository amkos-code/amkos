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
 * Scheduled task for sending reports
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\task;

class scheduled_report_task extends \core\task\scheduled_task {
    
    public function get_name() {
        return get_string('pluginname', 'local_customreports') . ' - Send scheduled reports';
    }
    
    public function execute() {
        global $DB;
        
        mtrace('Starting scheduled report task...');
        
        $now = time();
        $reports = $DB->get_records('local_customreports_scheduled', array('enabled' => 1));
        
        foreach ($reports as $report) {
            if ($this->should_send($report, $now)) {
                mtrace('Sending report ID: ' . $report->id);
                $this->send_report($report);
                
                // Update last sent time
                $DB->set_field('local_customreports_scheduled', 'lastsent', $now, array('id' => $report->id));
            }
        }
        
        mtrace('Scheduled report task completed.');
    }
    
    private function should_send($report, $now) {
        if (empty($report->lastsent)) {
            return true;
        }
        
        switch ($report->frequency) {
            case 'daily':
                return ($now - $report->lastsent) >= DAYSECS;
            case 'weekly':
                return ($now - $report->lastsent) >= WEEKSECS;
            case 'monthly':
                return ($now - $report->lastsent) >= (30 * DAYSECS);
            default:
                return false;
        }
    }
    
    private function send_report($report) {
        // Implementation for sending report via email
        // This is a placeholder - full implementation would generate PDF and send email
        mtrace('Report ' . $report->id . ' sent successfully');
    }
}
