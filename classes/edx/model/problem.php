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
 * edX Model for chapter
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\edx\model;

defined('MOODLE_INTERNAL') || die();

/**
 * Class problem
 *
 * A dataobject type of class
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class problem extends base implements html_content {
    public $instructions = [];

    public $questions = [];
    public $solutions = [];

    const QUESTION_CLASS = '\\local_edximport\\edx\model\\question\\';

    /**
     * @var string[] $attributeslist
     */
    protected static $attributeslist = ['entityid', 'displayname', 'maxattempts', 'showanswers', 'weight', 'rerandomize'];

    /**
     * problem constructor.
     *
     * @param $entityid
     * @param $displayname
     * @param $maxattempts
     * @param $showanswers
     * @param $weight
     * @param $rerandomize
     * @throws \moodle_exception
     */
    public function __construct($entityid, $displayname, $maxattempts, $showanswers, $weight, $rerandomize) {
        // See https://edx.readthedocs.io/projects/edx-open-learning-xml/en/latest/components/problem-components.html .
        parent::__construct(
            compact(self::$attributeslist)
        );
    }

    /**
     * @param \DOMNode $node
     * @return mixed
     */
    public static function question_from_dom_node(\DOMNode $node) {
        $questionclass = self::QUESTION_CLASS . $node->nodeName;
        $question = new $questionclass();
        switch ($node->nodeName) {
            case 'multiplechoiceresponse':
                $choicegroup = $node->firstChild;
                $question->label = $choicegroup->attributes->getNamedItem('label') ?
                    $choicegroup->attributes->getNamedItem('label')->textContent : '';
                $question->type = $choicegroup->attributes->getNamedItem('type') ?
                    $choicegroup->attributes->getNamedItem('type')->textContent : '';
                self::convert_choices($question, $choicegroup);
                break;
            case 'choiceresponse':
                $checkboxgroup = $node->firstChild;
                $checkboxgroupdirection = $checkboxgroup->attributes->getNamedItem('direction') ?
                    $checkboxgroup->attributes->getNamedItem('direction')->textContent : 'vertical';
                $question->set_direction($checkboxgroupdirection);
                self::convert_choices($question, $checkboxgroup);
                break;
        }
        return $question;
    }

    /**
     * @param $question
     * @param $rootnode
     */
    protected static function convert_choices(&$question, $rootnode) {
        foreach ($rootnode->childNodes as $choicenode) {
            $correctstring = $choicenode->attributes->getNamedItem('correct')->textContent;
            $correct = (strtolower(trim($correctstring)) == "true") ? true : false;
            $label = $choicenode->textContent;
            $question->add_choice($correct, $label);
        }
    }

    /**
     * @param $instruction
     */
    public function add_instruction($instruction) {
        $this->instructions[] = $instruction;
    }

    /**
     * @param question\base $question
     */
    public function add_question(question\base $question) {
        $this->questions[] = $question;
        $this->set_parent($question);
    }

    /**
     * @param question\solution $solution
     */
    public function add_solution(question\solution $solution) {
        $this->solutions[] = $solution;
    }

    /**
     * @param $questiontype
     * @return bool
     */
    public static function is_known_question($questiontype) {
        return class_exists(self::QUESTION_CLASS . $questiontype);
    }

    /**
     * Get all instructions
     */
    public function get_content() {
        return join(" ", $this->instructions);
    }
}