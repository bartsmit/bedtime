<?php
include "dbconn.php";
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
echo "</select><hr>";
?>
<input type="submit" value="submit">
</form>
<a href="index.php">return</a>
</body></html>
