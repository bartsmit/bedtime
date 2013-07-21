<?php
include "dbconn.php";
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
$sql  = "select inet_ntoa(ip) as ip, (select short from manufacturers where
         (manufacturers.mac & x'FFFFFF000000') = (device.mac & x'FFFFFF000000') or
         (manufacturers.mac & x'FFFFFFFFF000') = (device.mac & x'FFFFFFFFF000'))
         as vendor, first_seen as first from device";
$res = $mysqli->query($sql);
while($obj = $res->fetch_object()) {
   printf("The device with ip %s was made by %s and first seen on %s <br>\n",$obj->ip, $obj->vendor, $obj->first);
}
#while ($row = $res->fetch_assoc()) {
#   sprintf("The device has a mac of %s, is owned by %s and first seen on %s <br>\n", $row['mac'], $row['id'],$row['first']);
#}
?>
