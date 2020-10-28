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

class vertical extends base {

    /**
     * @var string[] $attributeslist
     */
    protected static $attributeslist = ['entityid', 'displayname'];

    public $htmls = [];
    public $videos = [];
    public $discussions = [];
    public $problems = [];

    public $indexedentities = []; // Same entities as above but indexed by entity index.

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

    protected function add_indexed(base $model) {
        $this->indexedentities[$model->index] = $model;
    }

    public function add_html(html $html) {
        $this->htmls [] = $html;
        $this->set_parent($html);
        $this->add_indexed($html);
    }

    public function add_video(video $video) {
        $this->videos [] = $video;
        $this->set_parent($video);
        $this->add_indexed($video);
    }

    public function add_discussion(discussion $discussion) {
        $this->discussions [] = $discussion;
        $this->set_parent($discussion);
        $this->add_indexed($discussion);
    }

    public function add_problem(problem $problem) {
        $this->problems [] = $problem;
        $this->set_parent($problem);
        $this->add_indexed($problem);
    }
}