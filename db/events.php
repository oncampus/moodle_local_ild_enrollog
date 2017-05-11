<?php

$observers = array (
    array (
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => 'local_ild_enrollog_observer::user_enrolled',
    ),
	array (
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => 'local_ild_enrollog_observer::user_unenrolled',
    ),
	array (
        'eventname' => '\core\event\role_assigned',
        'callback' => 'local_ild_enrollog_observer::role_assigned',
    )
);

?>