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

class edx_importer {
    /**
     * @var string $archivepath
     */
    protected $archivepath = null;

    /**
     * @var bool $archivetoremove
     */
    protected $archivetoremove = false;

    /**
     * edx_importer constructor.
     *
     * @param string $archivepath
     */
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

    /**
     * Import  the course in memory
     *
     * @param \core\progress\base|null $progress
     * @return mixed
     */
    public function import(\core\progress\base $progress = null) {
        $course = simple_parser::simple_process_entity($this->archivepath, 'course', null, $progress);
        return $course;
    }

    /**
     * Get current archive path
     *
     * @return string|null
     */
    public function get_archive_path() {
        return $this->archivepath;
    }

    /**
     * Cleanup temp dir
     */
    public function __destruct() {
        if ($this->archivetoremove) {
            utils::cleanup_dir($this->archivepath);
        }
    }

    /**
     * Import a given edX archive and return the new course id.
     *
     * @param $path
     * @param \core\progress\base|null $progressbar
     * @param bool $importnewcourse
     * @param null $categoryid
     * @returns false|int courseid or false if the course has not been imported.
     * @return string|int either the backup folder (for the intermediate Moodle backup folder) or the newly
     * created course.
     * @throws \coding_exception
     * @throws \dml_transaction_exception
     * @throws \restore_controller_exception
     */
    public static function import_from_path($path, \core\progress\base $progressbar = null,
        $importnewcourse = true,
        $categoryid = null
    ) {
        if ($progressbar) {
            $progressbar->start_progress('in memory import');
        }
        $edximporter = new \local_edximport\edx_importer($path);
        $coursemodel = $edximporter->import($progressbar);
        if ($progressbar) {
            $progressbar->end_progress();
        }
        if ($progressbar) {
            $progressbar->start_progress('in memory convert');
        }

        $edxexporter = new \local_edximport\edx_to_moodle_exporter(
            $coursemodel,
            $edximporter->get_archive_path(),
            '',
            false);
        $fullpathbackupfolder = $edxexporter->export($progressbar);
        if ($progressbar) {
            $progressbar->end_progress();
        }

        if ($importnewcourse) {
            global $DB, $CFG;
            require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
            require_once($CFG->dirroot . '/backup/util/helper/backup_helper.class.php');
            // Transaction.
            $transaction = $DB->start_delegated_transaction();
            if ($progressbar) {
                $progressbar->start_progress('moodle archive import');
            }
            // Create new course.
            $categoryid = empty($categoryid) ? \core_course_category::get_default()->id : $categoryid;
            $userdoingrestore = get_admin()->id;
            list($fullname, $shortname) = \restore_dbops::calculate_course_names(
                0, get_string('restoringcourse', 'backup'),
                get_string('restoringcourseshortname', 'backup'));
            $courseid = \restore_dbops::create_new_course($fullname, $shortname, $categoryid);

            // Restore backup into course.
            $backupcoursesubpath = basename($fullpathbackupfolder);
            $controller = new \restore_controller($backupcoursesubpath, $courseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_CONVERTED,
                $userdoingrestore,
                \backup::TARGET_NEW_COURSE,
                $progressbar
            );
            $controller->execute_precheck();
            $controller->execute_plan();

            // Commit.
            $transaction->allow_commit();
            if ($progressbar) {
                $progressbar->end_progress();
            }
            return $courseid;
        }
        return $fullpathbackupfolder;
    }
}