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

use local_edximport\edx\model\course as edx_course;
use local_edximport\edx\model\wiki as edx_wiki;
use local_edximport\local\parser_utils;
use XMLReader;

/**
 * Class course_parser
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends simple_parser {
    /**
     * Get the filename to parse
     *
     * @return string
     */
    public function get_file_path() {
        return '/course/course.xml';
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
                case 'course':
                    $starttime = parser_utils::convert_edx_date_to_ts($xmlreader->getAttribute('start'));
                    $endtime = parser_utils::convert_edx_date_to_ts($xmlreader->getAttribute('end'));
                    $this->entity = new edx_course(
                        $xmlreader->getAttribute('display_name'),
                        $starttime,
                        $endtime,
                        $xmlreader->getAttribute('course_image')
                    );
                    break;
                case 'chapter':
                    $this->relatedentities[] = (object) [
                        'type' => 'chapter',
                        'url' => $xmlreader->getAttribute('url_name')
                    ];
                    break;
                case 'wiki':
                    $this->entity->set_wiki(new edx_wiki($xmlreader->getAttribute('slug')));
                    break;
            }
        }
        return true;
    }

    /**
     * Parse the original model
     */
    public function parse(\core\progress\base $progress = null) {
        parent::parse($progress);
        $this->entity->add_assets(json_decode(file_get_contents($this->archivepath . '/policies/assets.json')));
    }
}