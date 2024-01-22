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

use context;
use moodle_exception;
use moodle_url;
use core_form\dynamic_form;

use mod_snippet\local\manager;

/**
 * Create new snippet category form.
 *
 * @package    mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_category extends dynamic_form {

    /**
     * Process the form submission
     *
     * @return array
     * @throws moodle_exception
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER;

        $context = $this->get_context_for_dynamic_submission();

        $data = $this->get_data();

        $cm = get_coursemodule_from_id('snippet', $data->cmid);

        $newcategory = new \stdClass();
        $newcategory->snippetid = $cm->instance;
        $newcategory->name = $data->categoryname;

        $newcategoryid = \mod_snippet\local\categories::create_category($newcategory);

        $returnurl = new moodle_url(
            '/mod/' . manager::MODULE_NAME . '/view.php',
            ['id' => $context->instanceid]
        );
        return [
            'result' => true,
            'url' => $returnurl->out(false)
        ];
    }

    /**
     * Get context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $context = \context_module::instance($cmid);
        return $context;
    }

    /**
     * Set data
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $data = (object) [
            'cmid' => $this->optional_param('cmid', 0, PARAM_INT),
        ];
        $this->set_data($data);
    }

    /**
     * Has access ?
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        if (!has_capability('mod/' . manager::MODULE_NAME . ':view', $this->get_context_for_dynamic_submission())) {
            throw new moodle_exception('createcategorymissingcapability', manager::MODULE_NAME);
        }
    }

    /**
     * Get page URL
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return new moodle_url('/mod/' . manager::MODULE_NAME . '/view.php', ['id' => $cmid]);
    }

    /**
     * Form definition
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'categoryname', get_string('fieldlabel_categoryname', manager::PLUGIN_NAME));
        $mform->setType('categoryname', PARAM_TEXT);

        // Category name is required.
        $errormessage = get_string('fieldlabel_categoryname_required', manager::PLUGIN_NAME);
        $mform->addRule( 'categoryname', $errormessage, 'required', null, 'client');
    }
}
