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

class inforef extends base_output {

    protected $entityid;
    protected $type;

    public function __construct($modeldata, $type, $entityid) {
        parent::__construct($modeldata);
        $this->type = $type;
        $this->entityid = $entityid;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return array|mixed|object|stdClass|null
     */
    public function export_for_template(renderer_base $output) {
        /** @var ref_manager $entityrefs */
        $entityrefs = $this->modeldata;
        $refdata = new stdClass();
        $refdata->rolerefs = $entityrefs->get_refs($this->type, $this->entityid, 'role');
        $refdata->questioncategoryrefs = $entityrefs->get_refs($this->type, $this->entityid, 'question_category');
        $refdata->filerefs = $entityrefs->get_refs($this->type, $this->entityid, 'file');
        $refdata->hasrolerefs = !empty($refdata->rolerefs);
        $refdata->hasquestioncategoryrefs = !empty($refdata->questioncategoryrefs);
        $refdata->hasfilerefs = !empty($refdata->filerefs);
        return $refdata;
    }
}