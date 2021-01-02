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
use local_edximport\converter\utils;
use local_edximport\edx\model\base as base_edx_model;
use local_edximport\edx\model\course as course_model;
use local_edximport\local\parser_utils;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class question extends base {
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
        $question = new question(
            $helper,
            $originalmodels
        );

        $question->data = $question->build([
            'quizmoduleid' => $additionalargs[0],
        ]);
        return $question;
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
        $now = time();
        $quizmoduleid = $args['quizmoduleid'];

        $questionmodel = new stdClass();
        $questionid = $this->helper->entitypool->new_entity('question');
        $questionmodel->id = $questionid;
        $questionmodel->parentid = 0;
        $questionmodel->name = $model->displayname;
        $questionmodel->text = parser_utils::change_html_static_ref(utils::get_raw_content_from_model($model));
        // TODO: Here we assume that a edX problem will have only one question.
        $questionmodel->textformat = FORMAT_HTML;
        $questionmodel->generalfeedback = '';
        $questionmodel->generalfeedbackformat = FORMAT_HTML;
        $questionmodel->defaultmark = 1.000000;
        $questionmodel->penalty = 0.3333333;
        $questionmodel->qtype = 'multichoice';
        $questionmodel->stamp = builder_helper::get_stamp();
        $questionmodel->version = builder_helper::get_stamp();
        $questionmodel->timecreated = $now;
        $questionmodel->timemodified = $now;
        $questionmodel->createdby = get_admin()->id;
        $questionmodel->modifiedby = get_admin()->id;
        $questionmodel->idnumber = '$@NULL@$';

        if (builder_helper::is_static_content($model)) {
            $questionmodel->qtype = 'description';
            $questionmodel->maxmark = 0.000;
        } else {
            $edxquestion = $model->questions[0]; // For now we take the first question.
            switch (get_class($edxquestion)) {
                case 'local_edximport\edx\model\question\choiceresponse':
                default:
                    $questionmodel->qtype = 'multichoice';
                    $questionmodel->maxmark = 1.000;
                    $questionmodel->plugin_qtype_multichoice_question = new stdClass();
                    $questionmodel->plugin_qtype_multichoice_question->answers = [];
                    // See $areas = utils::get_qtype_fileareas($questionmodel->qtype) .
                    foreach ($edxquestion->choices as $choice) {
                        $answer = answer::convert($choice, $this->helper, $quizmoduleid);
                        $questionmodel->plugin_qtype_multichoice_question->answers[] = $answer->get_entity_data();
                    }
                    $multichoiceid = $this->helper->entitypool->new_entity('question_multichoice');
                    $multichoice = new stdClass();
                    $multichoice->id = $multichoiceid;
                    $multichoice->layout = 0;
                    $multichoice->single = 1;
                    $multichoice->shuffleanswers = (int) $model->rerandomize;
                    $multichoice->correctfeedback = get_string('correctfeedbackdefault', 'question');
                    $multichoice->correctfeedbackformat = FORMAT_HTML;
                    $multichoice->partiallycorrectfeedback = get_string('partiallycorrectfeedbackdefault', 'question');
                    $multichoice->partiallycorrectfeedbackformat = FORMAT_HTML;
                    $multichoice->incorrectfeedback = get_string('incorrectfeedbackdefault', 'question');
                    $multichoice->incorrectfeedbackformat = FORMAT_HTML;
                    $multichoice->answernumbering = 'abc';
                    $multichoice->shownumcorrect = 1;
                    $this->helper->entitypool->set_data('question_multichoice', $multichoiceid, $multichoice);
                    $questionmodel->plugin_qtype_multichoice_question->multichoice = $multichoice;
            }
        }

        $this->helper->entitypool->set_data('question', $questionid, $questionmodel);

        $this->helper->collect_files_refs(
            $questionid,
            'questiontext',
            $questionid,
            builder_helper::get_contextid(CONTEXT_MODULE, $quizmoduleid),
            utils::get_raw_content_from_model($model),
            'question');

        $this->helper->entitypool->set_data('question', $questionmodel->id, $questionmodel);
        return $questionmodel;
    }
}