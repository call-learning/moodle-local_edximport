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
 * Base converter from edX entity or entities to a moodle model
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\converter\builder;

use local_edximport\converter\entity_pool;
use local_edximport\converter\ref_manager;
use local_edximport\edx\model\base as base_edx_model;
use local_edximport\local\parser_utils;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class builder_helper {
    /**
     * FAKE CONTEXT
     */
    const FAKE_CONTEXT_SYSTEM = 1;
    /**
     * FAKE CONTEXT
     */
    const FAKE_CONTEXT_COURSE = 100;
    /**
     * FAKE CONTEXT
     */
    const FAKE_CONTEXT_MODULE = 200;
    public $edxfilesdir = null;
    public $edxassetlist = [];
    public $entitypool = null;
    public $entityrefs = null;

    /**
     * base constructor.
     *
     * @param $edxfiledir
     * @param $edxassetlist
     */
    public function __construct($edxfiledir, $edxassetlist) {
        $this->edxfilesdir = $edxfiledir;
        $this->edxassetlist = $edxassetlist;
        $this->entitypool = entity_pool::get_instance();
        $this->entityrefs = ref_manager::get_instance();
    }

    /**
     * Get a fake context id
     *
     * @param $contextype
     * @param int $moduleid
     * @return int|mixed
     */
    public static function get_contextid($contextype, $moduleid = 0) {
        if ($contextype == CONTEXT_SYSTEM) {
            return self::FAKE_CONTEXT_SYSTEM;
        }
        if ($contextype == CONTEXT_COURSE) {
            return self::FAKE_CONTEXT_COURSE;
        }
        if ($contextype == CONTEXT_MODULE) {
            return self::FAKE_CONTEXT_MODULE + $moduleid;
        }
    }

    /**
     * Check if content can go in a book or page
     *
     * @param base_edx_model $model
     * @return bool
     */
    public static function is_static_content(base_edx_model $model) {
        return array_key_exists('local_edximport\edx\model\static_content', class_implements($model));
    }

    /**
     * Check if content is a problem type
     *
     * @param base_edx_model $model
     * @return bool
     */
    public static function is_problem(base_edx_model $model) {
        return get_class($model) == 'local_edximport\edx\model\problem';
    }

    /**
     * Check if content is a discussion
     *
     * @param base_edx_model $model
     * @return bool
     */
    public static function is_discussion(base_edx_model $model) {
        return get_class($model) == 'local_edximport\edx\model\discussion';
    }

    /**
     * Create a stamp for model
     *
     * @return string
     * @throws moodle_exception
     */
    public static function get_stamp() {
        global $CFG;
        $time = time();
        $url = new moodle_url($CFG->wwwroot);
        $randomstring = substr(uniqid(), 0, 6);
        return "{$url->get_host()}+{$time}+{$randomstring}";
    }

    /**
     * Tool to build file reference
     *
     * @param $filename
     * @param $filepath
     * @param $originpath
     * @param $filearea
     * @param $itemid
     * @param $component
     * @param $contextid
     * @return object
     */
    public function build_file_reference(
        $filename,
        $filepath,
        $originpath,
        $filearea,
        $itemid,
        $component,
        $contextid
    ) {
        return (object) [
            'filename' => $filename,
            'filepath' => $filepath,
            'originalpath' => trim($originpath, "\"'"),
            'filearea' => $filearea,
            'itemid' => $itemid,
            'component' => $component,
            'contextid' => $contextid
        ];
    }

    /**
     * Collect file references in the text of this model
     *
     * @param int $entityid this is the global pool entity id
     * @param string $filearea
     * @param int $itemid item id in (for example chapterid)
     * @param int $contextid context for this file
     * @param base $model edx model
     * @throws moodle_exception
     */
    public function collect_files_refs(
        $entityid,
        $filearea,
        $itemid,
        $contextid,
        $rawtext,
        $moodlecomponent) {

        $refs = parser_utils::html_get_static_ref($rawtext);
        if ($refs) {
            foreach ($refs as $r) {
                $originalpath = array_shift($r);
                $filefullpath = '/' . array_shift($r);
                $filename = basename($filefullpath);
                $filepath = dirname($filefullpath);
                $filedata = file::convert(null,
                    $this,
                    $filename,
                    $filepath,
                    $originalpath,
                    $filearea,
                    $itemid,
                    $moodlecomponent,
                    $contextid);

                $this->entityrefs->add_ref($moodlecomponent, $entityid, 'file',
                    $filedata->get_entity_data()->id);
            }
        }
        // Hack !! here when we have an iframe including some static html content.
        // Check for src reference in the referenced file.
        $iframessrc = parser_utils::html_get_iframe_src_ref($rawtext);
        if ($iframessrc) {
            foreach ($iframessrc as $ir) {
                $filefullpath = end($ir); // First match.
                if (!empty($this->edxassetlist->$filefullpath)) {
                    // Check if file exist in /static folder and get the related sources included.
                    // TODO check it is an html file.
                    $htmlfilepath = $this->edxfilesdir . '/static/' . trim($filefullpath, '/');
                    if (file_exists($htmlfilepath)) {
                        $srcset = parser_utils::html_get_src_ref(file_get_contents($htmlfilepath));
                        if ($srcset) {
                            foreach ($srcset as $src) {
                                $subfilesrc = end($src); // First match.
                                if (!empty($this->edxassetlist->$subfilesrc)) {
                                    $subfilename = basename($subfilesrc);
                                    $subfilepath = (dirname($subfilesrc) == '.') ? '/' : dirname($subfilesrc);
                                    $filedata = file::convert(null,
                                        $this,
                                        $subfilename,
                                        $subfilepath,
                                        $subfilesrc,
                                        $filearea,
                                        $itemid,
                                        $moodlecomponent,
                                        $contextid);
                                    $this->entityrefs->add_ref($moodlecomponent, $entityid, 'file',
                                        $filedata->get_entity_data()->id);
                                }
                            }
                        }
                    }
                }
            }
        }

    }
}
