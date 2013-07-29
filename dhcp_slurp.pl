#!/usr/bin/perl

use BedtimeDB;
use DBI;
use strict;

# Connect to the database and get the leases file
my $dbh = &BedtimeDB::dbconn;
my $lease_f = &BedtimeDB::get_val('dhcp_leases');

# Read the leases file into @lease
open (LEASE,$lease_f);
my @lease = <LEASE>; close(LEASE);

# Filter out lines starting with lease or hardware
my @devs = grep { $_ =~ /^\s*lease|^\s*hardware/} @lease;
my $devs = join("", @devs);

# Replace newlines with a ### marker
$devs =~ s/\s*{\s*\n\s*/###/g;
$devs =~ s/\s*lease\s*|\s*hardware ethernet\s*//g;
$devs =~ s/;/\n/g;
chomp($devs);
my @devs = split(/\n/,$devs);
foreach (@devs) {
   my @row = split(/###/);
   my $ip = $row[0]; my $mac = $row[1];
   $mac =~ s/://g; $mac = uc($mac);
   my $sth = $dbh->prepare("select count(*) from device where lpad(hex(mac),12,'0')=lpad('$mac',12,'0');") or die "Cannot prepare query: $dbh->errstr";
   my $res = $sth->execute or die "Cannot execute query: $sth->errstr";
   my $sql;
   if ($sth->fetchrow_array() > 0) {
      $sql = "update device set ip=inet_aton('$ip') where lpad(hex(mac),12,'0')=lpad('$mac',12,'0');";
   } else {
      $sql = "insert into device set mac=x'$mac', ip=inet_aton('$ip'), first_seen=now();";
   }
   $res = $sth->finish;
   $sth = $dbh->prepare($sql) or die "Cannot prepare query: $dbh->errstr";
   $res = $sth->execute or die "Cannot execute query: $sth->errstr";
}
$dbh->disconnect;
