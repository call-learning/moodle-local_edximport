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
 * edX Model for course
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\edx\model;

defined('MOODLE_INTERNAL') || die();

class course extends base {

    protected static $attributeslist = ['image', 'startdate', 'enddate', 'fullname'];

    public $wiki = null;
    public $chapters = [];
    public $assets = null;

    /**
     * Course constructor.
     *
     * @param $image
     * @param $startdate
     * @param $enddate
     * @param $fullname
     * @throws \moodle_exception
     */
    public function __construct($image, $startdate, $enddate, $fullname) {
        parent::__construct(
            compact(self::$attributeslist)
        );
    }

    public function add_chapter(chapter $chapter) {
        $this->chapters[] = $chapter;
        $this->set_parent($chapter);
    }

    public function add_assets($assetdefs) {
        $this->assets = $assetdefs;
    }

    public function set_wiki(wiki $wiki) {
        $this->wiki = $wiki;
    }

    /**
     * ID is always course::COURSE_ID for course
     *
     * @param $keyname
     * @return mixed|null
     */
    public function __get($keyname) {
        if ($keyname == 'id') {
            return self::COURSE_ID;
        }
        return parent::__get($keyname);
    }

    /**
     * Course ID
     */
    const COURSE_ID = 2;
}