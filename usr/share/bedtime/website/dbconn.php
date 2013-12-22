<?php
/*
This is an inclusion cript to connect to the bedtime
database using the credentials set in the configuration
file /etc/bedtime.conf

It also sets the $vals array with values from this file.
*/

# Open the configuration file
$conf = file('/etc/bedtime.conf');
# Remove any comment lines
$conf = preg_grep ("/^#/",$conf,PREG_GREP_INVERT);
# And all empty lines
$conf = preg_grep ("/^\s*$/",$conf,PREG_GREP_INVERT);
# Fill the $vals array with the variable=value lines
foreach ($conf as $line) {
   $bits = preg_split("/\s*=\s*/",$line);
   $vals[$bits[0]] = rtrim($bits[1]);
}
# Connect to the database with the credentials
# The $mysqli variable is shared with the other scripts.
$mysqli = new mysqli ($vals["dbhost"], $vals["dbuser"], $vals["dbpass"], $vals["dbname"]);
