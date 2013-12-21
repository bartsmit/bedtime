create database if not exists bedtime;

use bedtime;

drop table if exists child;
create table child (
   user_id     mediumint(9) not null auto_increment comment 'glue record between tables',
   name        varchar(32)  not null                comment 'The name of your child',
   description varchar(128)                         comment 'Optional description',
   primary key (user_id)
) engine=MyISAM default charset=latin1 comment 'Maps child name to description and ID';

drop table if exists device;
create table device (
   mac         bigint(24)       not null     comment 'MAC address. Select with hex(mac), insert with conv(''MAC'',16,10)',
   description varchar(128)     default null comment 'Optional description',
   user_id     mediumint(9)     not null     comment 'glue record between tables',
   first_seen  datetime         not null     comment 'When it showed up in dhcp',
   ip          int(10) unsigned default null comment 'IP address from dhcp',
   manu        varchar(256)     default null comment 'OID manufacturer data',
   primary key (mac)
) engine=MyISAM default charset=latin1 comment 'Maps device MAC address to user ID and description with IP';

drop table if exists ground;
create table ground (
   user_id mediumint(9) not null comment 'ID of the miscreant',
   start   datetime     not null comment 'Time their punishment starts',
   end     datetime     not null comment 'Time they are off the hook again'
) engine=MyISAM default charset=latin1 comment 'Ground rules take precedent over bedtimes and rewards';

drop table if exists reward;
create table reward (
   user_id mediumint(9) not null comment 'ID of the little angel',
   start   datetime     not null comment 'Time their treat starts',
   end     datetime     not null comment 'Time they are back to normal'
) engine=MyISAM default charset=latin1 comment 'Sometimes you just want to be the cool parent';

drop table if exists holiday;
create table holiday (
   hol_id mediumint(9) not null auto_increment comment 'Internal ID for holidays',
   name   varchar(64)  not null                comment 'Name of the school holiday',
   start  date         not null                comment 'Lazy mornings start here',
   stop   date         not null                comment 'Oh well, it did not last forever',
   primary key (hol_id)
) engine=MyISAM default charset=latin1 comment 'Holidays is when there are no school nights';

drop table if exists parent;
create table parent (
   name        varchar(32)  not null                comment 'Login name of the parent',
   password    varchar(128) not null                comment 'Scrambled pass phrase',
   description varchar(128) not null                comment 'Optional description',
   parent_id   mediumint(9) not null auto_increment comment 'Internal ID for parents',
   primary key (parent_id)
) engine=MyISAM default charset=latin1 comment 'Authentication table for parent logins';

insert into parent (name,password,description) values('admin',md5('admin'),'delete me');

drop table if exists rules;
create table rules (
   user_id mediumint(9)        not null                comment 'glue record between tables',
   night   time                not null                comment 'Bedtime, the pivotal data',
   morning time                not null                comment 'Time to get up',
   days    tinyint(3) unsigned not null default '254'  comment 'Byte flag for days 0-Mon 7-Sun',
   rule_id mediumint(9)        not null auto_increment comment 'Unique rule ID to allow key',
   primary key (rule_id)
) engine=MyISAM default charset=latin1 comment 'This is the bread and butter of the project';

drop table if exists settings;
create table settings (
   variable varchar(16) not null comment 'name of the variable - duh',
   value    varchar(64) not null comment 'and the free text value',
   primary key (variable)
) engine=MyISAM default charset=latin1 comment 'Miscellaneous settings for internal use';

insert into settings values('weekend','12');
insert into settings values('rpm','1.1-0');
insert into settings values('version','1.1-0');
