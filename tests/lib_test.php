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
 * Unit tests for mod_snippet lib
 *
 * @package    mod_snippet
 * @category   external
 * @copyright  2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.1
 */
namespace mod_snippet;

defined('MOODLE_INTERNAL') || die();

use mod_snippet\local\manager;


/**
 * Unit tests for mod_snippet lib
 *
 * @package    mod_snippet
 * @category   external
 * @copyright  2023 Nicolas Dalpe <ndalpe@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class lib_test extends \advanced_testcase {

    /**
     * Prepares things before this test case is initialised
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/'.manager::MODULE_NAME.'/lib.php');
    }

    /**
     * Test page_view
     * @return void
     */
    public function test_page_view() {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $snippet = $this->getDataGenerator()->create_module(
            manager::MODULE_NAME,
            array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1)
        );
        $context = \context_module::instance($snippet->cmid);
        $cm = get_coursemodule_from_instance(manager::MODULE_NAME, $snippet->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $this->setAdminUser();
        snippet_view($page, $course, $cm, $context);

        $events = $sink->get_events();

        // 2 additional events thanks to completion.
        $this->assertCount(3, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_page\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodleurl = new \moodle_url('/mod/page/view.php', array('id' => $cm->id));
        $this->assertEquals($moodleurl, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Check completion status.
        $completion = new \completion_info($course);
        $completiondata = $completion->get_data($cm);
        $this->assertEquals(1, $completiondata->completionstate);
    }
}
