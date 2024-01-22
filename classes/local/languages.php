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
 * The mod_snippet course module viewed event.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_snippet\local;

defined('MOODLE_INTERNAL') || die();

use lang_string;
use mod_snippet\local\manager;

/**
 * The mod_snippet course module viewed event class.
 *
 * @package     mod_snippet
 * @copyright   2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class languages {

    /**
     * Get all the language syntax available.
     *
     * @return array The list of languages.
     */
    public static function get_languages_from_files():array {
        $languages = array();

        $languageslibrary = __DIR__ . '/../../amd/build/languages/';

        // Get all the language syntax files.
        $files = array_diff(scandir($languageslibrary), array('..', '.'));

        // Remove the .map files.
        $files = array_filter($files, fn ($file) => str_contains($file, '.map') ? false : $file);

        foreach ($files as $file) {
            $content = file_get_contents($languageslibrary . $file);
            preg_match('/^define\(\"(mod_snippet\/languages\/([a-z]+))/i', $content, $matches);
            // 2 - contains the language name.
            $languages[$matches[2]] = new lang_string($matches[2], manager::PLUGIN_NAME);
        }
        return $languages;
    }
}
