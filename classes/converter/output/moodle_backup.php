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

use backup;
use local_edximport\converter\builder\builder_helper;
use local_edximport\converter\entity_pool;
use local_edximport\converter\utils;
use moodle_exception;
use renderable;
use renderer_base;
use local_edximport\converter\builder\course as course_builder;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

class moodle_backup implements renderable, templatable {

    /**
     * @var mixed|null $course
     */
    protected $course = null;

    protected $settings = null;

    /**
     * moodle_backup constructor.
     *
     * @param $courseid
     * @param $settings
     * @throws moodle_exception
     */
    public function __construct($courseid, $settings) {

        $course = entity_pool::get_instance()->get_entities('course');
        $this->course = reset($course);
        $this->courseid = $courseid;
        $this->settings = $settings;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return object
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $now = time();
        $backupfilename = sha1($this->course->fullname) . '.mbz';
        $data = (object) [
            'name' => $backupfilename,
            'moodle' => (object) [
                'version' => $CFG->version,
                'release' => $CFG->release
            ],
            'backup' => (object) [
                'id' => sha1(base64_encode($now)),
                'release' => $CFG->backup_release,
                'version' => $CFG->backup_version,
                'date' => $now,
                'type' => backup::TYPE_1COURSE,
                'format' => backup::FORMAT_MOODLE,
                'interactive' => backup::INTERACTIVE_YES,
                'mode' => backup::MODE_CONVERTED,
                'execution' => backup::EXECUTION_INMEDIATE,
                'executiontime' => 0,
            ],
            'course' => (object) [
                'id' => $this->courseid,
                'title' => $this->course->fullname
            ],
            'original_wwwroot' => $CFG->wwwroot,
            'original_site_identifier_hash' => utils::get_original_site_identifier(),
            'original_course_id' => course_builder::get_fake_course_id(),
            'original_course_format' => course_builder::get_default_course_format(),
            'original_course_fullname' => $this->course->fullname,
            'original_course_shortname' => $this->course->fullname,
            'original_course_startdate' => $this->course->startdate,
            'original_course_enddate' => $this->course->enddate,
            'original_course_contextid' => builder_helper::get_contextid(CONTEXT_COURSE),
            'original_system_contextid' => builder_helper::get_contextid(CONTEXT_SYSTEM),
        ];
        $data->activities = [];
        $data->settings = [
            (object) ['level' => 'root', 'name' => 'filename', 'value' => $backupfilename],
            (object) ['level' => 'root', 'name' => 'users', 'value' => 0],
            (object) ['level' => 'root', 'name' => 'role_assignments', 'value' => 0],
            (object) ['level' => 'root', 'name' => 'activities', 'value' => 1],
            (object) ['level' => 'root', 'name' => 'blocks', 'value' => 0],
            (object) ['level' => 'root', 'name' => 'groups', 'value' => 0],
            (object) ['level' => 'root', 'name' => 'competencies', 'value' => 0],
            (object) ['level' => 'root', 'name' => 'files', 'value' => 1],
            (object) ['level' => 'root', 'name' => 'questionbank', 'value' => 1]
        ];
        if (!empty($this->settings) && !empty($this->settings->hasmathjax)) {
            $data->settings[] = (object) ['level' => 'root', 'name' => 'filters', 'value' => 1]; // We backup filters so we
            // can add mathjax filter.
        }
        foreach (entity_pool::get_instance()->get_entities('activity') as $activity) {
            $activitydata = new stdClass();
            $activitydata->moduleid = $activity->moduleid;
            $activitydata->sectionid = $activity->sectionid;
            $activitydata->modulename = $activity->modulename;
            $activitydata->title = $activity->title;
            $activitydata->directory = "activities/{$activity->modulename}_{$activity->id}";
            $data->activities [] = $activitydata;
            $data->settings[] = (object)
            [
                'level' => 'activity',
                'name' => "{$activity->modulename}_{$activity->moduleid}_included",
                'value' => 1,
                'additionaltag' => "<activity>{$activity->modulename}_{$activity->moduleid}</activity>"
            ];
            $data->settings[] = (object)
            [
                'level' => 'activity',
                'name' => "{$activity->modulename}_{$activity->moduleid}_userinfo",
                'value' => 0,
                'additionaltag' => "<activity>{$activity->modulename}_{$activity->moduleid}</activity>"
            ];
        }
        $data->sections = [];
        foreach (entity_pool::get_instance()->get_entities('section') as $section) {
            $sectiondata = new stdClass();
            $sectiondata->id = $section->id;
            $sectiondata->title = $section->title;
            $sectiondata->directory = 'sections/section_' . $section->id;
            $data->sections [] = $sectiondata;
            $data->settings[] = (object)
            [
                'level' => 'section',
                'name' => "section_{$section->id}_included",
                'value' => 1,
                'additionaltag' => "<section>section_{$section->id}</section>"
            ];
            $data->settings[] = (object)
            [
                'level' => 'section',
                'name' => "section_{$section->id}_userinfo",
                'value' => 0,
                'additionaltag' => "<section>section_{$section->id}</section>"
            ];
        }

        return $data;
    }
}
