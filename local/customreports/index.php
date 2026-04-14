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
 * Custom Reports Dashboard Page
 *
 * @package    local_customreports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

// Require login and capability
require_login();
$context = context_system::instance();
require_capability('local/customreports:viewdashboard', $context);

// Setup page
$pageurl = new moodle_url('/local/customreports/index.php');
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_customreports'));
$PAGE->set_heading(get_string('dashboard', 'local_customreports'));
$PAGE->navbar->add(get_string('pluginname', 'local_customreports'), new moodle_url('/admin/settings.php?section=local_customreports'));
$PAGE->navbar->add(get_string('dashboard', 'local_customreports'));

// Load CSS
$PAGE->requires->css('/local/customreports/styles.css');

// Load JavaScript modules
$PAGE->requires->js_call_amd('local_customreports/dashboard', 'init', [
    [
        'refreshInterval' => get_config('local_customreports', 'refresh_interval') ?: 300,
        'widgets' => [
            'site-overview' => true,
            'course-progress' => true,
            'popular-courses' => true,
            'daily-activities' => true,
            'realtime-users' => true,
            'inactive-users' => true,
            'certificates-stats' => true
        ]
    ]
]);

// Get data for initial render
$generator = new \local_customreports\utils\dashboard_generator();
$data = $generator->get_all_widgets_data();

// Render template
echo $OUTPUT->header();

$templatecontext = [
    'totalusers' => $data['site-overview']['totalusers'],
    'activetoday' => $data['site-overview']['activetoday'],
    'totalcourses' => $data['site-overview']['totalcourses'],
    'completedcourses' => $data['site-overview']['completedcourses'],
    'usergrowth' => $data['site-overview']['usergrowth'],
    'activegrowth' => $data['site-overview']['activegrowth'],
    'completionrate' => $data['site-overview']['completionrate'],
    'lastupdate' => userdate(time(), get_string('strftimetime', 'langconfig')),
    'onlinenow' => $data['realtime-users']['onlinenow'],
    'lasthour' => $data['realtime-users']['lasthour'],
    'today' => $data['realtime-users']['today'],
    'inactive7days' => $data['inactive-users']['inactive7days'],
    'inactive30days' => $data['inactive-users']['inactive30days'],
    'inactive90days' => $data['inactive-users']['inactive90days'],
    'popularcourses' => array_map(function($course, $index) {
        return [
            'rank' => $index + 1,
            'rankclass' => $index < 3 ? 'primary' : 'secondary',
            'shortname' => $course['shortname'],
            'enrolled' => $course['enrolled'],
            'completionrate' => $course['completionrate']
        ];
    }, $data['popular-courses']['courses'], array_keys($data['popular-courses']['courses'])),
    'certificates' => $data['certificates-stats']['courses'],
    'refreshinterval' => get_config('local_customreports', 'refresh_interval') ?: 300,
    'widgetsconfig' => json_encode([
        'site-overview' => true,
        'course-progress' => true,
        'popular-courses' => true,
        'daily-activities' => true,
        'realtime-users' => true,
        'inactive-users' => true,
        'certificates-stats' => true
    ])
];

echo $OUTPUT->render_from_template('local_customreports/dashboard', $templatecontext);

echo $OUTPUT->footer();
