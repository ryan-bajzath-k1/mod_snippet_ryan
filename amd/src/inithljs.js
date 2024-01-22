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
 * Initialise and highlight code snippets.
 *
 * @module    mod_snippet/hljs
 * @copyright 2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Import the highlight.js library.
import 'core/copy_to_clipboard';
import hljs from 'mod_snippet/highlight';
import Templates from 'core/templates';

/**
 * Initialise and highlight.js and load the language syntax module.
 *
 * @param {array} languages The languages used in the snip or list of snips.
 */
export const init = (languages) => {
    for (const language of languages) {

        // Load snip's language syntax module.
        import(`mod_snippet/languages/${language}`).then((liblanguage) => {

            // Register the snip's language syntax module.
            hljs.registerLanguage(language, liblanguage);

            // Highlight all code blocks with the loaded language.
            document.querySelectorAll(`pre code.language-${language}`).forEach((el) => {
                hljs.highlightElement(el);
                addClipboardButton(el);
            });
        });
    }
};

/**
 * Add a copy to clipboard button to a snip.
 *
 * @param {el} Element to attach the button to.
 */
const addClipboardButton = (el) => {
    // Get the parent pre element id and extract the snip id from its id.
    const parentsnipid = el.parentNode.id;
    const snipid = parseInt(parentsnipid.split('-')[1]);

    const context = {
        id: snipid
    };

    // This will call the function to load and render our template.
    Templates.renderForPromise('mod_snippet/components/btn_copyto_clipboard', context)

    // It returns a promise that needs to be resoved.
    .then(({ html, js }) => {
            // Here eventually I have my compiled template, and any javascript that it generated.
            // The templates object has append, prepend and replace functions.
            Templates.appendNodeContents('#' + parentsnipid + ' > code', html, js);
        })

        // Deal with this exception (Using core/notify exception function is recommended).
        .catch((error) => displayException(error));
};