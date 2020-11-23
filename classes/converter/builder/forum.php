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
use local_edximport\converter\utils;
use local_edximport\edx\model\base as base_edx_model;
use phpDocumentor\Reflection\Types\Array_;

defined('MOODLE_INTERNAL') || die();

class forum extends module {
    const MODULE_TYPE = 'forum';

    /**
     * Convert a series of static modules into a book
     *
     * Conversion will fill up the entity_pool and ref_pool
     *
     * @param null $args
     * @return mixed the built model (already inserted into the pool)
     * @throws \moodle_exception
     */
    public function build($args = null) {
        $displayname = $args['title'];
        $now = time();

        $module = parent::build($args);

        $forumid = $this->helper->entitypool->new_entity(static::MODULE_TYPE);
        $forum = $this->create_specialised_module_type($module);
        $forum->id = $forumid;
        $forum->type = 'blog';
        $forum->name = $displayname;
        $forum->timecreated = $now;
        $forum->timemodified = $now;
        $this->helper->entitypool->set_data(static::MODULE_TYPE, $forumid, $forum);
        $this->module_associate($module, $forumid);
        return $forum;
    }
}
