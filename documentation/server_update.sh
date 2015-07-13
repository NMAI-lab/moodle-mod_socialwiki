#!/usr/bin/expect -f
set pass [lindex $argv 0]
spawn ssh cameron@inm-04.sce.carleton.ca
expect "password:"
send "$pass\r"
expect "$ "
send "cd /opt/lampp/htdocs/moodle/mod/socialwiki\r"
expect "$ "
send "sudo git pull origin master\r"
expect "password:"
send "$pass\r"
expect "$ "
send "exit\r"
interact
