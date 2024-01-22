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
 * Snip management class.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_snippet\local;

defined('MOODLE_INTERNAL') || die();

use core_analytics\user;
use stdClass;
use lang_string;
use moodle_url;

/**
 * Class to manage snips.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class snips {

    /**
     * Create a new snip.
     *
     * @param stdClass $data The data to create the snip.
     *
     * @return stdClass The new snip id.
     */
    public static function create($snipformdata):int {
        global $DB, $USER;

        $cm = get_coursemodule_from_id('snippet', $snipformdata->id);

        $time = time();

        // Create a new category if firstcategory hidden field is set to 'yes'.
        if ($snipformdata->firstcategory == 'yes') {
            $category = new stdClass();
            $category->snippetid = $cm->instance;
            $category->name = $snipformdata->categoryname;

            $snipformdata->categoryid = \mod_snippet\local\categories::create_category($category);
        } else {
            $snipformdata->categoryid = (int) $snipformdata->categoryid;
        }

        // Create a new snip object.
        $introtext = $snipformdata->intro['text'];
        $introformat = $snipformdata->intro['format'];

        $snipformdata->snippetid = $snipformdata->id;
        $snipformdata->userid = $USER->id;
        $snipformdata->intro = $introtext;
        $snipformdata->introformat = $introformat;
        $snipformdata->timecreated = $time;
        $snipformdata->timemodified = $time;

        // Clean up.
        unset($snipformdata->firstcategory);
        unset($snipformdata->id);
        if ($snipformdata->snipid === 0) {
            unset($snipformdata->snipid);
        }

        // Insert the new snip into the database.
        $snipformdata->id = $DB->insert_record(
            'snippet_snips',
            $snipformdata
        );

        // Return the new snip.
        return $snipformdata->id;
    }

    /**
     * Update an existing snip.
     *
     * @param stdClass $data The data to update the snip with.
     *
     * @return bool True if snip has been successfully updated.
     */
    public static function update($data): bool {
        global $DB, $USER;

        $introtext = $data->intro['text'];
        $introformat = $data->intro['format'];

        // Create a new snip object with the submitted data.
        $snip = new \stdClass();
        $snip->id = $data->snipid;
        $snip->categoryid = $data->categoryid;
        $snip->name = $data->name;
        $snip->intro = $introtext;
        $snip->introformat = $introformat;
        $snip->private = $data->private;
        $snip->language = $data->language;
        $snip->snippet = $data->snippet;
        $snip->timemodified = time();

        $update = $DB->update_record('snippet_snips', $snip);

        return $update;
    }

    /**
     * Get the snip count for a given category.
     *
     * @param int $userid Id of the user who created the category.
     * @param int $categoryid The category id.
     *
     * @return int The snippet count.
     */
    public static function get_snip_count_for_category(int $userid, int $categoryid): int {
        global $DB;

        return $DB->count_records(
            'snippet_snips',
            ['userid' => $userid, 'categoryid' => $categoryid]
        );
    }

    /**
     * Get the last 10 snips for the current user.
     *
     * @param int $userid The user id.
     * @param int $maxrecords The maximum number of snips to return.
     *
     * @return array The snip list.
     */
    public static function get_latest_snips(bool|int $userid = false, int $maxrecords = 10): array {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $snips = $DB->get_records(
            'snippet_snips', ['userid' => $userid], 'timecreated DESC', '*', 0, $maxrecords
        );

        return $snips;
    }

    /**
     * Get all the snips for a given user in a given category.
     *
     * @param int $userid The user id.
     * @param int $categoryid The category id.
     *
     * @return array The snip list.
     */
    public static function get_snips_for_category(int $userid, int $categoryid): array {
        global $DB;

        $snips = $DB->get_records(
            'snippet_snips', ['userid' => $userid, 'categoryid' => $categoryid], 'timecreated DESC'
        );

        foreach ($snips as $snip) {
            // Add the display language key.
            $snip->display_language = new lang_string($snip->language, manager::PLUGIN_NAME);

            // Create the full snip URL.
            $snip->fullsnipurl = new moodle_url(
                '/mod/snippet/view.php',
                ['id' => $snip->snippetid, 'categoryid' => $snip->categoryid, 'snipid' => $snip->id]
            );
        }

        return $snips;
    }

    /**
     * Set the current snip as active in a given list of snips.
     *
     * @param int $snipid The current snip id.
     * @param array $snips The list of snips.
     *
     * @return array The list of snips with the active snip set.
     */
    public static function set_active(int $snipid, array $snips): array {
        foreach ($snips as $key => $snip) {
            $snips[$key]->active = ($snip->id == $snipid);
        }

        return $snips;
    }

    /**
     * Get all the individual languages from a list of snips.
     *
     * @param array $snips The list of snips.
     *
     * @return array $languages The list of languages.
     */
    public static function get_all_languages_from_snips(array $snips): array {
        $languages = [];
        foreach ($snips as $snip) {
            $languages[] = strtolower($snip->language);
        }

        return array_unique($languages);
    }
}
