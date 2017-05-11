<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
 
class select_date_form extends moodleform {
    //Add elements to form
    function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
		$mform->addElement('date_selector', 'from', get_string('from'));
		$mform->addElement('date_selector', 'to', get_string('to'));
		$mform->addElement('select', 'role', get_string('role'), array('all', 'editingteacher', 'teacher', 'student'));
		
		$this->add_action_buttons(true, get_string('filter'));
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

?>