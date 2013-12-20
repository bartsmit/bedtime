#!/bin/bash

setsebool -P httpd_can_network_connect 1

cp -f /usr/share/bedtime/iptables-config /etc/sysconfig
cp -f /usr/share/bedtime/ip6tables-config /etc/sysconfig

iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X 
iptables -t mangle -F
iptables -t mangle -X

iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
iptables -A INPUT -i lo -j ACCEPT
iptables -A INPUT -p icmp -j ACCEPT
iptables -A INPUT -m conntrack --ctstate NEW -p tcp -m tcp --dport 22 -j ACCEPT
iptables -A INPUT -m conntrack --ctstate NEW -p tcp -m tcp --dport 80 -j ACCEPT
iptables -A INPUT -m conntrack --ctstate NEW -p tcp -m tcp --dport 3128 -j ACCEPT
iptables -A INPUT -m conntrack --ctstate NEW -p udp -m udp --dport 5353 -j ACCEPT
iptables -A INPUT -m conntrack --ctstate NEW -p udp -m udp --dport 67:68 -j ACCEPT
iptables -A INPUT -j REJECT --reject-with icmp-host-prohibited
iptables-save > /etc/sysconfig/iptables

ip6tables -F
ip6tables -X
ip6tables -t nat -F
ip6tables -t nat -X
ip6tables -t mangle -F
ip6tables -t mangle -X

ip6tables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
ip6tables -A INPUT -i lo -j ACCEPT
ip6tables -A INPUT -p ipv6-icmp -j ACCEPT
ip6tables -A INPUT -m conntrack --ctstate NEW -p tcp -m tcp --dport 80 -j ACCEPT
ip6tables -A INPUT -m conntrack --ctstate NEW -p tcp -m tcp --dport 3128 -j ACCEPT
ip6tables -A INPUT -m conntrack --ctstate NEW -p udp -m udp --dport 5353 -j ACCEPT
ip6tables -A INPUT -j REJECT --reject-with icmp6-adm-prohibited
ip6tables-save > /etc/sysconfig/ip6tables

/usr/share/bedtime/bin/setconfs

if hash systemctl 2>/dev/null; then
   cp /usr/share/bedtime/bedtime.service /lib/systemd/system/
   systemctl daemon-reload
   systemctl start mysqld.service
   systemctl enable mysqld.service
   systemctl start squid.service
   systemctl enable squid.service
   systemctl start ntpd.service
   systemctl enable ntpd.service
   systemctl start iptables.service
   systemctl enable iptables.service
   systemctl unmask ip6tables.service
   systemctl start ip6tables.service
   systemctl enable ip6tables.service
   apachectl start
   systemctl enable httpd.service
   systemctl unmask avahi-daemon.socket
   systemctl unmask avahi-daemon.service
   systemctl start avahi-daemon.service
   systemctl enable avahi-daemon.service
   systemctl enable dhcpd.service
   systemctl start bedtime.service
   systemctl enable bedtime.service
else
   /etc/init.d/mysqld start
   chkconfig mysqld on
   /etc/init.d/squid start
   chkconfig squid on
   /etc/init.d/ntpd stop
   ntpdate 0.pool.ntp.org.
   /etc/init.d/ntpd start
   chkconfig ntpd on
   /etc/init.d/httpd start
   chkconfig httpd on
   /etc/init.d/messagebus start
   chkconfig messagebus on
   /etc/init.d/avahi-daemon start
   chkconfig avahi-daemon on
   chkconfig dhcpd on
   cp /usr/share/bedtime/bedtime /etc/init.d
   chmod 755 /etc/init.d/bedtime
   /etc/init.d/bedtime start
   chkconfig bedtime on
fi
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql mysql
mysql < /usr/share/bedtime/create.sql
mysql -e "create table bedtime.time_zone_name select * from mysql.time_zone_name;"
