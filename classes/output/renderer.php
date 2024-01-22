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

use plugin_renderer_base;

use mod_snippet\local\snips;

class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    public function render_index_page($page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_snippet/index_page', $data);
    }

    /**
     * Defer to template.
     *
     * @param view_page $page
     *
     * @return string html for the page
     */
    public function render_view_page($page): string {
        global $PAGE;

        $data = $page->export_for_template($this);

        // Add the highlight.js script only if we are rendering a snip.
        if (!isset($data->hasnosnip)) {

            if (isset($data->snips)) {
                $languages = snips::get_all_languages_from_snips($data->snips);
            } else {
                $languages = array(strtolower($data->snip['language']));
            }

            $PAGE->requires->js_call_amd(
                'mod_snippet/inithljs', 'init',
                array('languages' => $languages)
            );
        }

        return parent::render_from_template('mod_snippet/view_page', $data);
    }
}
