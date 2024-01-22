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
 * The mod_snippet renderer.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_snippet\output;

use context_module;
use lang_string;
use renderable;
use renderer_base;
use stdClass;
use templatable;

use mod_snippet\local\categories;
use mod_snippet\local\manager;
use mod_snippet\local\snips;

class view_page implements renderable, templatable {

    /** @var stdClass $cm The course module object. */
    private $cm = null;

    /** @var stdClass $cm The catefory id. */
    private $categoryid = null;

    /** @var stdClass $cm The snip id. */
    private $snipid = null;

    /** @var context_module $context The module context. */
    private $context = null;

    public function __construct($cm, $paramfromurl) {

        $this->cm = $cm;

        $this->context = context_module::instance($this->cm->id);

        if (isset($paramfromurl['categoryid'])) {
            $this->categoryid = $paramfromurl['categoryid'];
        }

        if (isset($paramfromurl['snipid'])) {
            $this->snipid = $paramfromurl['snipid'];
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass $data The data to be used in the template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $DB, $USER;

        // Data for the template.
        $data = new stdClass();

        // The snippet course module id.
        $data->cmid = $this->cm->id;

        // Can the current user add a snip?
        $data->cap_addsnip = has_capability('mod/snippet:addsnip', $this->context);

        // Can the current user add a category?
        $data->cap_addcategory = has_capability('mod/snippet:addcategory', $this->context);

        // This layout display a single snip with the snip navigation on the left.
        $data->layout_snipwithsnipnav = false;

        // This layout displays a single snip without navigation.
        // This layout is usually used when there is only one snip in the current cat.
        $data->layout_fullsnip = false;

        // This layout displays a list of snips in an accordion component.
        $data->layout_accordionlist = false;

        // This layout is used when there is no snip to display.
        // It displays a link to create a new snip.
        $data->layout_hasnosnip = false;

        // Possible URL:
        // - /mod/snippet/view.php?id=x
        // - /mod/snippet/view.php?id=x&categoryid=y
        // - /mod/snippet/view.php?id=x&categoryid=y&snipid=z

        if ($this->categoryid === 0 && $this->snipid === 0) {

            // Displaya the 10 last snips.
            $data->snips = array_values(
                snips::get_latest_snips()
            );

            if (count($data->snips) > 0) {
                // Tell the template to display the snips as a list.
                $data->layout_accordionlist = true;
            } else {
                // Display the no snip message.
                $data->layout_hasnosnip = true;
            }

        } else if ($this->categoryid !== 0 && $this->snipid === 0) {

            // Display all the snips for the given category.
            $data->snips = array_values(
                snips::get_snips_for_category($USER->id, $this->categoryid)
            );

            // Make the current category id available to the template.
            $data->categoryid = $this->categoryid;

            if (count($data->snips) > 0) {
                // Tell the template to display the snips as a list.
                $data->layout_accordionlist = true;
            } else {
                // Display the no snip message.
                $data->layout_hasnosnip = true;
            }
        } else {

            // Get all the snips for the given category if there is more than one snip in the category.
            $snipcount = snips::get_snip_count_for_category($USER->id, $this->categoryid);
            if ($snipcount > 1) {
                $data->layout_snipwithsnipnav = true;
                $snips = snips::get_snips_for_category($USER->id, $this->categoryid);
                $snips = snips::set_active($this->snipid, $snips);
                $data->snipsincat = array_values($snips);
            } else {
                $data->layout_fullsnip = true;
            }

            // Get the snip content.
            $snip = $DB->get_record('snippet_snips', ['id' => $this->snipid]);
            if ($snip !== false) {
                $data->snip = array(
                    'categoryid' => $snip->categoryid,
                    'id' => $snip->id,
                    'name' => $snip->name,
                    'intro' => $snip->intro,
                    'display_language' => new lang_string($snip->language, manager::PLUGIN_NAME),
                    'language' => $snip->language,
                    'snippet' => $snip->snippet
                );
            }
        }

        // Get all the snippet categories for the given user.
        $categories = categories::get_category_list_for_nav($USER->id);
        $categories = categories::set_active($this->categoryid, $categories);
        $data->categories = array_values($categories);

        return $data;
    }
}