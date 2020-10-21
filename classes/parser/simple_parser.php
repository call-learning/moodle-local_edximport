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

namespace local_edximport\parser;
use progressive_parser;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/backup/util/xml/parser/progressive_parser.class.php');


class simple_parser extends progressive_parser {

    protected function start_tag($parser, $tag, $attributes) {

        // Normal update of parser internals.
        $this->level++;
        $this->path .= '/' . $tag;
        $this->accum = '';
        $this->attrs = !empty($attributes) ? $attributes : array();

        // Inform processor we are about to start one tag.
        $this->inform_start($this->path);

        // Entering a new inner level, publish all the information available.
        if ($this->level > $this->prevlevel) {
            if (!empty($this->currtag) && (!empty($this->currtag['attrs']) || !empty($this->currtag['cdata']))) {
                // We add all tags as children.
            }
            if (!empty($this->topush['tags'])) {
                $this->publish($this->topush);
            }
            $this->currtag = array();
            $this->topush = array();
        }

        // If not set, build to push common header.
        if (empty($this->topush)) {
            $this->topush['path']  = progressive_parser::dirname($this->path);
            $this->topush['level'] = $this->level;
            $this->topush['tags']  = array();
        }

        // Handling a new tag, create it.
        $this->currtag['name'] = $tag;
        // And add attributes if present.
        if ($this->attrs) {
            $this->currtag['attrs'] = $this->attrs;
        }

        // For the records.
        $this->prevlevel = $this->level;
    }

    protected function end_tag($parser, $tag) {

        // Ending rencently started tag, add value to current tag.
        if ($this->level == $this->prevlevel) {
            $this->currtag['cdata'] = $this->postprocess_cdata($this->accum);
            // We always add the last not-empty repetition. Empty ones are ignored.
            $this->topush['tags'][] = $this->currtag;
            $this->currtag = array();
        }

        // Leaving one level, publish all the information available.
        if ($this->level <= $this->prevlevel) {
            if (!empty($this->topush['tags'])) {
                $this->publish($this->topush);
            }
            $this->currtag = array();
            $this->topush = array();
        }

        // For the records.
        $this->prevlevel = $this->level;

        // Inform processor we have finished one tag.
        $this->inform_end($this->path);

        // Normal update of parser internals.
        $this->level--;
        $this->path = progressive_parser::dirname($this->path);
    }

}