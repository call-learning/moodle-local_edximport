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

namespace local_edximport;
defined('MOODLE_INTERNAL') || die();

use core\progress\base;
use local_edximport\converter\moodle_model_exporter;
use local_edximport\converter\builder\course as course_builder;
use local_edximport\edx\model\course as course_model;
use local_edximport\local\utils;

/**
 * Class edx_to_moodle_exporter
 *
 * @package    local_edximport
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edx_to_moodle_exporter {
    /**
     * @var string $archivepath
     */
    protected $archivepath = null;
    /**
     * @var string|string[]|null $destfolder
     */
    protected $destfolder = null;
    /**
     * @var bool $destfoldertoremove
     */
    protected $destfoldertoremove = false;
    /**
     * @var course_model $course
     */
    private $course;

    /**
     * edx_to_moodle_exporter constructor.
     *
     * @param course_model $course
     * @param $edxarchpath
     * @param string $destinationpath
     * @param bool $autoremove
     */
    public function __construct(course_model $course, $edxarchpath, $destinationpath = '', $autoremove = true) {
        $this->course = $course;
        $this->archivepath = $edxarchpath;
        $this->destfolder = $destinationpath;
        if (empty($destinationpath)) {
            $this->destfolder = \local_edximport\local\utils::make_backup_folder('edxbackupfolder');
            $this->destfoldertoremove = $autoremove;
        }
    }

    /**
     * Export the course
     *
     * @param base|null $progressbar
     * @return mixed|string|string[]|null
     * @throws \moodle_exception
     */
    public function export(\core\progress\base $progressbar = null) {
        $builder = course_builder::convert($this->course, null, $this->archivepath);
        $exporter = new moodle_model_exporter($this->archivepath, $this->destfolder, $builder->get_entity_pool(),
            $builder->get_ref_manager(), (object) array('hasmathjax' => true));
        $exporter->create_full_backup($progressbar);
        return $this->destfolder;
    }

    /**
     * Cleanup temp dir
     */
    public function __destruct() {
        if ($this->destfoldertoremove) {
            utils::cleanup_dir($this->destfolder);
        }
    }
}