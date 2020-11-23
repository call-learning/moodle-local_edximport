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

defined('MOODLE_INTERNAL') || die();

abstract class base {
    protected $helper = null;

    protected $data = null;

    /**
     * @var base_edx_model|array either a single model or an array of models
     */
    protected $models = null;

    /**
     * base constructor.
     *
     * @param builder_helper $helper helper that store all refs and entities already built
     * @param base_edx_model|array $originalmodels original models
     * @param mixed ...$additionalargs
     */
    protected function __construct($helper, $originalmodels, ...$additionalargs) {
        $this->helper = $helper;
        $this->models = $originalmodels;
    }

    /**
     * Convert the model and returns a set of object in a pool and set of refs
     *
     * @param base_edx_model|array $originalmodels either a single model or a set of models
     * @param builder_helper $helper
     * @param mixed ...$additionalargs
     * @return mixed : matching enti
     * @throws \moodle_exception
     */
    public static function convert($originalmodels, $helper = null, ...$additionalargs) {
        throw new \moodle_exception('Not implemented');
    }

    /**
     * Build internal model
     *
     * Conversion will fill up the entity_pool and ref_pool
     *
     * @param null $args
     * @return mixed : matching entity
     */
    public abstract function build($args = null);

    public function get_entity_pool() {
        return $this->helper->entitypool;
    }

    public function get_ref_manager() {
        return $this->helper->entityrefs;
    }

    public function get_entity_data() {
        return $this->data;
    }
}
