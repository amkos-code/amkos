// AMD Module for Custom Analytics Reports Dashboard
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, Ajax, Templates, Notification) {
    
    'use strict';
    
    var Dashboard = {
        
        init: function() {
            console.log('Custom Analytics Reports Dashboard initialized');
            this.loadWidgets();
            this.setupRefreshHandlers();
            this.startAutoRefresh();
        },
        
        loadWidgets: function() {
            var widgets = document.querySelectorAll('.report-widget');
            var self = this;
            
            widgets.forEach(function(widget) {
                var widgetId = widget.dataset.widgetId;
                self.loadWidgetData(widget, widgetId);
            });
        },
        
        loadWidgetData: function(widget, widgetId) {
            var contentDiv = widget.querySelector('.widget-content');
            var self = this;
            
            var promises = Ajax.call([{
                methodname: 'local_customreports_get_dashboard_data',
                args: { widgetid: widgetId }
            }]);
            
            promises[0].done(function(data) {
                self.renderWidget(widget, data);
            }).fail(function(error) {
                contentDiv.innerHTML = '<div class="alert alert-danger">Error loading data</div>';
                console.error('Error loading widget data:', error);
            });
        },
        
        renderWidget: function(widget, data) {
            var contentDiv = widget.querySelector('.widget-content');
            var widgetId = widget.dataset.widgetId;
            
            switch(widgetId) {
                case 'site-overview':
                    this.renderSiteOverview(contentDiv, data);
                    break;
                case 'course-progress':
                    this.renderCourseProgress(contentDiv, data);
                    break;
                case 'popular-courses':
                    this.renderPopularCourses(contentDiv, data);
                    break;
                case 'daily-activities':
                    this.renderDailyActivities(contentDiv, data);
                    break;
                case 'realtime-users':
                    this.renderRealtimeUsers(contentDiv, data);
                    break;
                case 'inactive-users':
                    this.renderInactiveUsers(contentDiv, data);
                    break;
                case 'certificates-stats':
                    this.renderCertificatesStats(contentDiv, data);
                    break;
            }
        },
        
        renderSiteOverview: function(container, data) {
            if (!data || !data.stats) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var html = '<div class="row">';
            html += '<div class="col-6 stat-card">';
            html += '<div class="stat-value">' + (data.stats.total_users || 0) + '</div>';
            html += '<div class="stat-label">Total Users</div>';
            html += '<div class="stat-change positive">+' + (data.stats.users_growth || 0) + '%</div>';
            html += '</div>';
            html += '<div class="col-6 stat-card">';
            html += '<div class="stat-value">' + (data.stats.active_courses || 0) + '</div>';
            html += '<div class="stat-label">Active Courses</div>';
            html += '</div>';
            html += '<div class="col-6 stat-card mt-2">';
            html += '<div class="stat-value">' + (data.stats.active_today || 0) + '</div>';
            html += '<div class="stat-label">Active Today</div>';
            html += '</div>';
            html += '<div class="col-6 stat-card mt-2">';
            html += '<div class="stat-value">' + (data.stats.completed_courses || 0) + '</div>';
            html += '<div class="stat-label">Completed</div>';
            html += '</div>';
            html += '</div>';
            
            container.innerHTML = html;
        },
        
        renderCourseProgress: function(container, data) {
            if (!data || !data.labels) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var canvas = container.querySelector('canvas');
            if (canvas && typeof Chart !== 'undefined') {
                var ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Courses',
                            data: data.values,
                            backgroundColor: [
                                '#dc3545',
                                '#ffc107',
                                '#fd7e14',
                                '#28a745',
                                '#20c997',
                                '#007bff'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        },
        
        renderPopularCourses: function(container, data) {
            if (!data || !data.courses) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var html = '<ul class="list-group list-group-flush">';
            data.courses.forEach(function(course, index) {
                html += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                html += '<span>' + (index + 1) + '. ' + course.name + '</span>';
                html += '<span class="badge badge-primary">' + course.enrolled + '</span>';
                html += '</li>';
            });
            html += '</ul>';
            
            container.innerHTML = html;
        },
        
        renderDailyActivities: function(container, data) {
            if (!data || !data.labels) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var canvas = container.querySelector('canvas');
            if (canvas && typeof Chart !== 'undefined') {
                var ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Activities',
                            data: data.values,
                            borderColor: '#0f6cbf',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(15, 108, 191, 0.1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        },
        
        renderRealtimeUsers: function(container, data) {
            if (!data) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var html = '<div class="text-center">';
            html += '<h2 class="text-success">' + (data.online_now || 0) + '</h2>';
            html += '<p class="text-muted">Users Online Now</p>';
            html += '<hr>';
            html += '<div class="row">';
            html += '<div class="col-6"><small>Last Hour: ' + (data.last_hour || 0) + '</small></div>';
            html += '<div class="col-6"><small>Today: ' + (data.today || 0) + '</small></div>';
            html += '</div>';
            html += '</div>';
            
            container.innerHTML = html;
        },
        
        renderInactiveUsers: function(container, data) {
            if (!data || !data.labels) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var canvas = container.querySelector('canvas');
            if (canvas && typeof Chart !== 'undefined') {
                var ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },
        
        renderCertificatesStats: function(container, data) {
            if (!data) {
                container.innerHTML = '<div class="text-muted">No data</div>';
                return;
            }
            
            var html = '<div class="text-center">';
            html += '<h3 class="text-warning">' + (data.total || 0) + '</h3>';
            html += '<p class="text-muted">Total Certificates</p>';
            html += '<p><small>This Month: ' + (data.this_month || 0) + '</small></p>';
            html += '</div>';
            
            container.innerHTML = html;
        },
        
        setupRefreshHandlers: function() {
            var self = this;
            document.querySelectorAll('[data-action="refresh"]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var widget = this.closest('.report-widget');
                    var widgetId = widget.dataset.widgetId;
                    self.loadWidgetData(widget, widgetId);
                });
            });
        },
        
        startAutoRefresh: function() {
            var self = this;
            setInterval(function() {
                var realtimeWidget = document.querySelector('[data-widget-id="realtime-users"]');
                if (realtimeWidget) {
                    self.loadWidgetData(realtimeWidget, 'realtime-users');
                }
            }, 30000); // Refresh every 30 seconds
        }
    };
    
    return Dashboard;
});
