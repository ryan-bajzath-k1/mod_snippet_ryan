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
 * Categories management class.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_snippet\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;

use mod_snippet\local\snips;

/**
 * The mod_snippet course module viewed event class.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categories {

    /**
     * Create a new category.
     *
     * @param stdClass $categorydata The data to create the category.
     *
     * @return int The new category id.
     */
    public static function create_category($categorydata):int {
        global $DB, $USER;

        $time = time();

        // Set userid if not already set.
        if (!isset($categorydata->userid)) {
            $categorydata->userid = $USER->id;
        }

        // Return false if snippetid does not exists in $categorydata.
        if (!isset($categorydata->snippetid)) {
            return false;
        }

        // Set the default category name if not already set.
        if (!isset($categorydata->name) || empty($categorydata->name)) {
            $categorydata->name = get_string('defaultcategoryname', 'mod_snippet');
        }

        $category = new stdClass();
        $category->snippetid = $categorydata->snippetid;
        $category->userid = $USER->id;
        $category->name = $categorydata->name;
        $category->timecreated = $time;
        $category->timemodified = $time;

        $categoryid = $DB->insert_record('snippet_categories', $category);

        return $categoryid;
    }

    /**
     * Get the user categories for a given user.
     *
     * @param int $userid The user id.
     *
     * @return array The list of categories.
     */
    public static function get_category_list_for_user(int $userid):array {
        global $DB;

        $records = $DB->get_records('snippet_categories', ['userid' => $userid], 'name ASC');

        return $records;
    }

    /**
     * Get a list of categories, formated for <select> menu, for a given user.
     *
     * @param int $userid The user id.
     *
     * @return array The list of categories.
     */
    public static function get_category_list_for_input(int $userid):array {
        global $DB;

        $categories = $DB->get_records_menu(
            'snippet_categories', ['userid' => $userid], 'name ASC', 'id, name'
        );

        return $categories;
    }

    /**
     * Get a list of categories, formated for nav menu, for a given user.
     *
     * @param int $userid The user id.
     *
     * @return array The list of categories.
     */
    public static function get_category_list_for_nav(int $userid):array {

        $categories = self::get_category_list_for_user($userid);

        foreach ($categories as $key => $category) {
            // Get the snippet count for each category.
            $categories[$key]->count = snips::get_snip_count_for_category($userid, $category->id);

            // If the category contains no snippet, set hasnosnippet to true.
            $categories[$key]->hasnosnippet = ($categories[$key]->count == 0);
        }

        return $categories;
    }

    /**
     * Set the current category as active.
     *
     * @param int $categoryid The category id to set as active.
     * @param array $categories The list of categories.
     *
     * @return array The list of categories with the active category set.
     */
    public static function set_active(int $categoryid, array $categories): array {
        foreach ($categories as $key => $category) {
            $categories[$key]->active = ($category->id == $categoryid);
        }

        return $categories;
    }

    /**
     * Check if the user has at least one category.
     *
     * @param int $userid The user id.
     *
     * @return bool True if the user has at least one category.
     */
    public static function has_category($userid):bool {
        global $DB;

        $record = $DB->record_exists('snippet_categories', ['userid' => $userid]);

        return $record;
    }
}
