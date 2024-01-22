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

use renderable;
use renderer_base;
use templatable;
use stdClass;

use mod_snippet\local\categories;

class snip_page implements renderable, templatable {

    /** @var stdClass $cm The course module object. */
    private $cm = null;

    /** @var str $form The rendered (HTML) snip form. */
    private $form = null;

    public function __construct(stdClass $cm, string $form) {
        $this->cm = $cm;
        $this->form = $form;
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

        // Set the rendered form in the template.
        $data->form = $this->form;

        // Get all the snippet categories for the given user.
        $data->categories = array_values(
            categories::get_category_list_for_nav($USER->id)
        );

        return $data;
    }
}