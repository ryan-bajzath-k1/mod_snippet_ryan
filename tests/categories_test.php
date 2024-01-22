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
 * Unit tests for mod_snippet.
 *
 * @package mod_snippet
 * @copyright 2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 4.1
 * @group mod_snippet
 */

namespace mod_snippet;

defined('MOODLE_INTERNAL') || die();

use mod_snippet\local\categories;

/**
 * Unit tests for categories snippet management class.
 *
 * @package mod_snippet
 * @copyright 2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 4.1
 * @group mod_snippet
 * @coversDefaultClass \mod_snippet\local\categories
 */
class categories_test extends \advanced_testcase {

    /** @var testing_data_generator PHPUnit generator. */
    public $generator;

    /** @var mod_snippet_generator mod_snippet generator. */
    public $snippetgenerator;

    /** @var stdClass The test course. */
    public $course;

    /** @var stdClass The test user. */
    public $user;

    /** @var stdClass The test snippet. */
    public $snippet;

    /** @var int The test category id. */
    public $categoryid;

    /**
     * Setup function
     */
    protected function setUp(): void {

        $this->resetAfterTest(true);

        $this->generator = $this->getDataGenerator();
        $this->snippetgenerator = $this->generator->get_plugin_generator('mod_snippet');

        $this->course = $this->generator->create_course();
        $this->user = $this->generator->create_user();
        $this->snippet = $this->snippetgenerator->create_instance(['course' => $this->course->id]);
        $this->categoryid = $this->snippetgenerator->create_category(
            ['snippetid' => $this->snippet->id, 'userid' => $this->user->id]
        );
    }

    /**
     * Test create a new category.
     *
     * @covers ::create_category
     */
    public function test_create_category() {
        global $DB;

        // Test normal category creation.
        $categorydata = new \stdClass();
        $categorydata->snippetid = $this->snippet->id;
        $categorydata->userid = $this->user->id;
        $categorydata->name = 'This is the category name';
        $categoryid = categories::create_category($categorydata);
        $this->assertEquals(1, $DB->count_records('snippet_categories', ['id' => $categoryid]));

        // Creating a category without a snippetid should return false.
        $categorydata = new \stdClass();
        $categorydata->userid = $this->user->id;
        $categorydata->name = 'This is the category name';
        $categoryid = categories::create_category($categorydata);
        $this->assertFalse(boolval($categoryid));

        // Creating a catefory without a userid should assign the new category to the current user.
        $this->setUser($this->user);
        $categorydata = new \stdClass();
        $categorydata->snippetid = $this->snippet->id;
        $categorydata->name = 'This is the category name';
        $categoryid = categories::create_category($categorydata);
        $this->assertEquals(1, $DB->count_records('snippet_categories', ['id' => $categoryid]));
        $category = $DB->get_record('snippet_categories', ['id' => $categoryid]);
        $this->assertEquals($this->user->id, $category->userid);

        // Creating a category without a name should use defaultcategoryname as name.
        $categorydata = new \stdClass();
        $categorydata->snippetid = $this->snippet->id;
        $categorydata->userid = $this->user->id;
        $categoryid = categories::create_category($categorydata);
        $this->assertEquals(1, $DB->count_records('snippet_categories', ['id' => $categoryid]));
        $category = $DB->get_record('snippet_categories', ['id' => $categoryid]);
        $this->assertEquals(get_string('defaultcategoryname', 'mod_snippet'), $category->name);
    }


    /**
     * Get the user categories for a given user.
     *
     * @covers ::get_category_list_for_user
     */
    public function test_get_category_list_for_user() {
        global $DB;

        $catlist = categories::get_category_list_for_user($this->user->id);
        $this->assertEquals(1, count($catlist));

        // Create 5 more categories for the user.
        for ($i = 0; $i < 5; $i++) {
            $this->snippetgenerator->create_category(
                ['snippetid' => $this->snippet->id, 'userid' => $this->user->id]
            );
        }

        $catlist = categories::get_category_list_for_user($this->user->id);
        $this->assertEquals(6, count($catlist));

        // Delete all categories and make sure the list is empty.
        $DB->delete_records('snippet_categories');
        $catlist = categories::get_category_list_for_user($this->user->id);
        $this->assertEquals(0, count($catlist));
    }

    /**
     * Get a list of categories, formated for <select> menu, for a given user.
     *
     * @covers ::get_category_list_for_input
     */
    public function test_get_category_list_for_input() {
        global $DB;

        // Create 5 more categories for the user.
        for ($i = 0; $i < 5; $i++) {
            $this->snippetgenerator->create_category(
                ['snippetid' => $this->snippet->id, 'userid' => $this->user->id]
            );
        }

        $catlist = categories::get_category_list_for_input($this->user->id);
        $this->assertEquals(6, count($catlist));

        // Make sure all the keys are int and all the values are string.
        foreach ($catlist as $key => $value) {
            $this->assertIsInt($key);
            $this->assertIsString($value);
        }

        // Delete all categories and make sure the list is empty.
        $DB->delete_records('snippet_categories');
        $catlist = categories::get_category_list_for_input($this->user->id);
        $this->assertEquals(0, count($catlist));
    }

    /**
     * Get a list of categories, formated for nav menu, for a given user.
     *
     * @covers ::get_category_list_for_nav
     */
    public function test_get_category_list_for_nav() {

        // No snip has been created yet. The snip count should be 0 and hasnosnippet should be true.
        $catlist = categories::get_category_list_for_nav($this->user->id);
        $this->assertEquals(0, $catlist[$this->categoryid]->count);
        $this->assertTrue($catlist[$this->categoryid]->hasnosnippet);

        // Create 3 snips for the category. The snip count should be 3 and hasnosnippet should be false.
        $snipparam = [
            'categoryid' => $this->categoryid,
            'userid' => $this->user->id,
            'snippetid' => $this->snippet->id
        ];
        $this->snippetgenerator->create_snip($snipparam);
        $this->snippetgenerator->create_snip($snipparam);
        $this->snippetgenerator->create_snip($snipparam);

        $catlist = categories::get_category_list_for_nav($this->user->id);
        $this->assertEquals(3, $catlist[$this->categoryid]->count);
        $this->assertFalse($catlist[$this->categoryid]->hasnosnippet);
    }

    /**
     * Set the current category as active.
     *
     * @covers ::set_active
     */
    public function test_set_active() {
        global $DB;

        // Create 5 more categories for the user.
        for ($i = 0; $i < 5; $i++) {
            $this->snippetgenerator->create_category(
                ['snippetid' => $this->snippet->id, 'userid' => $this->user->id]
            );
        }

        // Get all the categories for the user.
        $categories = $DB->get_records('snippet_categories', ['userid' => $this->user->id]);

        // Make sure no category has an active attribute.
        foreach ($categories as $category) {
            $this->assertObjectNotHasAttribute('active', $category);
        }

        // Pick the first category.
        $categorykey = key($categories);

        // Set this category as active.
        $categories = categories::set_active($categorykey, $categories);

        // Make sure the category has an active attribute.
        $this->assertObjectHasAttribute('active', $categories[$categorykey]);
    }

    /**
     * Check if the user has at least one category.
     *
     * @covers ::has_category
     */
    public function test_has_category() {
        global $DB;

        // There should be one category for the user created in setUp() already.
        $hascategories = categories::has_category($this->user->id);
        $this->assertTrue($hascategories);

        // Create another category and retest.
        $this->snippetgenerator->create_category(
            ['snippetid' => $this->snippet->id, 'userid' => $this->user->id]
        );
        $this->assertTrue($hascategories);

        // Delete all categories and make sure the user has no category.
        $DB->delete_records('snippet_categories');
        $hascategories = categories::has_category($this->user->id);
        $this->assertFalse($hascategories);

    }
}
