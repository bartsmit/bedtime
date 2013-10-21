<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
include "dbconn.php";
$name = (isset($_GET['name']))      ? $_GET['name']      : '';
$desc = (isset($_GET['desc']))      ? $_GET['desc']      : '';
$wkst = (isset($_GET['weekstart'])) ? $_GET['weekstart'] : '';
$wknd = (isset($_GET['week_end']))  ? $_GET['week_end']  : '';
$ltst = (isset($_GET['latestart'])) ? $_GET['latestart'] : '';
$ltnd = (isset($_GET['late_end']))  ? $_GET['late_end']  : '';
$copy = (isset($_GET['copy']))      ? $_GET['copy']      : '';
$gone = (isset($_GET['gone']))      ? $_GET['gone']      : '';
$orig = (isset($_GET['origin']))    ? $_GET['origin']    : '';
$res = squery("select value from settings where variable='weekend'",$mysqli);
$weekend = $res['value'];
$weekdays = 254 ^ $weekend;
if (($name != '') && ($copy == 'copy') && ($orig != '')) {
   $mysqli->query("insert into child set name='$name', description='$desc'");
   $res = squery("select user_id from child where name='$name'",$mysqli);
   $id = $res['user_id'];
   $mysqli->query("insert into rules (user_id, night, morning, days) select $id, night, morning, days from rules where user_id=$orig");
   header("Location: index.php"); 
} elseif (($name != '') && ($wkst != '') && ($wknd != '') && ($ltst != '') && ($ltnd != '')) {
   $mysqli->query("insert into child set name='$name', description='$desc'");
   $mysqli->query("insert into rules (user_id, night, morning, days) select user_id, '$wkst', '$wknd', $weekdays from child where name='$name'");
   $mysqli->query("insert into rules (user_id, night, morning, days) select user_id, '$ltst', '$ltnd', $weekend  from child where name='$name'");
   header("Location: index.php");
} elseif ($gone != '') {
   $mysqli->query("delete from child where user_id=$gone");
   $mysqli->query("delete from rules where user_id=$gone");
   header("Location: index.php");
} else {
   echo "<html><head><title>Add a child</title>\n";
   echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body>\n";
   echo "<h1>Add/Remove a Child</h1><h2>Add</h2>";
   echo "<form name=\"addchild\">\n";
   $res = $mysqli->query("select user_id,name from child order by name");
   $numrows = $res->num_rows;
   if ($numrows > 0) {
      $children = array();
      echo "<input type=\"checkbox\" name=\"copy\" value=\"copy\"> Copy bedtimes from ";
      if ($numrows == 1) {
         $row = $res->fetch_assoc(); 
         $id = $row['user_id'];
         $cn = $row['name'];
         $children[$id] = $cn;
         echo $cn."?";
         echo "<input type=\"hidden\" name=\"origin\" value=\"$id\">\n";
      } else {
         echo "<select name =\"origin\">\n";
         while ($row = $res->fetch_assoc()) {
            $id = $row['user_id'];
            $cn = $row['name'];
            $children[$id] = $cn;
            echo "<option value=\"$id\">$cn</option>\n";
         }
         echo "</select>\n";
      }
      echo "<br><br>";
   }
   $res->close();
}
?>
Name: <input type="text" name="name" value="<?php echo $name;?>"> Description: <input type="text" name="desc" value="<?php echo $desc;?>"><p>
<table border="0">
<tr><td>Weekend</td><td>Start:</td><td><input type="text" name="latestart"></td><td>End:</td><td><input type="text" name="late_end"></td></tr>
<tr><td>School night</td><td>Start:</td><td><input type="text" name="weekstart"></td><td>End:</td><td><input type="text" name="week_end"></td></tr>
</table>
<hr>
<h2>Remove</h2>
<select name="gone">
<option value=""> </option>
<?php
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n"; 
}
?>
</select> Is responsible enough to switch off their devices for bed. Remove them from bedtime.<br><br><hr>
<input type="submit" value="submit">
</form>
<br>Cancel and <a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime</body></html>
