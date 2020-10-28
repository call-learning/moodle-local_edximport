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
 * edX Model Visitor (mainly to check for files but can be used for other purposes)
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\converter;

defined('MOODLE_INTERNAL') || die();

/**
 * Class base
 *
 * A dataobject type of class
 *
 * @package local_edximport\edx\model
 */
abstract class model_visitor {
    public abstract function visit_course(\local_edximport\edx\model\course $model);
    public abstract  function visit_chapter(\local_edximport\edx\model\chapter $model);
    public abstract  function visit_discussion(\local_edximport\edx\model\discussion $model);
    public abstract  function visit_html(\local_edximport\edx\model\html $model);
    public abstract  function visit_problem(\local_edximport\edx\model\problem $model);
    public abstract  function visit_sequential(\local_edximport\edx\model\sequential $model);
    public abstract  function visit_vertical(\local_edximport\edx\model\vertical $model);
    public abstract  function visit_video(\local_edximport\edx\model\video $model);
    public abstract  function visit_wiki(\local_edximport\edx\model\wiki $model);
    public abstract  function visit_choicetype(\local_edximport\edx\model\question\choicetype $model);
    public abstract  function visit_multiplechoiceresponse(\local_edximport\edx\model\question\multiplechoiceresponse $model);
}