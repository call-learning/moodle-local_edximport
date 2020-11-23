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

namespace local_edximport\parser;

use local_edximport\edx\model\problem as edx_problem;
use local_edximport\edx\model\question\solution;
use XMLReader;

defined('MOODLE_INTERNAL') || die();

class problem extends simple_parser {
    const INTRO_ELEMENTS = ['p', 'label'];

    /**
     * Get the filename to parse
     *
     * @return string
     */
    public function get_file_path() {
        return "/problem/{$this->entityid}.xml";
    }

    /**
     * Process a given element.
     *
     * This method can also have side effects on the xmlreader (move to next node for example)
     *
     * @param XMLReader $xmlreader
     */
    public function process_element(&$xmlreader) {
        if ($xmlreader->nodeType == XMLReader::ELEMENT) { // Opening tags only.
            switch ($xmlreader->name) {
                case 'problem':
                    $this->entity = new edx_problem(
                        $this->entityid,
                        $xmlreader->getAttribute('display_name'),
                        $xmlreader->getAttribute('max_attempts'),
                        $xmlreader->getAttribute('show_answers'),
                        $xmlreader->getAttribute('weight'),
                        $xmlreader->getAttribute('rerandomize')
                    );
                    break;
                case 'solution':
                    $this->entity->add_solution(new solution($xmlreader->readInnerXml()));
                    $xmlreader->next();
                    break;
                default:
                    if (edx_problem::is_known_question($xmlreader->name)) {
                        $doc = new \DOMDocument();
                        $doc->loadXML($xmlreader->readOuterXml(), LIBXML_NOBLANKS);
                        $this->entity->add_question(
                            edx_problem::question_from_dom_node($doc->firstChild)
                        );
                    } else {
                        $this->entity->add_instruction($xmlreader->readOuterXml());
                    }
                    $xmlreader->next();

            }
        }
        return true;
    }
}