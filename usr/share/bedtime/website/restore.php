<?php
/*
This script takes the file selection input
from the restore part of the settings script.

It extracts the sql dump from the zip and
puts this next to the zip in /tmp

Note that the /tmp location can be specific
to the systemd process ID in later versions
*/

# Pick up the file name from the server FILES array
if($_FILES['dump']['name']) {
   $fname = $_FILES['dump']['name'];
   # If there is no error
   if(!$_FILES['dump']['error']) {
      # Move the file to the temporary directory /tmp
      move_uploaded_file($_FILES['dump']['tmp_name'], "/tmp/$fname");
      # And open it as a ZIP archive
      $zip = new ZipArchive;
      if ($zip->open("/tmp/$fname") === TRUE) {
         $zip->extractTo('/tmp');
         $zip->close();
      }
      # The sql dump script has the same name
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "r$fname\n";
      socket_write($sock,$buf,strlen($buf));
      # Return to the settings page
      header("Location: settings.php");
   # If there is an error show a report
   } else {
      echo "An error occurred in the upload of the restore file\n";
      echo $_FILES['dump']['error']."\n";
   }
}
# No need for a footer since restores normally return to settings.php
?>
