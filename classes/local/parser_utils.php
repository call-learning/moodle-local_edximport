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

namespace local_edximport\local;

class parser_utils {
    public static function convert_edx_date_to_ts($date) {
        if (!$date) {
            return 0;
        }
        return strtotime(trim($date, '"'));
    }

    /**
     * Like normalize but with nodes with empty space in it.
     *
     * @param $node
     * @return \DOMNode|null
     */
    public static function remove_empty_nodes($node) {
        $doc = new \DOMDocument();
        $doc->appendChild($node);
        $doc->normalize();;
        $xpath = new \DOMXPath($doc);
        $emptynodes = $xpath->query('//*[normalize-space(.) = \'\']');
        foreach ($emptynodes as $node) {
            $node->parentNode->removeChild($node);
        }
        return $doc->firstChild;
    }

    /**
     * Get all reference to static URL into an html soup
     *
     * @param $htmlcontent
     * @return array|boolean
     */
    public static function html_get_static_ref($htmlcontent) {
        $reval = preg_match_all('/"\/static\/([^"]+)"|\'\/static\/([^\']+)\'/', $htmlcontent, $matches,
            PREG_SET_ORDER);
        if ($reval) {
            return $matches;
        }
        return false;
    }

    /**
     * Get all reference to an iframe in the static folder
     *
     * @param $htmlcontent
     * @return array|boolean
     */
    public static function html_get_iframe_src_ref($htmlcontent) {
        $iframessrc = preg_match_all('/iframe\s+src=["\']\/static\/([^"]+)["\']/', $htmlcontent, $matches,
            PREG_SET_ORDER);
        if ($iframessrc) {
            return $matches;
        }
        return false;
    }

    /**
     * Get all reference to a local src file
     *
     * @param $htmlcontent
     * @return array|boolean
     */
    public static function html_get_src_ref($htmlcontent) {
        $srcset = preg_match_all('/src=["\']([^"\']+)["\']|href=["\']([^"\']+)["\']/', $htmlcontent, $matches,
            PREG_SET_ORDER);
        if ($srcset) {
            return $matches;
        }
        return false;
    }
    /**
     * Change all references in a text
     *
     * @param $staticrefs
     */
    public static function change_html_static_ref($htmlcontent) {
        $replacer = function($matches) {
            $filepath =  $matches[1];
            $parts   = explode('/', $filepath);
            $encoded = implode('/', array_map('rawurlencode', $parts));
            return '@@PLUGINFILE@@/'.$encoded;
        };
        return preg_replace_callback('/"\/static\/([^"]+)"|\'\/static\/([^\']+)\'/', $replacer, $htmlcontent);
    }
}