<?php

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once('select_date_form.php');
require_login();

$sort = optional_param('tsort', 'time', PARAM_RAW);
$sortdir = optional_param('tsortdir', 'ASC', PARAM_RAW);

$f_role = optional_param('f_role', 'all', PARAM_RAW);
$f_from = optional_param('f_from', '', PARAM_RAW);
$f_to = optional_param('f_to', '', PARAM_RAW);
$action = optional_param('action', '', PARAM_RAW);

$context = context_system::instance();

//if (!has_capability('moodle/site:config', $context)) {
if (!has_capability('moodle/site:viewparticipants', $context)) {
	redirect($CFG->wwwroot);
}

$PAGE->set_context($context);
$PAGE->set_url('/local/ild_enrollog/view.php');

$table = new flexible_table(MODULE_TABLE);
$table->define_columns(array('name', 'course', 'role', 'modifier', 'time'));
$table->define_headers(array(get_string('name'), get_string('course'), get_string('role'), get_string('from'), get_string('time')));
$table->define_baseurl($CFG->wwwroot.'/local/ild_enrollog/view.php');
//$table->set_attribute('id', 'modules');
$table->set_attribute('class', 'admintable generaltable');
$table->sortable(true, 'time', SORT_ASC);
$table->setup();

$filterrole = 'all';
$filtercourse = 'all';
$roles = array('all', 'editingteacher', 'teacher', 'student');

$mform = new select_date_form(new moodle_url('/local/ild_enrollog/view.php?tsort='.$sort));

if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
	redirect(new moodle_url('/local/ild_enrollog/view.php'));
}
else if ($fromform = $mform->get_data()) {
	$from = $fromform->from;
	$to = $fromform->to;
	$filterrole = $roles[$fromform->role];
	//echo date('H:i - d.m.Y', $from);
	$to = $to + 60 * 60 * 24 - 1;
	//echo ' / '.date('H:i:s - d.m.Y', $to);
	
	$old_enrolments = $DB->get_records_sql('SELECT * FROM {user_preferences} WHERE name LIKE ? AND value >= ? AND value <= ?', array('local_ild_enrollog_user_enrolled_%', $from, $to));
	$enrolments = $DB->get_records_sql('SELECT * FROM {local_ild_enrollog} WHERE event = :event AND timecreated >= :from AND timecreated <= :to ', 
										array('event' => 'user_enrolled', 'from' => $from, 'to' => $to));
} 
else if ($f_from != '' and $f_to != '') {
	$filterrole = $f_role;
	$from = $f_from;
	$to = $f_to;
	$old_enrolments = $DB->get_records_sql('SELECT * FROM {user_preferences} WHERE name LIKE ? AND value >= ? AND value <= ?', array('local_ild_enrollog_user_enrolled_%', $from, $to));
	$enrolments = $DB->get_records_sql('SELECT * FROM {local_ild_enrollog} WHERE event = :event AND timecreated >= :from AND timecreated <= :to ', 
										array('event' => 'user_enrolled', 'from' => $from, 'to' => $to));
}
else {
	// TODO: limit (Seitenweise anzeigen)
	$old_enrolments = $DB->get_records_sql('SELECT * FROM {user_preferences} WHERE name LIKE ?', array('local_ild_enrollog_user_enrolled_%'));
	$enrolments = $DB->get_records_sql('SELECT * FROM {local_ild_enrollog} WHERE event = :event ', 
										array('event' => 'user_enrolled'));
}

$all = 0;
$active = 0;
$deleted_enrolments = 0;
$tabledata = array();

if (count($old_enrolments) > 0) {
	foreach($old_enrolments as $old_enrolment) {
		$exploded = explode('_', $old_enrolment->name);
		$oe = new stdClass();
		$oe->enrolmentid = $exploded[5];
		$oe->courseid = $exploded[6];
		$oe->modifierid = $exploded[7];
		$oe->timecreated = $old_enrolment->value;
		$oe->userid = $old_enrolment->userid;
		if (count($exploded) == 9) {
			 $oe->role = $exploded[8];
		}
		$oe->old = true;
		$enrolments[] = $oe;
	}
}

foreach ($enrolments as $enrolment) {
	//$all++;
	/*
	$exploded = explode('_', $enrolment->name);
	$userenrolmentid = $exploded[5];
	$courseid = $exploded[6];
	$modifierid = $exploded[7];
	*/
	$userenrolmentid = $enrolment->enrolmentid;
	$courseid = $enrolment->courseid;
	$modifierid = $enrolment->modifierid;
	$role = $enrolment->role;
	//$enrolment->timecreated
	//$enrolment->userid
	
	//if ($user = $DB->get_record('user', array('id' => $enrolment->userid))) {
	if ($user = $DB->get_record_sql('SELECT id, firstname, lastname, email, city FROM {user} WHERE id = :id', array('id' => $enrolment->userid))) {
		$fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'">'.$user->firstname.' '.$user->lastname.' ('.$user->email.')</a>';
	}
	else {
		$fullname = get_string('deleted');
	}
	
	//if ($muser = $DB->get_record('user', array('id' => $modifierid))) {
	if ($muser = $DB->get_record_sql('SELECT id, firstname, lastname, email FROM {user} WHERE id = :id', array('id' => $modifierid))) {
		$mfullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$muser->id.'">'.$muser->firstname.' '.$muser->lastname.' ('.$muser->email.')</a>';
	}
	else {
		$mfullname = get_string('deleted');
	}
	
	//if ($course = $DB->get_record('course', array('id' => $courseid))) {
	if ($course = $DB->get_record_sql('SELECT id, fullname FROM {course} WHERE id = :id', array('id' => $courseid))) {
		$coursename = '<a href="'.$CFG->wwwroot.'/user/index.php?id='.$course->id.'">'.$course->fullname.'</a>';
		$courseshortname = $course->fullname;
	}
	else {
		$coursename = get_string('deleted');
		$courseshortname = $coursename;
	}
	
	$rolename = get_string('deleted');
	$class =   'dimmed_text';
	$is_deleted = true;
	// Rolle aus local_ild_enrollog holen falls dort datensatz vorhanden ist. Ansonsten wie gehabt...
	//if ($userenrolment = $DB->get_record('user_enrolments', array('id' => $userenrolmentid, 'userid' => $enrolment->userid))) {
		
	if ($userenrolment = $DB->get_field('user_enrolments', 'id', array('id' => $userenrolmentid, 'userid' => $enrolment->userid))) {
		
		if (isset($role)) {
			$rolename = $role;
			$class =   '';
			$is_deleted = false;
		}
		else {
			//$rolecontext = $DB->get_record('context', array('instanceid' => $courseid, 'contextlevel' => 50)); //CONTEXT_COURSE
			$rolecontextid = $DB->get_field('context', 'id', array('instanceid' => $courseid, 'contextlevel' => 50)); //CONTEXT_COURSE
			//$roleassignment = $DB->get_record('role_assignments', array('userid' => $enrolment->userid, 'contextid' => $rolecontext->id, 'modifierid' => $modifierid));
			$roleassignmentroleid = $DB->get_field('role_assignments', 'roleid', array('userid' => $enrolment->userid, 'contextid' => $rolecontextid, 'modifierid' => $modifierid));
			if ($role = $DB->get_record('role', array('id' => $roleassignmentroleid))) {
				$rolename = $role->shortname;
				$class =   '';
				$is_deleted = false;
			}
		}
	}
	else {
		//if ($deleted = $DB->get_record_sql('SELECT * FROM {user_preferences} WHERE name LIKE ? AND value = ?', array('local_ild_enrollog_user_unenrolled_%', $userenrolmentid))) {
		// old_enrolments
		
		if (isset($enrolment->old) and $enrolment->old == true) {
			
			if ($deleted = $DB->get_record_sql('SELECT id, name FROM {user_preferences} WHERE name LIKE ? AND value = ?', array('local_ild_enrollog_user_unenrolled_%', $userenrolmentid))) {
				$exploded2 = explode('_', $deleted->name);
				$rolename = get_string('deleted').' ('.date('d.m.Y - H:i', $exploded2[6]).')';
				if (isset($role)) {
					$rolename = $role.' - '.$rolename;
				}
			}
		}
		elseif ($deleted = $DB->get_record_sql('SELECT id, timecreated FROM {local_ild_enrollog} WHERE event = :event AND enrolmentid = :enrolmentid ', array('event' => 'user_unenrolled', 'enrolmentid' => $userenrolmentid))) {
			$rolename = get_string('deleted').' ('.date('d.m.Y - H:i', $deleted->timecreated).')';
			if (isset($role)) {
				$rolename = $role.' - '.$rolename;
			}
			//$rolename = get_string('deleted').' ('.$exploded2[5].')';
		}
		else {
			//$rolename = '*';
		}
	}

	$data = new stdClass();
	$data->fullname = $fullname;
	$data->uname = $user->firstname.' '.$user->lastname;
	$data->city = $user->city;
	$data->coursename = $coursename;
	$data->courseshortname = $courseshortname;
	$data->courseid = $courseid;
	$data->rolename = $rolename;//.' '.$userenrolmentid;
	$data->mfullname = $mfullname;
	$data->mname = $muser->firstname.' '.$muser->lastname;
	$data->ttime = $enrolment->timecreated;
	$data->tclass = $class;
	$data->tsort = $sort;
	$data->sortdir = $sortdir;
	$data->deleted = $is_deleted;
	$tabledata[] = $data;
	
	if ($action == 'xls') {
		$data->tsort = 'course';
	}
	
}
//echo $sort;
function cmp_obj($a, $b) {
	$aa = $a;
	$bb = $b;
	if ($a->sortdir == 'DESC') {
		$aa = $b;
		$bb = $a;
	}
	if ($a->tsort == 'name') 		return strcmp(strtolower($aa->uname),   strtolower($bb->uname));
	if ($a->tsort == 'course') 		return strcmp(strtolower($aa->courseshortname), strtolower($bb->courseshortname));
	if ($a->tsort == 'role') 		return strcmp(strtolower($aa->rolename),   strtolower($bb->rolename));
	if ($a->tsort == 'modifier') 	return strcmp(strtolower($aa->mname),  strtolower($bb->mname));
	if ($a->tsort == 'time') 		return strcmp(strtolower($aa->ttime),      strtolower($bb->ttime));
}

if ($action == 'xls') {
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	usort($tabledata, 'cmp_obj');
	
	$sheets = array();

	$workbook = new MoodleExcelWorkbook('ild_enrollog_workbook');
	$workbook->send('enrolments.xls');
	
	// Deckblatt
	$sheets[0] = $workbook->add_worksheet('Einschreibungen');// es gibt keinen Kurs mit der id 0
	
	$columns = array('Name', 'Kurs', 'Rolle', 'Ort', 'angelegt von', 'Zeit');
	$col = 0;
	$current_rows = array();
	$deleted_rows = array(); 
	
	foreach ($tabledata as $td) {
		if ($filterrole != 'all') {
			if (strpos($td->rolename, $filterrole) !== 0) {
				continue;
			}
		}
		if (!isset($sheets[$td->courseid])) { // Überschrift
			$current_rows[$td->courseid] = 0;
			$deleted_rows[$td->courseid] = 0;
			$sheets[$td->courseid] = $workbook->add_worksheet($td->courseshortname.' ('.$td->courseid.')');
			$sheets[$td->courseid]->set_column(0, 0, 17); // Breite ändern
			$sheets[$td->courseid]->set_column(1, 1, 34);
			$sheets[$td->courseid]->set_column(2, 2, 34);
			$sheets[$td->courseid]->set_column(3, 3, 34);
			$sheets[$td->courseid]->set_column(4, 4, 17);
			$sheets[$td->courseid]->set_column(5, 5, 18);
			foreach ($columns as $column) {
				$sheets[$td->courseid]->write_string($current_rows[$td->courseid], $col++, $column);
			}
			$current_rows[$td->courseid]++;
			$col = 0;
		}
		$sheets[$td->courseid]->write_string(($current_rows[$td->courseid] + 1), 0, $td->uname);
		$sheets[$td->courseid]->write_string(($current_rows[$td->courseid] + 1), 1, $td->courseshortname);
		$sheets[$td->courseid]->write_string(($current_rows[$td->courseid] + 1), 2, $td->rolename);
		$sheets[$td->courseid]->write_string(($current_rows[$td->courseid] + 1), 3, $td->city);	// Ort	
		$sheets[$td->courseid]->write_string(($current_rows[$td->courseid] + 1), 4, $td->mname);
		$sheets[$td->courseid]->write_string(($current_rows[$td->courseid] + 1), 5, date('d.m.Y - H:i', $td->ttime));
		$current_rows[$td->courseid]++;
		if ($td->deleted == true) {
			$deleted_rows[$td->courseid]++;
		}
	}
	
	// Deckblatt
	$format = new MoodleExcelFormat();
	$format->set_bold();
	$count_participants = 0;
	$count_active = 0;
	$count_deleted = 0;
	//$sheets[0]->apply_row_format(1, $format);
	$sheets[0]->set_column(1, 1, 60); // Breite ändern
	$sheets[0]->set_column(2, 2, 15); // Breite ändern
	$sheets[0]->write_string(1, 1, 'Kurseinschreibungen ('.$filterrole.')', $format);
	if (isset($from)) {
		$fromto = date('d.m.Y', $from).' - '.date('d.m.Y', $to);
		$sheets[0]->write_string(1, 2, $fromto, $format);
	}
	$rows = 3;
	$sheets[0]->write_string($rows, 1, get_string('course'));
	$sheets[0]->write_string($rows, 2, get_string('participants'));
	$sheets[0]->write_string($rows, 3, get_string('active'));
	$sheets[0]->write_string($rows, 4, get_string('deleted'));
	$rows++;
	$rows++;
	foreach ($current_rows as $key => $value) {
		try{
			$xlscourse = get_course($key);
		}
		catch (Exception $e) {
			$xlscourse = new stdClass();
			$xlscourse->fullname = get_string('deleted');
		}
		$sheets[0]->write_string($rows, 1, $xlscourse->fullname.' ('.$key.')');
		$sheets[0]->write_string($rows, 2, ($value - 1));
		$count_participants = $count_participants + ($value - 1);
		$sheets[0]->write_string($rows, 3, ($value - 1 - $deleted_rows[$key]));
		$count_active = $count_active + ($value - 1 - $deleted_rows[$key]);
		$sheets[0]->write_string($rows, 4, $deleted_rows[$key]);
		$count_deleted = $count_deleted + $deleted_rows[$key];
		$rows++;
	}
	$rows++;
	$sheets[0]->write_string($rows, 1, 'Gesamt:');
	$sheets[0]->write_string($rows, 2, $count_participants);
	$sheets[0]->write_string($rows, 3, $count_active);
	$sheets[0]->write_string($rows, 4, $count_deleted);
	// Deckblatt Ende
	
	$workbook->close();
	exit;
}
else {
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
			$deleted_enrolments++;
		}
		$all++;
	}
}

$active = $all - $deleted_enrolments;
//*/
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('overview', 'local_ild_enrollog'));

$mform->display();

echo '<div>';

// Anzahl anzeigen
$url_params = '';
if (isset($from) and isset($to)) {
	$url_params = '&f_from='.$from.'&f_to='.$to;
	echo '<br />'.get_string('enrolments', 'local_ild_enrollog').'('.get_string('role').': '.$filterrole.') '.get_string('from').' '.date('d.m.Y - H:i', $from).' bis '.date('d.m.Y - H:i', $to).': <b>'.$all.'</b> ('.get_string('active').': <b>'.$active.'</b>, '.get_string('deleted').': <b>'.($all - $active).'</b>)';
}
else {
	echo '<br />'.get_string('all').' '.get_string('enrolments', 'local_ild_enrollog').': '.$all.' ('.get_string('active').': '.$active.', '.get_string('deleted').': '.($all - $active).')';
}

echo '<br /><a href="'.$CFG->wwwroot.'/local/ild_enrollog/view.php?action=xls&f_role='.$filterrole.$url_params.'">download</a><br />';

$table->print_html();

echo '</div>';

echo $OUTPUT->footer();

?>