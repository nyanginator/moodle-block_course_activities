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
 * @package    block_course_activities
 * @copyright  Nicholas Yang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/lib.php');

class block_course_activities extends block_base {

    /** @var string The name of the block */
    public $blockname = null;

    /**
     * Initialize the block.
     */
    public function init() {
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }
    
    /**
     * Configuration settings (Site Admin menu, i.e. settings.php).
     * This block has no plugin configuration settings.
     */
    public function has_config() {
        return false;
    }

    /**
     * Amend the block instance after it is loaded. Blank titles allowed.
     */
    public function specialization() {
        if (empty($this->config)) {
            $this->title = get_string('pluginname', $this->blockname);
        }
        else {
            $trimmed_formatted = trim(format_string($this->config->blocktitle));

            $this->config->blocktitle = $trimmed_formatted;
            $this->title = $trimmed_formatted;
        }
    }

    /**
     * No real reason to have multiple instances.
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Allow the block to be added on site-index so that it can be added to
     * all courses by default ("Display throughout entire site"). Allow
     * course-view-* because this block is meant for courses with the
     * topics/weekly format.
     */
    public function applicable_formats() {
        return array('site-index' => true, 'course-view-*' => true);
    }

    /**
     * Returns the contents.
     *
     * @return stdClass Contents of block
     */
    public function get_content() {
        if (isset($this->content)) {
            return $this->content;
        }

        // Initialize content object
        $this->content = new stdClass();

        // This block only has content on courses with sections (topics/weekly)
        $format = course_get_format($this->page->course);
        if (!$format->uses_sections()) {

            // Message for Site Admin. For everyone else, the block is hidden.
            if (has_capability('moodle/site:config', context_system::instance())) {
                $this->content->text = '<div class="message">' . get_string('notusingsections', 'block_course_activities') . '</div>';
            }

            return $this->content;

        }

        // Pass in block instance object so config is accessible in renderer
        $renderable = new \block_course_activities\output\main($this);
        $renderer = $this->page->get_renderer('block_course_activities');
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }

}
