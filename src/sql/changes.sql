/*
 * These changes should be made to a fresh copy of the existing database
 */
update user add admin tinyint(1) not null default 0;
update user add superadmin tinyint(1) not null default 0;

update user set admin = 1 where id in (1,14,16,33,100);
update user set superadmin = 1 where id in (1);

update badge set img = REPLACE(img, 'img/', 'images/badges/');
update badge set zindex=73 where id = 17;

update user add timezone int not null default 0;
update user add use_dst tinyint(1) not null default 1;

update pick add created datetime null;
update pick add updated datetime null;

update user add collapse_history tinyint(1) not null default 0;
update user add show_badges tinyint(1) not null default 1;
update user add show_logos tinyint(1) not null default 1;
update user add show_mov tinyint(1) not null default 1;

update losertalk add admin tinyint(1) not null default 0;
