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
use local_edximport\converter\edx_moodle_model;


defined('MOODLE_INTERNAL') || die();

class course extends base {

    protected $edxmodel = null;

    protected $backupfilename = '';

    /**
     * @param $model
     */
    public function __construct($outputdir, edx_moodle_model $model) {
        parent::__construct($outputdir);
        $this->edxmodel = $model;
        $this->backupfilename = sha1($model->coursemodel->displayname) . '.mbz';
    }

    /**
     * Create a moodle backup from edX Model
     *
     * We have decided that:
     *  -  sequentials <=> sections
     *  -  verticals <=> either a single quiz or a series of pages, discussions, videos
     *
     * For the verticals, we either squash all problems into one big quiz or we output
     * html pages one by one (videos are also HTML pages).
     * The problems are likely a match for moodle question (although it is not clear
     * in OLX if we can have several problems per quiz).
     */
    public function create_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');

        $now = time();

        $this->open_xml_writer('moodle_backup.xml');

        $this->xmlwriter->begin_tag('moodle_backup');
        $this->xmlwriter->begin_tag('information');

        // Moodle_backup/information.
        $this->xmlwriter->full_tag('name', $this->backupfilename);
        $this->xmlwriter->full_tag('moodle_version', $CFG->version);
        $this->xmlwriter->full_tag('moodle_release', $CFG->release);
        $this->xmlwriter->full_tag('backup_version', $CFG->backup_version);
        $this->xmlwriter->full_tag('backup_release', $CFG->backup_release);
        $this->xmlwriter->full_tag('backup_date', $now);

        $this->xmlwriter->full_tag('original_wwwroot', '');
        $this->xmlwriter->full_tag('original_site_identifier_hash', null);
        $this->xmlwriter->full_tag('original_course_id', 0);
        $this->xmlwriter->full_tag('original_course_fullname', $this->edxmodel->coursemodel->displayname);
        $this->xmlwriter->full_tag('original_course_shortname', $this->edxmodel->coursemodel->displayname);
        $this->xmlwriter->full_tag('original_course_startdate', $this->edxmodel->coursemodel->startdate);
        $this->xmlwriter->full_tag('original_system_contextid', edx_moodle_model::get_contextid(CONTEXT_SYSTEM));
        // Note that even though we have original_course_contextid available, we regenerate the
        // original course contextid using our helper method to be sure that the data are consistent
        // within the MBZ file.
        $this->xmlwriter->full_tag('original_course_contextid', edx_moodle_model::get_contextid(CONTEXT_COURSE));

        // Moodle_backup/information/details.
        $this->xmlwriter->begin_tag('details');
        $this->write_xml('detail', array(
            'backup_id' => $this->backupid,
            'type' => \backup::TYPE_1COURSE,
            'format' => \backup::FORMAT_MOODLE,
            'interactive' => \backup::INTERACTIVE_YES,
            'mode' => \backup::MODE_CONVERTED,
            'execution' => \backup::EXECUTION_INMEDIATE,
            'executiontime' => 0,
        ), array('/detail/backup_id'));
        $this->xmlwriter->end_tag('details');

        // Moodle_backup/information/contents.
        $this->xmlwriter->begin_tag('contents');

        // Moodle_backup/information/contents/activities.
        $this->xmlwriter->begin_tag('activities');
        $activitysettings = array();

        foreach ($this->edxmodel->sections as $section) {
            foreach ($section->modules as $module) {
                $this->create_module_ref($module->type,
                    $module->moduleid, $section->edxmodel->id, $module->displayname, $activitysettings);
            }
        }
        $this->xmlwriter->end_tag('activities');

        // Moodle_backup/information/contents/sections.
        $this->xmlwriter->begin_tag('sections');
        $sectionsettings = array();
        $activities = [];
        // NOTE: Sections (Moodle) are equivalent to Chapter (edX).
        foreach ($this->edxmodel->sections as $section) {
            $sectionsettings[] = array(
                'level' => 'section',
                'section' => 'section_' . $section->edxmodel->id,
                'name' => 'section_' . $section->edxmodel->id . '_included',
                'value' => 1);
            $sectionsettings[] = array(
                'level' => 'section',
                'section' => 'section_' . $section->edxmodel->id,
                'name' => 'section_' . $section->edxmodel->id . '_userinfo',
                'value' => 0);
            $this->write_xml('section', array(
                'sectionid' => $section->edxmodel->id,
                'title' => $section->edxmodel->id,
                // Because the title is not available.
                'directory' => 'sections/section_' . $section->edxmodel->id));
        }
        $this->xmlwriter->end_tag('sections');

        // Moodle_backup/information/contents/course.
        $this->write_xml('course', array(
            'courseid' => 0,
            'title' => $this->edxmodel->coursemodel->fullname,
            'directory' => 'course'));

        $this->xmlwriter->end_tag('contents');

        // Moodle_backup/information/settings.
        $this->xmlwriter->begin_tag('settings');

        // Fake backup root seetings.
        $rootsettings = array(
            'filename' => $this->backupfilename,
            'users' => 0,
            'anonymize' => 0,
            'role_assignments' => 0,
            'activities' => 1,
            'blocks' => 1,
            'filters' => 0,
            'comments' => 0,
            'userscompletion' => 0,
            'logs' => 0,
            'grade_histories' => 0,
        );
        unset($backupinfo);
        foreach ($rootsettings as $name => $value) {
            $this->write_xml('setting', array(
                'level' => 'root',
                'name' => $name,
                'value' => $value));
        }
        unset($rootsettings);

        // Activity settings populated above.
        foreach ($activitysettings as $activitysetting) {
            $this->write_xml('setting', $activitysetting);
        }
        unset($activitysettings);

        // Section settings populated above.
        foreach ($sectionsettings as $sectionsetting) {
            $this->write_xml('setting', $sectionsetting);
        }
        unset($sectionsettings);

        $this->xmlwriter->end_tag('settings');

        $this->xmlwriter->end_tag('information');
        $this->xmlwriter->end_tag('moodle_backup');

        $this->close_xml_writer();

        //
        // Write files.xml.
        $files =
            new files(
                $this->outputdir,
                $this->edxmodel->entitypool,
                $this->edxmodel->refs,
                $this->edxmodel->edxarchpath,
                $this->edxmodel->coursemodel->assets
                );
        $files->create_backup();
        //
        // Write scales.xml.

        $this->open_xml_writer('scales.xml');
        $this->xmlwriter->begin_tag('scales_definition');
        //foreach ($this->converter->get_stash_itemids('scales') as $scaleid) {
        //    $this->write_xml('scale', $this->converter->get_stash('scales', $scaleid), array('/scale/id'));
        //}
        $this->xmlwriter->end_tag('scales_definition');
        $this->close_xml_writer();

        //
        // Write course/inforef.xml.
        $courseinforef =
            new inforef(
                $this->outputdir,
                $this->edxmodel->refs,
                'course'
            );
        $courseinforef->create_backup();

        // Make sure that the files required by the restore process have been generated.
        // missing file may happen if the watched tag is not present in moodle.xml (for example
        // QUESTION_CATEGORIES is optional in moodle.xml but questions.xml must exist in
        // moodle2 format) or the handler has not been implemented yet.
        // apparently this must be called after the handler had a chance to create the file.
        $this->make_sure_xml_exists('questions.xml', 'question_categories');
        $this->make_sure_xml_exists('groups.xml', 'groups');
        $this->make_sure_xml_exists('outcomes.xml', 'outcomes_definition');
        $this->make_sure_xml_exists('users.xml', 'users');
        $this->make_sure_xml_exists('course/roles.xml', 'roles',
            array('role_assignments' => array(), 'role_overrides' => array()));
        $this->make_sure_xml_exists('course/enrolments.xml', 'enrolments',
            array('enrols' => array()));

        // Create sections and modules.
        foreach ($this->edxmodel->sections as $section) {
            $backupsection = new section($this->outputdir, $section->edxmodel);
            $backupsection->create_backup();
        }
        foreach ($this->edxmodel->sections as $sectionindex => $section) {
            foreach ($section->modules as $module) {
                $backupsection =
                    new module(
                        $this->outputdir,
                        $module->edxmodels,
                        $module->moduleid,
                        $module->localid,
                        $module->displayname,
                        $module->type,
                        $section->edxmodel->id,
                        $sectionindex);
                $backupsection->create_backup();

                // Now create the inforef for this module.
                $courseinforef =
                    new inforef(
                        $this->outputdir,
                        $this->edxmodel->refs,
                        'activity',
                        $module
                    );
                $courseinforef->create_backup();
            }
        }
    }

    protected function create_module_ref($type, $moduleid, $sectionid, $title, &$activitysettings) {
        $this->write_xml('activity', array(
            'moduleid' => $moduleid,
            'sectionid' => $sectionid,
            'modulename' => $type,
            'title' => $title,
            'directory' => 'activities/' . $type . '_' . $moduleid
        ));
        $activitysettings[] = array(
            'level' => 'activity',
            'activity' => $type . '_' . $moduleid,
            'name' => $type . '_' . $moduleid . '_included',
            'value' => 0);
        $activitysettings[] = array(
            'level' => 'activity',
            'activity' => $type . '_' . $moduleid,
            'name' => $type . '_' . $moduleid . '_userinfo',
            'value' => 0);
    }
}
