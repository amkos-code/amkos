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
 * Privacy provider for Custom Analytics Reports
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_customreports_saved',
            [
                'name' => 'privacy:metadata:local_customreports_saved:name',
                'description' => 'privacy:metadata:local_customreports_saved:description',
                'config' => 'privacy:metadata:local_customreports_saved:config',
                'timecreated' => 'privacy:metadata:local_customreports_saved:timecreated',
            ],
            'privacy:metadata:local_customreports_saved'
        );
        
        return $collection;
    }
    
    public static function get_contexts(\contextlist $contextlist): contextlist {
        // No user-specific contexts to export
        return $contextlist;
    }
    
    public static function export_user_data(\approved_contextlist $contextlist) {
        // No user data to export
    }
}
