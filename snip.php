<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_snippet.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

use mod_snippet\local\manager;
use mod_snippet\local\snips;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Code part of the snippet.
$snipid = optional_param('snipid', 0, PARAM_INT);

// Id of the currently selected category.
$categoryid = optional_param('categoryid', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id(manager::MODULE_NAME, $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('snippet', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('snippet', array('id' => $activityid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('snippet', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url(new \moodle_url(
    '/mod/' . manager::MODULE_NAME . '/snip.php', manager::get_param_for_url()
));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$a = manager::get_param_from_url();

$mform = new mod_snippet\form\snip_form(null, manager::get_param_from_url());

if ($mform->is_cancelled()) {
    redirect(new \moodle_url(
        '/mod/' . manager::MODULE_NAME . '/view.php',
        manager::get_param_for_url()
    ));
} else if ($data = $mform->get_data()) {
    if ($data->snipid === 0) {

        // Create the new snip.
        $snipid = snips::create($data);

        $url = new \moodle_url(
            '/mod/' . manager::MODULE_NAME . '/view.php',
            manager::get_param_for_url()
        );

        // Add snipid to the url.
        $url->param('snipid', $snipid);

        redirect($url);
    } else {
        $snipid = snips::update($data);
    }
}

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_snippet');
$renderable = new \mod_snippet\output\snip_page($cm, $mform->render());
echo $renderer->render($renderable);

echo $OUTPUT->footer();
