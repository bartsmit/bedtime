<?php
/*
This is the script for the main page
The most often used items are shown:
Bedtimes, ground and reward.
All others are linked to other pages.
*/

# Use the parent security through login
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
# Create a link to the database
include "dbconn.php";
# The top left set checkbox set the same times for all children
$sall = (isset($_GET['sel_all']));
# And the four times apply to all
$awst = (isset($_GET['a_w_start']))   ? $_GET['a_w_start']   : '';
$awnd = (isset($_GET['a_w_end']))     ? $_GET['a_w_end']     : '';
$asst = (isset($_GET['a_s_start']))   ? $_GET['a_s_start']   : '';
$asnd = (isset($_GET['a_s_end']))     ? $_GET['a_s_end']     : '';
# Check the ground unit (hour,day,week or month)
$grni = (isset($_GET['ground']))      ? $_GET['ground']      : '';
# And the value of that unit
$grnu = (isset($_GET['ground_num']))  ? $_GET['ground_num']  : '';
# Alternatively give a set end time
$grnt = (isset($_GET['ground_time'])) ? $_GET['ground_time'] : '';
# And the end date
$grnd = (isset($_GET['ground_date'])) ? $_GET['ground_date'] : '';
# Same unit, value or end date/time of rewards
$rewi = (isset($_GET['reward']))      ? $_GET['reward']      : '';
$rewu = (isset($_GET['reward_num']))  ? $_GET['reward_num']  : '';
$rewt = (isset($_GET['reward_time'])) ? $_GET['reward_time'] : '';
$rewd = (isset($_GET['reward_date'])) ? $_GET['reward_date'] : '';
# Remove old ground and reward lines
$mysqli->query("delete from ground where end < now()");
$mysqli->query("delete from reward where end < now()");
# Run an update of the iptables
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "u\n";
socket_write($sock,$buf,strlen($buf));
# Check if we have four non-zero values for all
$four = (($awst != '') && ($awnd != '') && ($asst != '') && ($asnd != ''));
# Pick up the weekend and weekdays days masks from the database
$res = $mysqli->query("select value from settings where variable='weekend'");
$row = $res->fetch_assoc(); $weekend = $row['value']; $weekdays = 254 ^ $weekend;
# Pick up the values for the children table
$sql_lst = "select child.user_id,name,description,
            (select count(*) from ground where ground.user_id=rules.user_id) as gc,
            (select max(end) from ground where ground.user_id=rules.user_id) as ge,
            (select count(*) from reward where reward.user_id=rules.user_id) as rc,
            (select max(end) from reward where reward.user_id=rules.user_id) as re,
            max(if(days=$weekend, night,null)) as w_fm,
            max(if(days=$weekend, morning,null)) as w_to,
            max(if(days=$weekdays,night,null)) as s_fm,
            max(if(days=$weekdays,morning,null)) as s_to
            from rules inner join child on child.user_id=rules.user_id group by name";
$res = $mysqli->query($sql_lst);
# Show a child per row
while ($row = $res->fetch_assoc()) {
   $id  = $row['user_id'];
   # See if there is a new time for all and this child is in the selection
   if (($four) && (($sall) || (isset($_GET['sel'.$id])))) {
      # Otherwise set the times from the form
      $wst = $awst; $wnd = $awnd; $sst = $asst; $snd = $asnd;
   } else {
      # Check if there are new individual times set, if not set them from the database
      $wst = (isset($_GET['w_start'.$id])) ? $_GET['w_start'.$id] : $row['w_fm'];
      $wnd = (isset($_GET['w_end'.$id]))   ? $_GET['w_end'.$id]   : $row['w_to'];
      $sst = (isset($_GET['s_start'.$id])) ? $_GET['s_start'.$id] : $row['s_fm'];
      $snd = (isset($_GET['s_end'.$id]))   ? $_GET['s_end'.$id]   : $row['s_to'];
   }
   # Update the school night and weekend times
   $mysqli->query("update rules set night='$wst', morning='$wnd' where user_id='$id' and days='$weekend'");
   $mysqli->query("update rules set night='$sst', morning='$snd' where user_id='$id' and days='$weekdays'");
}

# If the ground dropdown yields an ID, we'll need to ground somebody
if ($grni != '') {
   # See if a ground unit is zero - which means remove the ground
   if ($grnu == '0') {
      $sql = "delete from ground where user_id='$grni'";
   } else {
      # There is a real ground set. First check if units are set
      if ($grnu != '') {
         # Let MySQL calculate the end date/time from that
         $end = "date_add(now(),interval floor($grnu) ".$_GET['ground_unit'].")";
      } elseif ($grnt != '') {
         # No units set, check for a manual end time
         $g_time = explode(':',$grnt);
         $hour = $g_time[0]; $min = $g_time[1];
         # If the time was set as hh:mm, add zero seconds
         $sec = (count($g_time) == 2) ? "00" : $g_time[2];
         $grnt = $hour.":".$min.":".$sec;
         if ($grnd == '') {
            # No date entered. See if the end time is tomorrow
            if (strtotime($grnt) > time()) {
               $end = "'".date("Y-m-d")." ".$grnt."'";
            } else {
               $end = "'".date("Y-m-d",time()+86400)." ".$grnt."'";
            }
         # Set the end from the date and time
         } else {
            $end = "'$grnd $grnt'";
         }
      }
      # Insert or update the grounded child
      $res = $mysqli->query("select * from ground where user_id='$grni'");
      $sql = ($res->num_rows == 0) ? "insert into ground values ('$grni',now(),$end)" : "update ground set start=now(), end=$end where user_id='$grni'";
   }
   $mysqli->query($sql);
}
# Same for the reward entry
if ($rewi != '') {
   if ($rewu == '0') {
      $sql = "delete from reward where user_id='$rewi'";
   } else {
      if ($rewu != '') {
         $end = "date_add(now(),interval floor($rewu) ".$_GET['reward_unit'].")";
      } elseif ($rewt != '') {
         # No units set, check for a manual end time
         $r_time = explode(':',$rewt);
         $hour = $r_time[0]; $min = $r_time[1];
         # If the time was set as hh:mm, add zero seconds
         $sec = (count($r_time) == 2) ? "00" : $r_time[2];
         $rewt = $hour.":".$min.":".$sec;
         if ($rewd == '') {
            # No date entered. See if the end time is tomorrow
            if (strtotime($rewt) > time()) {
               $end = "'".date("Y-m-d")." ".$rewt."'";
            } else {
               $end = "'".date("Y-m-d",time()+86400)." ".$rewt."'";
            }
         } else {
            $end = "'$rewd $rewt'";
         }
      }
      $res = $mysqli->query("select * from reward where user_id='$rewi'");
      $sql = ($res->num_rows == 0) ? "insert into reward values ('$rewi',now(),$end)" : "update reward set start=now(), end=$end where user_id='$rewi'";
   }
   $mysqli->query($sql);
}
# Get the IP address to display when the parent hovers over the Bedtime header
$res = $mysqli->query("select value from settings where variable='myip'");
$numrows = $res->num_rows;
if ($numrows != 0) {
   $row = $res->fetch_assoc();
   $url = "http://".$row['value'];
} else {
   $url = '';
}
?>
<html><head><title>Bedtime</title>
<link rel="stylesheet" type="text/css" href="desktop.css">
</head><body>
<div title="<?php echo $url; ?>"><h1>Bedtime</h1></div>
<h2><a href="addchild.php">Add/remove a child</a></h2>
<hr>
<h2>Edit bedtimes</h2>
<form name="main">
<?php
# Run the sql query for the children bedtimes table
$res = $mysqli->query($sql_lst);
$numrows = $res->num_rows;
if ($numrows == 0) {
   # No children entered - refer to the add child script
   echo "You have not entered any children yet. <a href=\"addchild.php\">Add a child</a>\n";
} else {
   $children = array();
   echo "<table border=\"0\">\n";
   echo "<th>Name</th><th>Description</th><th>Set <input type=\"checkbox\" name=\"sel_all\" value=\"sel_all\"></th>";
   echo "<th>Weekend from: <input type=\"text\" name=\"a_w_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_w_end\" size=\"8\"></th>";
   echo "<th>School night from: <input type=\"text\" name=\"a_s_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_s_end\" size=\"8\"></th>\n";
   while ($row = $res->fetch_assoc()) {
      $id = $row['user_id']; $cn = $row['name']; $ds = $row['description'];
      $children[$id] = $cn;
      $wf = $row['w_fm'];$wt = $row['w_to']; $sf = $row['s_fm']; $st = $row['s_to'];
      $en = ''; $rn = ''; $gn = '';
      $nl = (isset($_GET["l_$id"])) ? $_GET["l_$id"] : '';
      # If there is a reward or a ground set, display a bold R and/or G respectively
      if ($row['rc'] > 0) { $rn = " <strong>R</strong>"; $en = "reward ends ".$row['re']; }
      if ($row['gc'] > 0) { $gn = " <strong>G</strong>"; $en = "ground ends ".$row['ge']; }
      # See if there is a new description
      if (($nl <> '') && ($nl <> $ds)) {
         $ds = $nl;
         $mysqli->query("update child set description='$ds' where user_id=$id");
      }
      # Set the hover text to the end time(s)
      echo "<tr><td><div title =\"$en\">$cn $gn $rn</div></td>";
      echo "<td align=\"right\"><input type=\"text\" value=\"$ds\" name=\"l_$id\"></td><td align=\"right\">";
      # Show the selection box
      echo "<input type=\"checkbox\" name=\"sel$id\" value=\"sel$id\"></td>\n";
      # And the four times
      echo "<td align=\"right\"><input type=\"text\" name=\"w_start$id\" value=\"$wf\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"w_end$id\" value=\"$wt\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"s_start$id\" value=\"$sf\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"s_end$id\" value=\"$st\" size=\"8\"></td></tr>\n";
   }
   echo "</table>\n";
}
?>
<br>Edit bedtime per child, or edit all selected children in the top row. Tick the top box to apply top row to all children.
<hr>
<h2>Ground</h2>
<select name="ground">
<option value=""></option>
<?php
# Create the drop-down list for the ground selection
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n";
}
?>
</select> for <input type="text" size="4" name="ground_num"> <select name="ground_unit">
<option value="hour">hour(s)</option><option value="day">day(s)</option>
<option value="week">week(s)</option><option vaule="month">month(s)</option>
</select> Or until <input type="text" size="8" name="ground_time"> hh:mm:ss on <input type="text" size="8" name="ground_date"> yyyy-mm-dd<br>
<br>Remove a child from grounding by grounding them for zero hours
<hr>
<h2>Reward</h2>
<select name="reward">
<option value=""></option>
<?php
# Same for rewards
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n";
}
?>
</select> for <input type="text" size="4" name="reward_num"> <select name="reward_unit">
<option value="hour">hour(s)</option><option value="day">day(s)</option>
<option value="week">week(s)</option><option vaule="month">month(s)</option>
</select> Or until <input type="text" size="8" name="reward_time"> hh:mm:ss on <input type="text" size="8" name="reward_date"> yyyy-mm-dd<br>
<br>Remove a child's reward by rewarding them for zero hours.
<hr>
<h2><a href="devices.php">Devices</a></h2>
<hr>
<h2><a href="settings.php">Parents and Settings</a></h2>
<hr>
<input type="submit" value="submit">
</form><br>
Cancel and <a href="index.php">reset</a><br>
Or <a href="logout.html">log out</a> of Bedtime<br>
<?php
# Show the time if the timezone is set
$res = $mysqli->query("select value from settings where variable='town'");
$numrows = $res->num_rows;
if ($numrows != 0) {
   $row = $res->fetch_assoc();
   date_default_timezone_set($row['value']);
   # Show the refresh time to convince teenagers that the local time is correct
   echo "Last refresh: ".date("H:i" ,time());
}
echo "<br><br>\n";
# Common version footer
$res = $mysqli->query("select value from settings where variable='version'");
$row = $res->fetch_assoc(); $ver = $row['value'];
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
