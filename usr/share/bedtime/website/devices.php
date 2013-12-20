<style>
table, td, th
{
padding: 1px 5px;
}
</style>
<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
include "dbconn.php";

$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "d\n";
socket_write($sock,$buf,strlen($buf));

$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "u\n";
socket_write($sock,$buf,strlen($buf));

$res = $mysqli->query("select user_id, name from child order by name");
$numrows = $res->num_rows;
if ($numrows > 0) {
   $children = array();
   while ($obj = $res->fetch_object()) {
      $id = $obj->user_id;
      $cn = $obj->name;
      $children[$id] = $cn;
   }
} else {
   header("Location: addchild.php");
}

if (isset($_GET['sortdev'])) {
   $sortdev = $_GET["sortdev"];
   $mysqli->query("replace into settings values('sortdev','$sortdev')");
} 

if (isset($_GET['dirdev'])) {
   $dirdev = $_GET['dirdev'];
   $mysqli->query("replace into settings values('dirdev','$dirdev')");
}

$sortopts = array(
'owner' => 'owner',
'make'  => 'make',
'descr' => 'description',
'mac'   => 'mac address',
'ip'    => 'ip address',
'first' => 'first seen');

echo "<html><head><title>Manage Devices</title>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body><h1>Devices</h1>\n";
echo "Assign a child to each device from the drop-down list. Delete devices that are no longer around\n";
echo "<br><br><form name=\"devices\">\n";
echo "Sort device list by <select name=\"sortdev\">\n";
$res = $mysqli->query("select value from settings where variable='sortdev'");
$row = $res->fetch_assoc();
$sortdev = $row['value'];
foreach ($sortopts as $key => $opt) {
   $sel = ($key == $sortdev) ? ' selected ' : '';
   echo "<option value=\"$key\"$sel>$opt</option>\n";
}
echo "</select>\n";
$res = $mysqli->query("select value from settings where variable='dirdev'");
$row = $res->fetch_assoc();
$dirdev = $row['value'];
echo "<input type=\"radio\" name=\"dirdev\" value=\"ascending\"";
if ($dirdev == 'ascending') { echo " checked=\"checked\" "; }
echo "/> Ascending ";
echo "<input type=\"radio\" name=\"dirdev\" value=\"descending\"";
if ($dirdev == 'descending') { echo " checked=\"checked\" "; }
echo "/> Descending<br>\n";
if ((isset($sortdev)) && (isset($dirdev))) {
   $dir  = (substr($dirdev,0,4) == 'desc') ? ' desc' : '';
   $sort = " order by $sortdev".$dir;
} else {
   $sort = '';
}
$sql = "select inet_ntoa(ip) as ip,
        (select name from child where device.user_id = child.user_id) as owner,
        manu as make,
        lpad(hex(mac),12,'0') as mac,
        first_seen  as first,
        description as descr,
        user_id as id from device $sort;";
$res = $mysqli->query($sql);
echo "<table borders=\"0\"><th>Owner</th><th>Make</th><th>Description</th>";
echo "<th>MAC</th><th>IP address</th><th>First seen</th><th>Delete</th>\n";
$kill_list = '';
while($obj = $res->fetch_object()) {
   $did    = $obj->id;
   $owner  = $obj->owner;
   $mac    = $obj->mac;
   $ip     = $obj->ip;
   $label  = preg_replace('/\|/',',',$obj->make);
   $first  = $obj->first;
   $descr  = $obj->descr;
   $newid  = (isset($_GET["o_$mac"])) ? $_GET["o_$mac"] : '';
   $remove = (isset($_GET["d_$mac"])) ? $_GET["d_$mac"] : '';
   $newdsc = (isset($_GET["l_$mac"])) ? $_GET["l_$mac"] : '';
   $bits   = explode("|",$obj->make);
   $vendor = substr(preg_replace('/\s+|,/','',$bits[0]),0,8);
   if (($newid <> '') && ($newid <> $did)) {
      # The new owner is non-zero and different from the one in the database
      $mysqli->query("update device set user_id=$newid where lpad(hex(mac),12,'0') = '$mac'");
      $did = $newid;
   }
   if (($newdsc <> '') && ($newdsc <> $descr)) {
      # Ditto for the description
      $mysqli->query("update device set description='$newdsc' where lpad(hex(mac),12,'0') = '$mac'");
      $descr = $newdsc;
   }
   if ($remove == '') {
      echo "<tr><td><select name=\"o_$mac\">\n";
      echo "<option value=\"0\"> </option>\n";
      foreach ($children as $id => $name) {
         echo "<option value=\"$id\""; 
         if ($did == $id) { echo " selected"; }
         echo ">$name</option>\n";
      }
      $dis_mac = rtrim(strtolower(chunk_split($mac,2,'-')),'-');
      echo "</select></td><td><div title=\"$label\">$vendor</div></td>";
      echo "<td><input type=\"text\" value=\"$descr\" name=\"l_$mac\"></td>";
      echo "<td>$dis_mac</td><td>$ip</td><td>$first</td>\n";
      echo "<td><input type=\"checkbox\" name=\"d_$mac\" value=\"d_$mac\"></td></tr>\n";
   } else {
      $kill_list .= "x'$mac',";
      # Get the mac out of the leases file
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "k$mac\n";
      socket_write($sock,$buf,strlen($buf));
   }
}
$kill_list = rtrim($kill_list,',');
$res = $mysqli->query("delete from device where mac in ($kill_list)");
?>
</table><br>Press submit to update devices.<hr>
<input type="submit" value="submit">
</form>
<a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime<br><br>
<?php
$res = squery("select value from settings where variable='version'",$mysqli);
$ver = $res['value'];
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
