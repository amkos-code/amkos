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
 * Scheduled tasks for Custom Reports plugin
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_customreports\task\scheduled_report_task',
        'minutecomponent' => '*/5', // Every 5 minutes
        'hourcomponent' => '*',
        'daycomponent' => '*',
        'monthcomponent' => '*',
        'dayofweekcomponent' => '*',
        'faildelay' => 60,
        'blocking' => 0
    ),
    array(
        'classname' => 'local_customreports\task\cache_cleanup_task',
        'minutecomponent' => '0',
        'hourcomponent' => '2', // 2 AM daily
        'daycomponent' => '*',
        'monthcomponent' => '*',
        'dayofweekcomponent' => '*',
        'faildelay' => 300,
        'blocking' => 0
    )
);
