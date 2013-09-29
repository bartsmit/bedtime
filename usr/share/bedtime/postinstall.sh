#!/bin/bash

setsebool -P httpd_can_network_connect 1

/etc/init.d/iptables start && chkconfig iptables on
/etc/init.d/ip6tables start && chkconfig ip6tables on

iptables -F FORWARD
iptables -I INPUT 4 -m state --state NEW -p tcp -m tcp --dport 80 -j ACCEPT
iptables -I INPUT 4 -m state --state NEW -p tcp -m tcp --dport 3128 -j ACCEPT
iptables -I INPUT 4 -m state --state NEW -p udp -m udp --dport 5353 -j ACCEPT
iptables -I INPUT 4 -m state --state NEW -p udp -m udp --dport 67:68 -j ACCEPT
/etc/init.d/iptables save

ip6tables -I INPUT 4 -m state --state NEW -p tcp -m tcp --dport 80 -j ACCEPT
ip6tables -I INPUT 4 -m state --state NEW -p tcp -m tcp --dport 3128 -j ACCEPT
ip6tables -I INPUT 4 -m state --state NEW -p udp -m udp --dport 5353 -j ACCEPT
/etc/init.d/ip6tables save

/etc/init.d/mysqld start && chkconfig mysqld on
mysql < /usr/share/bedtime/create.sql

mv /etc/squid/squid.conf /etc/squid/squid.conf.rpmsave
cp /usr/share/bedtime/squid.conf /etc/squid
/etc/init.d/squid start || chkconfig squid on

/etc/init.d/ntpd stop
ntpdate 0.pool.ntp.org.
/etc/init.d/ntpd start || chkconfig ntpd on

/etc/init.d/httpd start || chkconfig httpd on
/etc/init.d/messagebus start || chkconfig messagebus on
/etc/init.d/avahi-daemon start || chkconfig avahi-daemon on

cp /usr/share/bedtime/bedtime /etc/init.d
chmod 755 /etc/init.d/bedtime
/etc/init.d/bedtime start || chkconfig bedtime on