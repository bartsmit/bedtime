<?php
include "dbconn.php";
if (isset($_GET['we_start'])) {
    $we_st = $_GET['we_start'];
    $mysqli->query("update settings set value='$we_st' where variable='weekend'");
}
echo "<html><head><title>Settings</title></head><body>\n";
echo ">>Change settings for bedtime<br><br>\n";
echo "<form name=\"settings\">\n";
$weekdays = array(
130 => "Monday",
192 => "Tuesday",
96  => "Wednesday",
48  => "Thursday",
24  => "Friday",
12  => "Saturday",
6   => "Sunday");
$res  = $mysqli->query("select value from settings where variable='weekend'");
$row  = $res->fetch_assoc();
$mask = $row['value'];
echo "First day of the weekend: <select name=\"we_start\">\n";
foreach ($weekdays as $key => $day) {
   $sel = ($key == $mask) ? ' selected' : '';
   echo "<option value=\"$key\"$sel>$day</option>\n";
}
echo "</select><hr>";
?>
<br><input type="submit" value="submit">
</form>
<a href="index.php">return</a>
</body></html>
