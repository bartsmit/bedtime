#!/usr/bin/perl

package BedtimeDB;

use Exporter;
use DBI;
use strict;

our @ISA = qw( Exporter );
our @EXPORT_OK = qw(dbconn get_val set_val ip2long long2ip);

sub dbconn {
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
   # Collect the credentials and connect to the database
   my $user = $vals{'dbuser'};
   my $pass = $vals{'dbpass'};
   my $dbis = "DBI:mysql:".$vals{'dbname'}.":".$vals{'dbhost'};
   DBI->connect($dbis,$user,$pass) or die "Cannot connect to database $dbis with user $user and password $pass - $!\n";
}

sub trim {
   my $str = shift;
   $str =~ s/^\s+//;
   $str =~ s/\s+$//;
   $str;
}

sub get_val {
   my $var = shift;
   my $dbh = &dbconn;
   my $sth = $dbh->prepare("select value from bedtime.settings where variable='$var';") or die "Cannot prepare query: $dbh->errstr";
   my $res = $sth->execute or die "Cannot execute query: $sth->errstr";
   $sth->fetchrow_array();
}

sub set_val {
   my ($var,$val) = @_;
   my $dbh = &dbconn;
   my $sth = $dbh->prepare("replace into bedtime.settings (variable,value) values('$var','$val')") or die "Cannot prepare query: $dbh->errstr";
   my $res = $sth->execute or die "Cannot execute query: $sth->errstr";
}

sub ip2long {
   $_ = shift;
   my @ip = split(/\./);
   my $long = 0;
   for (my $i=0;$i<=3;$i++) {
      $long += (256 ** $i) * $ip[3-$i];
   }
   $long;
}

sub long2ip {
   my $long = shift;
   my @ip;
   push (@ip,$long % 256);
   $long = int($long/256);
   push (@ip,$long % 256);
   $long = int($long/256);
   push (@ip,$long % 256);
   $long = int($long/256);
   push (@ip,$long % 256);
   join('.',reverse(@ip));
}

1;
