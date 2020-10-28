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

namespace local_edximport\converter\output;

defined('MOODLE_INTERNAL') || die();

class questions extends base {

    protected $questionlist = null;

    protected $backupfilename = '';

    /**
     * @param $model
     */
    public function __construct($outputdir, $model) {
        parent::__construct($outputdir);
        $this->questionlist = $model;
    }

    /**
     *
     */
    public function create_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');

        $now = time();
        // Write section's inforef.xml with the file references.
        $this->open_xml_writer('questions.xml');
        $this->xmlwriter->begin_tag('question_category', ['category_id'=>]);
        $this->xmlwriter->begin_tag('fileref');
        // Write file ref here
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('question_category');
        $this->close_xml_writer();

        $section = [
            'number' => $this->sequential->index,
            'name' => $this->sequential->displayname,
            'summary' => '',
            'summaryformat' => FORMAT_HTML,
            'visible' => 1,
            'availabilityjson' => '{"op":"&amp;","c":[],"showc":[]}',
            'timemodified' => $now
        ];
        $this->open_xml_writer('sections/section_' . $this->sequential->id . '/section.xml');
        $this->write_xml('section', $section);
        $this->close_xml_writer();
    }
}
