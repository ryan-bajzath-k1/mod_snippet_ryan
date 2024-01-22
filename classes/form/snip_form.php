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

namespace mod_snippet\form;

// require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/lib/formslib.php');

// use moodleform_mod;
use lang_string;

use mod_snippet\local\categories;
use mod_snippet\local\languages;
use mod_snippet\local\manager;

class snip_form extends \moodleform {

    /**
     * Snippet form constructor.
     */
    public function definition() {
        global $CFG, $DB, $USER;

        $mform = $this->_form;

        // Get the snip data in case of edit.
        if (isset($this->_customdata['snipid'])) {
            $snip = $DB->get_record('snippet_snips', ['id' => $this->_customdata['snipid']]);
        }

        // Set the course module id in the form.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        // Set the snip id in the form.
        $mform->addElement('hidden', 'snipid');
        $mform->setType('snipid', PARAM_INT);
        $mform->setDefault('snipid', $this->_customdata['snipid']);

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('snippetname', manager::PLUGIN_NAME), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'snippetname', manager::PLUGIN_NAME);

        // Adding the description field.
        $mform->addElement('editor',
            'intro',
            get_string('snippetdesc', manager::PLUGIN_NAME),
            array('enable_filemanagement' => false)
        );
        $mform->setType('intro', PARAM_RAW);

        // Wheter the snippet is private of not.
        $mform->addElement('selectyesno', 'private', get_string('private', manager::PLUGIN_NAME));
        $mform->addHelpButton('private', 'private', 'snippet');
        $mform->setType('private', PARAM_BOOL);
        $mform->setDefault('private', 1);

        // First category of the user.
        $mform->addElement('hidden', 'firstcategory');
        $mform->setType('firstcategory', PARAM_TEXT);

        // Category of the snip. Add a text field if the user has no category so he can create one.
        // Otherwise, add a select field with the user's categories.
        if (!categories::has_category($USER->id)) {
            $label = new lang_string('add_first_category', manager::PLUGIN_NAME);
            $mform->addElement('text', 'categoryname', $label);
            $mform->setType('categoryname', PARAM_TEXT);
            $mform->setDefault('firstcategory', 'yes');
        } else {
            $label = new lang_string('select_category', manager::PLUGIN_NAME);
            $categories = categories::get_category_list_for_input($USER->id);
            $mform->addElement('select', 'categoryid', $label, $categories);
            $mform->setType('categoryid', PARAM_INT);

            // Set the current category if available.
            if ($this->_customdata['categoryid'] !== 0) {
                $mform->setDefault('categoryid', $this->_customdata['categoryid']);
            }
            $mform->setDefault('firstcategory', 'no');
        }

        // Programming language used in the snippet.
        $label = get_string('language', manager::PLUGIN_NAME);
        $languages = languages::get_languages_from_files();
        $options = [
            'multiple' => false,
            'noselectionstring' => get_string('select_language', manager::PLUGIN_NAME)
        ];
        $mform->addElement('autocomplete', 'language', $label, $languages, $options);
        $mform->setType('language', PARAM_TEXT);

        // The snippet content.
        $label = get_string('snippet', manager::PLUGIN_NAME);
        $options = array('rows' => 10, 'cols' => 30);
        $mform->addElement('textarea', 'snippet', $label, $options);
        $mform->setType('language', PARAM_TEXT);

        // Add standard buttons.
        $this->add_action_buttons();

        if ($snip !== false) {
            // Remove the id from the snip object so the course module id
            // is not overwritten by the snip id.
            if (isset($snip->id)) {
                unset($snip->id);
            }

            $this->set_data($snip);
        }
    }
}
