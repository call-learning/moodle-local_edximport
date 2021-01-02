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
 * edX Model for chapter
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edximport\edx\model;
defined('MOODLE_INTERNAL') || die();
use local_edximport\converter\course_visitor;
use local_edximport\converter\model_converter;

/**
 * Class base
 *
 * A dataobject type of class
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base {
    protected static $attributeslist = [];
    protected $modelid = -1;
    protected $keyedargs = [];

    public $index = 0; // Index in the parent's list.
    public $parent = null; // Parent model.

    /**
     * base constructor.
     *
     * @param $keyedargs
     * @throws \moodle_exception
     */
    public function __construct($keyedargs) {
        if (!$this->check_attributes($keyedargs)) {
            throw new \moodle_exception('cannotbuildedxmodel', 'local_edximport',
                (object) ['model' => self::class, 'args' => var_export($keyedargs)]
            );
        }
        $this->keyedargs = $keyedargs;
        $this->modelid = self::get_unique_id();
    }

    /**
     * Checked attributes
     *
     * @param $keyedargs
     * @return false
     */
    protected function check_attributes($keyedargs) {
        foreach ($keyedargs as $key => $args) {
            if (!in_array($key, static::$attributeslist)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get related attributes
     *
     * @param $keyname
     * @return mixed|null
     */
    public function __get($keyname) {
        if ($keyname == 'id') {
            return $this->modelid;
        }
        if (!empty($this->keyedargs[$keyname])) {
            return $this->keyedargs[$keyname];
        }
        return null;
    }

    /**
     * Set attributes
     *
     * @param $keyname
     * @param $value
     */
    public function __set($keyname, $value) {
        if (!empty($this->keyedargs[$keyname])) {
            $this->keyedargs[$keyname] = $value;
        }
    }

    /**
     * Retrieve all static url ("/static/..", or '/static/...')
     * From itself and subcomponents
     */
    public function collect_statics() {
        return [];
    }

    const START_ID = 50;

    /**
     * @return int|mixed
     */
    protected static function get_unique_id() {
        static $idlist = [self::START_ID];
        $max = max($idlist) + 1;
        $idlist[] = $max;
        return $max;
    }

    /**
     * Set parent model
     *
     * @param $model
     */
    protected function set_parent(&$model) {
        $model->parent = $this;
    }

}