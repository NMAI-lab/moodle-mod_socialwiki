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
 * This file keeps track of upgrades to the socialwiki module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * @package mod_socialwiki
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Jordi Piguillem
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */

/**
 * SocialWiki upgrade function.
 *
 * @param int $oldversion The old version.
 * @return true
 */
function xmldb_socialwiki_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add user views table.
    $revision = 2014021100;
    if ($oldversion < $revision) {
        $table = new xmldb_table('socialwiki_user_views');

        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field("userid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
        $table->add_field("pageid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
        $table->add_field("viewcount", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, '0');
        $table->add_field("latestview", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userkey', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('pagekey', XMLDB_KEY_FOREIGN, array('pageid'), 'socialwiki_pages', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, $revision, 'socialwiki'); // Socialwiki savepoint reached.
    }

    // Remove locks, synonyms and links tables.
    $revision = 2015070100;
    if ($oldversion < 2015070100) {
        $table = new xmldb_table('socialwiki_locks');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('socialwiki_synonyms');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('socialwiki_links');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2015070100, 'socialwiki'); // Socialwiki savepoint reached.
    }

    return true;
}