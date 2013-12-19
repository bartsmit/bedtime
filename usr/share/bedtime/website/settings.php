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
$res = $mysqli->query("select hol_id from holiday");
$kill = '';
while ($row = $res->fetch_assoc()) {
   $id = $row['hol_id'];
   if (isset($_GET["hd_$id"])) {
      $kill = $kill."$id,";
   }
}
$kill = rtrim($kill,',');
$mysqli->query("delete from holiday where hol_id in($kill)");
$newhol = (isset($_GET['hname'])) ? $_GET['hname'] : '';
$start  = (isset($_GET['hstrt'])) ? $_GET['hstrt'] : '';
$stop   = (isset($_GET['hstop'])) ? $_GET['hstop'] : '';
if ((! ($newhol == '')) && (! ($start == '')) && (! ($stop == ''))) {
   $mysqli->query("insert into holiday (name,start,stop) values('$newhol','$start','$stop')");
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
   $nl = (isset($_GET["l_$id"])) ? $_GET["l_$id"] : '';
   if (($nl <> '') && ($nl <> $ds)) {
      $mysqli->query("update parent set description='$nl' where parent_id=$id");
      $ds = $nl;
   }
   echo "<tr><td>$cn</td><td><input type=\"text\" value=\"$ds\" name=\"l_$id\"></td>";
   echo "<td><input type=\"password\" name=\"p_$id\"></td>";
   echo "<td><input type=\"checkbox\" name=\"d_$id\" value=\"d_$id\"></td></tr>\n";
}
echo "</table><br>Add Parent<br>Name <input type=\"text\" name=\"name\"> Description ";
echo "<input type=\"text\" name=\"desc\"> Password <input type=\"password\" name=\"pass\"><hr>\n";
echo "<h2>School Holidays</h2><table borders=\"0\">\n";
echo "<th>Holiday</th><th>Start date</th><th>End date</th><th>Delete</th>\n";
$hols = array();
$res = $mysqli->query("select name, start, stop, hol_id from holiday");
while($obj = $res->fetch_object()) {
   $h_id  = $obj->hol_id;
   $name  = $obj->name;
   $strt  = $obj->start;
   $stop  = $obj->stop;
   $nstrt = (isset($_GET["hs_$id"])) ? $_GET["hs_$id"] : '';
   $nstop = (isset($_GET["hf_$id"])) ? $_GET["hf_$id"] : '';
   if (($nstrt <> '') && ($nstrt <> $strt)) {
      $mysqli->query("update holiday set start='$nstrt' where hol_id=$h_id");
   }
   if (($nstop <> '') && ($nstop <> $stop)) {
      $mysqli->query("update holiday set stop='$nstop' where hol_id=$h_id");
   }
   echo "<tr><td>$name</td><td><input type=\"text\" value=\"$strt\" name=\"hs_$h_id\" size=\"10\"></td>";
   echo "<td><input type=\"text\" value=\"$stop\" name=\"hf_$h_id\" size=\"10\"></td>";
   echo "<td><input type=\"checkbox\" name=\"hd_$h_id\" value=\"hd_$h_id\"></td></tr>\n";
}
echo "</table><br>Add Holiday<br>Name <input type=\"text\" name=\"hname\"> Start date (yyyy-mm-dd)";
echo "<input type=\"text\" name=\"hstrt\"> End date (yyyy-mm-dd)<input type=\"text\" name=\"hstop\"><hr>\n";
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
<br><br><input type="submit" value="Submit"> parent, holiday and settings changes.
</form><br><hr><h2>Backup/Restore</h2>
<a href="backup.php">backup</a><br>
<form action="restore.php" method="post" enctype="multipart/form-data">
Restore from: <input type="file" name="dump" size="40" />
<input type="submit" name="submit" value="Restore" /></form>
<?php
$res = squery("select value from settings where variable='version'",$mysqli);
$ver = $res['value'];
$res = squery("select value from settings where variable='rpm'",$mysqli);
$rpm = $res['value'];
$filepage = file_get_contents("http://sourceforge.net/projects/bedtime/files/");
preg_match('/bedtime([0-9]|\.|-)+tgz/',$filepage,$matches);
$latest = $matches[0];
preg_match("/href=\"http:\/\/(sourceforge\.net|sf\.net)\/projects\/bedtime\/files\/$latest\/download\"/",$filepage,$matches);
$latest = preg_replace('/bedtime-|\.tgz/','',$latest);
$url = $matches[0];
$url = preg_replace('/^href=\"|\"$/','',$url);
$res = squery("replace into settings (variable,value) values('latest','$latest'), ('latest_url','$url')",$mysqli);
if ($latest <> $ver) {
   echo "<hr><h2>Upgrade</h2>There is a newer version of bedtime ($latest).<br>\n";
   echo "Make sure you back up before you <a href=\"upgrade.php\">upgrade.</a>\n";
}
if ($rpm <> $ver) {
   echo "<hr><h2>Downgrade</h2>If you are having trouble with this version($ver).<br>\n";
   echo "You can <a href=\"downgrade.php\">downgrade</a> to version $rpm.\n";
   echo "Make a backup before you do so.\n";
}
?>
<hr>
<a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime<br><br>
<?php
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
