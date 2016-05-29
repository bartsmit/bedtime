#!/bin/bash

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
iptables-save > /etc/iptables/rules.v4

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
ip6tables-save > /etc/iptables/rules.v6

/usr/share/bedtime/bin/setconfs

cp /usr/share/bedtime/bedtime.service /lib/systemd/system/
systemctl daemon-reload
systemctl enable mysql.service
systemctl start mysql.service
systemctl enable squid3.service
systemctl start squid3.service
systemctl enable ntp.service
systemctl start ntp.service
systemctl enable apache2.service
systemctl start apache2.service
systemctl enable avahi-daemon.service
systemctl start avahi-daemon.service
systemctl enable isc-dhcp-server.service
systemctl enable bedtime.service
systemctl start bedtime.service

mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql
mysql < /usr/share/bedtime/create.sql
mysql -e "create table bedtime.time_zone_name select * from mysql.time_zone_name;"
