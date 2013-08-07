#!/usr/bin/perl

use BedtimeDB;
use DBI;
use strict;

# Create array of days of the week
my @weekdays = ('Mon','Tue','Wed','Thu','Fri','Sat','Sun');

# We only care about reject forward rules, redirect nat rules and accept rules
my @f_rules = `iptables -L FORWARD --line-numbers | grep MAC | grep REJECT`;
my @n_rules = `iptables -t nat -L PREROUTING --line-numbers | grep MAC | grep REDIRECT`;
my @r_rules = `iptables -L FORWARD --line-numbers | grep MAC | grep ACCEPT`;

# Set up the bedtime, ground and reward arrays
my @f_bt_ru_a;
my @f_gr_ru_a;
my @f_rw_ru_a;

# Process the bedtime rules
foreach (grep(/ TIME from /, @f_rules)) {
   my @bits   = split(/ TIME from /);
   my @bobs   = split(/\s*REJECT\s*/,$bits[0]);
   my $line   = $bobs[0];
   @bobs      = split(/\s*MAC\s*/,$bobs[1]);
   my $mac    = $bobs[1];
   @bobs      = split(/\s*to\s*/,$bits[1]);
   my $start  = $bobs[0];
   @bobs      = split(/reject-with icmp-port-unreachable/,$bobs[1]);
   my $finish = '';
   my $days   = '';
   my $mask   = 0;
   if ($bobs[0] =~ /\s+on\s+/) {
       @bobs = split(/\s+on\s+/,$bobs[0]);
       $finish = $bobs[0];
       $days = $bobs[1];
       for (my $i=0;$i<8;$i++) {
          my $try = $weekdays[$i];
          $mask = $mask | (128 >> $i) if ($days =~ /$try/);
       }
   } else {
      $finish = $bobs[0];
   }
   push (@f_bt_ru_a,{line=>$line, mac=>$mac, start=>$start, finish=>$finish, bits=>$mask});
}

# Now the ground rules
foreach (grep (!/ TIME from /, @f_rules)) {
   my @bits = split(/\s*MAC\s*/);
   my @bobs = split(/\s*reject-with\s*/,$bits[1]);
   my $mac  = $bobs[0];
   @bobs    = split(/\s*REJECT\s*/,$bits[0]);
   my $line = $bobs[0];
   push (@f_gr_ru_a,{line=>$line, mac=>$mac});
}

# And finally the reward rules
foreach (@r_rules) {
   my @bits = split(/\s*MAC\s*/);
   my @bobs = split(/\s*-j ACCEPT/,$bits[1]);
   my $mac  = $bobs[0];
   @bobs    = split(/\s*ACCEPT\s*/,$bits[0]);
   my $line = $bobs[0];
   push (@f_rw_ru_a,{line=>$line, mac=>$mac});
}

print "Bedtime rules\n";
print "$_->{line} : $_->{mac} from $_->{start} to $_->{finish} on $_->{bits}\n" foreach(@f_bt_ru_a);
print "Grounded rules\n";
print "$_->{line} : $_->{mac}\n" foreach(@f_gr_ru_a);
print "Reward rules\n";
print "$_->{line} : $_->{mac}\n" foreach(@f_rw_ru_a);
die "Done for now\n";

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
   my @ipt = grep {$_ =~ /$mac/} @f_rules;
   foreach (@ipt) {
      m/^\d*/;
      $tables .= "iptables -D FORWARD $1\n";
   }
   @ipt = grep {$_ =~ /$mac/} @n_rules;
   foreach (@ipt) {
      m/^\d*/;
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
