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
 * The mod_snippet general use object.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_snippet\local;

defined('MOODLE_INTERNAL') || die();

class manager {

    /**
     * Full plugin name.
     */
    const PLUGIN_NAME = 'mod_snippet';

    /**
     * The module name.
     */
    const MODULE_NAME = 'snippet';

    /**
     * Ge the ids for the page URL.
     *
     * @return array $ids The ids for URL.
     */
    public static function get_param_for_url():array {
        $formids = self::get_param_from_url();

        // Remove the key that are equal to 0.
        $urlids = array_filter($formids, function($value) {
            return $value !== 0;
        });

        return $urlids;
    }

    /**
     * Get the ids for the snip form.
     *
     * @return array $snipformids The ids from URL for the snip form.
     */
    public static function get_param_from_url():array {
        $snipformids = array(
            'id' => optional_param('id', 0, PARAM_INT),
            'snipid' => optional_param('snipid', 0, PARAM_INT),
            'categoryid' => optional_param('categoryid', 0, PARAM_INT),
        );

        return $snipformids;
    }
}