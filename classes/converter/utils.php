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

namespace local_edximport\converter;

use moodle_exception;

class utils {
    /**
     * Makes sure that the given name is a valid citizen of inforef.xml file
     *
     * @param string $item the name of reference (like user, file, scale, outcome or grade_item)
     * we also add the type 'question' as a convenience for us.
     * @return bool
     * @throws \moodle_exception
     */
    public static function validate_refitemtype($itemtype) {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');
        require_once($CFG->dirroot . '/backup/util/helper/backup_helper.class.php');

        $valid = in_array($itemtype, \backup_helper::get_inforef_itemnames());
        if (!$valid) {
            throw new moodle_exception('Invalid inforef item type');
        }
        return true;
    }

    /**
     * Get qtype fileareas
     */
    public static function get_qtype_fileareas($qtype) {
        static $filearea = null;
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');
        require_once($CFG->dirroot . '/backup/backup_qtype_plugin.class.php');
        if($filearea  == null) {
            $filearea = \backup_qtype_plugin::get_components_and_fileareas();
        }
        return $filearea[$qtype];
    }
}