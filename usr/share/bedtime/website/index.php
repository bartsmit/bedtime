<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.html"); }
include "dbconn.php";
$sall = (isset($_GET['sel_all']));
$awst = (isset($_GET['a_w_start']))   ? $_GET['a_w_start']   : '';
$awnd = (isset($_GET['a_w_end']))     ? $_GET['a_w_end']     : '';
$asst = (isset($_GET['a_s_start']))   ? $_GET['a_s_start']   : '';
$asnd = (isset($_GET['a_s_end']))     ? $_GET['a_s_end']     : '';
$grni = (isset($_GET['ground']))      ? $_GET['ground']      : '';
$grnu = (isset($_GET['ground_num']))  ? $_GET['ground_num']  : '';
$grnt = (isset($_GET['ground_time'])) ? $_GET['ground_time'] : '';
$grnd = (isset($_GET['ground_date'])) ? $_GET['ground_date'] : '';
$rewi = (isset($_GET['reward']))      ? $_GET['reward']      : '';
$rewu = (isset($_GET['reward_num']))  ? $_GET['reward_num']  : '';
$rewt = (isset($_GET['reward_time'])) ? $_GET['reward_time'] : '';
$rewd = (isset($_GET['reward_date'])) ? $_GET['reward_date'] : '';

$mysqli->query("delete from ground where end < now()");
$mysqli->query("delete from reward where end < now()");

$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "u\n";
socket_write($sock,$buf,strlen($buf));

$four = (($awst != '') && ($awnd != '') && ($asst != '') && ($asnd != ''));
$res = squery("select value from settings where variable='weekend'",$mysqli);
$weekend = $res['value']; $weekdays = 254 ^ $weekend;
$sql_lst = "select child.user_id,name,description,
            (select count(*) from ground where ground.user_id=rules.user_id) as gc,
            (select max(end) from ground where ground.user_id=rules.user_id) as ge,
            (select count(*) from reward where reward.user_id=rules.user_id) as rc,
            (select max(end) from reward where reward.user_id=rules.user_id) as re,
            max(if(days=$weekend, night,null)) as w_fm,
            max(if(days=$weekend, morning,null)) as w_to,
            max(if(days=$weekdays,night,null)) as s_fm,
            max(if(days=$weekdays,morning,null)) as s_to
            from rules inner join child on child.user_id=rules.user_id group by name";
$res = $mysqli->query($sql_lst);
while ($row = $res->fetch_assoc()) {
   $id  = $row['user_id'];
   if (($four) && (($sall) || (isset($_GET['sel'.$id])))) {
      $wst = $awst; $wnd = $awnd; $sst = $asst; $snd = $asnd;
   } else {
      $wst = (isset($_GET['w_start'.$id])) ? $_GET['w_start'.$id] : $row['w_fm'];
      $wnd = (isset($_GET['w_end'.$id]))   ? $_GET['w_end'.$id]   : $row['w_to'];
      $sst = (isset($_GET['s_start'.$id])) ? $_GET['s_start'.$id] : $row['s_fm'];
      $snd = (isset($_GET['s_end'.$id]))   ? $_GET['s_end'.$id]   : $row['s_to'];
   }
   $mysqli->query("update rules set night='$wst', morning='$wnd' where user_id='$id' and days='$weekend'");
   $mysqli->query("update rules set night='$sst', morning='$snd' where user_id='$id' and days='$weekdays'");
}

if ($grni != '') {
   if ($grnu == '0') {
      $sql = "delete from ground where user_id='$grni'";
   } else {
      if ($grnu != '') {
         $end = "date_add(now(),interval floor($grnu) ".$_GET['ground_unit'].")";
      } elseif (($grnt != '') && ($grnd != '')) {
         $g_time = explode(':',$grnt);
         $hour = $g_time[0]; $min = $g_time[1]; $sec = $g_time[2];
         $g_date = explode('-',$grnd);
         $year = $g_date[0]; $month = $g_date[1]; $day = $g_date[2];
         $end = "'$grnd $grnt'";
      }
      $res = $mysqli->query("select * from ground where user_id='$grni'");
      $sql = ($res->num_rows == 0) ? "insert into ground values ('$grni',now(),$end)" : "update ground set start=now(), end=$end where user_id='$grni'";
   }
   $mysqli->query($sql);
}

if ($rewi != '') {
   if ($rewu == '0') {
      $sql = "delete from reward where user_id='$rewi'";
   } else {
      if ($rewu != '') {
         $end = "date_add(now(),interval floor($rewu) ".$_GET['reward_unit'].")";
      } elseif (($rewt !='') && ($rewd != '')) {
         $r_time = explode(':',$rewt);
         $hour = $r_time[0]; $min = $r_time[1]; $sec = $r_time[2];
         $r_date = explode('-',$rewd);
         $year = $r_date[0]; $month = $r_date[1]; $day = $r_date[2];
         $end = "'$rewd $rewt'";
      }
      $res = $mysqli->query("select * from reward where user_id='$rewi'");
      $sql = ($res->num_rows == 0) ? "insert into reward values ('$rewi',now(),$end)" : "update reward set start=now(), end=$end where user_id='$rewi'";
   }
   $mysqli->query($sql);
}
?>
<html><head><title>Bedtime</title>
<link rel="stylesheet" type="text/css" href="desktop.css">
</head><body>
<h1>Bedtime on <?php echo $_SERVER['SERVER_ADDR'] ?></h1>
<h2><a href="addchild.php">Add/remove a child</a></h2>
<hr>
<h2>Edit bedtimes</h2>
<form name="main">
<?php
$res = $mysqli->query($sql_lst);
$numrows = $res->num_rows;
if ($numrows == 0) {
   echo "You have not entered any children yet. <a href=\"addchild.php\">Add a child</a>\n";
} else {
   $children = array();
   echo "<table border=\"0\">\n";
   echo "<th>Name</th><th>Description</th><th>Set <input type=\"checkbox\" name=\"sel_all\" value=\"sel_all\"></th>";
   echo "<th>Weekend from: <input type=\"text\" name=\"a_w_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_w_end\" size=\"8\"></th>";
   echo "<th>School night from: <input type=\"text\" name=\"a_s_start\" size=\"8\"></th>";
   echo "<th>to: <input type=\"text\" name=\"a_s_end\" size=\"8\"></th>\n";
   while ($row = $res->fetch_assoc()) {
      $id = $row['user_id']; $cn = $row['name']; $ds = $row['description'];
      $children[$id] = $cn;
      $wf = $row['w_fm'];$wt = $row['w_to']; $sf = $row['s_fm']; $st = $row['s_to'];
      $en = ''; $rn = ''; $gn = '';
      if ($row['rc'] > 0) { $rn = " <strong>R</strong>"; $en = "reward ends ".$row['re']; }
      if ($row['gc'] > 0) { $gn = " <strong>G</strong>"; $en = "ground ends ".$row['ge']; }
      echo "<tr><td><div title =\"$en\">$cn $gn $rn</div></td><td align=\"right\">$ds</td><td align=\"right\">";
      echo "<input type=\"checkbox\" name=\"sel$id\" value=\"sel$id\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"w_start$id\" value=\"$wf\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"w_end$id\" value=\"$wt\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"s_start$id\" value=\"$sf\" size=\"8\"></td>\n";
      echo "<td align=\"right\"><input type=\"text\" name=\"s_end$id\" value=\"$st\" size=\"8\"></td></tr>\n";
   }
   echo "</table>\n";
}
?>
<br>Edit bedtime per child, or edit all selected children in the top row. Tick the top box to apply top row to all children.
<hr>
<h2>Ground</h2>
<select name="ground">
<option value=""></option>
<?php
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n";
}
?>
</select> for <input type="text" size="4" name="ground_num"> <select name="ground_unit">
<option value="hour">hour(s)</option><option value="day">day(s)</option>
<option value="week">week(s)</option><option vaule="month">month(s)</option>
</select> Or until <input type="text" size="8" name="ground_time"> hh:mm:ss on <input type="text" size="8" name="ground_date"> yyyy-mm-dd<br>
<br>Remove a child from grounding by grounding them for zero hours
<hr>
<h2>Reward</h2>
<select name="reward">
<option value=""></option>
<?php
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n";
}
?>
</select> for <input type="text" size="4" name="reward_num"> <select name="reward_unit">
<option value="hour">hour(s)</option><option value="day">day(s)</option>
<option value="week">week(s)</option><option vaule="month">month(s)</option>
</select> Or until <input type="text" size="8" name="reward_time"> hh:mm:ss on <input type="text" size="8" name="reward_date"> yyyy-mm-dd<br>
<br>Remove a child's reward by rewarding them for zero hours.
<hr>
<h2><a href="devices.php">Devices</a></h2>
<hr>
<h2><a href="settings.php">Parents and Settings</a></h2>
<hr>
<input type="submit" value="submit">
</form><br>
Cancel and <a href="index.php">reset</a><br>
Or <a href="logout.html">log out</a> of Bedtime
</body></html>
