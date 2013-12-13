<?php
if($_FILES['dump']['name']) {
   $fname = $_FILES['dump']['name'];
   if(!$_FILES['dump']['error']) {
      move_uploaded_file($_FILES['dump']['tmp_name'], "/tmp/$fname");
      $zip = new ZipArchive;
      if ($zip->open("/tmp/$fname") === TRUE) {
         $zip->extractTo('/tmp');
         $zip->close();
      }
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "r$fname\n";
      socket_write($sock,$buf,strlen($buf));
      header("Location: settings.php");
   } else {
      echo "An error occurred in the upload of the restore file\n";
      echo $_FILES['dump']['error']."\n";
   }
}
?>
