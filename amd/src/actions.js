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
 * @module     block_course_activities/actions
 * @package    block_course_activities
 * @copyright  Nicholas Yang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/ajax', 'core/templates'], function($, str, ajax, templates) {

    return {
        init: function(args) {
            // Handle clicking of collapse/expand all
            var accordioncontent = $('.block_course_activities .accordion-content');
            var expandtoggle = $('.block_course_activities .expandtoggle');
            $('.block_course_activities #accordion-collapse-all').on('click', function(e) {
                $(accordioncontent).removeClass('show');
                $(expandtoggle).attr('aria-expanded', 'false');
            });

            $('.block_course_activities #accordion-expand-all').on('click', function(e) {
                $(accordioncontent).addClass('show');
                $(expandtoggle).attr('aria-expanded', 'true');
            });

            // Activity completion toggles. This jQuery version adapted from
            // YUI version in /course/completion.js.
            $("form.togglecompletion").each(function() {
                $(this).on('click', toggle); // "submit" does not work right
            });
                
            function toggle(e) {
                e.preventDefault();

                var form = this;
                var cmid = 0;
                var completionstate = 0;
                var state = null;
                var image = null;
                var module = null;
                var sesskey = null;;

                var inputs = $('input', this);
                for (var i = 0; i < inputs.length; i++) {
                    switch (inputs[i].name) {
                        case 'id':
                            cmid = inputs[i].value;
                            break;
                        case 'completionstate':
                            completionstate = inputs[i].value;
                            state = inputs[i];
                            break;
                        case 'modulename':
                            module = inputs[i];
                            break;
                        case 'sesskey':
                            sesskey = inputs[i].value;
                            break;
                    }
                }

                image = $('button .icon', form);

                // Check for other instances of this module's completion box.
                // They should all stay in sync when toggled.
                allimages = $('form.togglecompletion input[name="id"][value="'+cmid+'"]').parent().find('button .icon');
                allbuttontextspans = $('form.togglecompletion input[name="id"][value="'+cmid+'"]').parent().find('button .mac-btn-text');
                allstates = $('form.togglecompletion input[name="id"][value="'+cmid+'"]').parent().find('input[name="completionstate"]');

                // Show some feedback for loading
                var ajaxspinner = $('<div class="ajaxworking"></div>');
                $(form).append(ajaxspinner);

                // Don't specify "dataType: json" or you will get a status of
                // 'parsererror'. This is because it would expect the response
                // to be JSON, but it's just an 'OK'.
                $.ajax({
                    url: args['wwwroot'] + '/course/togglecompletion.php',
                    data: { 'id':cmid, 'completionstate':completionstate, 'fromajax':1, 'sesskey':sesskey },
                    type: 'post',
                    success: function(data, status, jqXHR) {
                        handle_success(data);
                    },
                    error: function(jqXHR, status, err) {
                        handle_failure(status);
                    }
                });

                function handle_success(data) {
                    $('#completion_dynamic_change').val(1);
                    
                    if (data != 'OK') {
                        alert('An error occurred when attempting to save your tick mark.\n\n('+data.responseText+')');
                    }
                    else {
                        var current = $(state).val();
                        var modulename = $(module).val();
                        var iconkey;
                        var button = $(image).closest('button');

                        if (current == 1) {
                            var altstrPresent = str.get_string('completion-alt-manual-y', 'completion', modulename);
                            iconkey = 'i/completion-manual-y';
                            $(allstates).each(function() {
                                $(this).val(0);
                            });
                        }
                        else {
                            var altstrPresent = str.get_string('completion-alt-manual-n', 'completion', modulename);
                            iconkey = 'i/completion-manual-n';
                            $(allstates).each(function() {
                                $(this).val(1);
                            });
                        }

                        // Update icons of all form.togglecompletion instances
                        $.when(altstrPresent).done(function(localizedString) {
                            var altstr = localizedString;
                            $(button).attr('title', altstr);

                            templates.renderPix(iconkey, 'core', altstr).then(function(html) {
                                templates.replaceNode($(allimages), html, '');
                            });
                        });

                        // Update Mark As Complete button, if present
                        $(allbuttontextspans).each(function() {
                            var buttontextspan = this;
                            var buttonTextPresent = str.get_string( (current == 1 ? 'completed' : 'notcompleted'), 'completion' );

                            $.when(buttonTextPresent).done(function(localizedString) {
                                var buttonText = localizedString;
                                $(buttontextspan).text(buttonText);
                            });
                        });
                    }

                    $(ajaxspinner).remove();
                }

                function handle_failure(status) {
                    alert('An error occurred when attempting to save your tick mark ('+status+').');
                    $(ajaxspinner).remove();
                }
            }
        }
    };
});
