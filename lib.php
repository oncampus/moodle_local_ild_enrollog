<?php

function local_ild_enrollog_cron() {
	// Einschreibungen werden nicht durch den cronjob geloggt 
	// sondern durch das event core\event\user_enrolment_created 
	// getriggert	
}

function local_ild_enrollog_extend_settings_navigation($settingsnav, $context) {
	global $USER;
	// TODO: devmode deaktivieren
	if ($USER->username != 'riegerj') {
		//return;
	}

    if (!has_capability('moodle/site:config', context_system::instance())) {
        return;
    }
 
	$siteadministration = false;
	$settingnode = '';
 
    if ($settingnode = $settingsnav->find('siteadministration', navigation_node::TYPE_SITE_ADMIN)) {
        $siteadministration = true;
		
		
    }
	else if ($settingnode = $settingsnav->find('root_node', navigation_node::TYPE_SITE_ADMIN)) {
		$siteadministration = true;
	}
	
	if ($siteadministration == true) {
		$strfoo = get_string('node_name', 'local_ild_enrollog');
        $url = new moodle_url('/local/ild_enrollog/view.php');
        $foonode = navigation_node::create(
            $strfoo,
            $url,
            navigation_node::NODETYPE_LEAF,
            'ild_enrollog',
            'ild_enrollog',
            new pix_icon('i/settings', $strfoo)
        );
        
		//$foonode->make_active();

        $settingnode->add_node($foonode);
	}
}

?>