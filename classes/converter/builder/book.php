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

class book extends module {
    const MODULE_TYPE = 'book';

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
        $models = $this->models;
        $displayname = $args['title'];
        $now = time();

        $module = parent::build($args);

        $bookid = $this->helper->entitypool->new_entity(static::MODULE_TYPE);
        $book = $this->create_specialised_module_type($module);
        $book->id = $bookid;
        $book->name = $displayname;
        $book->timecreated = $now;
        $book->timemodified = $now;
        $book->chapters = [];
        foreach ($models as $index => $chaptermodel) {
            $id = $this->helper->entitypool->new_entity('chapter');
            $chapter = new \stdClass();
            $chapter->id = $id;
            $chapter->type = 'chapter';
            $chapter->pagenum = $index;
            $chapter->subchapter = 0;
            $chapter->title = $chaptermodel->displayname;
            $chapter->content = utils::get_content_for_module($chaptermodel);
            $chapter->timemodified = $now;
            $this->helper->entitypool->set_data('chapter', $id, $chapter);
            $book->chapters[] = $chapter;
            $this->helper->collect_files_refs(
                $book->id,
                'chapter',
                $chapter->id,
                $this->helper->get_contextid(CONTEXT_MODULE, $book->moduleid),
                $chaptermodel->get_content(),
                'mod_' . static::MODULE_TYPE
            );

        }
        $this->helper->entitypool->set_data(static::MODULE_TYPE, $bookid, $book);
        $this->module_associate($module, $bookid);
        return $book;
    }
}
