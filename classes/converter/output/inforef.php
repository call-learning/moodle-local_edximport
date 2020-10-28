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

use local_edximport\converter\edx_moodle_model;

defined('MOODLE_INTERNAL') || die();

class inforef extends base {

    protected $refmanager = null;
    protected $componenttype = null;
    protected $refdata = null;

    /**
     * @param $model
     */
    public function __construct($outputdir, $refmanager, $componenttype, $refdata = null) {
        parent::__construct($outputdir);
        $this->refmanager = $refmanager;
        $this->componenttype = $componenttype;
        $this->refdata = $refdata;
    }

    /**
     *
     */
    public function create_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');
        $componentid = null;
        if (!empty($this->refdata->moduleid)) {
            $componentid = $this->refdata->moduleid;
        }

        if ($this->componenttype == 'course') {
            $this->create_info_ref('course','course', null);
        } else if ($this->componenttype == 'activity') {
            $this->create_info_ref(
                'activities/' . $this->refdata->type . '_' . $this->refdata->moduleid,
                $this->refdata->type,
                $this->refdata->moduleid);
        }
    }

    protected function create_info_ref($inforefpath, $ctype,$currentcid) {
        $this->open_xml_writer($inforefpath.'/inforef.xml');
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->refmanager->get_refs_for_type('file') as $componenttype => $filerefdata) {
            foreach ($filerefdata as $componentid => $filesid) {
                foreach ($filesid as $fileid) {
                    if (
                        $componenttype == $ctype ) {
                        if ($currentcid && $componentid != $currentcid) {
                            continue;
                        }
                        $this->write_xml('file',
                            array('id' => $fileid));
                    }
                }
            }
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
