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
 * Snipet category manager.
 *
 * @module     mod_snippet/category
 * @class      Category
 * @copyright 2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import { prefetchStrings } from 'core/prefetch';
import { get_string as getString } from 'core/str';
import * as snippetSelectors from 'mod_snippet/selectors';

/**
 * Modal form to create new category.
 *
 * @param {integer} cmid The module context id.
 */
export const init = (cmid) => {

    prefetchStrings('mod_snippet', [
        'newcategory_modal_title',
        'create_new_category',
        'notification_newcategory_created'
    ]);

    const newCategoryButton = document.querySelector(snippetSelectors.actions.createNewCategory);
    newCategoryButton.addEventListener('click', event => {
        event.preventDefault();

        const modalForm = new ModalForm({
            modalConfig: {
                title: getString('newcategory_modal_title', 'mod_snippet'),
                large: true
            },
            args: { cmid: cmid },
            saveButtonText: getString('create_new_category', 'mod_snippet'),
            formClass: 'mod_snippet\\form\\create_category'
        });

        // Called when the form is submitted.
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, event => {
            if (event.detail.result) {
                Notification.addNotification({
                    type: 'success',
                    message: getString('notification_newcategory_created', 'mod_snippet')
                });
            } else {
                Notification.addNotification({
                    type: 'error',
                    message: event.detail.errors.join('<br>')
                });
            }
        });

        modalForm.show();
    });
};
