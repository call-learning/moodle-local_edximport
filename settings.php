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
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    global $CFG;
    $edximportmanagement = new admin_category(
        'edximportmanagement',
        get_string('edximportmanagement', 'local_edximport')
    );

    // General settings.
    $pagedesc = get_string('edximportgeneralsettings', 'local_edximport');
    $generalsettingspage = new admin_settingpage('edximportgeneral',
        $pagedesc,
        array('local/edximport:managesettings'),
        empty($CFG->enableedximport));

    // Data import page.
    $pagedesc = get_string('courseimport', 'local_edximport');
    $pageurl = new moodle_url($CFG->wwwroot . '/local/edximport/admin/courseimport.php');
    $edximportmanagement->add('edximportmanagement',
        new admin_externalpage(
            'courseimport',
            $pagedesc,
            $pageurl,
            array('local/edximport:managesettings'),
            empty($CFG->enableedximport)
        )
    );

    if (!empty($CFG->enableedximport)) {
        $ADMIN->add('root', $edximportmanagement);
    }

    // Create a global Advanced Feature Toggle.
    $optionalsubsystems = $ADMIN->locate('optionalsubsystems');
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableedximport',
            new lang_string('enableedximport', 'local_edximport'),
            new lang_string('enableedximport_help', 'local_edximport'),
            1)
    );
}