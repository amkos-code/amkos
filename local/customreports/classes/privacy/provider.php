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
 * Privacy provider for Custom Reports plugin - GDPR compliance
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return meta data about this plugin.
     *
     * @param collection $collection A list of information to add to.
     * @return collection Return the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_customreports_saved',
            [
                'name' => 'privacy:metadata:local_customreports_saved:name',
                'description' => 'privacy:metadata:local_customreports_saved:description',
                'config' => 'privacy:metadata:local_customreports_saved:config',
                'visibility' => 'privacy:metadata:local_customreports_saved:visibility',
                'timecreated' => 'privacy:metadata:local_customreports_saved:timecreated',
                'timemodified' => 'privacy:metadata:local_customreports_saved:timemodified'
            ],
            'privacy:metadata:local_customreports_saved'
        );

        $collection->add_database_table(
            'local_customreports_scheduled',
            [
                'reportid' => 'privacy:metadata:local_customreports_scheduled:reportid',
                'frequency' => 'privacy:metadata:local_customreports_scheduled:frequency',
                'schedule_time' => 'privacy:metadata:local_customreports_scheduled:schedule_time',
                'schedule_day' => 'privacy:metadata:local_customreports_scheduled:schedule_day',
                'lastsent' => 'privacy:metadata:local_customreports_scheduled:lastsent',
                'enabled' => 'privacy:metadata:local_customreports_scheduled:enabled'
            ],
            'privacy:metadata:local_customreports_scheduled'
        );

        return $collection;
    }

    /**
     * Get contexts where user data is stored.
     *
     * @param int $userid The user ID.
     * @return contextlist List of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get system context for saved reports.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                 INNER JOIN {local_customreports_saved} rs ON ctx.instanceid = :systemid AND ctx.contextlevel = :systemlevel
                 WHERE rs.userid = :userid";

        $params = [
            'systemid' => SYSCONTEXTID,
            'systemlevel' => CONTEXT_SYSTEM,
            'userid' => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        $sql = "SELECT userid FROM {local_customreports_saved}";
        $userlist->add_from_sql('userid', $sql, []);

        $sql = "SELECT userid FROM {local_customreports_scheduled}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            // Export saved reports.
            $savedreports = $DB->get_records('local_customreports_saved', ['userid' => $userid]);
            foreach ($savedreports as $report) {
                writer::with_context($context)->export_data(
                    [get_string('privacysavedreports', 'local_customreports'), $report->id],
                    (object) [
                        'name' => $report->name,
                        'description' => $report->description,
                        'visibility' => $report->visibility,
                        'timecreated' => userdate($report->timecreated),
                        'timemodified' => userdate($report->timemodified)
                    ]
                );
            }

            // Export scheduled reports.
            $scheduled = $DB->get_records_sql(
                "SELECT s.* FROM {local_customreports_scheduled} s
                          JOIN {local_customreports_saved} rs ON s.reportid = rs.id
                         WHERE rs.userid = :userid",
                ['userid' => $userid]
            );

            foreach ($scheduled as $schedule) {
                writer::with_context($context)->export_data(
                    [get_string('privacyscheduledreports', 'local_customreports'), $schedule->id],
                    (object) [
                        'frequency' => $schedule->frequency,
                        'schedule_time' => $schedule->schedule_time,
                        'schedule_day' => $schedule->schedule_day,
                        'lastsent' => $schedule->lastsent ? userdate($schedule->lastsent) : '',
                        'enabled' => $schedule->enabled
                    ]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_system) {
            return;
        }

        // Delete all saved reports.
        $DB->delete_records('local_customreports_saved');
        // Scheduled reports will be deleted via foreign key cascade.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            // Get report IDs to delete scheduled reports first.
            $reportids = $DB->get_fieldset_select('local_customreports_saved', 'id', 'userid = :userid', ['userid' => $userid]);
            
            if (!empty($reportids)) {
                list($insql, $inparams) = $DB->get_in_or_equal($reportids);
                $DB->delete_records_select('local_customreports_scheduled', "reportid $insql", $inparams);
            }

            // Delete saved reports.
            $DB->delete_records('local_customreports_saved', ['userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids);

        // Get report IDs to delete scheduled reports first.
        $reportids = $DB->get_fieldset_select(
            'local_customreports_saved',
            'id',
            "userid $insql",
            $inparams
        );

        if (!empty($reportids)) {
            list($reportinsql, $reportinparams) = $DB->get_in_or_equal($reportids);
            $DB->delete_records_select('local_customreports_scheduled', "reportid $reportinsql", $reportinparams);
        }

        // Delete saved reports.
        $DB->delete_records_select('local_customreports_saved', "userid $insql", $inparams);
    }
}
