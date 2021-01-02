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

use core\progress\display_if_slow;
use local_edximport\edx_importer;

define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '../../../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');
require_once('course_import_form.php');

admin_externalpage_setup('courseimport');
require_login();

// Override pagetype to show blocks properly.
$header = get_string('courseimport', 'local_edximport');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/edximport/admin/courseimport.php');

$PAGE->set_url($pageurl);

$mform = new course_import_form();

if ($mform->is_cancelled()) {
    redirect($pageurl);
}
echo $OUTPUT->header();
if ($data = $mform->get_data()) {
    if ($mform->get_new_filename('edxcoursearchive')) {
        $progress = new display_if_slow();
        $progress->set_display_names(true);
        if (($dir = make_temp_directory('forms')) &&
            ($tempfile = (tempnam($dir, 'tempup_') . '.tar.gz'))) {
            $mform->save_file('edxcoursearchive', $tempfile, true);
            $courseid = edx_importer::import_from_path($tempfile, $progress);
            unlink($tempfile); // Remove temp file.
            if ($courseid !== false) {
                echo $OUTPUT->notification(get_string('edxcoursearchiveimported', 'local_edximport'), 'error');
                echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $courseid)));
            } else {
                echo $OUTPUT->notification(get_string('edxcoursearchiveimported:error', 'local_edximport'), 'error');
                echo $OUTPUT->continue_button($pageurl);
            }
        } else {
            echo $OUTPUT->notification(get_string('edxcoursearchiveimported:error', 'local_edximport'), 'error');
            echo $OUTPUT->continue_button($pageurl);
        }
    }
} else {
    $mform->display();
}
echo $OUTPUT->footer();
