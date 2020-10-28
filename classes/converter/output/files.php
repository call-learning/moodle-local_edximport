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

class files extends base {

    protected $entitypool = null;
    protected $refmanager = null;
    protected $assetmatcher = null;
    protected $edxarchpath = ""; // Uncompressed edX uncompressed archive path, to copy files from.

    /**
     * @param $model
     */
    public function __construct($outputdir, $entitypool, $refmanager, $edxarchpath, $assetmatcher) {
        parent::__construct($outputdir);
        $this->entitypool = $entitypool;
        $this->refmanager = $refmanager;
        $this->edxarchpath = $edxarchpath;
        $this->assetmatcher = $assetmatcher;
    }

    /**
     *
     */
    public function create_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');
        $this->open_xml_writer('files.xml');
        $this->xmlwriter->begin_tag('files');

        foreach ($this->refmanager->get_refs_for_type('file') as $componenttype => $filerefdata) {
            foreach ($filerefdata as $componentid => $filesid) {
                foreach ($filesid as $fileid) {
                    $filedata = $this->entitypool->get_entity('file', $fileid);
                    $component = 'course';
                    $filearea = 'summary';
                    $contextid = CONTEXT_COURSE;
                    // TODO: this should be taken from the module/backup structure directly.
                    switch ($componenttype) {
                        case 'book':
                            $component = 'mod_book';
                            $filearea = 'chapter';
                            $contextid = edx_moodle_model::get_contextid(CONTEXT_MODULE, $componentid);
                            break;
                        case 'page':
                            $component = 'mod_page';
                            $filearea = 'content';
                            $contextid = edx_moodle_model::get_contextid(CONTEXT_MODULE, $componentid);
                            break;
                        case 'question':
                            $component = 'question';
                            $filearea = 'questiontext';
                            $contextid = edx_moodle_model::get_contextid(CONTEXT_MODULE, $componentid);
                            break;
                    }
                    $this->write_file($component, $filearea, $contextid, $filedata->filename, $filedata->filepath,
                        $filedata->originalpath);

                    // TODO: copy file into relevant folder.
                }
            }
        }
        $this->xmlwriter->end_tag('files');
        $this->close_xml_writer();
    }

    protected function write_file($component, $filearea, $contextid, $filename, $filepath, $originalfilepath) {
        // Write file data
        $now = time();
        if (!empty($this->assetmatcher->$filename) && !empty($this->assetmatcher->$filename->import_path)) {
            $originalfilepath = '/static/'.$this->assetmatcher->$filename->import_path;
        }
        $originalfilefullpath = $this->edxarchpath . '/' .trim($originalfilepath, '/');

        $contenthash = get_file_storage()::hash_from_path($originalfilefullpath);
        $filesize = filesize($originalfilefullpath);

        $fileinfo = [
            'contenthash' => $contenthash,
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => 0,
            'filepath' => $filepath,
            'filename' => $filename,
            'userid' => 2,
            'filesize' => $filesize,
            'mimetype' => '$@NULL@$',
            'status' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'source' => '$@NULL@$',
            'author' => '$@NULL@$',
            'license' => '$@NULL@$',
            'sortorder' => 0,
            'repositorytype' => '$@NULL@$',
            'repositoryid' => '$@NULL@$',
            'reference' => '$@NULL@$'
        ];
        $this->write_xml('file', $fileinfo, array('/file/id'));
        $hashpath    = $this->outputdir . '/files/'.substr($contenthash, 0, 2);
        $hashfile    = "$hashpath/$contenthash";

        if (file_exists($hashfile)) {
            if (filesize($hashfile) !== $filesize) {
                throw new \moodle_exception('same_hash_different_size');
            }
        } else {
            check_dir_exists($hashpath);
            if (!copy($originalfilefullpath, $hashfile)) {
                throw new \moodle_exception('unable_to_copy_file');
            }

            if (filesize($hashfile) !== $filesize) {
                throw new \moodle_exception('filesize_different_after_copy');
            }
        }
    }

}
