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

use coding_exception;
use moodle_exception;
use SebastianBergmann\FileIterator\Iterator;

defined('MOODLE_INTERNAL') || die();

/**
 * Reference an enty (being file, question or other)
 *
 * @package local_edximport\converter
 */
class entity_pool {
    /** @var array the actual storage of entities, currently implemented as a in-memory structure */
    public $entities = array();

    /**
     * Setup a new entity.
     * @throws moodle_exception
     */

    /**
     * Setup a new entity.
     * @param $entitytype
     * @return int|mixed|string the new entity
     */
    public function new_entity($entitytype) {
        if (empty($this->entities[$entitytype])) {
            $this->entities[$entitytype] = [];
        }
        if (count($this->entities[$entitytype]) > 0) {
            $maxid = max(array_keys($this->entities[$entitytype])) + 1;
        } else {
            $maxid = 1; // 0 has sometimes a special meaning.
        }
        $this->entities[$entitytype][$maxid] = null;
        return $maxid;
    }

    /**
     * Sets a value for a given entity.
     *
     * @param string $item the name of referenced item (like user, file, scale, outcome or grade_item)
     * @param int $id the value of the reference
     * @param mixed $data refer to. Can be a structure or a filepath or something else.
     * @return int|mixed|string
     * @throws moodle_exception
     */
    public function set_data($entitytype, $id, $data) {
        $this->entities[$entitytype][$id] = $data;
    }

    /**
     * Get an entity data.
     *
     * @param $itemtype
     * @param int $id
     * @return mixed
     * @throws moodle_exception
     */
    public function get_entity($itemtype, $id) {
        if (!empty($this->entities[$itemtype][$id])) {
            return $this->entities[$itemtype][$id];
        }
        return null;
    }

    /**
     * Get an entity data.
     *
     * @param $itemtype
     * @param int $id
     * @return array
     * @throws moodle_exception
     */
    public function get_entities($itemtype) {
        if (!empty($this->entities[$itemtype])) {
            return $this->entities[$itemtype];
        }
        return null;
    }

    public static final function get_instance() {
        static $entitypool = null;
        if (!$entitypool) {
            $entitypool = new entity_pool(); // Store entities information.
        }
        return $entitypool;
    }


}