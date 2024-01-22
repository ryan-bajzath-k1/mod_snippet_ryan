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
 * Library of interface functions and constants.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_snippet\local\manager;

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function snippet_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_snippet into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_snippet_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function snippet_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = time();

    $id = $DB->insert_record('snippet', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_snippet in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_snippet_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function snippet_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('snippet', $moduleinstance);
}

/**
 * Removes an instance of the mod_snippet from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function snippet_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('snippet', array('id' => $id));
    if (!$exists) {
        return false;
    }

    // Delete all the snips associated to the snippet.
    $DB->delete_records('snippet_snips', array('snippetid' => $id));

    // Delete all the category associated to the snippet.
    $DB->delete_records('snippet_categories', array('snippetid' => $id));

    // Delete the snippet module.
    $DB->delete_records('snippet', array('id' => $id));

    return true;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $snippet    snippet object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 4.1
 */
function snippet_view($snippet, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $snippet->id
    );

    $event = \mod_snippet\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot(manager::MODULE_NAME, $snippet);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Is a given scale used by the instance of mod_snippet?
 *
 * This function returns if a scale is being used by one mod_snippet
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_snippet instance.
 */
function snippet_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('snippet', array('id' => $moduleinstanceid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mod_snippet.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_snippet instance.
 */
function snippet_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('snippet', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given mod_snippet instance.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function snippet_grade_item_update($moduleinstance, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $moduleinstance->grade;
        $item['grademin']  = 0;
    } else if ($moduleinstance->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$moduleinstance->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('/mod/snippet', $moduleinstance->course, 'mod', 'mod_snippet', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given mod_snippet instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function snippet_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('/mod/snippet', $moduleinstance->course, 'mod', 'snippet',
                        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update mod_snippet grades in the gradebook.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function snippet_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('/mod/snippet', $moduleinstance->course, 'mod', 'mod_snippet', $moduleinstance->id, 0, $grades);
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}.
 *
 * @package     mod_snippet
 * @category    files
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[].
 */
function snippet_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for mod_snippet file areas.
 *
 * @package     mod_snippet
 * @category    files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info Instance or null if not found.
 */
function snippet_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mod_snippet file areas.
 *
 * @package     mod_snippet
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_snippet's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function snippet_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);
    send_file_not_found();
}

/**
 * Extends the global navigation tree by adding mod_snippet nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $snippetnode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function snippet_extend_navigation($snippetnode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_snippet settings.
 *
 * This function is called when the context for the page is a mod_snippet module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $snippetnode {@see navigation_node}
 */
function snippet_extend_settings_navigation($settingsnav, $snippetnode = null) {
}
