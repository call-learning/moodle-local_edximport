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

use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class completion extends base_output {
    /**
     * Export data for template
     *
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $coursemodel = $this->modeldata;
        $completionmodel = new stdClass();
        $completionmodel->coursecompletionaggrmethod = [];
        foreach (['$@NULL@$',
            COMPLETION_CRITERIA_TYPE_ACTIVITY,
            COMPLETION_CRITERIA_TYPE_COURSE,
            COMPLETION_CRITERIA_TYPE_ROLE] as $index => $aggregmethod) {
            $completionmodel->coursecompletionaggrmethod[] = (object) [
                'id' => $index,
                'course' => $coursemodel->id,
                'criteriatype' => $aggregmethod,
                'method' => COMPLETION_AGGREGATION_ALL,
                'values' => '$@NULL@$'
            ];
        }
        return $completionmodel;
    }
}
