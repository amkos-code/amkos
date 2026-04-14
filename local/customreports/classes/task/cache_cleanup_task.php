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
 * Cache cleanup task - removes expired cache entries
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\task;

defined('MOODLE_INTERNAL') || die();

class cache_cleanup_task extends \core\task\scheduled_task {
    
    /**
     * Get a descriptive name for this task
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cache_cleanup', 'local_customreports');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        mtrace('Starting cache cleanup task...');
        
        $now = time();
        
        // Delete expired cache entries from database table
        $deleted = $DB->delete_records_select(
            'local_customreports_cache',
            'expires < :now',
            ['now' => $now]
        );
        
        mtrace("Deleted {$deleted} expired cache entries.");
        
        // Also purge Moodle application cache for this plugin
        $cache = \cache::make('local_customreports', 'reports');
        $cache->purge();
        
        mtrace('Cache cleanup task completed.');
    }
}
