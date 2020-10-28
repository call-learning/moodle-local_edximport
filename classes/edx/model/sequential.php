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
 * edX Model for chapter
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\edx\model;

defined('MOODLE_INTERNAL') || die();

class sequential extends base {
    /**
     * @var string[] $attributeslist
     */
    protected static $attributeslist = ['entityid','displayname'];

    public $verticals = [];

    /**
     * Course constructor.
     *
     * @throws \moodle_exception
     */
    public function __construct($entityid, $displayname) {
        parent::__construct(
            compact(self::$attributeslist)
        );
    }


    /**
     * Add a new vertical
     *
     * @param vertical $vertical
     */
    public function add_vertical(vertical $vertical) {
        $this->verticals[] = $vertical;
        $this->set_parent($vertical);
    }
}