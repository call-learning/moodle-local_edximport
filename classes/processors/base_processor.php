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

namespace local_edximport\processors;

use local_edximport\parser\simple_parser;

defined('MOODLE_INTERNAL') || die();
global $CFG;
global $CFG;

require_once($CFG->dirroot . '/backup/util/xml/parser/progressive_parser.class.php');
require_once($CFG->dirroot . '/backup/util/xml/parser/processors/progressive_parser_processor.class.php');

abstract class base_processor extends \progressive_parser_processor {
    /**
     * Entity currently processed
     *
     * @var null
     */
    protected $entity = null;

    /**
     * @var null
     */
    protected $archivepath = null;

    /**
     * @var null
     */
    protected $urlname = null;

    /**
     * base_processor constructor.
     *
     * @param $archivepath
     */
    public function __construct($archivepath, $urlname = null) {
        parent::__construct();
        $this->archivepath = $archivepath;
        $this->urlname = $urlname;
    }

    /**
     * Process a chunk of data
     *
     * @param $data
     */
    public function process_chunk($data) {
        foreach ($data['tags'] as $tag) {
            $attrs =  new attributes((!empty($tag['attrs']))?$tag['attrs']:[]);
            $cdata = !empty($tag['cdata']) ? $tag['cdata'] : null;
            $this->process_element($tag['name'], $attrs, $cdata);
        }
        $this->post_process();
    }

    /**
     * Process elements and subelements
     *
     * @param $elementname
     * @param $attrs
     * @return mixed
     */
    public abstract function process_element($elementname, $attrs, $cdata = null);

    /**
     * Archive path
     *
     * @return mixed
     */

    public function post_process() {

    }

    /**
     * Get processed entity
     *
     * @return |null
     */
    public function get_entity() {
        return $this->entity;
    }

    /**
     * Simple utility to parse sub entity
     *
     * @param $entityname
     * @param $url
     * @return mixed
     */
    protected function simple_process_entity($entityname, $url) {
        $pp = new simple_parser();
        $processorname = 'local_edximport\\processors\\' . $entityname . '_processor';
        $pr = new $processorname($this->archivepath, $url);
        $pp->set_processor($pr);
        $pp->set_file($this->archivepath . "/${entityname}/" . $url . '.xml');
        $pp->process();
        return $pr->get_entity();
    }
}