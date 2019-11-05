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
 * @category    form
 * @copyright   2017 oncampus GmbH, <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class select_date_form extends moodleform
{
    // Add elements to form.
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('date_selector', 'from', get_string('from'));
        $mform->addElement('date_selector', 'to', get_string('to'));
        $mform->addElement('select', 'role', get_string('role'), array('all', 'editingteacher', 'teacher', 'student'));

        $this->add_action_buttons(true, get_string('filter'));
    }
}