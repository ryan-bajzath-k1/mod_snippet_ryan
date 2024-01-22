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
 * mod_snippet data generator
 *
 * @package    mod_snippet
 * @category   test
 * @copyright  2023 Nicolas Dalpe {@link ndalpe@gmail.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Snippet module data generator class
 *
 * @package    mod_snippet
 * @category   test
 * @copyright  2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_snippet_generator extends testing_module_generator {

    /**
     * Create a snippet instance.
     *
     * @param array|stdClass $record The snippet data.
     *
     * @return stdClass The snippet instance.
     */
    public function create_instance($record = null, array $options = null): stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/lib/resourcelib.php');

        $record = (object)(array)$record;

        if (!isset($record->name)) {
            $record->name = 'This is test snippet';
        }
        if (!isset($record->intro)) {
            $record->intro = 'This is the intro for the snippet';
        }
        if (!isset($record->introformat)) {
            $record->introformat = FORMAT_MOODLE;
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Function to create a dummy category.
     *
     * @param array|stdClass $record Only the snippetid and userid are required.
     *
     * @return int The categoryid.
     */
    public function create_category($record) {
        global $DB;

        $record = (array) $record;

        if (!isset($record['snippetid'])) {
            throw new coding_exception('snippetid must be present in phpunit_util::create_category() $record');
        }

        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present in phpunit_util::create_category() $record');
        }

        $countcat = $DB->count_records('snippet_categories', array('snippetid' => $record['snippetid']));
        $record['name'] = 'Category ' . ($countcat + 1);
        $record['timecreated'] = $record['timemodified'] = time();

        $categoryid = $DB->insert_record('snippet_categories', $record);

        return $categoryid;
    }

    /**
     * Function to create a dummy snip.
     *
     * @param array|stdClass $record Only the categoryid, snippetid and userid are required.
     *
     * @return int The snipid.
     */
    public function create_snip($record = null): int {
        global $DB;

        if (!isset($record['categoryid'])) {
            throw new coding_exception('categoryid must be present in phpunit_util::create_category() $record');
        }

        if (!isset($record['snippetid'])) {
            throw new coding_exception('snippetid must be present in phpunit_util::create_category() $record');
        }

        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present in phpunit_util::create_category() $record');
        }

        $countsnip = $DB->count_records('snippet_snips', array('snippetid' => $record['snippetid']));
        $record['name'] = 'Snip ' . ($countsnip + 1);
        $record['intro'] = 'This is the intro for the snippet';
        $record['introformat'] = FORMAT_MOODLE;
        $record['private'] = 1;
        $record['language'] = 'php';
        $record['snippet'] = '<?php echo "Hello World"; ?>';

        $snipid = $DB->insert_record('snippet_snips', $record);

        return $snipid;
    }
}
