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

use local_edximport\edx_importer;

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
list($options, $unrecognised) = cli_get_params([
    'import' => null,
    'help' => false,
    'path' => '~/edx-course',
], [
    'h' => 'help',
    'i' => 'import'
]);

$usage = "Import a edx archive as a new course

Usage:
    # php import.php --path=<edxarchivepath>
    # php import.php --import
    # php import.php [--help|-h]

Options:
    -h --help                   Print this help.
    --path=<edxarchivepath>     Full path of the archive to import
    --import                    Convert the model AND impport it into a new moodle course
    ";

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_logo();
    cli_writeln($usage);
    exit(2);
}
global $DB, $USER;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/helper/backup_helper.class.php');

$path = '/home/laurentd/development/minesdouai/course-full';

/**
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Class cli_progress
 */
class cli_progress extends \core\progress\base {
    /**
     * Constructs the progress reporter.
     *
     * @param bool $startnow If true, outputs HTML immediately.
     * @throws coding_exception
     */
    public function __construct($startnow = false) {
        if ($startnow) {
            $this->start_progress('start');
        }
    }

    /**
     * Start progress
     *
     * @param string $description
     * @param int $max
     * @param int $parentcount
     * @throws coding_exception
     */
    public function start_progress($description, $max = self::INDETERMINATE,
        $parentcount = 1) {
        cli_writeln("\n" . $description);
        parent::start_progress($description, $max, $parentcount);
    }

    /**
     * Update progress
     */
    protected function update_progress() {
        cli_write(str_repeat('.', $this->get_progress_count()));
        flush();
    }
}

$cliprogress = new cli_progress();
cli_writeln('Starting import');
$pathorid = edx_importer::import_from_path($path, $cliprogress, $options['import']);
cli_writeln("\nImport finished, returned value: " . $pathorid);