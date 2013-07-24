#! /usr/bin/perl

use File::Tail;
use Proc::Daemon;
use Sys::Syslog;
use Sys::Hostname;
use DBI;
use strict;

# Do the daemon thing - fork out of TTY, close all file handles, current dir to /
Proc::Daemon::Init;

# Find the pid, log it and write it to the pid file
my $pid = $$;
openlog('macsniff','','user');
syslog('info', "started daemon with pid $pid");
open PID, ">/var/run/macsniff.pid";
print PID "$pid\n";
close PID;

# Run cleanup when told to quit
$SIG{HUP} = 'hangup';

# Include credentials for the database
our $user;
our $pass;
our $dbi;
require "/etc/tclip/macsniff.conf";

# And use them to open a DBI
my $dbh = DBI->connect($dbi, $user, $pass);
unless ($dbh) {
   my $dberr = $DBI::errstr;
   syslog('err', "database error $dberr");
   closelog;
   die "Database: $dberr";
}

my $host = hostname;

# Tail messages for dhcp discover requests
my $ref = tie *FH, "File::Tail",(name=>"/var/log/messages", maxinterval=>2);
while (<FH>) {
   if (m/dhcpd: DHCPDISCOVER/) {
      split(/ from /);
      $_ = $_[1];
      my ($mac, $if) = split(/ via /);
      $mac =~ s/://g;
      $mac = $& if ($mac =~ m/([A-Fa-f0-9]){12}/);
      if ($if =~ m/(\d+)/) {
         my $ifno = $1;
         my $count = $dbh->selectrow_array("select count(*) from provision where mac='$mac';");
         if ($count == 0) {
            syslog('info', "new discovery from $mac");
            $dbh->do("insert into provision set host='$host', mac='$mac', state='1', iface='$ifno';");
         } elsif ($count == 1) {
            $dbh->do("update provision set host='$host',iface='$ifno', state='1' where mac='$mac';");
         } else {
            $dbh->do("delete from provision where mac='$mac';");
            $dbh->do("insert into provision set host='$host', mac='$mac', state='1', iface='$ifno';");
         }
      }
   # and dhcp lease requests
   } elsif (m/dhcpd: DHCPOFFER/) {
      split (/ on /);
      $_ = $_[1];
      split (/ via /);
      $_ = $_[0];
      my $if = $_[1];
      my $ifno = $1 if ($if =~ m/(\d+)/);
      my ($ip, $mac) = split(/ to /);
      $mac =~ s/://g;
      $mac = $& if ($mac =~ m/([A-Fa-f0-9]){12}/);
      $ip = IP2Long($ip);
      my $count = $dbh->selectrow_array("select count(*) from provision where mac='$mac';");
      if ($count == 1) {
         syslog('info', "dhcprequest from $mac for $ip");
         $dbh->do("update provision set ip='$ip' where mac='$mac';");
      } elsif ($count > 1) {
         $dbh->do("delete from provision where mac='$mac';");
         $dbh->do("insert into provision set host='$host', mac='$mac', ip='$ip';");
      }
   }
}

sub hangup {
   $dbh->disconnect;
   syslog('info', "stopped daemon");
   closelog;
   exit(0);
}

sub IP2Long {
   return unpack('N', (pack 'C4', split(/\./, shift)));
}

sub Long2IP {
   return join('.', unpack('C4', pack('N', shift)));
}
