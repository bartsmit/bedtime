#!/usr/bin/perl

use DBI;
use LWP::Simple;
use strict;

open (CONF,'/etc/bedtime.conf') or die "Cannot open configuration file - $!\n";
# Read the conf file into an array and filter out all lines consisting of just white space and comments
my @conf = <CONF>; close CONF;
@conf = grep (!/^\s*$/,@conf);
@conf = grep (!/^#/,@conf);

# Read the remaining lines into a hash split on =
my %vals;
foreach (@conf) {
   chomp;
   my @pair = split(/\s*=\s*/);
   $vals{$pair[0]}=$pair[1];
}

# Collect the credentials and connect to the database
my $user = $vals{'dbuser'};
my $pass = $vals{'dbupass'};
my $dbis = "DBI:mysql:".$vals{'dbname'}.":".$vals{'dbhost'};
my $dbh = DBI->connect($dbis,$user,$pass) or die "Cannot connect to database $dbis with user $user - $!\n";

# Download the manufacturers list from Wireshark and use only the lines starting with three MAC bytes
my @man = split(/\n/,get("http://anonsvn.wireshark.org/wireshark/trunk/manuf"));
my @lnman = grep(/\/36/,@man);
@man = grep(/^(([A-F0-9]){2}[-:]){2}([A-F0-9]){2}\s+/,@man);
# Take the long MAC's and trim them
foreach(@lnman) {
   s/0:00\/36//;
   push(@man,$_);
}
# Split each line into MAC, short name and long name in the comment
foreach (@man) {
   chomp;
   my @bits = split /# /;
   my $long = $bits[1];
   $long =~ s/'/`/g;
   my @bobs = split(/\s+/,$bits[0]);
   my $mac = $bobs[0];
   my $short = $bobs[1];
   $mac =~ s/-/:/g;
   $mac =~ s/://g;
   $mac .= '0' x (12 - length $mac);
   my $que  = "replace into manufacturers values (x'$mac','$short','$long');";
   my $sql = $dbh->prepare($que) or die "Cannot prepare query: $dbh->errstr";
   my $res = $sql->execute or die "Cannot execute query: $sql->errstr";
   $res = $sql->finish;
}
$dbh->disconnect;

