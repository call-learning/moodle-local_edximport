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

class vertical {
    public $urlname = "";

    public $displayname = "";

    public $htmls = [];
    public $videos = [];
    public $discussions = [];
    public $problems = [];

    public function __construct($urlname, $displayname) {
        $this->urlname = $urlname;
        $this->displayname = $displayname;
    }

    public function add_html(html $html) {
        $this->htmls [] = $html;
    }

    public function add_video(video $video) {
        $this->videos [] = $video;
    }

    public function add_discussion(discussion $discussion) {
        $this->discussions [] = $discussion;
    }
    public function add_problem(problem $problem) {
        $this->problems [] = $problem;
    }
}