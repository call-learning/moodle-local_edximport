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
use stdClass;

defined('MOODLE_INTERNAL') || die();

class question_instance extends base {
    /**
     * Convert the model and returns a set of object in a pool and set of refs
     *
     * @param base_edx_model|array $originalmodels either a single model or a set of models
     * @param base $helper
     * @param mixed ...$additionalargs
     * @return mixed the built model (already inserted into the pool)
     * @throws moodle_exception
     */
    public static function convert($originalmodels, $helper = null, ...$additionalargs) {
        $questioni = new question_instance(
            $helper,
            $originalmodels
        );

        $questioni->data = $questioni->build([
            'questioncategory' => $additionalargs[0],
            'pageindex' => $additionalargs[1],
            'questionindex' => $additionalargs[2],
            'questionid' => $additionalargs[3],
        ]);
        return $questioni;
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
        $model = $this->models;
        $questioncategory = $args['questioncategory'];
        $pageindex = $args['pageindex'];
        $questionindex = $args['questionindex'];
        $questionid = $args['questionid'];

        $qimodel = new stdClass();
        $questioninstanceid = $this->helper->entitypool->new_entity('question_instance');
        $qimodel->id = $questioninstanceid;
        $qimodel->questionindex = $questionindex;
        $qimodel->page = $pageindex;
        $qimodel->slot = $questionindex;
        $qimodel->questionid = $questionid;
        $qimodel->questioncategoryid = $questioncategory;
        $qimodel->maxmark = 1.00000;

        $this->helper->entitypool->set_data('question_instance', $qimodel->id, $qimodel);
        return $qimodel;
    }
}