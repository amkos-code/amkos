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
 * Scheduled task for cache cleanup
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\task;

class cache_cleanup_task extends \core\task\scheduled_task {
    
    public function get_name() {
        return get_string('pluginname', 'local_customreports') . ' - Cleanup expired cache';
    }
    
    public function execute() {
        global $DB;
        
        mtrace('Starting cache cleanup task...');
        
        $now = time();
        $expired = $DB->delete_records_select('local_customreports_cache', 'expires < ?', array($now));
        
        mtrace('Deleted ' . $expired . ' expired cache records.');
        mtrace('Cache cleanup task completed.');
    }
}
