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
use local_edximport\local\parser_utils;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

class page extends module {
    const MODULE_TYPE = 'page';

    /**
     * Convert a series of static modules into a book
     *
     * Conversion will fill up the entity_pool and ref_pool
     *
     * @param null $args
     * @return mixed|void
     * @throws moodle_exception
     */
    public function build($args = null) {
        $model = reset($this->models);
        $title = $args['title'];
        $now = time();

        $module = parent::build($args);
        $pageid = $this->helper->entitypool->new_entity(static::MODULE_TYPE);
        $page = $this->create_specialised_module_type($module);
        $page->id = $pageid;
        $page->name = $title;
        $page->intro = '';
        $page->introformat = FORMAT_HTML;
        $page->content = parser_utils::change_html_static_ref(utils::get_raw_content_from_model($model));
        $page->contentformat = FORMAT_HTML;
        $page->timemodified = $now;
        $this->helper->entitypool->set_data(static::MODULE_TYPE, $pageid, $page);
        $this->helper->collect_files_refs(
            $page->id,
            'content',
            0,
            $this->helper->get_contextid(CONTEXT_MODULE, $page->moduleid),
            utils::get_raw_content_from_model($model),
            'mod_' . static::MODULE_TYPE
        );
        $this->module_associate($module, $pageid);
        return $page;
    }
}



