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
 * course_activities block renderer
 *
 * @package    block_course_activities
 * @copyright  Nicholas Yang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_course_activities\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use html_writer;
use completion_info;

class renderer extends plugin_renderer_base {
    /**
     * Whether AMD module has been loaded yet. We need to keep track of this so
     * that it is only loaded once on pages with both a
     * mark_as_completed_button() and a Course Activities block.
     * @var bool
     */
    protected static $amd_loaded = false;

    /**
     * Override the constructor so that we can load the AMD module.
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(\moodle_page $page, $target) {
        global $CFG, $PAGE;

        // Initialize the Asynchronous Module Definition actions (AJAX stuff)
        if (!self::$amd_loaded) {
            $PAGE->requires->js_call_amd('block_course_activities/actions', 'init', [ ['wwwroot' => $CFG->wwwroot] ]);
            self::$amd_loaded = true;
        }

        parent::__construct($page, $target);
    }

    /**
     * Return the main content for the block.
     *
     * @param main $main The main renderable
     * @param array $addl_info Additional content. Indexed by module IDs.
     * @return string Block main content
     */
    public function render_main(main $main, $addl_info = []) {
        return $this->render_from_template('block_course_activities/main', $main->export_for_template($this, $addl_info));
    }

    /**
     * Creates a button that allows users to mark currently displayed module
     * as completed or not completed.
     *
     * @return HTML for a Mark As Complete button
     */
    public function mark_as_completed_button() {
        global $CFG;

        // Only show when on a course module page
        if (!is_object($this->page->cm)) {
            return '';
        }

        $mod = $this->page->cm;
        $course = $this->page->course;

        $completioninfo = new completion_info($course);
        $completion = $completioninfo->is_enabled($mod);
        $completiondata = $completioninfo->get_data($mod, true);

        $output = '';

        $disabled = false;

        if ($completion) {
            $is_complete = in_array($completiondata->completionstate, [ COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS ]);

            // Get the button text (Completed/Not Completed)
            $button_text = ($is_complete ? get_string('completed', 'completion') : get_string('notcompleted', 'completion'));
            $button_text = html_writer::tag('span', $button_text, [ 'class' => 'mac-btn-text' ]);

            // Use completion icon code from core's course renderer
            $course_renderer = $this->page->get_renderer('core', 'course');
            $output .= $course_renderer->course_section_cm_completion($course, $completioninfo, $mod);

            if (!$this->page->user_is_editing()) {
                // Add button text
                $output = str_replace('</button>', $button_text . '</button>', $output);
            }
            // Will be a disabled span.autocompletion icon if editing is on
            else {
                // Insert button text
                $output = str_replace('</span>', $button_text . '</span>', $output);

                // Help text explaining that button is disabled in editing mode
                $output = str_replace('<span', '<span title="' . get_string('disabled_mark_as_complete_btn', 'block_course_activities') . '"', $output);
            }
        }

        if ($output !== '') {
            $output = '<div class="mark-as-completed-btn">' . $output . '</div>';
        }

        return $output;
    }
}
