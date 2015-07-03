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

class topictable extends socialwiki_table {

    private $tlist;

    public function __construct($uid, $swid, $list, $headers) {
        parent::__construct($uid, $swid, $headers);
        $this->tlist = $list;
    }

    public static function all_topictable($uid, $swid) {
        $topics = socialwiki_get_topics($swid);

        if (empty($topics)) {
            return null;
        }

        return new topictable($uid, $swid, $topics, 'topics');
    }

    protected function get_table_data() {
        Global $COURSE, $PAGE;

        $table = array();

        foreach ($this->tlist as $title => $data) {
            $titlelink = '<a href="search.php?searchstring=' . $title
                    . '&courseid=' . $COURSE->id . '&cmid=' . $PAGE->cm->id
                    . '&exact=1&option=1">' . $title . '</a>';

            $row = array(
                get_string('title', 'socialwiki') => $titlelink,
                get_string('versions', 'socialwiki') => $data["Versions"],
                get_string('views', 'socialwiki') => $data["Views"],
                get_string('likes', 'socialwiki') => $data["Likes"],
            );

            $table[] = $row; // Add row to table.
        }

        return $table;
    }
}
