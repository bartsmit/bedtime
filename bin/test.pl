#!/usr/bin/perl

use BedtimeDB qw(dbconn);
use DBI;
use strict;
use warnings;

dbconn;
#DBI->connect("DBI:mysql:bedtime:localhost","sleepy","MrSandman");

1;
