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
 * Charts helper module for Custom Reports
 *
 * @module      local_customreports/charts
 * @copyright   2024
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['chartjs'], function(Chart) {

    'use strict';

    // Global Chart.js defaults
    const defaultConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        family: "'Segoe UI', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                cornerRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += context.parsed.y;
                        } else if (context.parsed.x !== null) {
                            label += context.parsed.x;
                        } else if (context.parsed !== null) {
                            label += context.parsed;
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        }
    };

    /**
     * Create a bar chart
     *
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {Object} data - Chart data
     * @param {Object} options - Additional options
     * @returns {Chart} Chart instance
     */
    function createBarChart(canvas, data, options = {}) {
        const config = {
            type: 'bar',
            data: data,
            options: {
                ...defaultConfig,
                ...options
            }
        };

        return new Chart(canvas, config);
    }

    /**
     * Create a line chart
     *
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {Object} data - Chart data
     * @param {Object} options - Additional options
     * @returns {Chart} Chart instance
     */
    function createLineChart(canvas, data, options = {}) {
        const config = {
            type: 'line',
            data: data,
            options: {
                ...defaultConfig,
                ...options,
                plugins: {
                    ...defaultConfig.plugins,
                    tooltip: {
                        ...defaultConfig.plugins.tooltip,
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        };

        return new Chart(canvas, config);
    }

    /**
     * Create a pie chart
     *
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {Object} data - Chart data
     * @param {Object} options - Additional options
     * @returns {Chart} Chart instance
     */
    function createPieChart(canvas, data, options = {}) {
        const config = {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                ...options
            }
        };

        return new Chart(canvas, config);
    }

    /**
     * Create a doughnut chart
     *
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {Object} data - Chart data
     * @param {Object} options - Additional options
     * @returns {Chart} Chart instance
     */
    function createDoughnutChart(canvas, data, options = {}) {
        const config = {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                ...options
            }
        };

        return new Chart(canvas, config);
    }

    /**
     * Create a horizontal bar chart
     *
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {Object} data - Chart data
     * @param {Object} options - Additional options
     * @returns {Chart} Chart instance
     */
    function createHorizontalBarChart(canvas, data, options = {}) {
        const config = {
            type: 'bar',
            data: data,
            options: {
                ...defaultConfig,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                },
                ...options
            }
        };

        return new Chart(canvas, config);
    }

    /**
     * Create a radar chart
     *
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {Object} data - Chart data
     * @param {Object} options - Additional options
     * @returns {Chart} Chart instance
     */
    function createRadarChart(canvas, data, options = {}) {
        const config = {
            type: 'radar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        pointLabels: {
                            font: {
                                size: 12
                            }
                        },
                        ticks: {
                            backdropColor: 'transparent',
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                ...options
            }
        };

        return new Chart(canvas, config);
    }

    /**
     * Update chart with new data
     *
     * @param {Chart} chart - Chart instance
     * @param {Object} newData - New chart data
     */
    function updateChart(chart, newData) {
        if (!chart) return;

        chart.data.labels = newData.labels || chart.data.labels;
        
        newData.datasets.forEach((dataset, index) => {
            if (chart.data.datasets[index]) {
                chart.data.datasets[index].data = dataset.data;
                if (dataset.backgroundColor) {
                    chart.data.datasets[index].backgroundColor = dataset.backgroundColor;
                }
                if (dataset.borderColor) {
                    chart.data.datasets[index].borderColor = dataset.borderColor;
                }
            }
        });

        chart.update('active');
    }

    /**
     * Destroy chart instance
     *
     * @param {Chart} chart - Chart instance to destroy
     */
    function destroyChart(chart) {
        if (chart) {
            chart.destroy();
        }
    }

    /**
     * Get gradient for chart
     *
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {string} type - Gradient type (blue, green, orange, red)
     * @returns {CanvasGradient} Gradient
     */
    function getGradient(ctx, type) {
        const gradients = {
            blue: ctx.createLinearGradient(0, 0, 0, 400),
            green: ctx.createLinearGradient(0, 0, 0, 400),
            orange: ctx.createLinearGradient(0, 0, 0, 400),
            red: ctx.createLinearGradient(0, 0, 0, 400)
        };

        gradients.blue.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
        gradients.blue.addColorStop(1, 'rgba(118, 75, 162, 0.8)');

        gradients.green.addColorStop(0, 'rgba(11, 163, 96, 0.8)');
        gradients.green.addColorStop(1, 'rgba(60, 186, 146, 0.8)');

        gradients.orange.addColorStop(0, 'rgba(240, 147, 251, 0.8)');
        gradients.orange.addColorStop(1, 'rgba(245, 87, 108, 0.8)');

        gradients.red.addColorStop(0, 'rgba(220, 53, 69, 0.8)');
        gradients.red.addColorStop(1, 'rgba(253, 126, 20, 0.8)');

        return gradients[type] || gradients.blue;
    }

    return {
        createBarChart: createBarChart,
        createLineChart: createLineChart,
        createPieChart: createPieChart,
        createDoughnutChart: createDoughnutChart,
        createHorizontalBarChart: createHorizontalBarChart,
        createRadarChart: createRadarChart,
        updateChart: updateChart,
        destroyChart: destroyChart,
        getGradient: getGradient,
        defaultConfig: defaultConfig
    };
});
