<?php
/*
This script manages the child and initial rules tables.
For efficiency, it allwos you to copy initial rules
from another child or start from scratch
The remove option deletes a child from the table
*/

# This script is secured by parent login
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
# And requires a database connection
include "dbconn.php";
# Pick up the variables from the web form on submit.
# Unset variables will be set to the empty string
$name = (isset($_GET['name']))      ? $_GET['name']      : '';
$desc = (isset($_GET['desc']))      ? $_GET['desc']      : '';
$wkst = (isset($_GET['weekstart'])) ? $_GET['weekstart'] : '';
$wknd = (isset($_GET['week_end']))  ? $_GET['week_end']  : '';
$ltst = (isset($_GET['latestart'])) ? $_GET['latestart'] : '';
$ltnd = (isset($_GET['late_end']))  ? $_GET['late_end']  : '';
$copy = (isset($_GET['copy']))      ? $_GET['copy']      : '';
$gone = (isset($_GET['gone']))      ? $_GET['gone']      : '';
$orig = (isset($_GET['origin']))    ? $_GET['origin']    : '';
# Get the weekend days mask from the settings table
$res = $mysqli->query("select value from settings where variable='weekend'");
$row = $res->fetch_assoc(); $weekend = $row['value'];
# All non-weekend days are weekdays
$weekdays = 254 ^ $weekend;
# If we have a new name, copy is selected and a template child is picked
if (($name != '') && ($copy == 'copy') && ($orig != '')) {
   $mysqli->query("insert into child set name='$name', description='$desc'");
   # Get the auto incremented ID
   $res = $mysqli->query("select user_id from child where name='$name'");
   $row = $res->fetch_assoc(); $id = $row['user_id'];
   # Copy the rules from the template
   $mysqli->query("insert into rules (user_id, night, morning, days) select $id, night, morning, days from rules where user_id=$orig");
   # All done; go back to the main page
   header("Location: index.php"); 
# Otherwise, if we have a new name with four non-zero times
} elseif (($name != '') && ($wkst != '') && ($wknd != '') && ($ltst != '') && ($ltnd != '')) {
   # Set the rules for the new child
   $mysqli->query("insert into child set name='$name', description='$desc'");
   $mysqli->query("insert into rules (user_id, night, morning, days) select user_id, '$wkst', '$wknd', $weekdays from child where name='$name'");
   $mysqli->query("insert into rules (user_id, night, morning, days) select user_id, '$ltst', '$ltnd', $weekend  from child where name='$name'");
   # And go back to the main page
   header("Location: index.php");
# No valid new child. Check if there is one to be deleted
} elseif ($gone != '') {
   # Remove the child from the system
   $mysqli->query("delete from child where user_id=$gone");
   $mysqli->query("delete from rules where user_id=$gone");
   # And go back to the main page
   header("Location: index.php");
} else {
   # First load of the page with no settings made
   # Draw the page with the form 
   echo "<html><head><title>Add a child</title>\n";
   echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body>\n";
   echo "<h1>Add/Remove a Child</h1><h2>Add</h2>";
   echo "<form name=\"addchild\">\n";
   # See if there is a child in the database
   $res = $mysqli->query("select user_id,name from child where user_id > 0 order by name");
   $numrows = $res->num_rows;
   if ($numrows > 0) {
      # At least one child so offer the copy option
      $children = array();
      echo "<input type=\"checkbox\" name=\"copy\" value=\"copy\"> Copy bedtimes from ";
      # If there is only one, offer it as the template
      if ($numrows == 1) {
         $row = $res->fetch_assoc(); 
         $id = $row['user_id'];
         $cn = $row['name'];
         $children[$id] = $cn;
         echo $cn."?";
         echo "<input type=\"hidden\" name=\"origin\" value=\"$id\">\n";
      # There are multiple templates to choose from. Offer a drop-down list
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
# Render the form elements
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
# And the selection for removal
foreach ($children as $id => $name) {
   echo "<option value=\"$id\">$name</option>\n"; 
}
?>
</select> Is responsible enough to switch off their devices for bed. Remove them from bedtime.<br><br><hr>
<input type="submit" value="submit">
</form>
<br>Cancel and <a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime<br><br>
<?php
# Render the version below the submit button, return and logout links.
$res = $mysqli->query("select value from settings where variable='version'");
$row = $res->fetch_assoc(); $ver = $row['value'];
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
