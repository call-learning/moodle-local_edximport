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

class quiz extends module {
    const MODULE_TYPE = 'quiz';

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
        $models = $this->models;
        $title = $args['title'];
        $now = time();
        $questions = [];
        foreach ($models as $index => $entity) {
            if (!builder_helper::is_static_content($entity) && !builder_helper::is_problem($entity)) {
                debugging("The entity  {$entity->displayname}, should be static");
                continue;
            }
            $questions[] = $entity; // Can be a problem or html or video.
        }
        $module = parent::build($args);
        $quizid = $this->helper->entitypool->new_entity(static::MODULE_TYPE);
        $quiz = $this->create_specialised_module_type($module);
        $quiz->id = $quizid;
        $quiz->name = $title;
        $quiz->timecreated = $now;
        $quiz->timemodified = $now;
        $quiz->intro = '';
        $quiz->shuffleanswers = 0; // Shuffle is managed at question level.
        $quiz->introformat = FORMAT_HTML;
        $quiz->question_instances = [];
        // Add questions to module as submodules and question instances.
        $questioncategory = $this->build_questions($questions, $quiz);
        // TODO : Here we should not have any global feedback unless there is a description section.
        $this->helper->entitypool->set_data('quiz', $quiz->id, $quiz);

        $gradecategories = $this->helper->entitypool->get_entities('grade_category');
        // Get the first one.
        $globalgradecategory = reset($gradecategories);
        grade_item::convert($models, $this->helper, $title, $globalgradecategory->id, 'mod', 'quiz', 100, 0,
            $quiz->id);
        $this->module_associate($module, $quizid);
        return $quiz;
    }

    /**
     * Build questions
     *
     * @param $questions
     * @param $quizmodule
     * @return stdClass
     */
    protected function build_questions($questions, &$quizmodule) {
        //// Setup a parent category for this quiz
        $pquestioncategoryid = $this->helper->entitypool->new_entity('question_category');
        $pquestioncategory = new stdClass();
        $pquestioncategory->id = $pquestioncategoryid;
        $pquestioncategory->name = 'top';
        $pquestioncategory->info = '';
        $pquestioncategory->infoformat = FORMAT_MOODLE;
        $pquestioncategory->contextid = builder_helper::get_contextid(CONTEXT_MODULE, $quizmodule->moduleid);
        $pquestioncategory->contextlevel = CONTEXT_MODULE;
        $pquestioncategory->contextinstanceid = $quizmodule->moduleid;
        $pquestioncategory->stamp = builder_helper::get_stamp();
        $pquestioncategory->parentid = 0; // Always 0 here as this is a flat category structure.
        $pquestioncategory->sortorder = 0;
        $pquestioncategory->idnumber = '$@NULL@$';
        $pquestioncategory->questions = [];
        $this->helper->entitypool->set_data(
            'question_category',
            $pquestioncategoryid,
            $pquestioncategory);

        // Now setup a category for this set of questions.
        $questioncategoryid = $this->helper->entitypool->new_entity('question_category');
        $questioncategory = new stdClass();
        $questioncategory->id = $questioncategoryid;
        $questioncategory->name = 'Default for ' . $quizmodule->name;
        $questioncategory->info = 'The default category for questions shared in context ' . $quizmodule->name;
        $questioncategory->infoformat = FORMAT_HTML;
        $questioncategory->contextid = builder_helper::get_contextid(CONTEXT_MODULE, $quizmodule->moduleid);
        $questioncategory->contextlevel = CONTEXT_MODULE;
        $questioncategory->contextinstanceid = $quizmodule->moduleid;
        $questioncategory->stamp = builder_helper::get_stamp();
        $questioncategory->parentid = $pquestioncategoryid; // The top parent category ID.
        $questioncategory->sortorder = 0;
        $questioncategory->idnumber = '$@NULL@$';
        $questioncategory->questions = [];
        foreach ($questions as $index => $edxquestion) {
            $question = question::convert($edxquestion, $this->helper, $quizmodule->id);

            $questioncategory->questions[] = $question->get_entity_data();
            // Add related question instance entity (in the quiz model).
            $questioninstance = question_instance::convert($edxquestion,
                $this->helper, $questioncategoryid, 1, $index + 1,
                $question->get_entity_data()->id);
            $quizmodule->question_instances[] = $questioninstance->get_entity_data();
        }
        $this->helper->entitypool->set_data(
            'question_category',
            $questioncategoryid,
            $questioncategory);

        // Add info ref.
        $this->helper->entityrefs->add_ref(
            'mod_quiz',
            $quizmodule->id,
            'question_category',
            $questioncategoryid
            );
        $this->helper->entityrefs->add_ref(
                  'mod_quiz',
                    $quizmodule->id,
                    'question_category',
                    $pquestioncategoryid);
        return $questioncategory;
    }
}