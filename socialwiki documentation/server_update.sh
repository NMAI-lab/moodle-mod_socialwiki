#!/usr/bin/expect -f
set user [lindex $argv 0]
set pass [lindex $argv 1]
spawn ssh $user
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
