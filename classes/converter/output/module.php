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

class module extends base {

    protected $edxmodels = [];
    protected $moduleid = 0; // This is the global module id, once edxmodels have been squashed.
    protected $type = '';
    protected $localid = 0;
    protected $backupfilename = '';
    protected $sectionid = 0;
    protected $sectionindex = 0;
    protected $moduledname = "";

    /**
     * @param $model
     */
    public function __construct($outputdir, $edxmodels, $moduleid, $localid, $moduledname, $type, $sectionid, $sectionindex) {
        parent::__construct($outputdir);
        $this->edxmodels = $edxmodels;
        $this->moduleid = $moduleid;
        $this->type = $type;
        $this->localid = $localid;
        $this->sectionid = $sectionid;
        $this->sectionindex = $sectionindex;
        $this->moduledname = $moduledname;
    }

    /**
     *
     */
    public function create_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');
        $this->write_module_basic_files();
        $this->{'write_' . $this->type . '_module'}();
    }

    protected function write_module_basic_files() {
        global $CFG;
        $now = time();
        $type = $this->type;
        $moduleid = $this->moduleid;
        $directory = 'activities/' . $type . '_' . $moduleid;

        // Write module.xml.
        $this->open_xml_writer($directory . '/module.xml');
        $cminfo = [
            'modulename' => $type,
            'sectionid' => $this->sectionid,
            'sectionnumber' => $this->sectionindex,
            'idnumber' => '$@NULL@$',
            'added' => $now,
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'visibleold' => 1,
            'groupmode' => 1,
            'groupingid' => 0,
            'completion' => 2,
            'completiongradeitemnumber' => '$@NULL@$',
            'completionview' => 1,
            'completionexpected' => 0,
            'availability' => '$@NULL@$',
            'showdescription' => 0,
            'id' => $moduleid,
            'version' => $CFG->version
        ];
        $this->write_xml('module', $cminfo, array('/module/id', '/module/version'));
        $this->close_xml_writer();

        // Write grades.xml.
        $this->open_xml_writer($directory . '/grades.xml');
        $this->xmlwriter->begin_tag('activity_gradebook');
        if (!empty($gradeitems)) {
            $this->xmlwriter->begin_tag('grade_items');
            foreach ($gradeitems as $gradeitem) {
                $this->write_xml('grade_item', $gradeitem, array('/grade_item/id'));
            }
            $this->xmlwriter->end_tag('grade_items');
        }
        $this->write_xml('grade_letters', array()); // No grade_letters in module context in Moodle 1.9.
        $this->xmlwriter->end_tag('activity_gradebook');
        $this->close_xml_writer();

        // Write the inforef.

        $this->make_sure_xml_exists($directory . '/roles.xml', 'roles');
        $this->make_sure_xml_exists($directory . '/calendar.xml', 'events');

    }

    protected function write_book_module() {
        $now = time();
        $moduleid = $this->moduleid;
        $directory = 'activities/book_' . $moduleid;

        // Write module.xml.
        $this->open_xml_writer($directory . '/book.xml');
        $this->write_activity_tag($this->type);
        $bookmodule = [
            'name' => $this->moduledname,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'numbering' => 1,
            'navstyle' => 1,
            'customtitles' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'id' => $this->moduleid
        ];
        $this->write_xml('module', $bookmodule, array('/book/id'));
        $this->xmlwriter->begin_tag('chapters');
        foreach ($this->edxmodels as $index => $model) {
            $chapter = [
                'pagenum' => 1,
                'subchapter' => 0,
                'title' => $model->displayname,
                'content' =>
                    method_exists($model, 'get_content') ?
                        $model->get_content() : '',
                'contentformat' => FORMAT_HTML,
                'hidden' => 0,
                'timemodified' => $now,
                'importsrc' => '',
                'id' => $index,
            ];
            $this->write_xml('chapter', $chapter, array('/chapter/id'));
        }
        $this->xmlwriter->end_tag('chapters');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

    }

    protected function write_forum_module() {
        $now = time();
        $moduleid = $this->moduleid;
        $directory = 'activities/forum_' . $moduleid;

        // Write module.xml.
        $this->open_xml_writer($directory . '/forum.xml');
        $this->write_activity_tag($this->type);
        $forummodule = [
            'type' => 'news',
            'name' => $this->edxmodels[0]->displayname,
            'intro' => $this->edxmodels[0]->displayname,
            'introformat' => FORMAT_HTML,
            'duedate' => 0,
            'cutoffdate' => 0,
            'assessed' => 0,
            'assesstimestart' => 0,
            'assesstimefinish' => 0,
            'scale' => 0,
            'maxbytes' => 0,
            'maxattachments' => 1,
            'forcesubscribe' => 1,
            'trackingtype' => 1,
            'rsstype' => 0,
            'rssarticles' => 0,
            'timemodified' => $now,
            'warnafter' => 0,
            'blockafter' => 0,
            'blockperiod' => 0,
            'completiondiscussions' => 0,
            'completionreplies' => 0,
            'completionposts' => 0,
            'displaywordcount' => 0,
            'lockdiscussionafter' => 0,
            'grade_forum' => 0,
            'id' => $this->moduleid
        ];
        $this->write_xml('module', $forummodule, array('/forum/id'));
        $this->write_xml('discussions', array());
        $this->write_xml('subscriptions', array());
        $this->write_xml('digests', array());
        $this->write_xml('readposts', array());
        $this->write_xml('trackedprefs', array());
        $this->write_xml('poststags', array());
        $this->write_xml('grades', array());
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();
    }

    protected function write_page_module() {
        $now = time();
        $moduleid = $this->moduleid;
        $directory = 'activities/page_' . $moduleid;

        // Write module.xml.
        $this->open_xml_writer($directory . '/page.xml');
        $this->write_activity_tag($this->type);
        $pagemodel = $this->edxmodels[0];
        $page = [
            'name' => $pagemodel->displayname,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'content' => method_exists($pagemodel, 'get_content') ?
                    $pagemodel->get_content() : '',
            'contentformat' => FORMAT_HTML,
            'legacyfiles' => 0,
            'legacyfileslast' => '$@NULL@$',
            'display' => 5,
            'displayoptions' => 'a:3:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";s:17:"printlastmodified";s:1:"1";}',
            'revision' => 1,
            'timemodified' => $now,
            'id' => $this->moduleid
        ];
        $this->write_xml('page', $page, array('/page/id'));
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();
    }

    protected function write_quiz_module() {
        $now = time();
        $moduleid = $this->moduleid;
        $directory = 'activities/quiz_' . $moduleid;

        // Write module.xml.
        $this->open_xml_writer($directory . '/quiz.xml');
        $this->write_activity_tag($this->type);
        $pagemodel = $this->edxmodels[0];
        $page = [
            'id' => $this->moduleid,
            'name' => $pagemodel->displayname,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'timeopen' => 0,
            'timeclose' => 0,
            'timelimit' => 0,
            'overduehandling' => 'autosubmit',
            'graceperiod' => 0,
            'preferredbehaviour' => 'deferredfeedback',
            'canredoquestions' => 0,
            'attempts_number' => 0,
            'attemptonlast' => 0,
            'grademethod' => 1,
            'decimalpoints' => 2,
            'questiondecimalpoints' => -1,
            'reviewattempt' => 69888,
            'reviewcorrectness' => 4352,
            'reviewmarks' => 4352,
            'reviewspecificfeedback' => 4352,
            'reviewgeneralfeedback' => 4352,
            'reviewrightanswer' => 4352,
            'reviewoverallfeedback' => 4352,
            'questionsperpage' => 1,
            'navmethod' => 'free',
            'shuffleanswers' => 1,
            'sumgrades' => 1,
            'grade' => 10,
            'timecreated' => $now,
            'timemodified' => $now,
            'password' => '',
            'subnet' => '',
            'browsersecurity' => '',
            'delay1' => 0,
            'delay2' => 0,
            'showuserpicture' => 0,
            'showblocks' => 0,
            'completionattemptsexhausted' => 0,
            'completionpass' => 0,
            'allowofflineattempts' => 0,
            'subplugin_quizaccess_seb_quiz' => ''
        ];
        $this->write_xml('quiz', $page, array('/quiz/id'));
        foreach ($this->edxmodels as $index => $model) {

        }
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();
    }

    protected function write_activity_tag($modulename) {
        $this->xmlwriter->begin_tag('activity',
            array('id' => $this->localid, 'moduleid' => $this->moduleid, 'modulename' => $modulename,
                'contextid' =>
                    edx_moodle_model::get_contextid(CONTEXT_MODULE, $this->moduleid)
            )
        );
    }
}

