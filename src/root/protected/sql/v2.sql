alter table user add admin tinyint(1) default 0 not null;
alter table user add superadmin tinyint(1) default 0 not null;
update user set superadmin=1 where id = 1;
update user set admin=1 where id in (1,14,16,33,100);