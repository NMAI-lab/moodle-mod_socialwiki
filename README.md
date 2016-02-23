socialwiki
==========

## What is this?

This is a Moodle plugin. It is a social kind of wiki, where many page versions may coexist, and where users can connect ('follow' each other) in a social network. See https://sites.google.com/a/nmai.ca/home/research-projects/socialwiki for details.

## Moodle

To install this plugin you need a working instance of moodle, which itself requires an http server (typically apache httpd, also works with lighttpd, possibly others, php and a relational DB (mysql typically).
See moodle.org for installation details. Socialwiki was developed with moodle 2.5 and 2.6, and mysql DB. It is known to work with this configuration.

## Installation instructions

Download zip, or checkout using git.
Put all files in a directory called "socialwiki".
Copy socialwiki directory to moodle's mod directory.
Navigate to the website's Notifications page, follow directions on screen.

## New Styles
You can add a new style (css) to Socialwiki.
To add a new style:
* create a new css file name it stylename_style.css
* add your style name to the array in the locallib.php function socialwiki_get_styles
* add a string in language file with the name you added in locallib set it equal to what you want displayed in the selector
* all pictures are in the pix folder
* changes to pix names must be fixed in renderer.php

