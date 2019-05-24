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

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing block settings
 *
 * @package    block_course_activities
 * @author     Nicholas Yang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_activities_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $blockname = $this->block->blockname;
        $yesnooptions = [ 'yes' => get_string('yes'), 'no' => get_string('no') ];

        // Configuration title
        $mform->addElement('header', 'configheader', ucwords(get_string('generalsettings', 'admin')));
        $mform->addElement('text', 'config_blocktitle', get_string('config_blocktitle', $blockname));
        $mform->setDefault('config_blocktitle', get_string('pluginname', $blockname));
        $mform->setType('config_blocktitle', PARAM_RAW);

        // Accordion settings
        $mform->addElement('header', 'configheader', get_string('accordion_settings', $blockname));
        $accordion_yesnooptions = [ 'accordion' => 'yes', 'accordion_collapse_expand_icon' => 'yes', 'accordion_single' => 'no', 'accordion_collapse_expand_all' => 'no' ];
        foreach ($accordion_yesnooptions as $option => $default) {
            $mform->addElement('select', 'config_' . $option, get_string('config_' . $option, $blockname), $yesnooptions);
            $mform->setDefault('config_' . $option, $default);
        }

        $onload_options = [
            'current' => get_string('config_accordion_onload_current', $blockname),
            'allopen' => get_string('config_accordion_onload_allopen', $blockname),
            'allclosed' => get_string('config_accordion_onload_allclosed', $blockname),
        ];
        $mform->addElement('select', 'config_accordion_onload', get_string('config_accordion_onload', $blockname), $onload_options);
        $mform->setDefault('config_accordion_onload', 'current');

        // Section settings
        $mform->addElement('header', 'configheader', get_string('section_settings', $blockname));
        $section_yesnooptions = ['display_section_titles' => 'yes', 'display_section_titles_when_display_labels_off' => 'yes', 'link_section_titles' => 'yes', 'display_section_summaries'=>'yes', 'section_spacer' => 'yes' ];
        foreach ($section_yesnooptions as $option => $default) {
            $mform->addElement('select', 'config_' . $option, get_string('config_' . $option, $blockname), $yesnooptions);
            $mform->setDefault('config_' . $option, $default);
        }

        $divider_options = [
            'none' => get_string('config_display_divider_none', $blockname),
            'above' => get_string('config_display_divider_above', $blockname),
            'below' => get_string('config_display_divider_below', $blockname),
            'belowwhenopen' => get_string('config_display_divider_below_when_open', $blockname),
        ];
        $mform->addElement('select', 'config_display_divider', get_string('config_display_divider', $blockname), $divider_options);
        $mform->setDefault('config_display_divider', 'below');

        // Activity settings
        $mform->addElement('header', 'configheader', get_string('activity_settings', $blockname));
        $activity_yesnooptions = ['display_activities' => 'yes', 'display_activity_icons' => 'yes', 'display_activity_completion_toggles' => 'yes', 'display_labels' => 'yes' ];
        foreach ($activity_yesnooptions as $option => $default) {
            $mform->addElement('select', 'config_' . $option, get_string('config_' . $option, $blockname), $yesnooptions);
            $mform->setDefault('config_' . $option, $default);
        }
    }
}
