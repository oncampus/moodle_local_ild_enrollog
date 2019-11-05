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
 *
 * @package     local_ild_enrollog
 * @copyright   2017 oncampus GmbH, <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');

require_once($CFG->libdir . '/tablelib.php');
require_once('select_date_form.php');

require_login();

$sort = optional_param('tsort', 'time', PARAM_RAW);
$sortdir = optional_param('tsortdir', 'ASC', PARAM_RAW);


$ffrom = strtotime(date('m/d/y', time()));
$fto = $ffrom + 60 * 60 * 24 - 1;

$frole = optional_param('f_role', 'all', PARAM_RAW);
$ffrom = optional_param('f_from', $ffrom, PARAM_RAW);
$fto = optional_param('f_to', $fto, PARAM_RAW);
$action = optional_param('action', '', PARAM_RAW);

$context = context_system::instance();


if (!has_capability('moodle/site:viewparticipants', $context)) {
    redirect($CFG->wwwroot);
}

$PAGE->set_context($context);
$PAGE->set_url('/local/ild_enrollog/view.php');

$table = new flexible_table(MODULE_TABLE);
$table->define_columns(array('name', 'course', 'role', 'modifier', 'time'));
$table->define_headers(array(get_string('name'), get_string('course'), get_string('role'), get_string('from'), get_string('time')));
$table->define_baseurl($CFG->wwwroot . '/local/ild_enrollog/view.php');

$table->set_attribute('class', 'admintable generaltable');
$table->sortable(true, 'time', SORT_ASC);
$table->setup();

$filterrole = 'all';
$filtercourse = 'all';
$roles = array('all', 'editingteacher', 'teacher', 'student');

$mform = new select_date_form(new moodle_url('/local/ild_enrollog/view.php?tsort=' . $sort));

if ($mform->is_cancelled()) {

    redirect(new moodle_url('/local/ild_enrollog/view.php'));
} else if ($fromform = $mform->get_data()) {
    $from = $fromform->from;
    $to = $fromform->to;
    $filterrole = $roles[$fromform->role];
    $to = $to + 60 * 60 * 24 - 1;

    $oldenrolments = $DB->get_records_sql('SELECT * FROM {user_preferences} WHERE name LIKE ? AND value >= ? AND value <= ?',
        array('local_ild_enrollog_user_enrolled_%', $from, $to));
    $enrolments = $DB->get_records_sql('SELECT * FROM {local_ild_enrollog} WHERE event = :event AND timecreated >= :from
        AND timecreated <= :to ', array('event' => 'user_enrolled', 'from' => $from, 'to' => $to));
} else if ($ffrom != '' and $fto != '') {
    $filterrole = $frole;
    $from = $ffrom;
    $to = $fto;
    $oldenrolments = $DB->get_records_sql('SELECT * FROM {user_preferences} WHERE name LIKE ? AND value >= ? AND value <= ?',
        array('local_ild_enrollog_user_enrolled_%', $from, $to));
    $enrolments = $DB->get_records_sql('SELECT * FROM {local_ild_enrollog} WHERE event = :event AND timecreated >= :from
        AND timecreated <= :to ', array('event' => 'user_enrolled', 'from' => $from, 'to' => $to));
} else {
    $oldenrolments = $DB->get_records_sql('SELECT * FROM {user_preferences} WHERE name LIKE ?',
        array('local_ild_enrollog_user_enrolled_%'));
    $enrolments = $DB->get_records_sql('SELECT * FROM {local_ild_enrollog} WHERE event = :event ',
        array('event' => 'user_enrolled'));
}

$all = 0;
$active = 0;
$deletedenrolments = 0;
$tabledata = array();

if (count($oldenrolments) > 0) {
    foreach ($oldenrolments as $oldenrolment) {
        $exploded = explode('_', $oldenrolment->name);
        $oe = new stdClass();
        $oe->enrolmentid = $exploded[5];
        $oe->courseid = $exploded[6];
        $oe->modifierid = $exploded[7];
        $oe->timecreated = $oldenrolment->value;
        $oe->userid = $oldenrolment->userid;
        if (count($exploded) == 9) {
            $oe->role = $exploded[8];
        }
        $oe->old = true;
        $enrolments[] = $oe;
    }
}

foreach ($enrolments as $enrolment) {

    $userenrolmentid = $enrolment->enrolmentid;
    $courseid = $enrolment->courseid;
    $modifierid = $enrolment->modifierid;
    $role = $enrolment->role;

    if ($user = $DB->get_record_sql('SELECT id, firstname, lastname, email, city FROM {user} WHERE id = :id',
        array('id' => $enrolment->userid))) {
        $fullname = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '">' . $user->firstname . ' ' .
            $user->lastname . ' (' . $user->email . ')</a>';
    } else {
        $fullname = get_string('deleted');
    }

    if ($muser = $DB->get_record_sql('SELECT id, firstname, lastname, email FROM {user} WHERE id = :id',
        array('id' => $modifierid))) {
        $mfullname = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $muser->id . '">' . $muser->firstname . ' ' .
            $muser->lastname . ' (' . $muser->email . ')</a>';
    } else {
        $mfullname = get_string('deleted');
    }

    if ($course = $DB->get_record_sql('SELECT id, fullname FROM {course} WHERE id = :id', array('id' => $courseid))) {
        $coursename = '<a href="' . $CFG->wwwroot . '/user/index.php?id=' . $course->id . '">' . $course->fullname . '</a>';
        $courseshortname = $course->fullname;
    } else {
        $coursename = get_string('deleted');
        $courseshortname = $coursename;
    }

    $rolename = get_string('deleted');
    $class = 'dimmed_text';
    $isdeleted = true;

    if ($userenrolment = $DB->get_field('user_enrolments', 'id', array('id' => $userenrolmentid,
        'userid' => $enrolment->userid))) {

        if (isset($role)) {
            $rolename = $role;
            $class = '';
            $isdeleted = false;
        } else {
            $rolecontextid = $DB->get_field('context', 'id', array('instanceid' => $courseid, 'contextlevel' => 50));
            $roleassignmentroleid = $DB->get_field('role_assignments', 'roleid', array('userid' => $enrolment->userid,
                'contextid' => $rolecontextid, 'modifierid' => $modifierid));
            if ($role = $DB->get_record('role', array('id' => $roleassignmentroleid))) {
                $rolename = $role->shortname;
                $class = '';
                $isdeleted = false;
            }
        }
    } else {

        if (isset($enrolment->old) and $enrolment->old == true) {

            if ($deleted = $DB->get_record_sql('SELECT id, name FROM {user_preferences} WHERE name LIKE ? AND value = ?',
                array('local_ild_enrollog_user_unenrolled_%', $userenrolmentid))) {
                $exploded2 = explode('_', $deleted->name);
                $rolename = get_string('deleted') . ' (' . date('d.m.Y - H:i', $exploded2[6]) . ')';
                if (isset($role)) {
                    $rolename = $role . ' - ' . $rolename;
                }
            }
        } else if ($deleted = $DB->get_record_sql('SELECT id, timecreated FROM {local_ild_enrollog} WHERE event = :event AND
                    enrolmentid = :enrolmentid ', array('event' => 'user_unenrolled', 'enrolmentid' => $userenrolmentid))) {
            $rolename = get_string('deleted') . ' (' . date('d.m.Y - H:i', $deleted->timecreated) . ')';
            if (isset($role)) {
                $rolename = $role . ' - ' . $rolename;
            }
        }
    }

    $data = new stdClass();
    $data->fullname = $fullname;
    $data->uname = $user->firstname . ' ' . $user->lastname;
    $data->city = $user->city;
    $data->coursename = $coursename;
    $data->courseshortname = $courseshortname;
    $data->courseid = $courseid;
    $data->rolename = $rolename;
    $data->mfullname = $mfullname;
    $data->mname = $muser->firstname . ' ' . $muser->lastname;
    $data->ttime = $enrolment->timecreated;
    $data->tclass = $class;
    $data->tsort = $sort;
    $data->sortdir = $sortdir;
    $data->deleted = $isdeleted;
    $tabledata[] = $data;

    if ($action == 'xls') {
        $data->tsort = 'course';
    }

}
function cmp_obj($a, $b) {
    $aa = $a;
    $bb = $b;

    if ($a->sortdir == 'DESC') {
        $aa = $b;
        $bb = $a;
    }

    if ($a->tsort == 'name') {
        return strcmp(strtolower($aa->uname), strtolower($bb->uname));
    }

    if ($a->tsort == 'course') {
        return strcmp(strtolower($aa->courseshortname), strtolower($bb->courseshortname));
    }

    if ($a->tsort == 'role') {
        return strcmp(strtolower($aa->rolename), strtolower($bb->rolename));
    }
    if ($a->tsort == 'modifier') {
        return strcmp(strtolower($aa->mname), strtolower($bb->mname));
    }

    if ($a->tsort == 'time') {
        return strcmp(strtolower($aa->ttime), strtolower($bb->ttime));
    }

}

if ($action == 'xls') {
    require_once($CFG->dirroot . '/lib/excellib.class.php');
    usort($tabledata, 'cmp_obj');

    $sheets = array();

    $workbook = new MoodleExcelWorkbook('ild_enrollog_workbook');
    $workbook->send('enrolments.xls');

    $sheets[0] = $workbook->add_worksheet('Einschreibungen');

    $columns = array('Name', 'Kurs', 'Rolle', 'Ort', 'angelegt von', 'Zeit');
    $col = 0;
    $currentrows = array();
    $deletedrows = array();

    foreach ($tabledata as $td) {
        if ($filterrole != 'all') {
            if (strpos($td->rolename, $filterrole) !== 0) {
                continue;
            }
        }
        if (!isset($sheets[$td->courseid])) {
            $currentrows[$td->courseid] = 0;
            $deletedrows[$td->courseid] = 0;
            $sheets[$td->courseid] = $workbook->add_worksheet($td->courseshortname . ' (' . $td->courseid . ')');
            $sheets[$td->courseid]->set_column(0, 0, 17);
            $sheets[$td->courseid]->set_column(1, 1, 34);
            $sheets[$td->courseid]->set_column(2, 2, 34);
            $sheets[$td->courseid]->set_column(3, 3, 34);
            $sheets[$td->courseid]->set_column(4, 4, 17);
            $sheets[$td->courseid]->set_column(5, 5, 18);
            foreach ($columns as $column) {
                $sheets[$td->courseid]->write_string($currentrows[$td->courseid], $col++, $column);
            }
            $currentrows[$td->courseid]++;
            $col = 0;
        }
        $sheets[$td->courseid]->write_string(($currentrows[$td->courseid] + 1), 0, $td->uname);
        $sheets[$td->courseid]->write_string(($currentrows[$td->courseid] + 1), 1, $td->courseshortname);
        $sheets[$td->courseid]->write_string(($currentrows[$td->courseid] + 1), 2, $td->rolename);
        $sheets[$td->courseid]->write_string(($currentrows[$td->courseid] + 1), 3, $td->city);
        $sheets[$td->courseid]->write_string(($currentrows[$td->courseid] + 1), 4, $td->mname);
        $sheets[$td->courseid]->write_string(($currentrows[$td->courseid] + 1), 5, date('d.m.Y - H:i', $td->ttime));
        $currentrows[$td->courseid]++;
        if ($td->deleted == true) {
            $deletedrows[$td->courseid]++;
        }
    }

    $format = new MoodleExcelFormat();
    $format->set_bold();
    $countparticipants = 0;
    $countactive = 0;
    $countdeleted = 0;
    $sheets[0]->set_column(1, 1, 60);
    $sheets[0]->set_column(2, 2, 15);
    $sheets[0]->write_string(1, 1, 'Kurseinschreibungen (' . $filterrole . ')', $format);
    if (isset($from)) {
        $fromto = date('d.m.Y', $from) . ' - ' . date('d.m.Y', $to);
        $sheets[0]->write_string(1, 2, $fromto, $format);
    }
    $rows = 3;
    $sheets[0]->write_string($rows, 1, get_string('course'));
    $sheets[0]->write_string($rows, 2, get_string('participants'));
    $sheets[0]->write_string($rows, 3, get_string('active'));
    $sheets[0]->write_string($rows, 4, get_string('deleted'));
    $rows++;
    $rows++;
    foreach ($currentrows as $key => $value) {
        try {
            $xlscourse = get_course($key);
        } catch (Exception $e) {
            $xlscourse = new stdClass();
            $xlscourse->fullname = get_string('deleted');
        }
        $sheets[0]->write_string($rows, 1, $xlscourse->fullname . ' (' . $key . ')');
        $sheets[0]->write_string($rows, 2, ($value - 1));
        $countparticipants = $countparticipants + ($value - 1);
        $sheets[0]->write_string($rows, 3, ($value - 1 - $deletedrows[$key]));
        $countactive = $countactive + ($value - 1 - $deletedrows[$key]);
        $sheets[0]->write_string($rows, 4, $deletedrows[$key]);
        $countdeleted = $countdeleted + $deletedrows[$key];
        $rows++;
    }
    $rows++;
    $sheets[0]->write_string($rows, 1, 'Gesamt:');
    $sheets[0]->write_string($rows, 2, $countparticipants);
    $sheets[0]->write_string($rows, 3, $countactive);
    $sheets[0]->write_string($rows, 4, $countdeleted);

    $workbook->close();
    exit;
} else {
    usort($tabledata, 'cmp_obj');
    foreach ($tabledata as $td) {
        if ($filterrole != 'all') {
            if (strpos($td->rolename, $filterrole) !== 0) {
                continue;
            }
        }

        $table->add_data(array(
            $td->fullname,
            $td->coursename,
            $td->rolename,
            $td->mfullname,
            date('d.m.Y - H:i', $td->ttime)), $td->tclass);

        if (strpos($td->rolename, 'Gelöscht') !== false or strpos($td->rolename, 'deleted') !== false) {
            $deletedenrolments++;
        }
        $all++;
    }
}

$active = $all - $deletedenrolments;

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('overview', 'local_ild_enrollog'));

$mform->display();

echo '<div>';


$urlparams = '';
if (isset($from) and isset($to)) {
    $urlparams = '&f_from=' . $from . '&f_to=' . $to;
    echo '<br />' . get_string('enrolments', 'local_ild_enrollog') . '(' . get_string('role') . ': ' . $filterrole . ') ' .
        get_string('from') . ' ' . date('d.m.Y - H:i', $from) . ' bis ' . date('d.m.Y - H:i', $to) . ': <b>' .
        $all . '</b> (' . get_string('active') . ': <b>' . $active . '</b>, ' . get_string('deleted') . ': <b>' .
        ($all - $active) . '</b>)';
} else {
    echo '<br />' . get_string('all') . ' ' . get_string('enrolments', 'local_ild_enrollog') . ': ' . $all . ' (' .
        get_string('active') . ': ' . $active . ', ' . get_string('deleted') . ': ' .
        ($all - $active) . ')';
}

echo '<br /><a href="' . $CFG->wwwroot . '/local/ild_enrollog/view.php?action=xls&f_role=' . $filterrole . $urlparams .
    '">download</a><br />';

$table->print_html();

echo '</div>';

echo $OUTPUT->footer();