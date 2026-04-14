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
 * Export module for Custom Reports
 *
 * @module      local_customreports/export
 * @copyright   2024
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], 
function($, Ajax, Notification, Str) {

    'use strict';

    /**
     * Export report to PDF
     *
     * @param {string} reportType - Type of report
     * @param {Object} data - Report data
     * @param {string} filename - Output filename
     */
    function exportToPDF(reportType, data, filename) {
        const promise = Ajax.call([{
            methodname: 'local_customreports_export_report',
            args: {
                reporttype: reportType,
                format: 'pdf',
                data: JSON.stringify(data),
                filename: filename
            }
        }]);

        promise[0].then(result => {
            if (result.success) {
                downloadFile(result.fileurl);
                Str.get_string('exportsuccess', 'local_customreports')
                    .done(msg => Notification.alert('Success', msg));
            } else {
                throw new Error(result.error);
            }
        }).catch(Notification.exception);
    }

    /**
     * Export report to Excel
     *
     * @param {string} reportType - Type of report
     * @param {Object} data - Report data
     * @param {string} filename - Output filename
     */
    function exportToExcel(reportType, data, filename) {
        const promise = Ajax.call([{
            methodname: 'local_customreports_export_report',
            args: {
                reporttype: reportType,
                format: 'excel',
                data: JSON.stringify(data),
                filename: filename
            }
        }]);

        promise[0].then(result => {
            if (result.success) {
                downloadFile(result.fileurl);
                Str.get_string('exportsuccess', 'local_customreports')
                    .done(msg => Notification.alert('Success', msg));
            } else {
                throw new Error(result.error);
            }
        }).catch(Notification.exception);
    }

    /**
     * Export report to CSV
     *
     * @param {string} reportType - Type of report
     * @param {Object} data - Report data
     * @param {string} filename - Output filename
     */
    function exportToCSV(reportType, data, filename) {
        const promise = Ajax.call([{
            methodname: 'local_customreports_export_report',
            args: {
                reporttype: reportType,
                format: 'csv',
                data: JSON.stringify(data),
                filename: filename
            }
        }]);

        promise[0].then(result => {
            if (result.success) {
                downloadFile(result.fileurl);
                Str.get_string('exportsuccess', 'local_customreports')
                    .done(msg => Notification.alert('Success', msg));
            } else {
                throw new Error(result.error);
            }
        }).catch(Notification.exception);
    }

    /**
     * Export report to JSON
     *
     * @param {string} reportType - Type of report
     * @param {Object} data - Report data
     * @param {string} filename - Output filename
     */
    function exportToJSON(reportType, data, filename) {
        const promise = Ajax.call([{
            methodname: 'local_customreports_export_report',
            args: {
                reporttype: reportType,
                format: 'json',
                data: JSON.stringify(data),
                filename: filename
            }
        }]);

        promise[0].then(result => {
            if (result.success) {
                downloadFile(result.fileurl);
                Str.get_string('exportsuccess', 'local_customreports')
                    .done(msg => Notification.alert('Success', msg));
            } else {
                throw new Error(result.error);
            }
        }).catch(Notification.exception);
    }

    /**
     * Download file from URL
     *
     * @param {string} url - File URL
     */
    function downloadFile(url) {
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Export dashboard as image (PNG)
     *
     * @param {string} selector - CSS selector for dashboard element
     * @param {string} filename - Output filename
     */
    function exportDashboardAsImage(selector, filename) {
        // Note: This requires html2canvas library
        if (typeof html2canvas === 'undefined') {
            Str.get_string('html2canvasnotloaded', 'local_customreports')
                .done(msg => Notification.alert('Error', msg));
            return;
        }

        const element = document.querySelector(selector);
        if (!element) {
            Notification.alert('Error', 'Dashboard element not found');
            return;
        }

        html2canvas(element, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        }).then(canvas => {
            canvas.toBlob(blob => {
                const url = URL.createObjectURL(blob);
                downloadFile(url);
                URL.revokeObjectURL(url);
                Str.get_string('exportsuccess', 'local_customreports')
                    .done(msg => Notification.alert('Success', msg));
            }, 'image/png');
        }).catch(error => {
            console.error('Export error:', error);
            Notification.exception(error);
        });
    }

    /**
     * Print report
     *
     * @param {string} selector - CSS selector for report element
     */
    function printReport(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            Notification.alert('Error', 'Report element not found');
            return;
        }

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Custom Reports - Print</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .report-widget { break-inside: avoid; margin-bottom: 20px; }
                    @media print {
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    /**
     * Schedule report export
     *
     * @param {number} reportId - Report ID
     * @param {string} frequency - Frequency (daily, weekly, monthly)
     * @param {string} format - Export format
     * @param {string} email - Recipient email
     */
    function scheduleReport(reportId, frequency, format, email) {
        const promise = Ajax.call([{
            methodname: 'local_customreports_schedule_report',
            args: {
                reportid: reportId,
                frequency: frequency,
                format: format,
                email: email
            }
        }]);

        promise[0].then(result => {
            if (result.success) {
                Str.get_string('schedulesuccess', 'local_customreports')
                    .done(msg => Notification.alert('Success', msg));
            } else {
                throw new Error(result.error);
            }
        }).catch(Notification.exception);
    }

    return {
        exportToPDF: exportToPDF,
        exportToExcel: exportToExcel,
        exportToCSV: exportToCSV,
        exportToJSON: exportToJSON,
        exportDashboardAsImage: exportDashboardAsImage,
        printReport: printReport,
        scheduleReport: scheduleReport,
        downloadFile: downloadFile
    };
});
