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
 * @file Params page for auto group enrollment as defined by Comete
 *
 * @package    tool_groupautoenrol
 * @copyright  2016 Pascal
 * @author     Pascal M - https://github.com/pascal-my
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once('./manage_auto_group_enrol_form.php');

$id = required_param('id', PARAM_INT);
$url = new moodle_url('/admin/tool/groupautoenrol/manage_auto_group_enrol.php', array('id' => $id) );
$PAGE->set_url($url);
// TODO we need to gracefully shutdown if course not found.
$course   = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context  = context_course::instance($course->id);

require_login($course);

$coursecontext  = context_course::instance($course->id);
require_capability('moodle/course:update', $coursecontext);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($course->fullname);

$form = new manage_auto_group_enrol_form($url, array('course' => $course, 'page' => $PAGE, 'context' => $context));

if ($form->is_cancelled()) {
    redirect( new moodle_url("$CFG->wwwroot/course/view.php", array('id' => $course->id) ) );
} else if ( $data = $form->get_data() ) {
    // Checkbox cleaning : if checkbox are unchecked, the value is empty or null, this is not compatible with "tinyint" in database.
    if (!isset($data->enable_enrol) || ($data->enable_enrol == null) || ($data->enable_enrol == "")) {
        $data->enable_enrol = 0;
    }
    if (!isset($data->use_groupslist) || ($data->use_groupslist == null) || ($data->use_groupslist == "")) {
        $data->use_groupslist = 0;
    }

    $groupautoenrol = new stdClass();
    $groupautoenrol->courseid = $course->id;
    $groupautoenrol->enable_enrol = $data->enable_enrol;
    $groupautoenrol->use_groupslist = $data->use_groupslist;
    if (isset($data->groupslist)) { // Could be not set.
        $groupautoenrol->groupslist = implode(",", $data->groupslist);
    }

    $record = $DB->get_record('tool_groupautoenrol', array('courseid' => $course->id), 'id');
    if (!$record) {
        $DB->insert_record('tool_groupautoenrol', $groupautoenrol, false);
    } else {
        $groupautoenrol->id = $record->id;
        $DB->update_record('tool_groupautoenrol', $groupautoenrol);
    }
    redirect( new moodle_url("$CFG->wwwroot/admin/tool/groupautoenrol/manage_auto_group_enrol.php", array('id' => $course->id) ));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('auto_group_form_page_title', 'tool_groupautoenrol'));
$form->display();
echo $OUTPUT->footer();
