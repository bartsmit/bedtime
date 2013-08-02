#!/usr/bin/perl

use BedtimeDB;
use DBI;
use strict;

# Create array of days of the week
my @weekdays = ('Mon','Tue','Wed','Thu','Fri','Sat','Sun');

# We only care about forward filter rules and prerouting nat rules
my @frules = `iptables -L FORWARD --line-numbers | grep MAC`;
my @nrules = `iptables -t nat -L PREROUTING --line-numbers | grep MAC`;

# update iptables with rules in $tables
my $tables = '';

# Connect to the database
my $dbh = &BedtimeDB::dbconn;

# Get my IP address
my $sth = $dbh->prepare("select value from settings where variable='myip'");
my $res = $sth->execute or die "Cannot execute query: $sth->errstr";
my @row = $sth->fetchrow_array();
my $myip = $row[0];

# Get all mac addresses with rules to create delete iptables commands
$sth = $dbh->prepare("select lpad(hex(device.mac),12,'0') as mac from rules inner join device on rules.user_id=device.user_id group by mac");
$res = $sth->execute or die "Cannot execute query: $sth->errstr";
while (my @row = $sth->fetchrow_array()) {
   my $mac = join(":",($row[0] =~ m/../g));
   my @ipt = grep {$_ =~ /$mac/} @frules;
   foreach (@ipt) {
      m/^\d+/;
      $tables .= "iptables -D FORWARD $1\n";
   }
   @ipt = grep {$_ =~ /$mac/} @nrules;
   foreach (@ipt) {
      m/^\d+/;
      $tables .= "iptables -t nat -D PREROUTING $1\n";
   }

}

# Get all the rules to create insert iptables commands
$sth = $dbh->prepare("select lpad(hex(device.mac),12,'0') as mac, morning, night, days from rules inner join device on rules.user_id=device.user_id order by mac");
$res = $sth->execute or die "Cannot execute query: $sth->errstr";
while (my @row = $sth->fetchrow_array()) {
   my $mac    = lc(join(":",($row[0] =~ m/../g)));
   my $start  = $row[1];
   my $finish = $row[2];
   my $days   = '';
   for (my $i=0;$i<8;$i++) {
      $days .= $weekdays[$i]."," if (($row[3] & (128 >> $i)) == (128 >> $i));
   }
   $days =~ s/,$//;
   if ($start =~ m/^0/) {
      $tables .= "iptables -I FORWARD -m mac --mac-source $mac -m time --timestart $start --timestop $finish --weekdays $days -j REJECT\n";
      $tables .= "iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp ! -d $myip -m time --timestamp $start --timestop $finish ";
      $tables .= "--weekdays $days -m tcp --dport 80 -j REDIRECT --to-ports 3128\n";
   } else {
      $tables .= "iptables -I FORWARD -m mac --mac-source $mac -m time --timestart $start --timestop 23:59:59 --weekdays $days -j REJECT\n";
      $tables .= "iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp ! -d $myip -m time --timestart $start --timestop 23:59:59 ";
      $tables .= "--weekdays $days -m tcp --dport 80 -j REDIRECT --to-ports 3128\n";
      $tables .= "iptables -I FORWARD -m mac --mac-source $mac -m time --timestart 00:00:00 --timestop $finish --weekdays $days -j REJECT\n";
      $tables .= "iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp ! -d $myip -m time --timestart 00:00:00 --timestop $finish ";
      $tables .= "--weekdays $days -m tcp --dport 80 -j REDIRECT --to-ports 3128\n";
   }
}
print $tables;
#exec($tables);
