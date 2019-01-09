<?php

class local_ild_enrollog_observer {
	
	public static function user_enrolled(\core\event\user_enrolment_created $event) {
		/*
		core\event\user_enrolment_created	user_enrolled	core	user_enrolment	created	
		core\event\user_enrolment_deleted	user_unenrolled	core	user_enrolment	deleted	
		core\event\user_enrolment_updated	user_enrol_modified	core	user_enrolment	updated
		
		
		core\event\user_enrolment_created Object
		(
			[data:protected] => Array
				(
					[eventname] => \core\event\user_enrolment_created
					[component] => core
					[action] => created
					[target] => user_enrolment
					[objecttable] => user_enrolments
					[objectid] => 356
					[crud] => c
					[edulevel] => 0
					[contextid] => 40
					[contextlevel] => 50
					[contextinstanceid] => 2
					[userid] => 3
					[courseid] => 2
					[relateduserid] => 3
					[anonymous] => 0
					[other] => Array
						(
							[enrol] => manual
						)

					[timecreated] => 1470843159
				)

			[logextra:protected] => 
			[context:protected] => context_course Object
				(
					[_id:protected] => 40
					[_contextlevel:protected] => 50
					[_instanceid:protected] => 2
					[_path:protected] => /1/3/40
					[_depth:protected] => 3
				)

			[triggered:core\event\base:private] => 1
			[dispatched:core\event\base:private] => 
			[restored:core\event\base:private] => 
			[recordsnapshots:core\event\base:private] => Array
				(
				)

		)
*/
		
		global $DB;

        if (!get_config('local_ild_enrollog', 'active')) {
            return;
        }

        $courses = get_config('local_ild_enrollog', 'courses');
        $courseids = array();
        if ($courses != '') {
            $courseids = explode(',', $courses);
            if (!in_array($event->courseid, $courseids)) {
                return;
            }
        }

		$user_enrolment = new stdClass();
		$user_enrolment->enrolmentid = $event->objectid;
		$user_enrolment->courseid = $event->courseid;
		$user_enrolment->modifierid = $event->userid;
		$user_enrolment->userid = $event->relateduserid;
		$user_enrolment->timecreated = $event->timecreated;
		$user_enrolment->event = 'user_enrolled';
		
		$record = new stdClass();
		$record->userid = $user_enrolment->userid;
		// local_ild_enrollog_user_enrolled_userenrolmentid_courseid_modifierid
		$record->name = 'local_ild_enrollog_user_enrolled_'.$user_enrolment->enrolmentid.'_'.$user_enrolment->courseid.'_'.$user_enrolment->modifierid;
		$record->value = $user_enrolment->timecreated;
		
		//$DB->insert_record('user_preferences', $record);
		$DB->insert_record('local_ild_enrollog', $user_enrolment);
		
		return;
	}

	public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
		global $USER, $DB;

        if (!get_config('local_ild_enrollog', 'active')) {
            return;
        }
		
		$record = new stdClass();
		$record->userid = $event->relateduserid;
		$record->name = 'local_ild_enrollog_user_unenrolled_'.$event->userid.'_'.time();
		$record->value = $event->objectid;
		//$DB->insert_record('user_preferences', $record);
		
		$user_enrolment = new stdClass();
		$user_enrolment->enrolmentid = $event->objectid;
		$user_enrolment->courseid = 0;//$event->courseid;
		$user_enrolment->modifierid = $event->userid;
		$user_enrolment->userid = $event->relateduserid;
		$user_enrolment->timecreated = time();
		$user_enrolment->event = 'user_unenrolled';
		
		$DB->insert_record('local_ild_enrollog', $user_enrolment);

		return;
	}
	
	public static function role_assigned(\core\event\role_assigned $event) {
		global $USER, $DB;

        if (!get_config('local_ild_enrollog', 'active')) {
            return;
        }
		
		$sql = 'select * from mdl_user_preferences where userid = ? and name like ?';
		
		$userid = $event->relateduserid;
		$name = 'local_ild_enrollog_user_enrolled_%_'.$event->courseid.'_'.$event->userid;
		$params = array($userid, $name);
		
		//$records = $DB->get_records_sql($sql, $params);
		
		$sql = 'SELECT * 
				  FROM {local_ild_enrollog} 
				 WHERE userid = :userid 
				   AND event = :event 
				   AND courseid = :courseid 
				   AND modifierid = :modifierid ';
		$params = array('userid' => $event->relateduserid,
						'event' => 'user_enrolled',
						'courseid' => $event->courseid,
						'modifierid' => $event->userid);
		$records = $DB->get_records_sql($sql, $params);
		
		
				   
		foreach ($records as $record) {
			
			//$exploded = explode('_', $record->name);
			//$enrolmentid = $exploded[5];
			$enrolmentid = $record->enrolmentid;
			
			$sql = 'select * from mdl_user_preferences where userid = ? and name like ? and value = ?';
			$name = 'local_ild_enrollog_user_unenrolled_'.$exploded[7].'_%'; // $record->modifierid
			$params = array($record->userid, $name, $enrolmentid);
			
			$sql = 'SELECT * 
					  FROM {local_ild_enrollog} 
					 WHERE userid = :userid 
					   AND event = :event 
					   AND modifierid = :modifierid 
					   AND enrolmentid = :enrolmentid ';
			$params = array('userid' => $record->userid,
						    'event' => 'user_unenrolled',
							'modifierid' => $record->modifierid,
							'enrolmentid' => $record->enrolmentid);
			
			$records_unenrolled = $DB->get_records_sql($sql, $params);
			
			if (count($records_unenrolled) == 0) {
				//print_object($record);
				// Zu diesen Einschreibungs-Logdatensätzen existieren noch keine Ausschreibungsdatensätze
				// und es ist auch noch keine Rolle angegeben
				// rolle ermitteln und update des datensatzes
				$role = $DB->get_record('role', array('id' => $event->objectid));
				$role_shortname = $role->shortname;
				//$record->name = $record->name.'_'.$role_shortname;
				$record->role = $role_shortname;
				//$DB->update_record('user_preferences', $record);
				$DB->update_record('local_ild_enrollog', $record);
			}
		}
		
		return;
	}
}

?>