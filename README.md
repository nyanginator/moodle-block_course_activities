# Moodle - Course Activities Block
https://github.com/nyanginator/moodle-block_course_activities

Display course sections and activities in a block with icons, links, and completion toggles. Optionally, you can also render a Mark As Complete button.

Table of Contents
=================
* [Background](#background)
  * [Previous Work](#previous-work)
  * [This Plugin](#this-plugin)
* [Install](#install)
* [Block Configuration](#block-configuration)
  * [General Settings](#general-settings)
  * [Accordion Settings](#accordion-settings)
  * [Section Settings](#section-settings)
  * [Activity Settings](#activity-settings)
* [Other Features](#other-features)
  * [Adding Additional Info](#adding-additional-info)
  * [Mark As Complete Button](#mark-as-complete-button)
* [Notes](#notes)
* [Uninstall](#uninstall)
* [Contact](#contact)

Background
==========

Purpose
-------------
The purpose of this plugin is to facilitate: 1) activity navigation, and 2) completion toggling. A student should be able to intuitively navigate between activities and mark activities as complete on the activity pages themselves. This block can be placed on activity pages to allow this.

Moodle does now include navigation for next/previous activities on activity pages. However, the goal of this block is to give students an overall view of the course and allow them to jump to any other activity, not just the next or previous one.

As for completion toggling, Moodle currently requires users to return to the course page to mark an activity as complete or not complete, which is a hassle.

Previous Work
-------------
There are already existing block plugins that accomplish different aspects of the stated purpose in various ways. Here is a brief overview of them:

* [Course Module Navigation Block](https://moodle.org/plugins/block_course_modulenavigation)
  - **Pros**: Shows completion status, customizable block title, can display just current section, option to show/hide labels, option to show/hide activities, uses mustache templates
  - **Cons**: Cannot click to toggle completion status, can configure most options only globally and not per block, no activity icons, no section summaries, accordion does not work, labels not translated

* [Course Contents Block](https://moodle.org/plugins/block_course_contents)
  - **Pros**: Customizable block title, can enumerate sections, can auto-generate section titles, can add course page link with custom text
  - **Cons**: Only shows sections and nothing about course activities, no section summaries, shows empty sections

* [Navigation Block](https://docs.moodle.org/en/Navigation_block)
  - **Pros**: Built-in to Moodle core, has activity icons, uses a collapsible tree menu, is dockable, has trim text option, option to generate navigation for everything or just selected courses/activities
  - **Cons**: Shows a lot more than is necessary _(Home, Dashboard, Site Pages, Participants, Badges, Competencies, Grades)_, not very aesthetic, no section summaries, shows empty sections

* [Course Menu Block](https://moodle.org/plugins/block_course_menu)
  - **Pros**: Has chapter grouping feature, can add custom links at bottom, has trim text option, can display and reorder predefined elements _(Topics/Weeks, Show All Sections, Calendar, Site Pages, My Profile, My Courses, My Profile Settings, Course Administration, Participants, Reports)_
  - **Cons**: CSS is broken, some activities don't show in tree view, tree does not collapse/expand, no section summaries, shows empty sections, does not seem actively maintained

![Previous Work](https://raw.githubusercontent.com/nyanginator/moodle-block_course_activities/master/screenshots/previous-work.jpg)

This Plugin
-----------
Features:
* Optional and customizable block title
* Optional Bootstrap accordion tabs
* Optional display of section titles
* Optional display of section summaries
* Optional display of activities
* Optional display of activity icons
* Optional display of clickable activity completion toggles
* Optional display of labels
* Mustache template
* jQuery through AMD instead of YUI
* Handles multilingual spans in section titles and activity names

Missing:
* Trim text option
* Enumeration
* Auto-title generation
* Separate link back to course
* Chapter grouping
* Custom links
* Predefined elements _(Show All Sections, Calendar, Site Pages, My Profile, My Courses, My Profile Settings, Course Administration, Participants, Reports)_

![Course Activities](https://raw.githubusercontent.com/nyanginator/moodle-block_course_activities/master/screenshots/course-activities.jpg)

Install
=======
Create the folder `blocks/course_activities` in your Moodle installation and copy the contents of this repository there. Login as the Moodle admin and proceed through the normal installation of this new plugin. If the plugin is not automatically found, you may have to go to Site Administration > Notifications.

Block Configuration
===================
Configuration is done per block instance. There are no global admin settings. Block settings are pretty self-explanatory:

General Settings
----------------
* Block title (leave blank for no title) _(Default: Course activities)_

Accordion Settings
------------------
* Use Bootstrap accordion (section titles must be on) _(Default: Yes)_
* Display collapse/expand icon for each section _(Default: Yes)_
* Display one section at a time (i.e. when opening a section, close all others) _(Default: No)_
* Display collapse/expand all _(Default: No)_
* On page load, what section(s) should be open _(Default: Current section only)_

Section Settings
----------------
* Display section titles _(Default: Yes)_
* Display section titles for sections with only labels even though Display Labels is off _(Default: Yes)_
* Link section titles to course page anchors _(Default: Yes)_
* Display section summaries _(Default: Yes)_
* Generate a spacer between sections _(Default: Yes)_
* Display divider between sections _(Default: Below section title)_

Activity Settings
-----------------
* Display activities _(Default: Yes)_
* Display activity icons _(Default: Yes)_
* Display activity completion toggles _(Default: Yes)_
* Display labels _(Default: Yes)_

Other Features
==============

Adding Additional Info
----------------------
If you have activity-specific notes, text, or HTML to include, you can specify an array of such content indexed by activity (module) ID numbers. As a simple example, say we know that activity modules with IDs 8 and 25 should include the word "New!" in red. We can override the Course Activities block renderer in our theme (let's say `mytheme`) in the file `/theme/mytheme/classes/output/block_course_activities_renderer.php` (remember to Purge Caches if this is a new file), like so:

```php
<?php

namespace theme_mytheme\output;

defined('MOODLE_INTERNAL') || die;

class block_course_activities_renderer extends \block_course_activities\output\renderer {

    public function render_main(\block_course_activities\output\main $main, $addl_info = []) {

        $new_html = '<div style="color: red;">New!</div>';
        $addl_info = [ '8' => $new_html, '25' => $new_html ];

        return parent::render_main($main, $addl_info);
    }

}
```

This feature can be very useful when used in combination with other plugins. As a more complex example, say I use the [Extended Info](https://github.com/nyanginator/moodle-local_extendedinfo) local plugin to store IDs of Vimeo videos on module pages in a field named "vimeo-id." I can use the stored IDs to retrieve video durations using the [Vimeo API](https://github.com/nyanginator/moodle-local_vimeoapi) and insert them into the block using the `$addl_info` parameter.

```php
<?php

namespace theme_mytheme\output;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/extendedinfo/lib.php');
require_once($CFG->dirroot . '/local/vimeoapi/lib.php');

class block_course_activities_renderer extends \block_course_activities\output\renderer {

    public function render_main(\block_course_activities\output\main $main, $addl_info = []) {
        $modinfo = get_fast_modinfo($this->page->course->id);
        $sections = $modinfo->get_sections();
    
        foreach ($sections as $sectionid => $sequence) {
            foreach ($sequence as $modid) {
                $extinfo = local_extendedinfo_get('module', $modid);
    
                if (isset($extinfo['vimeo-id'])) {
                    $videoid = $extinfo['vimeo-id'];
                    $addl_info[$modid] = '<div class="videoduration">' .
                        local_vimeoapi_get_video_duration($videoid) . '</div>';
                }
            }
        }
    
        return parent::render_main($main, $addl_info);
    }
}
```

Mark As Complete Button
-----------------------
To make it easier to mark off the current activity as complete, I added the ability to generate a Mark As Complete button using the block's renderer. You will need to add the button in your theme's layout code. With the button, students can conveniently mark the current activity without having to go through the entire list of activities in the block. Clicking the button will also toggle the activity completion icon in the block. Similarly, clicking an activity completion icon in the block will toggle this button (as well as course page completion icons).

On a side note, it is not necessary to display the Course Activities block at all to render and use the button. The button can be used by itself.

Let's use the Boost theme as an example.

Get the button code from the renderer. Include it in the template context in /theme/boost/layout/columns2.php:
```php
...

global $PAGE;

$block_course_activities_renderer = $PAGE->get_renderer('block_course_activities');
$mac_button = $block_course_activities_renderer->mark_as_completed_button();

$templatecontext['mac_button'] = '<div class="clearfix">' . $mac_button . '</div>';

...

echo $OUTPUT->render_from_template('theme_boost/columns2', $templatecontext);
```

Output the button in /theme/boost/templates/columns2.mustache:
```php
...

{{{ output.main_content }}}

{{{ mac_button }}} {{! Add button after main content }}

{{{ output.activity_navigation }}}

...

```

![Mark As Complete Button](https://raw.githubusercontent.com/nyanginator/moodle-block_course_activities/master/screenshots/mark-as-complete.jpg)

Notes
=====
* Remember that if you delete courses or activities, cron must run first before they are actually deleted from the database. They will appear as disabled greyed-out items in the block until this happens and the page is refreshed. If you have unexpected items appearing in the block, try running cron.

* If you drag-and-drop to reorder activities and sections, the block will not reflect such changes until you refresh the page.

* Adjust the "Where This Block Appears" section to control on what pages the block will appear. Add it from the highest context (Site Home) if you want the block to automatically appear in all courses (select "Display throughout the entire site"). You can restrict the block to appear on only course pages or only activity pages from within the course afterwards. If you add this block while in a specific course only, that instance will only be available to that course.

* If you need to add more custom content (i.e. custom links, predefined elements, a separate link back to the course, etc.), simply override the renderer as detailed above in the [Other Features](#other-features) section. Actually, a lot of features found in the other plugins (i.e. trim text, enumeration, auto-titles, etc.) were deliberately left out to keep this plugin simpler. Anything more complex than the stock features are left as an exercise for you.

* Moodle core's course renderer is used to render the activity completion icons. This is to reduce any redundant or extra work in handling every case scenario of the possible icons that can be displayed for activity completion status.

* Override the block's CSS in your own theme's CSS to fine-tune the look. Take a look at either `styles.css` or `less/course_activities.less` for an idea of what to override. For example, to change the highlight color of the current activity to yellow, add this to your theme's CSS:

  ```css
  .block_course_activities ul.activities li.hilited {
    background: yellow;
  }
  ```

* Be wary of CSS class conflicts. For example, `li.activity` has a conflict which make activity completion toggles unclickable, so `li.activityli` was used instead.

Uninstall
=========
Uninstall by going to Site Administration > Plugins > Blocks > Manage Blocks and using the Uninstall link for the Course Activities block plugin.

Contact
=======
Nicholas Yang\
http://nyanginator.wixsite.com/home
