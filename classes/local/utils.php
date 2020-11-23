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

namespace local_edximport\local;

use backup_helper;
use PharData;

/**
 * Class utils
 *
 * Generic utilities
 *
 * @package local_edximport\local
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Change all references in a text
     *
     * @param $staticrefs
     * @return string|string[]|null
     */
    public static function make_backup_folder($backupfoldername = '', $deleteexisting = false) {
        global $CFG;
        if (empty($backupfoldername)) {
            $backupfoldername = \html_writer::random_id('edxconversion');
        }

        $fullpathbackupfolder = make_backup_temp_directory($backupfoldername);
        if ($deleteexisting) {
            self::cleanup_dir($fullpathbackupfolder);
        }

        return $fullpathbackupfolder;
    }

    public static function decompress_archive($filepath, $tempdirname=null) {
        $archive = new PharData($filepath);
        $decompressdest = $tempdirname;
        if (!$tempdirname) {
            $tempdirname = \html_writer::random_id('edximport');
            make_backup_temp_directory($tempdirname);
            $decompressdest = get_backup_temp_directory($tempdirname);
        }

        $archive->extractTo($decompressdest);
        return $decompressdest;
    }

    public static function cleanup_dir(string $decompressedpath) {
        global $CFG;
        if (!empty($decompressedpath)
            && is_dir($decompressedpath)
            && $CFG->tempdir != $decompressedpath) {
            require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
            require_once($CFG->dirroot . '/backup/backup.class.php');
            require_once($CFG->dirroot . '/backup/util/helper/backup_helper.class.php');
            \backup_helper::delete_dir_contents($decompressedpath);
            rmdir($decompressedpath);
        }
    }
}