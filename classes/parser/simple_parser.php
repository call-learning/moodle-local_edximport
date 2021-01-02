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

use local_edximport\edx\model\base;

defined('MOODLE_INTERNAL') || die();
global $CFG;

abstract class simple_parser {
    /**
     * @var string $archivepath
     */
    protected $archivepath = null;

    /**
     * Entity currently processed
     *
     * @var base $entity
     */
    protected $entity = null;

    /**
     * Entity name
     *
     * @var string $entityid
     */
    protected $entityid = null;

    /**
     * Related entities found during main parsing
     *
     * @var array $relatedentities
     */
    protected $relatedentities = [];

    /**
     * Build from archive path
     *
     * @param $archivepath
     */
    public function __construct($archivepath, $entityid = null) {
        $this->archivepath = $archivepath;
        $this->entityid = $entityid;
    }

    /**
     * Get original file name
     *
     * @return mixed
     */
    protected abstract function get_file_path();

    /**
     * Process a given element.
     *
     * This method can also have side effects on the xmlreader (move to next node for example)
     *
     * @param \XMLReader $xmlreader
     */
    protected abstract function process_element(&$xmlreader);

    /**
     * Parse the original model
     */
    public function parse(\core\progress\base $progress = null) {
        $xmlreader = new \XMLReader();
        $xmlreader->open($this->archivepath . '/' . $this->get_file_path(),
            LIBXML_NOBLANKS);
        $continue = true;
        while ($continue && $xmlreader->read()) {
            if ($progress) {
                $progress->progress();
            }
            $continue = $this->process_element($xmlreader); // We can also call ::next in this function.
        }
        $xmlreader->close();
        // This post loop avoids having several XML Reader opened at the same time.
        $this->add_related_entities();
    }

    /**
     * Parse other related entities in a loop
     */
    protected function add_related_entities() {
        foreach ($this->relatedentities as $index => $relatedentity) {
            $addfunction = 'add_' . $relatedentity->type;
            $this->entity->index = $index; // Make sure we set the index first.
            $this->entity->$addfunction(self::simple_process_entity(
                $this->archivepath,
                $relatedentity->type,
                $relatedentity->url
            ));
        }
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
     * Simple method to parse sub entities
     *
     * @param $entityname
     * @param $url
     * @return mixed
     */
    public static function simple_process_entity($archivepath, $entitytype, $entityid = null, \core\progress\base $progress=null) {
        $parserclass = '\\local_edximport\\parser\\' . $entitytype;
        $pp = new $parserclass($archivepath, $entityid);
        $pp->parse($progress);
        return $pp->get_entity();
    }

}