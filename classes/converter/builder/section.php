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
use local_edximport\parser\sequential;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class section extends base {
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
        $section = new section(
            $helper,
            $originalmodels
        );

        $section->data = $section->build(['index' => $additionalargs[0]]);
        return $section;
    }

    /**
     * Convert the section (edx) into a moodle like model
     *
     * Conversion will fill up the entity_pool and ref_pool
     *
     * @param null $args
     * @return mixed|void
     * @throws moodle_exception
     */
    public function build($args = null) {
        $index = $args['index'];
        $model = $this->models;
        $now = time();
        $sectionid = $this->helper->entitypool->new_entity('section');
        $section = new stdClass();
        $section->id = $sectionid;
        $section->number = $index;
        $section->summary = '';
        $section->title = $model->displayname;
        $section->summaryformat = FORMAT_HTML;
        $section->visible = 1;
        $section->availabilityjson = '$@NULL@$';
        $section->timemodified = $now;
        $allmodules = [];
        foreach ($model->sequentials as $s) {
            $this->convert_sequential($s, $section);
        }
        $allrelatedactivities = array_filter($this->helper->entitypool->get_entities('activity'),
            function($a) use ($section) {
                return $a->sectionid == $section->id;
            }
        );

        $section->sequence = join(',', array_map(function($a) {
            return $a->id;
        }, $allrelatedactivities));
        $this->helper->entitypool->set_data('section', $sectionid, $section);
        return $section;
    }

    /**
     * Convert a sequential list of items
     *
     * @param \local_edximport\edx\model\sequential $s
     * @param int $sectionindex
     */
    protected function convert_sequential($s, $sectiondata) {
        // There are two types of resources:
        // - Non static: problem or discussion
        // - Static: html, video.

        // Across vertical we convert a series of consecutive static resources into a book.
        // Each individual non-static resource is then converted separately. The problem being an exception: if
        // a vertical contains a problem, we convert it to a quiz, static converted into
        // static description questions.
        $edxstatic = [];
        $edxproblems = [];
        foreach ($s->verticals as $v) {
            // Now check every activity in the vertical and accumulate all static entity.
            foreach ($v->indexedentities as $index => $entity) {
                if ($this->helper->is_discussion($entity)) {
                    continue; // Ignore discussion for now.
                }
                if ($this->helper->is_static_content($entity)) {
                    $edxstatic[] = $entity;
                    $edxproblems[] = $entity; // Consider this as a part of a problem too.
                } else {
                    if ($this->helper->is_problem($entity)) {
                        $edxproblems[] = $entity;
                    } else {
                        $this->purge_static($edxstatic, $v->displayname, $sectiondata->id, $sectiondata->number);
                    }
                }
            }
            if (count($v->problems) >
                0) { // There was at least a problem, so try to convert all of this into a single quiz/problem.
                quiz::convert($edxproblems, $this->helper, $v->displayname, $sectiondata->id, $sectiondata->number);
                $this->purge_static($edxstatic, $v->displayname, $sectiondata->id, $sectiondata->number);
                $edxproblems = [];
            }
        }
        $this->purge_static($edxstatic, $s->displayname, $sectiondata->id, $sectiondata->number);
    }

    /**
     * Purge static items into either a page or a book
     *
     * @param array $models
     * @param stdClass $sectiondata
     * @throws moodle_exception
     */
    protected function purge_static(&$models, $title, $sectionid, $sectionnumber) {
        if (count($models) > 1) {
            book::convert($models, $this->helper, $title, $sectionid, $sectionnumber);
        } else if (!empty($models)) {
            page::convert($models, $this->helper, $title, $sectionid, $sectionnumber);
        }
        $models = []; // Empty the list.
    }
}