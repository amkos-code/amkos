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
 * Dashboard module for Custom Reports
 *
 * @module      local_customreports/dashboard
 * @copyright   2024
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'local_customreports/charts'], 
function($, Ajax, Templates, Notification, Charts) {

    'use strict';

    const SELECTORS = {
        DASHBOARD: '.local-customreports-dashboard',
        WIDGET: '.report-widget',
        WIDGET_CONTAINER: '.widget-container',
        REFRESH_BUTTON: '[data-action="refresh-dashboard"]',
        EXPORT_BUTTON: '[data-action="export-dashboard"]',
        FILTER: '[data-filter]',
        PERIOD_BUTTON: '[data-period]',
        LOADING_OVERLAY: '.dashboard-loading-overlay'
    };

    const CONFIG = {
        defaultRefreshInterval: 300, // 5 minutes
        realtimeUpdateInterval: 30, // 30 seconds
        cacheTTL: 300
    };

    let dashboardInstance = null;
    let charts = {};
    let refreshTimer = null;
    let realtimeTimer = null;

    class Dashboard {
        constructor(options) {
            this.options = $.extend({}, CONFIG, options);
            this.element = $(SELECTORS.DASHBOARD);
            this.widgets = [];
            this.filters = {};
            
            this.init();
        }

        init() {
            console.log('Dashboard initialized');
            
            this.bindEvents();
            this.loadFilters();
            this.loadVisibleWidgets();
            this.startAutoRefresh();
            this.startRealtimeUpdates();
            
            // Setup intersection observer for lazy loading
            this.setupLazyLoading();
        }

        bindEvents() {
            // Global refresh
            this.element.on('click', SELECTORS.REFRESH_BUTTON, () => this.refreshAll());
            
            // Export dashboard
            this.element.on('click', SELECTORS.EXPORT_BUTTON, () => this.exportDashboard());
            
            // Widget refresh
            this.element.on('click', '[data-action="refresh-widget"]', (e) => {
                const widget = $(e.target).closest(SELECTORS.WIDGET_CONTAINER);
                this.refreshWidget(widget.data('widget-id'));
            });
            
            // Period change for daily activities
            this.element.on('click', SELECTORS.PERIOD_BUTTON, (e) => {
                const button = $(e.target);
                button.closest('.btn-group').find('.active').removeClass('active');
                button.addClass('active');
                this.loadDailyActivities(button.data('period'));
            });
            
            // Filter changes
            this.element.on('change', SELECTORS.FILTER, (e) => {
                const input = $(e.target);
                this.filters[input.data('filter')] = input.val();
                this.applyFilters();
            });
            
            // Expand widget
            this.element.on('click', '[data-action="expand-widget"]', (e) => {
                const widget = $(e.target).closest(SELECTORS.WIDGET);
                widget.toggleClass('widget-expanded');
            });
        }

        setupLazyLoading() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !entry.target.dataset.loaded) {
                        const widgetId = entry.target.dataset.widgetId;
                        this.loadWidget(widgetId, entry.target);
                        entry.target.dataset.loaded = 'true';
                    }
                });
            }, {
                rootMargin: '100px',
                threshold: 0.1
            });

            $(SELECTORS.WIDGET_CONTAINER).each((_, container) => {
                observer.observe(container);
            });
        }

        loadVisibleWidgets() {
            $(SELECTORS.WIDGET_CONTAINER).each((_, container) => {
                const $container = $(container);
                if (this.isElementInViewport(container)) {
                    const widgetId = $container.data('widget-id');
                    this.loadWidget(widgetId, container);
                    $container.data('loaded', 'true');
                }
            });
        }

        isElementInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top >= -100 &&
                rect.left >= -100 &&
                rect.bottom <= (window.innerHeight + 100) &&
                rect.right <= (window.innerWidth + 100)
            );
        }

        async loadWidget(widgetId, container) {
            const $container = $(container);
            $container.addClass('loading');
            
            try {
                const promise = Ajax.call([{
                    methodname: 'local_customreports_get_widget_data',
                    args: { 
                        widgetid: widgetId,
                        filters: this.filters
                    }
                }]);
                
                const data = await promise[0];
                this.renderWidget($container, data);
                
            } catch (error) {
                console.error('Error loading widget:', widgetId, error);
                Notification.exception(error);
            } finally {
                $container.removeClass('loading');
            }
        }

        renderWidget($container, data) {
            const widgetId = $container.data('widget-id');
            
            switch (widgetId) {
                case 'site-overview':
                    this.renderSiteOverview($container, data);
                    break;
                case 'course-progress':
                    this.renderCourseProgress($container, data);
                    break;
                case 'popular-courses':
                    this.renderPopularCourses($container, data);
                    break;
                case 'daily-activities':
                    this.renderDailyActivities($container, data);
                    break;
                case 'realtime-users':
                    this.renderRealtimeUsers($container, data);
                    break;
                case 'inactive-users':
                    this.renderInactiveUsers($container, data);
                    break;
                case 'certificates-stats':
                    this.renderCertificatesStats($container, data);
                    break;
            }
            
            // Update last update time
            const now = new Date();
            $container.find('.last-update').text(now.toLocaleTimeString());
        }

        renderSiteOverview($container, data) {
            const $widget = $container.find(SELECTORS.WIDGET);
            
            $widget.find('[data-stat="totalusers"]').text(data.totalusers);
            $widget.find('[data-stat="activetoday"]').text(data.activetoday);
            $widget.find('[data-stat="totalcourses"]').text(data.totalcourses);
            $widget.find('[data-stat="completedcourses"]').text(data.completedcourses);
            
            // Update growth indicators
            if (data.usergrowth > 0) {
                $widget.find('.stat-change').first()
                    .removeClass('text-danger').addClass('text-success')
                    .html(`<small>+${data.usergrowth}%</small>`);
            }
        }

        renderCourseProgress($container, data) {
            const canvasId = 'courseProgressChart';
            const canvas = document.getElementById(canvasId);
            
            if (!canvas) return;
            
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }
            
            charts[canvasId] = Charts.createBarChart(canvas, {
                labels: data.labels,
                datasets: [{
                    label: 'Courses',
                    data: data.values,
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',   // 0-25%
                        'rgba(255, 193, 7, 0.8)',   // 26-50%
                        'rgba(253, 126, 20, 0.8)',  // 51-75%
                        'rgba(40, 167, 69, 0.8)',   // 76-100%
                        'rgba(23, 162, 184, 0.8)',  // 100%
                    ],
                    borderColor: [
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(253, 126, 20, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                    ],
                    borderWidth: 1
                }]
            });
        }

        renderPopularCourses($container, data) {
            const $widget = $container.find(SELECTORS.WIDGET);
            const $tbody = $widget.find('tbody');
            
            let html = '';
            data.courses.forEach((course, index) => {
                const rankClass = index < 3 ? 'primary' : 'secondary';
                html += `
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-${rankClass} rounded-circle">${index + 1}</span>
                        </td>
                        <td>
                            <div class="course-name small fw-medium">${course.shortname}</div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: ${course.completionrate}%"></div>
                            </div>
                        </td>
                        <td class="text-end pe-3 fw-bold">${course.enrolled}</td>
                    </tr>
                `;
            });
            
            $tbody.html(html);
        }

        async loadDailyActivities(period = 30) {
            try {
                const promise = Ajax.call([{
                    methodname: 'local_customreports_get_widget_data',
                    args: { 
                        widgetid: 'daily-activities',
                        filters: {...this.filters, period: period}
                    }
                }]);
                
                const data = await promise[0];
                const $container = $(`[data-widget-id="daily-activities"]`);
                this.renderDailyActivities($container, data);
                
            } catch (error) {
                console.error('Error loading daily activities:', error);
            }
        }

        renderDailyActivities($container, data) {
            const canvasId = 'dailyActivitiesChart';
            const canvas = document.getElementById(canvasId);
            
            if (!canvas) return;
            
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }
            
            charts[canvasId] = Charts.createLineChart(canvas, {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Registrations',
                        data: data.registrations,
                        borderColor: 'rgba(15, 108, 191, 1)',
                        backgroundColor: 'rgba(15, 108, 191, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Enrollments',
                        data: data.enrollments,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Completions',
                        data: data.completions,
                        borderColor: 'rgba(253, 126, 20, 1)',
                        backgroundColor: 'rgba(253, 126, 20, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            });
        }

        renderRealtimeUsers($container, data) {
            const $widget = $container.find(SELECTORS.WIDGET);
            $('#online-count').text(data.onlinenow);
            $widget.find('[data-stat="lasthour"]').text(data.lasthour);
            $widget.find('[data-stat="today"]').text(data.today);
        }

        renderInactiveUsers($container, data) {
            const canvasId = 'inactiveUsersChart';
            const canvas = document.getElementById(canvasId);
            
            if (!canvas) return;
            
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }
            
            charts[canvasId] = Charts.createPieChart(canvas, {
                labels: ['7 days', '30 days', '90 days'],
                datasets: [{
                    data: [data.inactive7days, data.inactive30days, data.inactive90days],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderColor: [
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
                }]
            });
            
            // Update list
            const $widget = $container.find(SELECTORS.WIDGET);
            $widget.find('[data-stat="inactive7days"]').text(data.inactive7days);
            $widget.find('[data-stat="inactive30days"]').text(data.inactive30days);
            $widget.find('[data-stat="inactive90days"]').text(data.inactive90days);
        }

        renderCertificatesStats($container, data) {
            const canvasId = 'certificatesChart';
            const canvas = document.getElementById(canvasId);
            
            if (!canvas) return;
            
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }
            
            charts[canvasId] = Charts.createBarChart(canvas, {
                labels: data.courses.map(c => c.shortname),
                datasets: [{
                    label: 'Certificates Issued',
                    data: data.courses.map(c => c.count),
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                }]
            }, {
                indexAxis: 'y'
            });
        }

        loadFilters() {
            // Load cohorts and courses for filters
            Ajax.call([{
                methodname: 'local_customreports_get_filter_options',
                args: {}
            }])[0].then(data => {
                if (data.cohorts) {
                    let options = '<option value="">All</option>';
                    data.cohorts.forEach(cohort => {
                        options += `<option value="${cohort.id}">${cohort.name}</option>`;
                    });
                    $('#filter-cohort').html(options);
                }
                if (data.courses) {
                    let options = '<option value="">All</option>';
                    data.courses.forEach(course => {
                        options += `<option value="${course.id}">${course.fullname}</option>`;
                    });
                    $('#filter-course').html(options);
                }
            }).catch(Notification.exception);
        }

        applyFilters() {
            console.log('Applying filters:', this.filters);
            this.refreshAll();
        }

        async refreshWidget(widgetId) {
            const $container = $(`[data-widget-id="${widgetId}"]`);
            $container.data('loaded', 'false');
            await this.loadWidget(widgetId, $container[0]);
        }

        async refreshAll() {
            this.showLoading(true);
            
            try {
                const promises = [];
                $(SELECTORS.WIDGET_CONTAINER).each((_, container) => {
                    const $container = $(container);
                    $container.data('loaded', 'false');
                    promises.push(this.loadWidget($container.data('widget-id'), container));
                });
                
                await Promise.all(promises);
                
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                Notification.exception(error);
            } finally {
                this.showLoading(false);
            }
        }

        exportDashboard() {
            // TODO: Implement export functionality
            Notification.alert('Info', 'Export functionality will be available soon.');
        }

        startAutoRefresh() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
            
            refreshTimer = setInterval(() => {
                this.refreshAll();
            }, this.options.refreshInterval * 1000);
        }

        startRealtimeUpdates() {
            if (realtimeTimer) {
                clearInterval(realtimeTimer);
            }
            
            realtimeTimer = setInterval(async () => {
                try {
                    const $container = $(`[data-widget-id="realtime-users"]`);
                    if ($container.length && $container.data('loaded') === 'true') {
                        await this.refreshWidget('realtime-users');
                    }
                } catch (error) {
                    console.error('Error updating realtime users:', error);
                }
            }, this.options.realtimeUpdateInterval * 1000);
        }

        showLoading(show) {
            const $overlay = this.element.find(SELECTORS.LOADING_OVERLAY);
            if (show) {
                $overlay.css('display', 'flex');
            } else {
                $overlay.css('display', 'none');
            }
        }

        destroy() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
            if (realtimeTimer) {
                clearInterval(realtimeTimer);
            }
            
            Object.values(charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            
            this.element.off();
        }
    }

    return {
        init: function(options) {
            if (dashboardInstance) {
                dashboardInstance.destroy();
            }
            dashboardInstance = new Dashboard(options);
            return dashboardInstance;
        },
        
        getInstance: function() {
            return dashboardInstance;
        }
    };
});
