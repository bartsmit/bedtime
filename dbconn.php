<?php
$conf = file('/etc/bedtime.conf');
$conf = preg_grep ("/^#/",$conf,PREG_GREP_INVERT);
$conf = preg_grep ("/^\s*$/",$conf,PREG_GREP_INVERT);

foreach ($conf as $line) {
   $bits = preg_split("/\s*=\s*/",$line);
   $vals[$bits[0]] = rtrim($bits[1]);
}

$mysqli = new mysqli ($vals["dbhost"], $vals["dbuser"], $vals["dbpass"], $vals["dbname"]);

if ($mysqli->connect_errno) {
   printf("Connect failed: %s\n",$mysqli->connect_error);
}

function squery($sql,$mysqli) {
   if ($result = $mysqli->query($sql)) {
      if (strtolower(substr($sql,0,6)) == 'select') {
         if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            return $row;
         } else {
            $array = array();
            while ($row = $result->fetch_assoc()) {
               array_push($array, $row);
            }
            return $array;
         }
         $result->close();
      }
   } else {
      return $mysqli->error;
   }
}
