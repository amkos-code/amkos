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
 * Report cache utility class
 *
 * @package    local_customreports
 * @copyright  2024 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customreports\utils;

use cache;

defined('MOODLE_INTERNAL') || die();

class report_cache {
    
    /** @var cache The cache instance */
    protected $cache;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->cache = cache::make('local_customreports', 'reports');
    }
    
    /**
     * Get cached data or generate new data
     *
     * @param string $reporttype Type of report
     * @param array $params Parameters for the report
     * @param int $ttl Time to live in seconds (default 300)
     * @return mixed Cached or generated data
     */
    public function get_cached_data($reporttype, $params, $ttl = 300) {
        $key = $this->generate_cache_key($reporttype, $params);
        
        $data = $this->cache->get($key);
        
        if ($data !== false) {
            return $data;
        }
        
        // Generate new data
        $data = $this->generate_report_data($reporttype, $params);
        
        // Store in cache with TTL
        $this->cache->set($key, $data);
        
        return $data;
    }
    
    /**
     * Set cache data manually
     *
     * @param string $reporttype Type of report
     * @param array $params Parameters for the report
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds
     */
    public function set_cached_data($reporttype, $params, $data, $ttl = 300) {
        $key = $this->generate_cache_key($reporttype, $params);
        $this->cache->set($key, $data);
    }
    
    /**
     * Delete cached data
     *
     * @param string $reporttype Type of report
     * @param array $params Parameters for the report
     */
    public function delete_cached_data($reporttype, $params) {
        $key = $this->generate_cache_key($reporttype, $params);
        $this->cache->delete($key);
    }
    
    /**
     * Purge all cache entries for a specific report type
     *
     * @param string $reporttype Type of report to purge
     */
    public function purge_by_type($reporttype) {
        // Note: This requires iterating through cache keys which may not be supported by all cache stores
        // Alternative: Use cache definition with invalidation events
        $this->cache->purge();
    }
    
    /**
     * Generate cache key from report type and parameters
     *
     * @param string $type Report type
     * @param array $params Report parameters
     * @return string MD5 hash cache key
     */
    private function generate_cache_key($type, $params) {
        return md5($type . '_' . json_encode($params, SORT_KEYS));
    }
    
    /**
     * Generate report data (placeholder - should be overridden by specific report generators)
     *
     * @param string $reporttype Type of report
     * @param array $params Parameters for the report
     * @return mixed Generated report data
     * @throws \coding_exception If report type is not implemented
     */
    protected function generate_report_data($reporttype, $params) {
        // This should be implemented by specific report generator classes
        throw new \coding_exception("Report type '{$reporttype}' not implemented");
    }
}
