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

use local_edximport\local\utils;
use local_edximport\parser\simple_parser;
use local_edximport\processors\course_processor;

class edx_importer {
    protected $archivepath = null;
    protected $archivetoremove = false;

    public function __construct($archivepath) {
        if (!is_dir($archivepath)) {
            $edxdestfile = \html_writer::random_id('edxdestfile');
            $decompressedpath = make_backup_temp_directory($edxdestfile);
            utils::decompress_archive($archivepath, $decompressedpath);
            $this->archivepath = $decompressedpath . '/course';
            $this->archivetoremove = true;
        } else {
            $this->archivepath = $archivepath;
        }
    }
    public function import() {
        $course = simple_parser::simple_process_entity($this->archivepath , 'course');
        return $course;
    }

    public function get_archive_path() {
        return $this->archivepath;
    }

    public function __destruct() {
        if ($this->archivetoremove) {
            utils::cleanup_dir($this->archivepath);
        }
    }
}