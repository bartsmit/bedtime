<style>
table, td, th
{
padding: 1px 5px;
}
</style>
<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.html"); }
include "dbconn.php";

$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "d\n";
socket_write($sock,$buf,strlen($buf));

if (isset($_GET["manlst"])) {
   $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
   $buf = "m\n";
   $result = socket_connect($sock,'127.0.0.1',5000);
   socket_write($sock,$buf,strlen($buf));
}

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
echo "<html><head><title>Manage Devices</title>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body><h1>Devices</h1>\n";
echo "Assign a child to each device from the drop-down list. Delete devices that are no longer around<br>\n";
$sql  = "select inet_ntoa(ip) as ip,
         (select name from child where device.user_id = child.user_id) as owner,
         (select short from manufacturers where
            (manufacturers.mac & x'FFFFFF000000') = (device.mac & x'FFFFFF000000') or
            (manufacturers.mac & x'FFFFFFFFF000') = (device.mac & x'FFFFFFFFF000')) as vendor,
         (select description from manufacturers where
            (manufacturers.mac & x'FFFFFF000000') = (device.mac & x'FFFFFF000000') or
            (manufacturers.mac & x'FFFFFFFFF000') = (device.mac & x'FFFFFFFFF000')) as label,
         lpad(hex(mac),12,'0') as mac,
         first_seen as first,
         user_id as id from device;";
$res = $mysqli->query($sql);
echo "<form name=\"devices\"><table borders=\"0\">\n";
echo "<th>Owner</th><th>Make</th><th>MAC</th><th>IP address</th><th>First seen</th><th>Delete</th>\n";
$kill_list = '';
while($obj = $res->fetch_object()) {
   $did    = $obj->id;
   $owner  = $obj->owner;
   $mac    = $obj->mac;
   $ip     = $obj->ip;
   $vendor = $obj->vendor;
   $label  = $obj->label;
   $first  = $obj->first;
   $newid  = (isset($_GET["o_$mac"])) ? $_GET["o_$mac"] : '0';
   $remove = (isset($_GET["d_$mac"])) ? $_GET["d_$mac"] : '';
   if (($newid <> '0') && ($newid <> $did)) {
      # The new owner is non-zero and different from the one in the database
      $mysqli->query("update device set user_id=$newid where lpad(hex(mac),12,'0') = '$mac'");
      $did = $newid;
   }
   if ($remove == '') {
      echo "<tr><td><select name=\"o_$mac\">\n";
      foreach ($children as $id => $name) {
         echo "<option value=\"$id\""; 
         if ($did == $id) { echo " selected"; }
         echo ">$name</option>\n";
      }
      $dis_mac = rtrim(strtolower(chunk_split($mac,2,'-')),'-');
      echo "</select></td><td><div title=\"$label\">$vendor</div></td><td>$dis_mac</td><td>$ip</td><td>$first</td>\n";
      echo "<td><input type=\"checkbox\" name=\"d_$mac\" value=\"d_$mac\"></td></tr>\n";
   } else {
      $kill_list .= "x'$mac',";
   }
}
$kill_list = rtrim($kill_list,',');
$res = $mysqli->query("delete from device where mac in ($kill_list)");
?>
</table><br>
Press submit to update devices.
<input type="checkbox" name="manlst"> also update manufacturers list (may take a minute)<br><hr>
<input type="submit" value="submit">
</form>
<a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime</body></html>
