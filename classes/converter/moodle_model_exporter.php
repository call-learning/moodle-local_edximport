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
 * edX Model to Moodle converter
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\converter;

use local_edximport\converter\output\course as course_output;
use local_edximport\converter\output\course_enrolments as course_enrolments_output;
use local_edximport\converter\output\course_completiondefaults as course_completiondefaults_output;
use local_edximport\converter\output\grade_history;
use local_edximport\converter\output\standard_roles as standard_roles_output;
use local_edximport\converter\output\completion as completion_output;
use local_edximport\converter\output\standard_grade_history as standard_grade_history_output;
use local_edximport\converter\output\groups as groups_output;
use local_edximport\converter\output\outcomes as outcomes_output;
use local_edximport\converter\output\scales as scales_output;
use local_edximport\converter\output\gradebook as gradebook_output;
use local_edximport\converter\output\moodle_backup as moodle_backup_output;
use local_edximport\converter\output\module as module_output;
use local_edximport\converter\output\inforef as inforef_output;
use local_edximport\converter\output\files as files_output;
use local_edximport\converter\output\questions as questions_output;
use local_edximport\converter\output\activity_grades as activity_grades_output;
use local_edximport\converter\output\activity_roles as activity_roles_output;
use local_edximport\converter\output\section as section_output;
use local_edximport\converter\output\grade_history as grade_history_output;
use renderable;

defined('MOODLE_INTERNAL') || die();

class moodle_model_exporter {
    private $edxarchivepath;
    private $outputdir;
    /**
     * @var entity_pool $entitypool
     */
    private $entitypool;
    private $entityrefs;

    /**
     * moodle_model_exporter constructor.
     *
     * @param $edxarchivepath
     * @param $outputdir
     */
    public function __construct($edxarchivepath, $outputdir, $entitypool, $entityrefs) {
        $this->edxarchivepath = $edxarchivepath;
        $this->outputdir = $outputdir;
        $this->entitypool = $entitypool;
        $this->entityrefs = $entityrefs;
    }

    /**
     * Create a full backup
     *
     */
    public function create_full_backup() {
        $course = $this->entitypool->get_entities('course');
        $course = reset($course);
        $this->create_xml_file('moodle_backup.xml', new moodle_backup_output($course->id, $course->format));
        $this->create_xml_file('groups.xml', new groups_output());
        $this->create_xml_file('outcomes.xml', new outcomes_output());
        $this->create_xml_file('roles.xml', new standard_roles_output());
        $this->create_xml_file('scales.xml', new scales_output());
        $this->create_xml_file('completion.xml', new completion_output($course));
        $this->create_xml_file('grade_history.xml', new standard_grade_history_output());
        $this->create_xml_file('gradebook.xml', new gradebook_output());

        $this->create_course_xml($course);
        $this->create_sections_xml();
        $this->create_activities_xml();
        $this->create_xml_file('questions.xml', new questions_output($this->entitypool));
        $this->create_files_xml();
    }

    public function create_xml_file($filename, renderable $templatable) {
        static $renderer = null;
        if (!$renderer) {
            global $PAGE;
            $renderer = $PAGE->get_renderer('local_edximport');
        }
        $fullpath = $this->outputdir . '/' . $filename;
        if (!is_dir(dirname($fullpath))) {
            mkdir(dirname($fullpath), 0777, true);
        }
        file_put_contents($fullpath, $renderer->render($templatable));
    }

    /**
     * Create course/course.xml
     *
     * @throws \moodle_exception
     */
    public function create_course_xml($course) {
        $this->create_xml_file('course/course.xml', new course_output($course));
        $this->create_xml_file('course/enrolments.xml', new course_enrolments_output());
        $this->create_xml_file('course/roles.xml', new standard_roles_output());
        $this->create_xml_file('course/completiondefaults.xml', new course_completiondefaults_output());
        $this->create_info_ref_file('course/inforef.xml', 'course', null);
    }

    /**
     * Create sections/section_xxx
     */
    public function create_sections_xml() {
        foreach ($this->entitypool->get_entities('section') as $section) {
            $this->create_xml_file("sections/section_{$section->id}/section.xml", new section_output($section));
            $this->create_info_ref_file("sections/section_{$section->id}/inforef.xml", 'section', $section->id);
        }
    }

    /**
     *  Create activities/<type>_xxx
     *
     */
    public function create_activities_xml() {
        $allgradeitems = $this->entitypool->get_entities('grade_item');
        foreach ($this->entitypool->get_entities('activity') as $activity) {
            $activityoutputclass = "local_edximport\\converter\\output\\activity_{$activity->modulename}";
            $this->create_xml_file("activities/{$activity->modulename}_{$activity->id}/module.xml",
                new module_output($activity));
            $entitydata = $this->entitypool->get_entity($activity->modulename, $activity->associatedentityid);
            $this->create_xml_file("activities/{$activity->modulename}_{$activity->id}/{$activity->modulename}.xml",
                new $activityoutputclass($entitydata));
            $activitiesgradesitems = array_filter($allgradeitems, function($item) use ($activity) {
                return $item->iteminstance == $activity->id && $item->itemtype == 'mod' &&
                    $item->itemmodule == $activity->modulename;
            });
            $this->create_xml_file("activities/{$activity->modulename}_{$activity->id}/grades.xml",
                new activity_grades_output($activitiesgradesitems));
            $this->create_xml_file("activities/{$activity->modulename}_{$activity->id}/grades_history.xml",
                new grade_history_output());
            $this->create_info_ref_file("activities/{$activity->modulename}_{$activity->id}/inforef.xml",
                "mod_{$activity->modulename}", $activity->associatedentityid);
            $this->create_xml_file("activities/{$activity->modulename}_{$activity->id}/roles.xml",
                new activity_roles_output($activitiesgradesitems));
        }
    }

    /**
     * Create file entities
     *
     */
    public function create_files_xml() {
        $this->create_xml_file('files.xml', new files_output($this->entitypool));
        $this->copy_files();
    }

    /**
     * Create file entities
     *
     */
    public function create_info_ref_file($path, $type, $entityid) {
        $this->create_xml_file($path, new inforef_output($this->entityrefs,$type,$entityid));
    }

    protected function copy_files() {
        // Write file data..
        $files = $this->entitypool->get_entities('file');
        foreach($files as $filedata) {
            $hashpath = $this->outputdir . '/files/' . substr($filedata->contenthash, 0, 2);
            $hashfile = "$hashpath/{$filedata->contenthash}";

            if (file_exists($hashfile)) {
                if (filesize($hashfile) !== $filedata->filesize) {
                    throw new \moodle_exception('same_hash_different_size');
                }
            } else {
                check_dir_exists($hashpath);
                if (!copy($filedata->originalfullpath, $hashfile)) {
                    throw new \moodle_exception('unable_to_copy_file');
                }

                if (filesize($hashfile) !== $filedata->filesize) {
                    throw new \moodle_exception('filesize_different_after_copy');
                }
            }
        }
    }
}