<html><head><title>Bedtime</title></head><body>
>> <a href="addchild.php">Add/remove a child</a>
<hr>
>> Edit bedtimes
<form name="main">
<?php
include "dbconn.php";
$res = squery("select value from settings where variable='weekend'",$mysqli);
$weekend = $res['value']; $weekdays = 254 ^ $weekend;
$sql = "select child.user_id,name,description,
        max(if(days=$weekend, morning,null)) as w_fm,
        max(if(days=$weekend, night,null)) as w_to,
        max(if(days=$weekdays,morning,null)) as s_fm,
        max(if(days=$weekdays,night,null)) as s_to
        from rules inner join child on child.user_id=rules.user_id group by name";
$res = $mysqli->query($sql);
$numrows = $res->num_rows;
if ($numrows == 0) {
   echo "You have not entered any children yet. <a href=\"addchild.php\">Add a child</a>\n";
} else {
   echo "<table border=\"0\">\n";
   echo "<th>Name</th><th>Description</th><th><input type=\"checkbox\" name=\"sel_all\" value=\"all\"></th>";
   echo "<th>Weekend from: <input type=\"text\" name=\"a_w_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_w_end\" size=\"8\"></th>";
   echo "<th>School night from: <input type=\"text\" name=\"a_s_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_s_end\" size=\"8\"></th>\n";
   while ($row = $res->fetch_assoc()) {
      $id = $row['user_id']; $cn = $row['name']; $ds = $row['description'];
      $wf = $row['w_fm'];$wt = $row['w_to']; $sf = $row['s_fm']; $st = $row['s_to'];
      echo "<tr><td>$cn</td><td align=\"right\">$ds</td><td><input type=\"checkbox\" name=\"$id\" value=\"$id\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"w_start$id\" value=\"$wf\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"w_end$id\" value=\"$wt\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"s_start$id\" value=\"$sf\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"s_end$id\" value=\"$st\" size=\"8\"></td></tr>\n";
   }
   echo "</table>\n";
}
?>
<br>Edit bedtime per child or selected children in the top row. Tick the top box to apply top row to all children.
<hr>
>> Ground
<hr>
>> Reward
<hr>
>> <a href="devices.php">Devices</a>
<hr>
>> <a href="settings.php">Settings</a>
<hr>
<input type="submit" value="submit">
</form>
</body></html>
