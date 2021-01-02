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
 * Manage references to files or question. Can be used to manage inforefs and files.
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\converter;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use moodle_exception;
use SebastianBergmann\FileIterator\Iterator;

/**
 * Class ref_manager
 *
 * Basically a reference to a given structure or item (from the list moodle has references to,
 * see \backup_helper::get_inforef_itemnames()))
 * This is also an interator sot we can then go over its content easily.
 *
 * @package local_edximport\converter
 */
class ref_manager {
    /** @var array the actual storage of references, currently implemented as a in-memory structure */
    public $refs = array();

    /**
     * Adds a reference
     *
     * @param string $item the name of referenced item (like user, file, scale, outcome or grade_item)
     * @param int $id the value of the reference
     * @throws moodle_exception
     */

    public static final function get_instance() {
        static $refs = null;
        if (!$refs) {
            $refs = new ref_manager(); // Stores ref to entities.
        }
        return $refs;
    }

    /**
     * Adds a reference
     *
     * @param string $componenttype type of component. Can be anything from quiz to a given module type or course
     * @param int $id id of the component referencing this entity
     * @param string $moodlereftype can only be a valid item in inforef.xml file
     * @param int $entityid id of the entity that can be found in the pool
     * @throws moodle_exception
     */
    public function add_ref($componenttype, $id, $moodlereftype, $entityid) {
        utils::validate_refitemtype($moodlereftype);
        if (empty($this->refs[$componenttype][$id][$moodlereftype])) {
            $this->refs[$componenttype][$id][$moodlereftype] = [];
        }
        $this->refs[$componenttype][$id][$moodlereftype][] = $entityid;
    }

    /**
     * Get references.
     *
     * @param string $componenttype type of component. Can be anything from quiz to a given module type or course
     * @param int $id id of the component that is referencing this entity
     * @param string $moodlereftype can only be a valid item in inforef.xml file
     * @return array
     * @throws moodle_exception
     */
    public function get_refs($componenttype, $id, $moodlereftype) {
        utils::validate_refitemtype($moodlereftype);
        if (!empty($this->refs[$componenttype][$id][$moodlereftype])) {
            return $this->refs[$componenttype][$id][$moodlereftype];
        }
        return [];
    }

    /**
     * Get references for a moodle ref type
     *
     * @param $moodlereftype (file, user, ...)
     * @return array and array of array keyed by component type.
     * @throws moodle_exception
     */
    public function get_all_refs_for_type($moodlereftype) {
        utils::validate_refitemtype($moodlereftype);
        $refbytype = $this->get_refs_for_type($moodlereftype);
        $allrefs = [];
        foreach ($refbytype as $componenttype => $refs) {
            foreach ($refs as $componentid => $refentities) {
                foreach ($refentities as $ref) {
                    $allrefs[] = $ref;
                }
            }
        }
        return $allrefs;
    }

    /**
     * Get references for a moodle ref type
     *
     * @param $moodlereftype (file, user, ...)
     * @return array and array of array keyed by component type.
     * @throws moodle_exception
     */
    public function get_refs_for_type($moodlereftype) {
        utils::validate_refitemtype($moodlereftype);
        $moodlereftypedata = [];
        foreach ($this->refs as $componenttype => $refdata) {
            foreach ($refdata as $componentid => $refdata) {
                if (!empty($refdata[$moodlereftype])) {
                    if (empty($moodlereftypedata[$componenttype][$componentid])) {
                        $moodlereftypedata[$componenttype][$componentid] = [];
                    }
                    $moodlereftypedata[$componenttype][$componentid] =
                        array_merge($moodlereftypedata[$componenttype][$componentid], $refdata[$moodlereftype]);
                }
            }
        }
        return $moodlereftypedata;
    }
}