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
 * Main dashboard page for Custom Analytics Reports
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/customreports:viewdashboard', $context);

$PAGE->set_url('/local/customreports/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_customreports'));
$PAGE->set_heading(get_string('pluginname', 'local_customreports'));
$PAGE->navbar->add(get_string('pluginname', 'local_customreports'));

// Load required JavaScript
$PAGE->requires->js_call_amd('local_customreports/dashboard', 'init');
$PAGE->requires->css('/local/customreports/styles.css');

echo $OUTPUT->header();

// Dashboard content
?>
<div class="customreports-dashboard container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fa fa-chart-line"></i> <?php echo get_string('dashboard', 'local_customreports'); ?></h2>
            <p class="text-muted"><?php echo get_string('dashboarddesc', 'local_customreports'); ?></p>
        </div>
    </div>

    <!-- Widgets Grid -->
    <div class="row" id="dashboard-widgets">
        <!-- Site Overview -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card report-widget" data-widget-id="site-overview">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-globe text-primary"></i> <?php echo get_string('siteoverview', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Progress -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card report-widget" data-widget-id="course-progress">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-chart-bar text-success"></i> <?php echo get_string('courseprogress', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <canvas id="courseProgressChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Courses -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card report-widget" data-widget-id="popular-courses">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-star text-warning"></i> <?php echo get_string('popularcourses', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Activities -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card report-widget" data-widget-id="daily-activities">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-calendar-alt text-info"></i> <?php echo get_string('dailyactivities', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <canvas id="dailyActivitiesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Users -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card report-widget" data-widget-id="realtime-users">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-users text-success"></i> <?php echo get_string('realtimeusers', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inactive Users -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card report-widget" data-widget-id="inactive-users">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-user-clock text-danger"></i> <?php echo get_string('inactiveusers', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <canvas id="inactiveUsersChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificates Stats -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card report-widget" data-widget-id="certificates-stats">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-certificate text-warning"></i> <?php echo get_string('certificatesstats', 'local_customreports'); ?></h5>
                    <button class="btn btn-sm btn-link" data-action="refresh"><i class="fa fa-sync"></i></button>
                </div>
                <div class="card-body">
                    <div class="widget-content" data-loading="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Reports Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h3><?php echo get_string('additionalreports', 'local_customreports'); ?></h3>
        </div>
        <div class="col-md-4 mb-3">
            <a href="<?php echo $CFG->wwwroot; ?>/local/customreports/report/courseprogress.php" class="btn btn-outline-primary btn-block">
                <i class="fa fa-chart-line"></i> <?php echo get_string('courseprogressreport', 'local_customreports'); ?>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="<?php echo $CFG->wwwroot; ?>/local/customreports/report/studentengagement.php" class="btn btn-outline-success btn-block">
                <i class="fa fa-users"></i> <?php echo get_string('studentengagementreport', 'local_customreports'); ?>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="<?php echo $CFG->wwwroot; ?>/local/customreports/report/timetracking.php" class="btn btn-outline-info btn-block">
                <i class="fa fa-clock"></i> <?php echo get_string('timetrackingreport', 'local_customreports'); ?>
            </a>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
