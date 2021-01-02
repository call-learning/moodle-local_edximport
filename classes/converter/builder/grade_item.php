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
 * Base converter from edX entity or entities to a moodle model
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\converter\builder;

use local_edximport\converter\entity_pool;
use local_edximport\converter\ref_manager;
use local_edximport\edx\model\base as base_edx_model;
use local_edximport\edx\model\course as course_model;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

class grade_item extends base {
    /**
     * Convert the model and returns a set of object in a pool and set of refs
     *
     * @param base_edx_model|array $originalmodels either a single model or a set of models
     * @param builder_helper $helper
     * @param mixed ...$additionalargs
     * @return mixed the built model (already inserted into the pool)
     * @throws moodle_exception
     */
    public static function convert($originalmodels, $helper = null, ...$additionalargs) {
        $gitem = new grade_item(
            $helper,
            $originalmodels
        );

        $gitem->data = $gitem->build([
            'itemname' => $additionalargs[0],
            'categoryid' => $additionalargs[1],
            'itemtype' => $additionalargs[2],
            'itemmodule' => $additionalargs[3],
            'grademax' => $additionalargs[4],
            'grademin' => $additionalargs[5],
            'iteminstance' => $additionalargs[6],
        ]);
        return $gitem;
    }

    /**
     * Convert a series of static modules into a book
     *
     * Conversion will fill up the entity_pool and ref_pool
     *
     * @param null $args
     * @return mixed|void
     * @throws moodle_exception
     */
    public function build($args = null) {
        $now = time();

        $gradeitem = (object) $args;
        $gradeitemid = $this->helper->entitypool->new_entity('grade_item');
        $gradeitem->id = $gradeitemid;
        $gradeitem->timecreated = $now;
        $gradeitem->timemodified = $now;
        $this->helper->entitypool->set_data('grade_item', $gradeitemid, $gradeitem);

        return $gradeitem;
    }
}