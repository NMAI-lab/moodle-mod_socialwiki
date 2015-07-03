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
 * This file contains all necessary code to view a discussion page
 *
 * @package mod-wiki-2.0
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Jordi Piguillem
 * @author Marc Alier
 * @author David Jimenez
 * @author Josep Arus
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/lib/formslib.php');

class mod_socialwiki_comments_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $current = $this->_customdata['current'];
        $commentoptions = $this->_customdata['commentoptions'];

        // Visible elements.
        $mform->addElement('editor', 'entrycomment_editor', get_string('comment', 'glossary'), null, $commentoptions);
        $mform->addRule('entrycomment_editor', get_string('required'), 'required', null, 'client');
        $mform->setType('entrycomment_editor', PARAM_RAW); // Processed by trust text or cleaned before the display.

        // Hidden optional params.
        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_ALPHAEXT);

        // Buttons.
        $this->add_action_buttons(false);

        $this->set_data($current);
    }

    public function edit_definition($current, $commentoptions) {
        $this->set_data($current);
        $this->set_data($commentoptions);
    }
}

