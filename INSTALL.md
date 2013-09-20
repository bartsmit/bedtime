bedtime installation
====================
Perform a minimum CentOS install on a spare computer

Install the prerequisite packages:
  sudo yum -y install php-mysql mysql-server mod_auth_mysql squid avahi nss-mdns dbus ntp

Run the postinstall.sh script

Copy the files to /usr/share/bedtime
Copy or move the files/directories under /usr/share/bedtime/etc to /etc

Install Avahi/ZeroConf/Bonjour if you are on a non-Apple computer
Browse with Chrome/Opera/IE/Firefox with DNSSD extension to http://bedtime.local
Follow the instructions on the install pages
Set parents with passwords and change/delete the default admin user
Add your children
Set their bedtimes
Assign their devices to them
