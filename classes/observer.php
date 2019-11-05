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
 * @category    event
 * @copyright   2017 oncampus GmbH, <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

class local_ild_enrollog_observer
{

    public static function user_enrolled(\core\event\user_enrolment_created $event) {

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

        $userenrolment = new stdClass();
        $userenrolment->enrolmentid = $event->objectid;
        $userenrolment->courseid = $event->courseid;
        $userenrolment->modifierid = $event->userid;
        $userenrolment->userid = $event->relateduserid;
        $userenrolment->timecreated = $event->timecreated;
        $userenrolment->event = 'user_enrolled';

        $record = new stdClass();
        $record->userid = $userenrolment->userid;
        $record->name = 'local_ild_enrollog_user_enrolled_' . $userenrolment->enrolmentid . '_' . $userenrolment->courseid
            . '_' . $userenrolment->modifierid;
        $record->value = $userenrolment->timecreated;

        $DB->insert_record('local_ild_enrollog', $userenrolment);

        return;
    }

    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
        global $USER, $DB;

        if (!get_config('local_ild_enrollog', 'active')) {
            return;
        }

        $record = new stdClass();
        $record->userid = $event->relateduserid;
        $record->name = 'local_ild_enrollog_user_unenrolled_' . $event->userid . '_' . time();
        $record->value = $event->objectid;

        $userenrolment = new stdClass();
        $userenrolment->enrolmentid = $event->objectid;
        $userenrolment->courseid = 0;
        $userenrolment->modifierid = $event->userid;
        $userenrolment->userid = $event->relateduserid;
        $userenrolment->timecreated = time();
        $userenrolment->event = 'user_unenrolled';

        $DB->insert_record('local_ild_enrollog', $userenrolment);

        return;
    }

    public static function role_assigned(\core\event\role_assigned $event) {
        global $USER, $DB;

        if (!get_config('local_ild_enrollog', 'active')) {
            return;
        }

        $sql = 'select * from mdl_user_preferences where userid = ? and name like ?';

        $userid = $event->relateduserid;
        $name = 'local_ild_enrollog_user_enrolled_%_' . $event->courseid . '_' . $event->userid;
        $params = array($userid, $name);

        $sql = 'SELECT *
				  FROM {local_ild_enrollog}
				 WHERE userid = :userid
				   AND event = :event
				   AND courseid = :courseid
				   AND modifierid = :modifierid';
        $params = array('userid' => $event->relateduserid,
            'event' => 'user_enrolled',
            'courseid' => $event->courseid,
            'modifierid' => $event->userid);
        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {

            $enrolmentid = $record->enrolmentid;

            $sql = 'select * from mdl_user_preferences where userid = ? and name like ? and value = ?';
            $name = 'local_ild_enrollog_user_unenrolled_' . $exploded[7] . '_%';
            $params = array($record->userid, $name, $enrolmentid);

            $sql = 'SELECT *
					  FROM {local_ild_enrollog}
					 WHERE userid = :userid
					   AND event = :event
					   AND modifierid = :modifierid
					   AND enrolmentid = :enrolmentid';
            $params = array('userid' => $record->userid,
                'event' => 'user_unenrolled',
                'modifierid' => $record->modifierid,
                'enrolmentid' => $record->enrolmentid);

            $recordsunenrolled = $DB->get_records_sql($sql, $params);

            if (count($recordsunenrolled) == 0) {
                $role = $DB->get_record('role', array('id' => $event->objectid));
                $roleshortname = $role->shortname;
                $record->role = $roleshortname;
                $DB->update_record('local_ild_enrollog', $record);
            }
        }

        return;
    }
}