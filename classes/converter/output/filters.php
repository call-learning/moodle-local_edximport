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
 * Plugin to import edX archive and convert it into a course
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\converter\output;

use local_edximport\converter\entity_pool;
use local_edximport\converter\ref_manager;
use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class filters extends base_output {
    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return array|mixed|object|stdClass|null
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        if (!empty($this->modeldata) && !empty($this->modeldata->actives)) {
            $data->filteractives = [];
            foreach ($this->modeldata->actives as $filtername => $status) {
                $data->filteractives[] = (object) ['name' => $filtername, 'status' => $status];
            }
        }
        if (!empty($this->modeldata) && !empty($this->modeldata->configs)) {
            $data->filterconfigs = $this->modeldata->configs;
        }

        return $data;
    }
}
