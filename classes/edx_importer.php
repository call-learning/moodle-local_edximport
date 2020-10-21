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

global $CFG;

use local_edximport\parser\simple_parser;
use local_edximport\processors\course_processor;
use progressive_parser;



class edx_importer {
    public static function decompress_archive($filepath, $tempdirname) {
        $archive = new PharData($filepath);
        if (!$tempdirname) {
            $tempdirname = \html_writer::random_id('edximport');
        }
        make_backup_temp_directory($tempdirname);
        $decompressdest = get_backup_temp_directory($tempdirname);
        $archive->extractTo($decompressdest);
    }
    public static function import($archivepath) {
        $pp = new simple_parser();
        $pr = new course_processor($archivepath);
        $pp->set_processor($pr);
        $pp->set_file($archivepath . '/course/course.xml');
        $pp->process();

        $course = $pr->get_entity();
        var_dump($course);
        return SITEID;
    }

}