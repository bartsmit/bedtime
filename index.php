<?php
include "dbconn.php";
$awst = (isset($_GET['a_w_start'])) ? $_GET['a_w_start'] : '';
$awnd = (isset($_GET['a_w_end']))   ? $_GET['a_w_end']   : '';
$asst = (isset($_GET['a_s_start'])) ? $_GET['a_s_start'] : '';
$asnd = (isset($_GET['a_s_end']))   ? $_GET['a_s_end']   : '';
$res = squery("select value from settings where variable='weekend'",$mysqli);
$weekend = $res['value']; $weekdays = 254 ^ $weekend;
$sql = "select child.user_id,name,description,
        max(if(days=$weekend, night,null)) as w_fm,
        max(if(days=$weekend, morning,null)) as w_to,
        max(if(days=$weekdays,night,null)) as s_fm,
        max(if(days=$weekdays,morning,null)) as s_to
        from rules inner join child on child.user_id=rules.user_id group by name";
$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
   $id  = $row['user_id'];
   $wst = (isset($_GET['w_start'.$id])) ? $_GET['w_start'.$id] : $row['w_fm'];
   $wnd = (isset($_GET['w_end'.$id]))   ? $_GET['w_end'.$id]   : $row['w_to'];
   $sst = (isset($_GET['s_start'.$id])) ? $_GET['s_start'.$id] : $row['s_fm'];
   $snd = (isset($_GET['s_end'.$id]))   ? $_GET['s_end'.$id]   : $row['s_to'];
   $mysqli->query("update rules set night='$wst', morning='$wnd' where user_id='$id' and days='$weekend'");
   $mysqli->query("update rules set night='$sst', morning='$snd' where user_id='$id' and days='$weekdays'");
} 
?>
<html><head><title>Bedtime</title></head><body>
>> <a href="addchild.php">Add/remove a child</a>
<hr>
>> Edit bedtimes
<form name="main">
<?php
$res = $mysqli->query($sql);
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
