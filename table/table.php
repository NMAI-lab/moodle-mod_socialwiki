<?php

abstract class socialwiki_table {

    protected $uid; //uid of user viewing
    protected $swid;
    protected $headers;
    
    /**
     * creates a table with the given headers, current uid (userid), subwikiid
     */
    public function __construct($u, $s, $h) {
        $this->uid = $u;
        $this->swid = $s;
        $this->headers = socialwiki_table::getHeaders($h);
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
    
    public static function getHeaders($type) {
        switch ($type) {
            case "version":
                return array(
                    get_string('title', 'socialwiki'),
                    get_string('contributors', 'socialwiki'),
                    get_string('updated', 'socialwiki'),
                    get_string('likes', 'socialwiki'),
                    get_string('views', 'socialwiki'),
                    get_string('favorite', 'socialwiki'),
                    get_string('popularity', 'socialwiki'),
                    get_string('likesim', 'socialwiki'),
                    get_string('followsim', 'socialwiki'),
                    get_string('networkdistance', 'socialwiki')
                );
            case "mystuff":
                return array(
                    get_string('title', 'socialwiki'),
                    get_string('contributors', 'socialwiki'),
                    get_string('updated', 'socialwiki'),
                    get_string('likes', 'socialwiki'),
                    get_string('views', 'socialwiki'),
                    get_string('favorite', 'socialwiki')
                );
            case "topics":
                return array(
                    get_string('title', 'socialwiki'),
                    get_string('versions', 'socialwiki'),
                    get_string('views', 'socialwiki'),
                    get_string('likes', 'socialwiki')
                );
            case "user":
                return array(
                    get_string('name', 'socialwiki'),
                    get_string('popularity', 'socialwiki'),
                    get_string('likesim', 'socialwiki'),
                    get_string('followsim', 'socialwiki'),
                    get_string('networkdistance', 'socialwiki')
                );
            default:
                return array('error in getHeaders:' . $type);
        }
    }
}
