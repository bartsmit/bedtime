#!/usr/bin/perl

use BedtimeDB qw(get_val);
use DBI;
use strict;

# Create array of days of the week
my @weekdays = ('Mon','Tue','Wed','Thu','Fri','Sat','Sun');

my (@old_t,@new_t);
push (@old_t,{line=>'1', mac=>'0055AA2211', start=>'02:00:00', finish=>'04:00:00', days=>'12'});
push (@new_t,{mac=>'0055AA2211', night=>'01:00:00', morning=>'04:00:00', days=>'12'});

my $tab_t = mod_rule(\@old_t,\@new_t,'FORWARD');
#print mask2days(12);
print "\n$tab_t\n";

die "done\n";

# Connect to the database
my $dbh = &BedtimeDB::dbconn;

# Get my IP address
my $myip = get_val('myip');

# Get the weekend days mask
my $weekend = get_val('weekend');

# Get all the current reject, redirect nat rules and accept rules with a MAC filter
my @f_rules = `iptables -L FORWARD --line-numbers | grep MAC | grep REJECT`;
my @n_rules = `iptables -t nat -L PREROUTING --line-numbers | grep MAC | grep REDIRECT`;
my @r_rules = `iptables -L FORWARD --line-numbers | grep MAC | grep ACCEPT`;

# Collect all the MAC addresses from iptables
my @i_macs;
foreach (@f_rules, @n_rules, @r_rules) {
   m/([0-9A-F]{2}:){5}([0-9A-F]{2})/;
   my $mac = $&;
   $mac =~ s/://g;
   push (@i_macs,$mac);
}

# Filter for unique values
my %seen;
my @i_macs = grep {! $seen{$_}++ } @i_macs;

# Get all unique MAC addresses from the rules table
my $sth = $dbh->prepare("select lpad(hex(device.mac),12,'0') as mac from rules
                         inner join device on rules.user_id=device.user_id group by mac");
$sth->execute();
my $all = $sth->fetchall_arrayref();
my @r_macs = map { $_->[0] } @$all;

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
   $mac       =~ s/://g;
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
      $mask   = 0;
   }
   push (@f_bt_ru_a,{line=>$line, mac=>$mac, start=>$start, finish=>$finish, days=>$mask});
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

# Get all the new rules from the database
my @db_rule_a;
my $sth = $dbh->prepare("select lpad(hex(device.mac),12,'0') as mac, night, morning, days
                         from rules inner join device on rules.user_id=device.user_id");
$sth->execute();
while (my @row = $sth->fetchrow_array) {
   my ($mac,$night,$morning,$days) = (@row);
   push (@db_rule_a,{mac=>$mac, night=>$night, morning=>$morning, days=>$days});
}

# Set the string for iptables modifications
my $tables = '';

# Copy the MAC arrays to record removals
my @i_macs_r = @i_macs;
my @r_macs_r = @r_macs;

# Check for matching MAC addresses
foreach my $old (@i_macs) {
   foreach my $new (@r_macs) {
      if ($old eq $new) {

         # If they match, get the old and new, weekend and school night rules
         my (@old_we_rules,@old_sn_rules,@new_we_rules, @new_sn_rules);

         # First the old rules from iptables
         foreach (@f_bt_ru_a) { 
            if ($_->{mac} eq $old) {
               if ($_->{days} == $weekend) {
                  push (@old_we_rules,$_);
               } else {
                  push (@old_sn_rules,$_);
               }
            }
         }

         # Then the new rules from the database
         foreach (@db_rule_a) { 
            if ($_->{mac} eq $new) {
               if ($_->{days} == $weekend) {
                  push (@new_we_rules,$_);
               } else {
                  push (@new_sn_rules,$_);
               }
            }
         }

         # Now create a modify rule from each set of old and new rules
         $tables .= mod_rule(\@old_we_rules,\@new_we_rules);
         $tables .= mod_rule(\@old_sn_rules,\@new_sn_rules); 

         # Finally remove the mac from both old and new lists
         remove(\@i_macs_r,$old);
         remove(\@r_macs_r,$new);

         # No need to check any other mac
         last;
      }
   }
}
####
die "that'll do\n";

print $tables;
#exec($tables);

### Subroutines ###

# Remove a matching record from an array
sub remove {
   my ($ref, $val) = (@_);

   # Get the index of the record
   my $i;
   foreach (@$ref) {
      last if ($_ eq $val);
      $i++;
   }

   # Splice out that record
   splice(@$ref,$i,1);
}

# Create iptables rules from an old and new array
sub mod_rule {
   my ($old_ref,$new_ref,$table) = (@_);
   my @old = @$old_ref;
   my @new = @$new_ref;

   # Set the action according to the table
   my $action = ($table eq 'FORWARD') ? " -j REJECT\n" : " -m tcp --dport 80 -j REDIRECT --to-ports 3128\n";

   # Return value for the iptables rule(s)
   my $ipt = '';

   # Add separators to the MAC and set it lowercase
   my $mac = join(':',( lc($old[0]->{mac}) =~ m/../g ));

   # If the bedtime straddles midnight, we'll need two new rules
   my $straddle = (time2secs($new[0]->{night}) < 43200) ? 1 : 0;

   # There are at least one each of  new and old rules. Check if they're the same
   if (($old[0]->{night}   eq $new[0]->{start}) &&
       ($old[0]->{days}    == $new[0]->{days})  &&
      (($old[0]->{morning} eq $new[0]->{finish}) || ($straddle))) {
      # MAC is already the same, so nothing to do here
   } else {
      $ipt .= "iptables -R $table $old[0]->{line} -m mac --mac-source $mac ";

      # If there are two new rules, start with the early one
      my $finish = ($straddle == 1) ? '23:59:59' : $new[0]->{morning};
      $ipt .= "-m time --timestart $new[0]->{start} --timestop $finish ";
      my $mask = $new[0]->{days};
      $mask = $mask >> 1 if ($straddle);
      $ipt .= "--weekdays " . mask2days($mask) if $mask;
      $ipt .= $action;
   }

   # Do we need a second rule?
   if ($straddle) {
      # Set days one day later
      my $mask = $new[0]->{days} >> 1;

      # Is there a second old rule to replace?
      if (scalar(@old) == 1){
         # Check if we need a second replace rule
         if (($old[1]->{morning} eq $new[0]->{finish}) &&
             ($old[1]->{days}    == $new[0]->{days})) {
            # Second rules are the same, no replace needed
         } else {
            $ipt .= "iptables -R $table $old[1]->{line} -m mac --mac-source $mac ";

            # Second rule is the late one
            $ipt .= "-m time --timestart 00:00:00 --timestop $new[0]->{finish} ";
            $ipt .= "--weekdays " . mask2days($mask) if $mask;
            $ipt .= $action;
         }
      } else {
         # Create an add rule
         $ipt .= "iptables -I $table $old[1]->{line} -m mac --mac-source $mac ";
         $ipt .= "-m time --timestart 00:00:00 --timestop $new[0]->{finish} ";
         $ipt .= "--weekdays " . mask2days($mask) if $mask;
         $ipt .= $action;
      }
   } else {
      # Is there a second old rule to delete?
      if (scalar(@old) == 1) {
         # Delete the second old rule
         $ipt .= "iptables -D $table $old[1]->{line}\n";
      }
   }
   $ipt;
}

# Create a time modifier from old and new times
sub times2mod {
   
}

# Find the number of seconds since midnight of a time in hh:mm:ss
sub time2secs {
   my $time = shift;
   my @bits = split(/:/,$time);
   my $secs = $bits[0] * 3600 + $bits[1] * 60 + $bits[2];
      $secs = $bits[0] * 3600 + $bits[1] * 60 if $#bits == 1;
   $secs;
}

# Convert a days mask to iptables modifier string
sub mask2days {
   my $mask = shift;
   my $days;

   # If the '8th' weekday is set then move its flag to the beginning
   $mask = ($mask & 254) | 128 if ($mask & 1);

   # Add a weekday and comma for each bit set
   for (my $i=0;$i<8;$i++) {
      $days .= "$weekdays[$i]," if ($mask & (128 >> $i));
   }

   # Remove the trailing comma
   $days =~ s/,$//;
   $days;
}
