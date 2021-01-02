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
 * Test script for edx archive parser
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_edximport\converter\builder\course as course_builder;
use local_edximport\converter\entity_pool;
use local_edximport\converter\output\course;
use local_edximport\edx_importer;
use local_edximport\edx_to_moodle_exporter;
use local_edximport\local\utils;

/**
 * Class test_edx_parser
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_edx_parser extends advanced_testcase {
    /**
     * Test decompress routine
     */
    public function test_decompress() {
        global $CFG;
        $edxdestfile = html_writer::random_id('edxdestfile');
        $decompressedpath = make_backup_temp_directory($edxdestfile);
        $decompressed = utils::decompress_archive(
            $CFG->dirroot . '/local/edximport/tests/fixtures/course.edx.simple.tar.gz',
            $decompressedpath);
        $this->assertDirectoryExists($decompressed . '/course');
        $this->assertFileExists($decompressed . '/course/course.xml');
        // Cleanup.
        utils::cleanup_dir($decompressed);
    }

    /**
     * Test importing simple Model
     */
    public function test_import_simple_model() {
        global $CFG;
        $edxmporter = new edx_importer($CFG->dirroot . '/local/edximport/tests/fixtures/course.edx.simple.tar.gz');
        $coursemodel = $edxmporter->import();

        $this->assertNotNull($coursemodel);
        $this->assertCount(2, $coursemodel->chapters);
        $this->assertEquals(strtotime('2021-01-01T00:00:00+00:00'), $coursemodel->startdate);
        $this->assertEquals(strtotime('2021-07-28T00:00:00+00:00'), $coursemodel->enddate);
    }

    /**
     * Test importing simple Model
     */
    public function test_build_simple_model() {
        global $CFG;
        $edximporter =
            new edx_importer($CFG->dirroot . '/local/edximport/tests/fixtures/course.edx.simple.tar.gz');
        $coursemodel = $edximporter->import();
        course_builder::convert($coursemodel, null, $edximporter->get_archive_path());
        $courses = entity_pool::get_instance()->get_entities('course');
        $course = reset($courses);
        $this->assertNotNull($course);
        $this->assertEquals($coursemodel->fullname, $course->fullname);
        $sections = entity_pool::get_instance()->get_entities('section');
        $this->assertCount(2, $sections);
    }

    /**
     * Test importing simple Model
     */
    public function test_output() {
        global $CFG, $PAGE;
        $edximporter =
            new edx_importer($CFG->dirroot . '/local/edximport/tests/fixtures/course.edx.simple.tar.gz');
        $coursemodel = $edximporter->import();
        $course = course_builder::convert($coursemodel, null, $edximporter->get_archive_path());
        $renderer = $PAGE->get_renderer('local_edximport');
        $coursedata = $course->get_entity_data();
        $rendered = $renderer->render(new course($coursedata));
        $this->assertNotEmpty($rendered);
        $expected = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<course id="2" contextid="100">
    <shortname>Simple Course</shortname>
    <fullname>Simple Course</fullname>
    <idnumber></idnumber>
    <summary></summary>
    <summaryformat>1</summaryformat>
    <format>topics</format>
    <showgrades>1</showgrades>
    <newsitems>5</newsitems>
    <startdate>1609459200</startdate>
    <enddate>1627430400</enddate>
    <marker>0</marker>
    <maxbytes>0</maxbytes>
    <legacyfiles>0</legacyfiles>
    <showreports>0</showreports>
    <visible>0</visible>
    <groupmode>0</groupmode>
    <groupmodeforce>0</groupmodeforce>
    <defaultgroupingid>0</defaultgroupingid>
    <lang></lang>
    <theme></theme>
    <timecreated>{$coursedata->timecreated}</timecreated>
    <timemodified>{$coursedata->timecreated}</timemodified>
    <requested>0</requested>
    <enablecompletion>1</enablecompletion>
    <completionnotify>0</completionnotify>
    <hiddensections>0</hiddensections>
    <coursedisplay>0</coursedisplay>
    <category id="1">
        <name>Miscellaneous</name>
        <description></description>
    </category>
    <tags></tags>
    <customfields></customfields>
</course>
EOT;
        $this->assertEquals($expected, $rendered);
    }

    /**
     * Test importing simple Model
     */
    public function test_export_simple_model() {
        global $CFG;
        $edximporter =
            new edx_importer($CFG->dirroot . '/local/edximport/tests/fixtures/course.edx.simple.tar.gz');
        $coursemodel = $edximporter->import();
        $edxexporter = new edx_to_moodle_exporter($coursemodel, $edximporter->get_archive_path());
        $fullpathbackupfolder = $edxexporter->export();
        $this->assertFileExists($fullpathbackupfolder);
    }
}