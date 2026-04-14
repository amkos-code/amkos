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
 * Export Handler for Reports
 *
 * @package    local_customreports
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\export;

defined('MOODLE_INTERNAL') || die();

/**
 * Report Export Handler
 */
class report_exporter {

    /** @var array Data to export */
    protected $data;

    /** @var string Filename */
    protected $filename;

    /** @var string Format */
    protected $format;

    /**
     * Constructor
     *
     * @param array $data Data to export
     * @param string $filename Filename
     * @param string $format Export format (csv|excel|pdf|json)
     */
    public function __construct($data, $filename = 'report', $format = 'csv') {
        $this->data = $data;
        $this->filename = $filename;
        $this->format = strtolower($format);
    }

    /**
     * Export data
     *
     * @return string File content or download URL
     */
    public function export() {
        switch ($this->format) {
            case 'csv':
                return $this->export_csv();
            case 'excel':
            case 'xlsx':
                return $this->export_excel();
            case 'pdf':
                return $this->export_pdf();
            case 'json':
                return $this->export_json();
            default:
                throw new \moodle_exception('invalid_export_format', 'local_customreports');
        }
    }

    /**
     * Export to CSV
     *
     * @return string CSV content
     */
    protected function export_csv() {
        if (empty($this->data)) {
            return '';
        }

        // Get headers from first row keys
        $headers = array_keys((array)$this->data[0]);
        
        $output = fopen('php://temp', 'r+');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($this->data as $row) {
            $row = (array)$row;
            $cleanrow = [];
            foreach ($headers as $header) {
                $value = isset($row[$header]) ? $row[$header] : '';
                // Handle nested arrays/objects
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $cleanrow[] = $value;
            }
            fputcsv($output, $cleanrow);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Export to Excel (XLSX)
     *
     * @return string Base64 encoded XLSX or file path
     */
    protected function export_excel() {
        // Check if PHPSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to simple XML-based Excel
            return $this->export_excel_xml();
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (empty($this->data)) {
            return $this->save_spreadsheet($spreadsheet);
        }

        // Get headers
        $headers = array_keys((array)$this->data[0]);
        
        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Style headers
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFont()->setBold(true)
            ->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('0F6CBF');
        
        // Write data
        $rownum = 2;
        foreach ($this->data as $row) {
            $col = 'A';
            $row = (array)$row;
            foreach ($headers as $header) {
                $value = isset($row[$header]) ? $row[$header] : '';
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $sheet->setCellValue($col . $rownum, $value);
                $col++;
            }
            $rownum++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        return $this->save_spreadsheet($spreadsheet);
    }

    /**
     * Save spreadsheet to temp file
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @return string File path
     */
    protected function save_spreadsheet($spreadsheet) {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filepath = make_temp_directory('customreports') . '/' . $this->filename . '.xlsx';
        $writer->save($filepath);
        return $filepath;
    }

    /**
     * Export to Excel XML (fallback)
     *
     * @return string XML content
     */
    protected function export_excel_xml() {
        if (empty($this->data)) {
            return '';
        }

        $headers = array_keys((array)$this->data[0]);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        // Styles
        $xml .= '<Styles>' . "\n";
        $xml .= '<Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF"/>' . "\n";
        $xml .= '<Interior ss:Color="#0F6CBF" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '</Styles>' . "\n";
        
        $xml .= '<Worksheet ss:Name="' . $this->filename . '">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Header row
        $xml .= '<Row>' . "\n";
        foreach ($headers as $header) {
            $xml .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . 
                    htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Data rows
        foreach ($this->data as $row) {
            $xml .= '<Row>' . "\n";
            $row = (array)$row;
            foreach ($headers as $header) {
                $value = isset($row[$header]) ? $row[$header] : '';
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $type = is_numeric($value) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . 
                        htmlspecialchars($value) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }

    /**
     * Export to PDF
     *
     * @return string PDF content or file path
     */
    protected function export_pdf() {
        // Use TCPDF which is included in Moodle
        global $CFG;
        require_once($CFG->libdir . '/pdflib.php');

        $pdf = new \pdf();
        $pdf->AddPage();
        
        // Set font
        $pdf->setFont('helvetica', '', 12);
        
        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, ucfirst($this->filename), 0, 1, 'C');
        $pdf->Ln(5);
        
        if (empty($this->data)) {
            $pdf->Cell(0, 10, get_string('nodata', 'local_customreports'), 0, 1, 'C');
            return $pdf->Output('', 'S');
        }

        // Get headers
        $headers = array_keys((array)$this->data[0]);
        
        // Calculate column widths
        $width = 190 / count($headers);
        
        // Header row
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(15, 108, 191); // Moodle blue
        $pdf->SetTextColor(255, 255, 255);
        
        foreach ($headers as $header) {
            $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Data rows
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        
        $fill = false;
        foreach ($this->data as $row) {
            $row = (array)$row;
            foreach ($headers as $header) {
                $value = isset($row[$header]) ? $row[$header] : '';
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $pdf->Cell($width, 8, $value, 1, 0, 'L', $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }
        
        return $pdf->Output('', 'S');
    }

    /**
     * Export to JSON
     *
     * @return string JSON content
     */
    protected function export_json() {
        return json_encode([
            'filename' => $this->filename,
            'generated' => date('Y-m-d H:i:s'),
            'data' => $this->data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send file for download
     *
     * @param string $content File content
     * @param string $mimetype MIME type
     */
    public static function send_download($content, $filename, $mimetype) {
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $content;
        exit;
    }

    /**
     * Get MIME type for format
     *
     * @param string $format Format
     * @return string MIME type
     */
    public static function get_mime_type($format) {
        $types = [
            'csv' => 'text/csv; charset=UTF-8',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            'json' => 'application/json'
        ];
        return $types[strtolower($format)] ?? 'application/octet-stream';
    }
}
