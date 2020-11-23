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

use local_edximport\edx\model\base as base_edx_model;
use local_edximport\edx\model\course as course_model;


defined('MOODLE_INTERNAL') || die();

class course extends base {
    /**
     * Convert the model and returns a set of object in a pool and set of refs
     *
     * @param base_edx_model|array $originalmodels either a single model or a set of models
     * @param builder_helper $helper
     * @param mixed ...$additionalargs
     * @return mixed : matching enti
     * @throws \moodle_exception
     */
    public static function convert($originalmodels, $helper = null, ...$additionalargs) {
        if (!($originalmodels instanceof course_model)) {
            throw new \moodle_exception('We cannot convert from anything else than a course model');
        }
        $course = new course(
            new builder_helper($additionalargs[0], $originalmodels->assets),
            $originalmodels
        );

        $course->data = $course->build();
        return $course;
    }

    /**
     * Convert the course (edx) into a moodle like model
     *
     * This is supposed to be used in the course/course.xml
     *
     * @param null $args
     * @return mixed the built model (already inserted into the pool)
     * @throws \moodle_exception
     */
    public function build($args = null) {
        $model = $this->models;
        $courseid = $this->helper->entitypool->new_entity('course');
        $now = time();
        $miscellaneous = \core_course_category::get_default();
        $course = (object) [
            'id' => self::get_fake_course_id(),
            'contextid' => $this->helper->get_contextid(CONTEXT_COURSE),
            'shortname' => $model->fullname,
            'fullname' => $model->fullname,
            'idnumber' => '',
            'summary' => '',
            'summaryformat' => FORMAT_HTML,
            'format' => self::get_default_course_format(),
            'showgrades' => 1,
            'newsitems' => 5,
            'startdate' => $model->startdate,
            'enddate' => $model->enddate,
            'marker' => 0,
            'maxbytes' => 0,
            'legacyfiles' => 0,
            'showreports' => 0,
            'visible' => 0,
            'groupmode' => 0,
            'groupmodeforce' => 0,
            'defaultgroupingid' => 0,
            'lang' => '',
            'theme' => '',
            'timecreated' => $now,
            'timemodified' => $now,
            'requested' => 0,
            'enablecompletion' => 1,
            'completionnotify' => 0,
            'hiddensections' => 0,
            'coursedisplay' => 0,
            'tags' => '',
            'customfields' => '',
        ];
        $course->category = (object) [
            'id' => $miscellaneous->id,
            'name' => $miscellaneous->name,
            'description' => $miscellaneous->description,
        ];
        $this->helper->entitypool->set_data('course', $courseid, $course);
        grade_category::convert($model, $this->helper, '$@NULL@$','Default category');
        foreach ($model->chapters as $index => $c) {
            section::convert($c, $this->helper, $index);
        }
        return $course;
    }

    public static function get_default_course_format() {
        return 'topics';
    }

    public static function get_fake_course_id() {
        return 2;
    }
}


