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
defined('MOODLE_INTERNAL') || die();

use local_edximport\edx\model\discussion as edx_discussion;
use XMLReader;

/**
 * Discussion
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion extends simple_parser {
    /**
     * Get the filename to parse
     *
     * @return string
     */
    public function get_file_path() {
        return "/discussion/{$this->entityid}.xml";
    }

    /**
     * Process a given element.
     *
     * This method can also have side effects on the xmlreader (move to next node for example)
     *
     * @param XMLReader $xmlreader
     * @return bool
     */
    public function process_element(&$xmlreader) {
        if ($xmlreader->nodeType == XMLReader::ELEMENT) { // Opening tags only.
            switch ($xmlreader->name) {
                case 'discussion':
                    $this->entity = new edx_discussion(
                        $xmlreader->getAttribute('discussion_category'),
                        $xmlreader->getAttribute('display_name'),
                        $xmlreader->getAttribute('discussion_target'));
                    break;
            }
        }
        return true;
    }
}