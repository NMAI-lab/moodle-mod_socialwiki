<?php

//require_once('../../../config.php');

abstract class socialwiki_table {

    protected $uid; //uid of user viewing
    protected $swid;
    protected $headers;

    //protected $cmid; //course module id, needed in versiontable...
    //protected $tabid; // table id for the html

    /**
     * creates a table with the given headers, current uid (userid), subwikiid
     */
    public function __construct($u, $s, $h) {
        Global $PAGE;
        $this->uid = $u;
        $this->swid = $s;
        $this->headers = $h;
        //$this->cmid = $PAGE->cm->id;
        // $this->initTable($col_names);
    }

    abstract protected function get_table_data();

    /**
     * gets the table in HTML format (string)
     */
    public function get_as_HTML($tableid = 'a_table') {

        $t = "<table id=" . $tableid . " class='datatable'>";
        $tabledata = $this->get_table_data();
        //headers
        $t .= "<thead><tr>";
        foreach ($this->headers as $h) {
            $t .= "<th>" . $h . "</th>";
        }
        $t .= "</tr></thead><tbody>";

        foreach ($tabledata as $row) {
            $t .= "<tr>";
            foreach ($row as $k => $val) {
                $t .= "<td>" . $val . "</td>";
            }
            $t .= "</tr>";
        }

        $t .= "</tbody></table>";
        return $t;
    }

    public function get_as_JSON() {
        return json_encode($this->get_table_data());
    }

    protected function make_time_String($t) {
        $display = '<span style="display:none">' . $t . '</span>';
        $aday = 86400;
        $timeofday = time() % $aday;
        $lastmidnight = time() - $timeofday;
        //seconds since midnight
        if ($t < $lastmidnight) { //yesterday or earlier
            $format = '%x';
        } else {
            $format = '%H:%M';
        }
        $display .= strftime($format, $t);
        return $display;
    }

    // public function initTable($col_names) {
    // 	for ($i = 0; $i<$col_names.count(); $i++) {
    // 		array_push($columns,
    // 			new column($i, $label)
    // 		);
    // 	}
    // }

    // public function has_column($col_name) {
    // 	foreach($columns as $c) {
    // 		if ($c->get_label === $col_name) {
    // 			return true;
    // 		}
    // 	}
    // 	return false;
    // }

    // public function get_column($col_name) {
    // 	foreach($columns as $c) {
    // 		if($c->get_label === $col_name) {
    // 			return $c;
    // 		}
    // 	}
    // 	return null;
    // }

    // public function toggle_visibility($col_name) {
    // 	$col = $this->get_column($col_name)
    // 	if (is_set($col)) {
    // 		$col->toggle_visibility();
    // 	}
    // }

    // public function set_visibility($col_name, $visibility) {
    // 	$col = $this->get_column($col_name)
    // 	if (is_set($col)) {
    // 		$col->set_visible($visibility);
    // 	}
    // } 
}
