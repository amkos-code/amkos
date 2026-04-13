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
 * Main admin page for the group report plugin.
 *
 * @package    local_groupreport
 * @category   admin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/excellib.class.php');

admin_externalpage_setup('local_groupreport_report');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$action = optional_param('action', 'upload', PARAM_ALPHA);

// Set up the page
$pageurl = new moodle_url('/local/groupreport/report.php');
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('pluginname', 'local_groupreport'));
$PAGE->set_heading(get_string('pluginname', 'local_groupreport'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

if ($action === 'upload') {
    // Display upload form
    echo $OUTPUT->heading(get_string('uploadcsv', 'local_groupreport'), 3);
    
    $uploadform = new moodle_url('/local/groupreport/report.php', ['action' => 'process']);
    
    echo html_writer::start_tag('form', [
        'action' => $uploadform,
        'method' => 'POST',
        'enctype' => 'multipart/form-data',
        'class' => 'mform'
    ]);
    
    echo html_writer::start_tag('fieldset', ['class' => 'felement ffile']);
    echo html_writer::tag('label', get_string('selectcsv', 'local_groupreport'), ['for' => 'csvfile']);
    echo html_writer::empty_tag('input', [
        'type' => 'file',
        'id' => 'csvfile',
        'name' => 'csvfile',
        'required' => 'required',
        'accept' => '.csv'
    ]);
    echo html_writer::end_tag('fieldset');
    
    echo html_writer::start_tag('div', ['class' => 'form-buttons']);
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('uploadandgenerate', 'local_groupreport'),
        'class' => 'btn btn-primary'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('form');
    
    echo $OUTPUT->box_start('generalbox mt-4');
    echo html_writer::tag('p', get_string('csvformatinfo', 'local_groupreport'));
    echo html_writer::tag('p', get_string('csvexample', 'local_groupreport'));
    echo $OUTPUT->box_end();
    
} elseif ($action === 'process') {
    // Process uploaded CSV file
    if (!isset($_FILES['csvfile']) || $_FILES['csvfile']['error'] !== UPLOAD_ERR_OK) {
        echo $OUTPUT->notification(get_string('uploaderror', 'local_groupreport'), 'notifyproblem');
        echo html_writer::link(new moodle_url('/local/groupreport/report.php'), 
            get_string('backtoupload', 'local_groupreport'), 
            ['class' => 'btn btn-secondary']);
    } else {
        // Read CSV file
        $filecontent = file_get_contents($_FILES['csvfile']['tmp_name']);
        $lines = explode("\n", $filecontent);
        
        $groupnames = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Remove quotes if present
                $line = trim($line, "\"'");
                $groupnames[] = $line;
            }
        }
        
        if (empty($groupnames)) {
            echo $OUTPUT->notification(get_string('nogroupsincsv', 'local_groupreport'), 'notifyproblem');
        } else {
            // Find groups and generate report
            echo $OUTPUT->heading(get_string('generatingreport', 'local_groupreport'), 3);
            
            try {
                $reportdata = local_groupreport_generate_report($groupnames);
                
                if (empty($reportdata)) {
                    echo $OUTPUT->notification(get_string('noreportdata', 'local_groupreport'), 'notifywarning');
                } else {
                    // Display report summary
                    echo $OUTPUT->box_start('generalbox mb-3');
                    echo html_writer::tag('p', 
                        sprintf(get_string('reportsummary', 'local_groupreport'), 
                            count($reportdata['courses']), 
                            count($reportdata['groups']), 
                            count($reportdata['students'])));
                    echo $OUTPUT->box_end();
                    
                    // Export button
                    $exporturl = new moodle_url('/local/groupreport/report.php', [
                        'action' => 'export',
                        'sesskey' => sesskey()
                    ]);
                    
                    // Store report data in session for export
                    $SESSION->groupreport_data = $reportdata;
                    
                    echo html_writer::link($exporturl, 
                        get_string('exporttoexcel', 'local_groupreport'), 
                        ['class' => 'btn btn-success mb-3']);
                    
                    // Display preview table
                    echo $OUTPUT->heading(get_string('reportpreview', 'local_groupreport'), 4);
                    
                    $table = new html_table();
                    $table->head = [
                        get_string('coursefullname', 'local_groupreport'),
                        get_string('groupname', 'local_groupreport'),
                        get_string('studentname', 'local_groupreport'),
                        get_string('testgrade', 'local_groupreport')
                    ];
                    $table->attributes = ['class' => 'generaltable'];
                    $table->data = [];
                    
                    foreach ($reportdata['rows'] as $row) {
                        $table->data[] = [
                            $row['course'],
                            $row['group'],
                            $row['student'],
                            $row['grade']
                        ];
                    }
                    
                    echo html_writer::table($table);
                }
            } catch (Exception $e) {
                echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
            }
        }
        
        echo html_writer::link(new moodle_url('/local/groupreport/report.php'), 
            get_string('newreport', 'local_groupreport'), 
            ['class' => 'btn btn-secondary mt-3']);
    }
    
} elseif ($action === 'export') {
    // Export report to Excel
    require_sesskey();
    
    if (!isset($SESSION->groupreport_data)) {
        echo $OUTPUT->notification(get_string('nodatatoprocess', 'local_groupreport'), 'notifyproblem');
    } else {
        $reportdata = $SESSION->groupreport_data;
        
        // Create Excel file
        $filename = 'group_report_' . userdate(time(), '%Y%m%d_%H%M%S') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Transfer-Encoding: binary');
        
        // Create Excel workbook
        $workbook = new MoodleExcelWorkbook("-");
        $worksheet = $workbook->add_worksheet(get_string('report', 'local_groupreport'));
        
        // Set column headers
        $formats = [];
        $formats['header'] = $workbook->add_format([
            'bold' => 1,
            'border' => 2,
            'align' => 'center',
            'valign' => 'vcenter',
            'bg_color' => '#FFFF00'
        ]);
        
        $formats['normal'] = $workbook->add_format([
            'border' => 1,
            'align' => 'left',
            'valign' => 'vcenter'
        ]);
        
        $row = 0;
        $col = 0;
        
        // Headers
        $worksheet->write($row, $col++, get_string('coursefullname', 'local_groupreport'), $formats['header']);
        $worksheet->write($row, $col++, get_string('groupname', 'local_groupreport'), $formats['header']);
        $worksheet->write($row, $col++, get_string('studentname', 'local_groupreport'), $formats['header']);
        $worksheet->write($row, $col++, get_string('testgrade', 'local_groupreport'), $formats['header']);
        $row++;
        
        // Data rows
        foreach ($reportdata['rows'] as $data) {
            $col = 0;
            $worksheet->write($row, $col++, $data['course'], $formats['normal']);
            $worksheet->write($row, $col++, $data['group'], $formats['normal']);
            $worksheet->write($row, $col++, $data['student'], $formats['normal']);
            $worksheet->write($row, $col++, $data['grade'], $formats['normal']);
            $row++;
        }
        
        // Set column widths
        $worksheet->set_column(0, 0, 40);
        $worksheet->set_column(1, 1, 25);
        $worksheet->set_column(2, 2, 35);
        $worksheet->set_column(3, 3, 15);
        
        $workbook->close();
        exit;
    }
    
    echo html_writer::link(new moodle_url('/local/groupreport/report.php'), 
        get_string('backtoreport', 'local_groupreport'), 
        ['class' => 'btn btn-secondary']);
}

echo $OUTPUT->footer();

/**
 * Generate report data for the given groups
 *
 * @param array $groupnames Array of group names
 * @return array Report data
 */
function local_groupreport_generate_report(array $groupnames) {
    global $DB;
    
    $reportdata = [
        'courses' => [],
        'groups' => [],
        'students' => [],
        'rows' => []
    ];
    
    // Find all groups by name
    list($insql, $inparams) = $DB->get_in_or_equal($groupnames, SQL_PARAMS_NAMED);
    $groups = $DB->get_records_select('groups', "name $insql", $inparams);
    
    if (empty($groups)) {
        return $reportdata;
    }
    
    foreach ($groups as $group) {
        $reportdata['groups'][$group->id] = $group->name;
        
        // Get course for this group
        if (!isset($reportdata['courses'][$group->courseid])) {
            $course = get_course($group->courseid);
            if ($course) {
                $reportdata['courses'][$group->courseid] = $course->fullname;
            }
        }
        
        // Get group members
        $members = groups_get_members($group->id, 'u.*', 'u.lastname, u.firstname');
        
        foreach ($members as $member) {
            $reportdata['students'][$member->id] = fullname($member);
            
            // Get test grades for this student in this course
            $grades = local_groupreport_get_test_grades($member->id, $group->courseid);
            
            foreach ($grades as $grade) {
                $reportdata['rows'][] = [
                    'course' => $reportdata['courses'][$group->courseid],
                    'group' => $group->name,
                    'student' => fullname($member),
                    'grade' => $grade->grade
                ];
            }
        }
    }
    
    return $reportdata;
}

/**
 * Get test/quiz grades for a student in a course
 *
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @return array Array of grade objects
 */
function local_groupreport_get_test_grades($userid, $courseid) {
    global $DB;
    
    $grades = [];
    
    // Get all quiz activities in the course
    $quizzes = $DB->get_records('quiz', ['course' => $courseid]);
    
    foreach ($quizzes as $quiz) {
        // Get grade items for this quiz
        $gradeitems = $DB->get_records('grade_items', [
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'courseid' => $courseid
        ]);
        
        foreach ($gradeitems as $item) {
            // Get the grade for this student
            $grade = $DB->get_record('grade_grades', [
                'itemid' => $item->id,
                'userid' => $userid
            ]);
            
            if ($grade) {
                $finalgrade = $grade->finalgrade;
                if ($finalgrade !== null) {
                    $grades[] = (object)[
                        'quizname' => $quiz->name,
                        'grade' => round($finalgrade, 2)
                    ];
                }
            }
        }
    }
    
    // If no quizzes found, you might want to check for other activity types
    // For example, lesson module with questions
    if (empty($grades)) {
        // Check for lesson module grades
        $lessons = $DB->get_records('lesson', ['course' => $courseid]);
        
        foreach ($lessons as $lesson) {
            $gradeitems = $DB->get_records('grade_items', [
                'itemtype' => 'mod',
                'itemmodule' => 'lesson',
                'iteminstance' => $lesson->id,
                'courseid' => $courseid
            ]);
            
            foreach ($gradeitems as $item) {
                $grade = $DB->get_record('grade_grades', [
                    'itemid' => $item->id,
                    'userid' => $userid
                ]);
                
                if ($grade && $grade->finalgrade !== null) {
                    $grades[] = (object)[
                        'quizname' => $lesson->name,
                        'grade' => round($grade->finalgrade, 2)
                    ];
                }
            }
        }
    }
    
    return $grades;
}
