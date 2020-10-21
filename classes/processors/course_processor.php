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

namespace local_edximport\processors;
use local_edximport\edx\model\chapter;
use local_edximport\edx\model\course;
use local_edximport\edx\model\wiki;
use local_edximport\local\processor_utils;

defined('MOODLE_INTERNAL') || die();
global $CFG;
global $CFG;

require_once($CFG->dirroot . '/backup/util/xml/parser/progressive_parser.class.php');
require_once($CFG->dirroot . '/backup/util/xml/parser/processors/progressive_parser_processor.class.php');

/**
 * Class course_processor
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_processor extends base_processor {

    public function process_element($elementname, $attrs, $cdata = null) {
        switch($elementname) {
            case 'course':
                $starttime = processor_utils::convert_edx_date_to_ts($attrs->start);
                $endtime = processor_utils::convert_edx_date_to_ts($attrs->end);
                $this->entity = new course($attrs->display_name, $starttime, $endtime, $attrs->course_image);
                break;
            case 'chapter':
                $this->entity->add_chapter($this->simple_process_entity('chapter', $attrs->url_name));
                break;
            case 'wiki':
                $this->entity->set_wiki(new wiki($attrs->slug));
                break;
        }
    }
}