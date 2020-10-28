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

namespace local_edximport;
defined('MOODLE_INTERNAL') || die();

use local_edximport\converter\course as course_converter;
use local_edximport\converter\edx_moodle_model;
use local_edximport\converter\output\course;
use local_edximport\edx\model\course as course_model;
use local_edximport\parser\simple_parser;
use local_edximport\processors\course_processor;
use progressive_parser;

class edx_to_moodle_exporter {
    public static function export($destinationpath, course_model $course, $edxarchpath) {
        $edxtomoodle = new edx_moodle_model($course, $edxarchpath);
        $converter = new course($destinationpath, $edxtomoodle);
        $converter->create_backup();
    }
}