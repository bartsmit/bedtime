<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
include "dbconn.php";
$myreg = (isset($_GET['region'])) ? $_GET['region'] : '';
$mytwn = (isset($_GET['town']))   ? $_GET['town']   : '';
if ($mytwn != '') {
   $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
   $result = socket_connect($sock,'127.0.0.1',5000);
   $buf = "t$mytwn\n";
   socket_write($sock,$buf,strlen($buf));
}
$res = $mysqli->query("select parent_id from parent");
$kill = '';
$pass_set = 0;
while ($row = $res->fetch_assoc()) {
   $id = $row['parent_id'];
   $pass = (isset($_GET["p_$id"])) ? $_GET["p_$id"] : '';
   if (!($pass == '')) {
      $mysqli->query("update parent set password=md5('$pass') where parent_id=$id");
      $pass_set = 1;
   }
   if (isset($_GET["d_$id"])) {
      $kill = $kill."$id,";
   }
}
$kill = rtrim($kill,',');
$mysqli->query("delete from parent where parent_id in ($kill)");
$name = (isset($_GET['name'])) ? $_GET['name'] : '';
$desc = (isset($_GET['desc'])) ? $_GET['desc'] : '';
$pass = (isset($_GET['pass'])) ? $_GET['pass'] : '';
if (! ($name == '')) {
   $mysqli->query("insert into parent (name,description,password) values('$name','$desc',md5('$pass'))");
   $pass_set = 1;
}
$res = $mysqli->query("select count(*) as num from parent");
$row = $res->fetch_assoc();
if ($row['num']==0) {
   $mysqli->query("insert into parent (name,password) values('admin',md5('admin'))");
}
if (isset($_GET['we_start'])) {
    $we_st = $_GET['we_start'];
    $mysqli->query("update settings set value='$we_st' where variable='weekend'");
}
if ($pass_set) {
   header("Location: index.php");
}
echo "<html><head><title>Settings</title>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body><h1>Parents and Settings</h1>\n";
echo "<h2>Parents</h2>\n";
echo "<form name=\"settings\"><table borders=\"0\">\n";
echo "<th>Name</th><th>Description</th><th>Password</th><th>Delete</th>\n";
$parents = array();
$res = $mysqli->query("select name, description, parent_id, password from parent");
while($obj = $res->fetch_object()) {
   $id = $obj->parent_id;
   $cn = $obj->name;
   $ds = $obj->description;
   $pw = $obj->password;
   echo "<tr><td>$cn</td><td>$ds</td><td><input type=\"password\" name=\"p_$id\"></td>";
   echo "<td><input type=\"checkbox\" name=\"d_$id\" value=\"d_$id\"></td></tr>\n";
}
echo "</table><br>Add Parent<br>Name <input type=\"text\" name=\"name\"> Description ";
echo "<input type=\"text\" name=\"desc\"> Password <input type=\"password\" name=\"pass\"><hr>\n";
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
echo "<h2>Settings</h2>First day of the weekend: <select name=\"we_start\">\n";
foreach ($weekdays as $key => $day) {
   $sel = ($key == $mask) ? ' selected' : '';
   echo "<option value=\"$key\"$sel>$day</option>\n";
}
echo "</select><br>";
# See if the region and town have been set
if ($myreg == '') {
   $res = $mysqli->query("select value from settings where variable='region'");
   if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $myreg = $row['value'];
   }
} else {
   $res = $mysqli->query("replace into settings values('region','$myreg')");
}
if ($mytwn == '') {
   $res = $mysqli->query("select value from settings where variable='town'");
   if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $mytwn = $row['value'];
   }
} else {
   $res = $mysqli->query("replace into settings values('town','$mytwn')");
}

$res = $mysqli->query("select left(Name,locate('/',Name)-1) as region from time_zone_name group by region order by region");
if ($res->num_rows == 0) {
   echo "Enter timezone: <input type=\"text\" name=\"man_region\">\n";
} else {
   echo "Region: <select name=\"region\">\n";
   while ($row = $res->fetch_assoc()) {
      $reg = $row['region'];
      $sel = ($reg == $myreg) ? ' selected ' : '';
      echo "<option value=\"$reg\"$sel>$reg</option>\n";
   }
   echo "</select><br>\n";
}
if ($myreg != '') {
   $res = $mysqli->query("select Name as town from time_zone_name where Name like '$myreg/%' order by town");
   echo "Timezone: <select name=\"town\">\n";
   while ($row = $res->fetch_assoc()) {
      $twn = $row['town'];
      $sel = ($twn == $mytwn) ? ' selected ' : '';
      echo "<option value\"$twn\"$sel>$twn</option>\n";
   }
   echo "</select><br>\n";
}
if (($mytwn != '') && (strpos($mytwn, $myreg)!== false)) {
   echo "The timezone is set to $mytwn<br>\n";
}
?>
<hr><input type="submit" value="submit">
</form>
<a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime</body></html>
