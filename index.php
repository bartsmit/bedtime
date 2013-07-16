<?php
include "dbconn.php";
$awst = (isset($_GET['a_w_start']))   ? $_GET['a_w_start']   : '';
$awnd = (isset($_GET['a_w_end']))     ? $_GET['a_w_end']     : '';
$asst = (isset($_GET['a_s_start']))   ? $_GET['a_s_start']   : '';
$asnd = (isset($_GET['a_s_end']))     ? $_GET['a_s_end']     : '';
$grnd = (isset($_GET['ground']))      ? $_GET['ground']      : '';
$grnu = (isset($_GET['ground_num']))  ? $_GET['ground_num']  : '';
$grnt = (isset($_GET['ground_time'])) ? $_GET['ground_time'] : '';
$grnd = (isset($_GET['ground_date'])) ? $_GET['ground_date'] : '';
$rewd = (isset($_GET['reward']))      ? $_GET['reward']      : '';
$rewu = (isset($_GET['reward_num']))  ? $_GET['reward_num']  : '';
$rewt = (isset($_GET['reward_time'])) ? $_GET['reward_time'] : '';
$rewd = (isset($_GET['reward_date'])) ? $_GET['reward_date'] : '';

$res = squery("select value from settings where variable='weekend'",$mysqli);
$weekend = $res['value']; $weekdays = 254 ^ $weekend;
$sql_lst = "select child.user_id,name,description,
            max(if(days=$weekend, night,null)) as w_fm,
            max(if(days=$weekend, morning,null)) as w_to,
            max(if(days=$weekdays,night,null)) as s_fm,
            max(if(days=$weekdays,morning,null)) as s_to
            from rules inner join child on child.user_id=rules.user_id group by name";
$res = $mysqli->query($sql_lst);
while ($row = $res->fetch_assoc()) {
   $id  = $row['user_id'];
   $wst = (isset($_GET['w_start'.$id])) ? $_GET['w_start'.$id] : $row['w_fm'];
   $wnd = (isset($_GET['w_end'.$id]))   ? $_GET['w_end'.$id]   : $row['w_to'];
   $sst = (isset($_GET['s_start'.$id])) ? $_GET['s_start'.$id] : $row['s_fm'];
   $snd = (isset($_GET['s_end'.$id]))   ? $_GET['s_end'.$id]   : $row['s_to'];
   $mysqli->query("update rules set night='$wst', morning='$wnd' where user_id='$id' and days='$weekend'");
   $mysqli->query("update rules set night='$sst', morning='$snd' where user_id='$id' and days='$weekdays'");
}
if ($grnd != '') {
print "Got this far<br>";
   $res = $mysqli->query("select * from ground where user_id='$grnd'");
   if ($grnu != '') {
      $end = "date_add(now(),interval cast($grnu as decimal(3,1)) ".$_GET['ground_unit'].")";
   } elseif (($grnt != '') && ($grnd != '')) {
      $g_time = explode(':',$grnt);
      $hour = $g_time[0]; $min = $g_time[1]; $sec = $g_time[2];
      $g_date = explode('-',$grnd);
      if (count($g_date) == 2) {
         $year = date('Y');  $month = $g_date[0]; $day = $g_date[1];
      } else {
         $year = $g_date[0]; $month = $g_date[1]; $day = $g_date[2];
      }
      $end = ((checkdate($month,$day,$year)) && (preg_match("([01[0-9]|2[0-3] ([0-5][0-9]) ([0-5][0-9])", "$hour $min $sec") === 1)) ? "$grnd $grnt" : "now()";
   }
   $sql = ($res->num_rows == 0) ? "insert into ground values ('$grnd',now(),'$end')" : "update ground set start=now(), end='$end' where user_id='$grnd'";
   $mysqli->query($sql);
}
if ($rewd != '') {
   if ($rewu != '') {
   } elseif (($rewt !='') && ($rewd != '')) {
   }
}
?>
<html><head><title>Bedtime</title></head><body>
>> <a href="addchild.php">Add/remove a child</a>
<hr>
>> Edit bedtimes
<form name="main">
<?php
$res = $mysqli->query($sql_lst);
$numrows = $res->num_rows;
if ($numrows == 0) {
   echo "You have not entered any children yet. <a href=\"addchild.php\">Add a child</a>\n";
} else {
   $children = array();
   echo "<table border=\"0\">\n";
   echo "<th>Name</th><th>Description</th><th>Set <input type=\"checkbox\" name=\"sel_all\" value=\"all\"></th>";
   echo "<th>Weekend from: <input type=\"text\" name=\"a_w_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_w_end\" size=\"8\"></th>";
   echo "<th>School night from: <input type=\"text\" name=\"a_s_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_s_end\" size=\"8\"></th>\n";
   while ($row = $res->fetch_assoc()) {
      $id = $row['user_id']; $cn = $row['name']; $ds = $row['description'];
      $children[$id] = $cn;
      $wf = $row['w_fm'];$wt = $row['w_to']; $sf = $row['s_fm']; $st = $row['s_to'];
      echo "<tr><td>$cn</td><td align=\"right\">$ds</td><td align=\"right\"><input type=\"checkbox\" name=\"$id\" value=\"$id\"></td>\n";
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
>> Ground <select name="ground">
<option value=""></option>
<?php
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n";
}
?>
</select> for <input type="text" size="4" name="ground_num"> <select name="ground_unit">
<option value="hour">hour(s)</option><option value="day">day(s)</option>
<option value="week">week(s)</option><option vaule="month">month(s)</option>
</select> Or until <input type="text" size="8" name="ground_time"> hh:mm:ss on <input type="text" size="8" name="ground_date"> (yyyy)-mm-dd
<hr>
>> Reward
<select name="reward">
<option value=""></option>
<?php
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n";
}
?>
</select> for <input type="text" size="4" name="reward_num"> <select name="reward_unit">
<option value="hour">hour(s)</option><option value="day">day(s)</option>
<option value="week">week(s)</option><option vaule="month">month(s)</option>
</select> Or until <input type="text" size="8" name="reward_time"> hh:mm:ss on <input type="text" size="8" name="reward_date"> (yyyy)-mm-dd
<hr>
>> <a href="devices.php">Devices</a>
<hr>
>> <a href="settings.php">Settings</a>
<hr>
<input type="submit" value="submit">
</form>
</body></html>
