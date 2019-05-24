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
namespace block_course_activities\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use completion_info;

use stdClass;
use moodle_url;

class main implements renderable, templatable {
    /**
     * Store block instance so it can be accessed later.
     */
    protected $block_instance;

    /**
     * Constructor.
     *
     * @param $block_instance The block instance.
     */
    public function __construct($block_instance) {
        $this->block_instance = $block_instance;
    }

    /**
     * Export data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array Mustache template context
     */
    public function export_for_template(renderer_base $output, $addl_info = []) {
        global $CFG, $USER, $PAGE, $SESSION;

        $templatecontext = [];

        // Convenience variables
        $course = $PAGE->course;
        $cid = $course->id;
        $userid = $USER->id;

        // Initialize with block settings
        $block_config = $this->initialize_config($this->block_instance->config);

        // Use modinfo because it's faster than $DB calls,
        // get_array_of_activities(), and get_course_mods()
        // (https://moodle.org/mod/forum/discuss.php?d=220073#p1046667).
        $modinfo = get_fast_modinfo($cid);
        $cms = $modinfo->get_cms();
        $sections = $modinfo->get_sections();
        $section_info = $modinfo->get_section_info_all();

        // If there's a title, a spacer will be added so it's not so cramped
        if ($block_config->blocktitle !== '') {
            $templatecontext['blocktitle'] = true;
        }

        // Check if we should add the code for accordion-style display
        if ($block_config->accordion === 'yes') {
            if ($block_config->display_section_titles === 'yes') {
                if ($block_config->display_section_summaries === 'yes' || $block_config->display_activities === 'yes') {
                    $templatecontext['accordion'] = true;
                }
            }
        }

        // Display of collapse/expand icon
        if ($block_config->accordion_collapse_expand_icon === 'yes') {
            $templatecontext['accordion_collapse_expand_icon'] = true;
        }

        // Allow/disallow display of multiple accordion sections
        if ($block_config->accordion_single === 'yes') {
            $templatecontext['accordion_single'] = true;
        }

        // Display of collapse/expand all
        if ($block_config->accordion_collapse_expand_all === 'yes') {
            $templatecontext['accordion_collapse_expand_all'] = true;
        }

        $templatecontext['sections'] = [];
        foreach ($sections as $sectionid => $sequence) {
            // Make sure section is visible and not restricted
            if (!$section_info[$sectionid]->visible || !$section_info[$sectionid]->available) {
                continue;
            }

            $thissection = [];
            $thissection['section_id'] = $sectionid;

            // Flag for whether current activity is in this section
            $currentsection = false;

            // Section title
            if ($block_config->display_section_titles === 'yes') {
                // Use get_section_name() to handle multilang
                $thissection['section_title'] = get_section_name($course, $section_info[$sectionid]);

                // Section title link
                if ($block_config->link_section_titles === 'yes') {
                    $thissection['section_title_link'] = new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $cid . '#section-' . $sectionid);
                }
            }

            // Section summary
            if ($block_config->display_section_summaries === 'yes') {
                $sectionsummary = '';

                // Get formatted text to handle multilang
                $sectionsummary = format_text($section_info[$sectionid]->summary, $section_info[$sectionid]->summaryformat);

                $thissection['section_summary'] = $sectionsummary;
            }

            // Activities

            // Loop through activities of the section
            $sectionactivities = [];
            $visiblecount = 0;
            if ($block_config->display_activities === 'yes') {
                foreach ($sequence as $modid) {
                    $thisactivity = [];

                    $mod = $cms[$modid];
                    $url = $mod->url;
                    $modname = $mod->modname; // i.e. "forum", "quiz", "page"
                    $visible = $mod->visible;
                    $uservisible = $mod->uservisible;
                    $available = $mod->available;
                    $name = $mod->get_formatted_name();

                    // Make sure activity is not hidden or restricted
                    if ($visible && $available) {
                        $visiblecount++;

                        // Check config option for displaying labels
                        if ($modname === 'label') {
                            if ($block_config->display_labels === 'no') {
                                continue;
                            }
                        }

                        // Prepare to build up this activity's content
                        $activityname_extraclasses = '';
                        $activity_li_extraclasses = '';

                        // Hilite current module and disable link
                        if (is_object($PAGE->cm)) {
                            if ($PAGE->cm->id == $modid) {
                                $activity_li_extraclasses .= ' hilited';
                                $url = '';

                                $currentsection = true;
                            }
                        }

                        // Activity icon
                        if ($block_config->display_activity_icons === 'yes') {
                            $icon = $mod->get_icon_url();
                            $thisactivity['activity_icon'] = $icon;
                        }
                        else {
                            $activityname_extraclasses .= ' no-cmicon';
                        }

                        // Activity completion
                        if ($block_config->display_activity_completion_toggles === 'yes') {
                            $completioninfo = new completion_info($course);

                            // Use completion icon code from course renderer
                            $course_renderer = $PAGE->get_renderer('core', 'course');
                            $thisactivity['completion_toggle'] = $course_renderer->course_section_cm_completion($course, $completioninfo, $mod);
                        }
                        else {
                            $activityname_extraclasses .= ' no-completiontoggle';
                        }

                        // Activity name

                        // Labels handled separately to take care of multilang
                        if ($modname === 'label') {
                            // Use get_formatted_content(), not just name
                            $thisactivity['activity_name'] = $mod->get_formatted_content();
                        }
                        // Normal case (i.e. forum, quiz, page)
                        else {
                            // Check that the user can view the activity
                            if ($uservisible) {
                                $thisactivity['activity_url'] = $url;
                            }
                            else {
                                $activityname_extraclasses .= ' dimmed_text';
                            }
                            $thisactivity['activity_name'] = $name;
                        }

                        // Insert additional content using $addl_info array
                        if (array_key_exists($modid, $addl_info)) {
                            $thisactivity['addl_info'] = $addl_info[$modid];
                        }

                        // Add modname to classes so you can customize styling
                        $activity_li_extraclasses .= ' activityli-' . $modname;

                        $thisactivity['activity_name_extra_classes'] = $activityname_extraclasses;
                        $thisactivity['activity_li_extra_classes'] = $activity_li_extraclasses;
                        $sectionactivities[] = $thisactivity;
                    }
                }
            }

            $thissection['section_activities_count'] = count($sectionactivities);
            $thissection['section_activities'] = $sectionactivities;


            // Check whether this section should be open on page load
            if (($block_config->accordion_onload === 'current' && $currentsection) || $block_config->accordion_onload === 'allopen') {
                $thissection['section_open'] = true;
            }

            // Don't add spacer after section titles with no activities
            if ($thissection['section_activities_count'] == 0) {
                $thissection['section_spacer'] = false;
            }

            // Divider
            $sectiontitle_extraclasses = '';
            $accordioncontent_extraclasses = '';
            if ($block_config->display_divider === 'above') {
                $sectiontitle_extraclasses .= ' dividerabove';
            }
            else if ($block_config->display_divider === 'below') {
                $sectiontitle_extraclasses .= ' dividerbelow';
            }
            else if ($block_config->display_divider === 'belowwhenopen') {
                // Empty section can't be opened, so must have activities
                if ($thissection['section_activities_count'] != 0) {
                    $accordioncontent_extraclasses .= ' dividertop';
                }
            }
            $thissection['section_title_extra_classes'] = $sectiontitle_extraclasses;
            $thissection['accordion_content_extra_classes'] = $accordioncontent_extraclasses;

            // Add section depending on config and whether activities exist
            if (
                ($block_config->display_section_titles_when_display_labels_off === 'yes' && $block_config->display_labels === 'no' && $visiblecount > 0)
                ||
                ($block_config->display_section_titles === 'yes' && $block_config->display_activities === 'no')
                ||
                ($thissection['section_activities_count'] != 0)
            ) {
                $templatecontext['sections'][] = $thissection;
            }
        }

        // Add in spacers if necessary
        if ($block_config->section_spacer === 'yes') {
            for ($x = 0; $x < (count($templatecontext['sections']) - 1); $x++) {
                // Don't overwrite any previous settings
                if (!isset($templatecontext['sections'][$x]['section_spacer'])) {
                    $templatecontext['sections'][$x]['section_spacer'] = true;
                }
            }
        }

        return $templatecontext;
    }

    /**
     * Initialize block config settings with defaults if not set.
     *
     * @param $config Block instance's config settings
     * @return string Initialized block instance's config settings
     */
    private function initialize_config($config) {
        if ($config == null) {
            $config = new stdClass();
        }

        if (!isset($config->blocktitle)) {
            $config->blocktitle = get_string('pluginname', 'block_course_activities');
        }

        if (!isset($config->accordion)) {
            $config->accordion = 'yes';
        }

        if (!isset($config->accordion_collapse_expand_icon)) {
            $config->accordion_collapse_expand_icon = 'yes';
        }

        if (!isset($config->accordion_single)) {
            $config->accordion_single = 'no';
        }

        if (!isset($config->accordion_collapse_expand_all)) {
            $config->accordion_collapse_expand_all = 'no';
        }

        if (!isset($config->accordion_onload)) {
            $config->accordion_onload = 'current';
        }

        if (!isset($config->display_section_titles)) {
            $config->display_section_titles = 'yes';
        }

        if (!isset($config->display_section_titles_when_display_labels_off)) {
            $config->display_section_titles_when_display_labels_off = 'yes';
        }

        if (!isset($config->display_section_summaries)) {
            $config->display_section_summaries = 'yes';
        }

        if (!isset($config->section_spacer)) {
            $config->section_spacer = 'yes';
        }

        if (!isset($config->display_activities)) {
            $config->display_activities = 'yes';
        }

        if (!isset($config->display_activity_icons)) {
            $config->display_activity_icons = 'yes';
        }

        if (!isset($config->display_activity_completion_toggles)) {
            $config->display_activity_completion_toggles = 'yes';
        }

        if (!isset($config->display_labels)) {
            $config->display_labels = 'yes';
        }

        if (!isset($config->link_section_titles)) {
            $config->link_section_titles = 'yes';
        }

        if (!isset($config->display_divider)) {
            $config->display_divider = 'below';
        }

        return $config;
    }
}
