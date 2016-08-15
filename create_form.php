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
 * This file contains all necessary code to define and process an edit form
 *
 * @package   mod_socialwiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require($CFG->libdir . '/formslib.php');

/**
 * Form used for creating a new page (first version).
 *
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_socialwiki_create_form extends moodleform {

    /**
     * Build the full form.
     */
    protected function definition() {
        $form = $this->_form;

        $formats = $this->_customdata['formats'];
        $defaultformat = $this->_customdata['defaultformat'];
        $forceformat = $this->_customdata['forceformat'];

        $form->addElement('header', 'general', get_string('newpagehdr', 'socialwiki'));

        $form->addElement('text', 'pagetitle', get_string('newpagetitle', 'socialwiki'));
        $form->setType('pagetitle', PARAM_TEXT);
        $form->addRule('pagetitle', get_string('required'), 'required', null, 'client');

        if ($forceformat) {
            $form->addElement('hidden', 'pageformat', $defaultformat);
        } else {
            $form->addElement('static', 'format', get_string('format', 'socialwiki'));
            $form->addHelpButton('format', 'format', 'socialwiki');
            foreach ($formats as $format) {
                if ($format == $defaultformat) {
                    $attr = array('checked' => 'checked');
                } else if (!empty($forceformat)) {
                    $attr = array('disabled' => 'disabled');
                } else {
                    $attr = array();
                }
                $form->addElement('radio', 'pageformat', "", get_string('format' . $format, 'socialwiki'), $format, $attr);
            }
            $form->addRule('pageformat', get_string('required'), 'required', null, 'client');
        }
        $form->setType('pageformat', PARAM_ALPHANUMEXT);

        if (!empty($this->_customdata['groups']->availablegroups)) {
            $groupinfo = [];
            foreach ($this->_customdata['groups']->availablegroups as $groupdata) {
                $groupinfo[$groupdata->id] = $groupdata->name;
            }
            if (count($groupinfo) > 1) {
                $form->addElement('select', 'groupinfo', get_string('group'), $groupinfo);
                $form->setDefault('groupinfo', $this->_customdata['groups']->currentgroup);
                $form->setType('groupinfo', PARAM_ALPHANUMEXT);
            } else {
                $groupid = key($groupinfo);
                $groupname = $groupinfo[$groupid];
                $form->addElement('static', 'groupdesciption', get_string('group'), $groupname);
                $form->addElement('hidden', 'groupinfo', $groupid);
                $form->setType('groupinfo', PARAM_ALPHANUMEXT);
            }
        }

        // Hidden elements.
        $form->addElement('hidden', 'action', 'create');
        $form->setType('action', PARAM_ALPHA);
        $form->addElement('submit', 'submitbutton', get_string('createpage', 'socialwiki'));
    }

}
