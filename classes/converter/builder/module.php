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
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

abstract class module extends base {
    /**
     * Convert the model and returns a set of object in a pool and set of refs
     *
     * @param base_edx_model|array $originalmodels either a single model or a set of models
     * @param base $helper
     * @param mixed ...$additionalargs
     * @return mixed : matching enti
     * @throws moodle_exception
     */
    public static function convert($originalmodels, $helper = null, ...$additionalargs) {
        if (!($errormessage = static::check_model_valid($originalmodels))) {
            throw new moodle_exception($errormessage);
        }
        $module = new static(
            $helper,
            $originalmodels
        );

        $module->data = $module->build(
            array(
                'title' => $additionalargs[0],
                'sectionid' => $additionalargs[1],
                'sectionnumber' => $additionalargs[2],
            ));
        return $module;
    }

    /**
     * Check if model valid, default check
     *
     * @param array|base_edx_model $originalmodels
     * @return bool|string
     */
    protected static function check_model_valid($originalmodels) {
        if (is_array($originalmodels)) {
            foreach ($originalmodels as $model) {
                if (builder_helper::is_static_content($model)) {
                    return 'We cannot convert from anything else than a static model';
                }
            }
        }
        return true;
    }

    /**
     * Convert a series of static modules into a book
     *
     * Conversion will fill up the entity_pool and ref_pool
     *
     * @param null $args
     * @return mixed the built model (already inserted into the pool)
     * @throws moodle_exception
     */
    public function build($args = null) {
        static $lastglobalmoduleid = 1;
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $sectionnumber = $args['sectionnumber'];
        $sectionid = $args['sectionid'];
        $title = $args['title'];

        $now = time();
        $id = $this->helper->entitypool->new_entity('activity');
        $module = new stdClass();
        $module->modulename = static::MODULE_TYPE;
        $module->id = $id;
        $module->sectionid = $sectionid;
        $module->sectionnumber = $sectionnumber;
        $module->idnumber = '';
        $module->title = $title;
        $module->added = $now;
        $module->version = get_component_version('mod_' . static::MODULE_TYPE);
        $module->moduleid = $lastglobalmoduleid++;

        $this->helper->entitypool->set_data('activity', $id, $module);
        return $module;
    }

    /**
     * Create specialised module type
     *
     * @param $module
     * @return stdClass
     */
    protected function create_specialised_module_type($module) {
        $specmodule = new stdClass();
        $specmodule->moduleid = $module->id;
        $specmodule->modulename = static::MODULE_TYPE;
        $specmodule->contextid = builder_helper::get_contextid(CONTEXT_MODULE, $module->id);
        return $specmodule;
    }

    /**
     * Associate with related entity (page, book, ...)
     *
     * @param $module
     * @param $entityid
     * @throws moodle_exception
     */
    protected function module_associate($module, $entityid) {
        $module->associatedentityid = $entityid;
        $this->helper->entitypool->set_data('activity', $module->id, $module);
    }
}

