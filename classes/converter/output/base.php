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
use file_xml_output;
use moodle_exception;
use xml_writer;
use xml_writer_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/xml/xml_writer.class.php');
require_once($CFG->dirroot . '/backup/util/xml/output/xml_output.class.php');
require_once($CFG->dirroot . '/backup/util/xml/output/file_xml_output.class.php');

class base {

    /** @var null|string backup id */
    protected $backupid;

    /** @var null|string the name of file we are writing to */
    protected $xmlfilename;

    /** @var null|xml_writer */
    protected $xmlwriter;

    /** @var null|string */
    protected $outputdir;

    public function __construct($outputdir) {
        $this->outputdir = $outputdir;
        $this->backupid = sha1(base64_encode(time()));
    }

    public function get_output_dir() {
        return $this->outputdir;
    }

    /**
     * Opens the XML writer - after calling, one is free to use $xmlwriter
     *
     * @param string $filename XML file name to write into
     * @return void
     * @throws xml_writer_exception
     * @throws moodle_exception
     */
    protected function open_xml_writer($filename) {

        if (!is_null($this->xmlfilename) and $filename !== $this->xmlfilename) {
            throw new moodle_exception('xml_writer_already_opened_for_other_file', $this->xmlfilename);
        }

        if (!$this->xmlwriter instanceof xml_writer) {
            $this->xmlfilename = $filename;
            $fullpath = $this->get_output_dir() . '/' . $this->xmlfilename;
            $directory = pathinfo($fullpath, PATHINFO_DIRNAME);

            if (!check_dir_exists($directory)) {
                throw new moodle_exception('unable_create_target_directory', $directory);
            }
            $this->xmlwriter = new xml_writer(new file_xml_output($fullpath));
            $this->xmlwriter->start();
        }
    }

    /**
     * Close the XML writer
     *
     * At the moment, the caller must close all tags before calling
     *
     * @return void
     * @throws xml_writer_exception
     */
    protected function close_xml_writer() {
        if ($this->xmlwriter instanceof xml_writer) {
            $this->xmlwriter->stop();
        }
        unset($this->xmlwriter);
        $this->xmlwriter = null;
        $this->xmlfilename = null;
    }

    /**
     * Checks if the XML writer has been opened by {@link self::open_xml_writer()}
     *
     * @return bool
     */
    protected function has_xml_writer() {

        if ($this->xmlwriter instanceof xml_writer) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Writes the given XML tree data into the currently opened file
     *
     * @param string $element the name of the root element of the tree
     * @param array $data the associative array of data to write
     * @param array $attribs list of additional fields written as attributes instead of nested elements
     * @param string $parent used internally during the recursion, do not set yourself
     * @throws moodle_exception
     * @throws xml_writer_exception
     */
    protected function write_xml($element, array $data, array $attribs = array(), $parent = '/') {

        if (!$this->has_xml_writer()) {
            throw new moodle_exception('write_xml_without_writer');
        }

        $mypath = $parent . $element;
        $myattribs = array();

        // Detect properties that should be rendered as element's attributes instead of children.
        foreach ($data as $name => $value) {
            if (!is_array($value)) {
                if (in_array($mypath . '/' . $name, $attribs)) {
                    $myattribs[$name] = $value;
                    unset($data[$name]);
                }
            }
        }

        // Reorder the $data so that all sub-branches are at the end (needed by our parser).
        $leaves = array();
        $branches = array();
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $branches[$name] = $value;
            } else {
                $leaves[$name] = $value;
            }
        }
        $data = array_merge($leaves, $branches);

        $this->xmlwriter->begin_tag($element, $myattribs);

        foreach ($data as $name => $value) {
            if (is_array($value)) {
                // Recursively call self.
                $this->write_xml($name, $value, $attribs, $mypath . '/');
            } else {
                $this->xmlwriter->full_tag($name, $value);
            }
        }

        $this->xmlwriter->end_tag($element);
    }

    /**
     * Makes sure that a new XML file exists, or creates it itself
     *
     * This is here so we can check that all XML files that the restore process relies on have
     * been created by an executed handler. If the file is not found, this method can create it
     * using the given $rootelement as an empty root container in the file.
     *
     * @param string $filename relative file name like 'course/course.xml'
     * @param string|bool $rootelement root element to use, false to not create the file
     * @param array $content content of the root element
     * @return bool true is the file existed, false if it did not
     * @throws moodle_exception
     * @throws xml_writer_exception
     */
    protected function make_sure_xml_exists($filename, $rootelement = false, $content = array()) {

        $existed = file_exists($this->get_output_dir() . '/' . $filename);

        if ($existed) {
            return true;
        }

        if ($rootelement !== false) {
            $this->open_xml_writer($filename);
            $this->write_xml($rootelement, $content);
            $this->close_xml_writer();
        }

        return false;
    }
}
