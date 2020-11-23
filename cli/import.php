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
 * CLI script to import a course from the command line.
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'path' => null,
], [
    'h' => 'help'
]);

$usage = "Import a edx archive as a new course

Usage:
    # php import.php --path=<edxarchivepath>
    # php import.php [--help|-h]

Options:
    -h --help                   Print this help.
    --path=<edxarchivepath>     Full path of the archive to import";

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}
global $DB, $USER;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/helper/backup_helper.class.php');

$path = '/home/laurentd/development/minesdouai/course-full';
$edximporter = new \local_edximport\edx_importer($path);
$coursemodel = $edximporter->import();
$edximporter = new \local_edximport\edx_to_moodle_exporter($coursemodel, $edximporter->get_archive_path(), '', false);
$fullpathbackupfolder = $edximporter->export();
cli_writeln('Moodle course in ' . $fullpathbackupfolder);
//die();
cli_writeln('Restoring...');
// Transaction.
$transaction = $DB->start_delegated_transaction();

// Create new course.

$categoryid = '1';
$userdoingrestore = get_admin()->id;
$restorecourse = $DB->get_record('course', array('shortname' => 'CTR'));
if ($restorecourse) {
    restore_dbops::delete_course_content($restorecourse->id);
    $courseid = $restorecourse->id;
} else {
    $courseid = restore_dbops::create_new_course('Course Test Restore', 'CTR', $categoryid);
}

// Restore backup into course.
$backupcoursesubpath = basename($fullpathbackupfolder);
$controller = new restore_controller($backupcoursesubpath, $courseid,
    backup::INTERACTIVE_NO, backup::MODE_CONVERTED, $userdoingrestore,
    backup::TARGET_NEW_COURSE);
$controller->execute_precheck();
$controller->execute_plan();

// Commit.
$transaction->allow_commit();


//if(empty($option['path']) || !is_file($option['path'])) {
//    $courseid = \local_edximport\edx_importer::import($option['path']);
//} else {
//    cli_error(get_string('patherror', 'local_edximport'));
//}