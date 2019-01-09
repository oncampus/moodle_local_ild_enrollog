<?php

function xmldb_local_ild_enrollog_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
	
	if ($oldversion < 2019010800) {

        // Define table local_ild_enrollog to be created.
        $table = new xmldb_table('local_ild_enrollog');

        // Adding fields to table local_ild_enrollog.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('event', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enrolmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modifierid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('role', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ild_enrollog.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_ild_enrollog.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ild_enrollog savepoint reached.
        upgrade_plugin_savepoint(true, 2019010800, 'local', 'ild_enrollog');
    }

    return true;
}