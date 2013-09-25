bedtime installation
====================
Perform a minimum CentOS install on a spare computer<br>
<br>
Install the prerequisite packages:<br>
  sudo yum -y install php-mysql mysql-server mod_auth_mysql squid avahi nss-mdns dbus ntp
<br>
Run the postinstall.sh script<br>
<br>
Copy the files to /usr/share/bedtime<br>
Copy or move the files/directories under /usr/share/bedtime/etc to /etc<br>
<br>
Install Avahi/ZeroConf/Bonjour if you are on a non-Apple computer<br>
Browse with Chrome/Opera/IE/Firefox with DNSSD extension to http://bedtime.local<br>
Follow the instructions on the install pages<br>
Set parents with passwords and change/delete the default admin user<br>
Add your children<br>
Set their bedtimes<br>
Assign their devices to them<br>
