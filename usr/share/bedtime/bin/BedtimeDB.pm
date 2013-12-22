#!/usr/bin/perl

# This is the Bedtime module.
# Its main use is to connect to the database
# similarly to dbconn.php for the web scripts
# There are a number of additional subroutines
# mostly related to database access

package BedtimeDB;
# Exporter is needed to share subs
# DBI is needed to connect the database
use Exporter;
use DBI;
use strict;

# Set the export flag for the appropriate subs
our @ISA = qw( Exporter );
our @EXPORT_OK = qw(getconf dbconn get_val set_val ip2long long2ip);

# Extract the database variables from /etc/bedtime.conf
sub getconf {
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
      $vals{trim($pair[0])}=trim($pair[1]);
   }
   # Collect the database values
   return ($vals{'dbhost'}, $vals{'dbname'}, $vals{'dbuser'}, $vals{'dbpass'});
}

# Use getconf to connect to the bedtime database
sub dbconn {
   my ($host,$db,$user,$pass) = getconf();
   my $dbis = "DBI:mysql:$db:$host";
   DBI->connect($dbis,$user,$pass) or die "Cannot connect to database $dbis with user $user and password $pass - $!\n";
}

# This is used in getconf to trim whitespace
sub trim {
   my $str = shift;
   $str =~ s/^\s+//;
   $str =~ s/\s+$//;
   $str;
}

# This sub is for the settings table.
sub get_val {
   my $var = shift;
   my $dbh = &dbconn;
   my $sth = $dbh->prepare("select value from bedtime.settings where variable='$var';") or die "Cannot prepare query: $dbh->errstr";
   my $res = $sth->execute or die "Cannot execute query: $sth->errstr";
   # It returns the array of the row
   $sth->fetchrow_array();
}

# Conversely, set a value in the settings table
sub set_val {
   # Read the variable and value passed
   my ($var,$val) = @_;
   # Connect to the database
   my $dbh = &dbconn;
   my $sql;
   if (get_val($var) eq '') {
      $sql = "insert into bedtime.settings (variable,value) values('$var','$val')";
   } else {
      $sql = "update bedtime.settings set value='$val' where variable='$var'"; 
   }
   my $sth = $dbh->prepare($sql) or die "Cannot prepare query $sql: $dbh->errstr";
   my $res = $sth->execute or die "Cannot execute query $sql: $sth->errstr";
}

# Calculate the binary from the IP string
sub ip2long {
   $_ = shift;
   # split into octets
   my @ip = split(/\./);
   my $long = 0;
   # add powers of 256 for each octet
   for (my $i=0;$i<=3;$i++) {
      $long += (256 ** $i) * $ip[3-$i];
   }
   # and resturn the result
   $long;
}

# Calculate the IP string from the binary
sub long2ip {
   my $long = shift;
   # Set up the array for the octets
   my @ip;
   # Add the modulus 256
   push (@ip,$long % 256);
   # continue with the div
   $long = int($long/256);
   # repeat for all octets
   push (@ip,$long % 256);
   $long = int($long/256);
   push (@ip,$long % 256);
   $long = int($long/256);
   push (@ip,$long % 256);
   # Join the octets by dots to form the IP string
   join('.',reverse(@ip));
}

1;
