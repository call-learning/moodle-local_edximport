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

defined('MOODLE_INTERNAL') || die();

class edx_moodle_model {
    public $coursemodel = null;

    public $sections = [];

    public $refs = null;

    public $entitypool = null;

    public $edxarchpath = ""; // This is used for the file upload/conversion.
    /**
     * @var int global module id, stores information about last module id to be stored in the module table
     */

    protected $lastlocalmoduleidbytype = []; // Track id for each type of module globally.

    protected $filerefs = []; // Files referenced in different modules and main course.

    protected $questionref = []; // Question referenced in different quiz/modules.

    /**
     * edx_moodle_model constructor.
     *
     * @param \local_edximport\edx\model\course $model
     */
    public function __construct(\local_edximport\edx\model\course $model, $edxarchpath) {
        $this->coursemodel = $model;
        $this->refs = new ref_manager(); // Stores ref to entities.
        $this->entitypool = new entity_pool(); // Store entities information.
        $this->edxarchpath = $edxarchpath;
        $this->build_temp_model();
    }

    protected function build_temp_model() {
        foreach ($this->coursemodel->chapters as $c) {
            $section = new \stdClass();
            $section->id = $c->id;
            $section->edxmodel = $c;
            $section->modules = [];
            foreach ($c->sequentials as $s) {
                $section->modules = array_merge($section->modules,
                    $this->get_sequential_modules($s));
            }
            $this->sections[] = $section;
        }
    }

    protected function get_sequential_modules(\local_edximport\edx\model\sequential $sequential) {
        // There are two types of resources:
        // - Interactive: problem or discussion
        // - Non interactive: html, video.

        // Across vertical we convert a series of consecutive non interactive resources into a book.
        // Each interactive resource is converted separately. The problem being an exception: if
        // a vertical contains a problem, we convert it to a quiz, non interactive converted into
        // static description questions.
        $allmodules = [];

        $edxnoninteractive = [];
        foreach ($sequential->verticals as $v) {
            if (count($v->problems) > 0) {
                // First output all previous interactive activities.
                $this->purge_non_interactive($allmodules, $edxnoninteractive, $v->displayname);
                // Secondly output the problem as quiz module.
                array_push($allmodules, $this->generate_quiz($v));
            } else {
                foreach ($v->indexedentities as $index => $entity) {
                    if ($this->is_static_content($entity)) {
                        $edxnoninteractive[] = $entity;
                    } else {
                        // First output all previous interactive activities.
                        $this->purge_non_interactive($allmodules, $edxnoninteractive, $v->displayname);
                        // Secondly output the interactive activity.
                        array_push($allmodules, $this->generate_single_module($entity));
                    }
                }
                // Make sure we purge everything now.

            }
        }
        $this->purge_non_interactive($allmodules, $edxnoninteractive, $sequential->displayname);
        return $allmodules;
    }

    protected function purge_non_interactive(&$allmodules, &$edxnoninteractive, $displayname) {
        if ($edxnoninteractive) {
            array_push($allmodules, $this->generate_book_or_page($edxnoninteractive, $displayname));
        }
        $edxnoninteractive = [];
    }

    protected function generate_book_or_page($niactivitieslist, $displayname) {
        return $this->create_module(
            count($niactivitieslist) > 1 ? 'book' : 'page',
            $niactivitieslist,
            $displayname);
    }

    protected function is_static_content(\local_edximport\edx\model\base $model) {
        return array_key_exists('local_edximport\edx\model\static_content', class_implements($model));
    }

    protected function is_problem(\local_edximport\edx\model\base $model) {
        return get_class($model) == 'local_edximport\edx\model\problem';
    }

    private function generate_quiz($v) {
        $questions = [];
        foreach ($v->indexedentities as $index => $entity) {
            if (!$this->is_static_content($entity) && !$this->is_problem($entity)) {
                debugging("The entity  {$entity->displayname}, should be static");
                continue;
            }
            $questions[] = $entity; // Can be a problem or html or video.
        }
        $quizmodule = $this->create_module('quiz', $v, $v->displayname);
        $this->build_questions($questions, $quizmodule);
        return $quizmodule;
    }

    protected function build_questions($questions, $quizmodule) {
        // Setup a category for this set of questions.
        $questioncategoryid = $this->entitypool->new_entity('question_category');
        $questioncategory = new \stdClass();
        $questioncategory->name = 'Default for ' . $quizmodule->displayname;
        $questioncategory->moduleid = 'Default for ' . $quizmodule->moduleid;
        $questioncategory->info = 'The default category for questions shared in context ' . $quizmodule->displayname;
        foreach ($questions as $edxquestion) {
            $question = $this->create_question($edxquestion, $quizmodule);
            $this->collect_files_refs($question->qtype, $question->id, $edxquestion);
        }
        $this->entitypool->set_data('question_category', $questioncategoryid,
            $questioncategoryid);
        // Add info ref.
        $this->refs->add_ref($quizmodule->type, $questioncategoryid, 'question_category', $questioncategoryid);
    }

    private function create_question($model, $quizmodule) {

        $defaultmark = 0.0;
        $penalty = 0.0;
        if ($this->is_static_content($model)) {
            $qtype = 'description';
            $questiontext = $model;
        } else {
            $qtype = 'multichoice';
            $questiontext = $model;
        }
        $questionmodel = new \stdClass();
        $questionid = $this->entitypool->new_entity('question');
        $questionmodel->id = $questionid;
        $questionmodel->qtype = $qtype;
        $questionmodel->name = $model->displayname;
        return $questionmodel;

    }

    private function generate_single_module($entity) {
        if (get_class($entity) != 'local_edximport\edx\model\discussion') {
            $entityclass = get_class($entity);
            debugging("The entity  {$entity->displayname}, should not be {$entityclass}");
            return [];
        }
        return $this->create_module('forum', [$entity], $entity->displayname);
    }

    /**
     * Create a module
     *
     * @param string $type type of module 'forum', 'book'...
     * @param array $edxmodels series of edx modles
     * @param $displayname
     * @return \stdClass
     */
    protected function create_module($type, $edxmodels, $displayname) {
        static $lastglobalmoduleid = 1;
        if (!is_array($edxmodels)) {
            $edxmodels = [$edxmodels];
        }
        $moduleid = $this->entitypool->new_entity($type);
        $module = new \stdClass();
        $module->type = $type;
        $module->edxmodels = $edxmodels;
        $module->displayname = $displayname;
        $module->moduleid = $lastglobalmoduleid++;
        $module->localid = $moduleid;
        $this->collect_files_refs($type, $moduleid, $edxmodels);
        return $module;
    }

    protected function collect_files_refs($entitytype, $entityid, $edxmodels) {
        if (!is_array($edxmodels)) {
            $edxmodels = [$edxmodels];
        }
        foreach ($edxmodels as $model) {
            if ($this->is_static_content($model)) {
                $refs = \local_edximport\local\parser_utils::html_get_static_ref($model->get_content());
                if ($refs) {
                    foreach ($refs as $r) {
                        $originalpath = array_shift($r);
                        $filefullpath = array_shift($r);
                        $filename = basename($filefullpath);
                        $filepath = dirname($filefullpath);

                        // TODO : check if file exist and get full path from original edX archive.
                        $fileid = $this->entitypool->new_entity('file');
                        $this->entitypool->set_data('file',
                            $fileid,
                            $this->build_file_reference($filename, $filepath, $originalpath));
                        $this->refs->add_ref($entitytype, $entityid, 'file', $fileid);
                    }
                }
            }
        }
    }

    protected function build_file_reference(
        $filename,
        $filepath,
        $originpath

    ) {
        return (object) [
            'filename' => $filename,
            'filepath' => $filepath,
            'originalpath' => trim($originpath, "\"'"),
        ];
    }

    const FAKE_CONTEXT_SYSTEM = 1;
    const FAKE_CONTEXT_COURSE = 100;
    const FAKE_CONTEXT_MODULE = 200;

    public static function get_contextid($contextype, $moduleid = 0) {
        if ($contextype == CONTEXT_SYSTEM) {
            return self::FAKE_CONTEXT_SYSTEM;
        }
        if ($contextype == CONTEXT_COURSE) {
            return self::FAKE_CONTEXT_COURSE;
        }
        if ($contextype == CONTEXT_MODULE) {
            return self::FAKE_CONTEXT_MODULE + $moduleid;
        }
    }
}
