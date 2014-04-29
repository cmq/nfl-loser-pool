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

update userbadge add yr int null;
update userbadge set yr = 2005 where (badgeid=17 and userid=38) or (badgeid=9 and userid=38);;
update userbadge set yr = 2006 where (badgeid=17 and userid=44);
update userbadge set yr = 2007 where (badgeid=17 and userid=57) or (badgeid=11 and userid=65);
update userbadge set yr = 2008 where (badgeid=17 and userid=76);
update userbadge set yr = 2009 where (badgeid=17 and userid=91) or (badgeid=9 and userid=96);;
update userbadge set yr = 2010 where (badgeid=17 and userid=117);
update userbadge set yr = 2011 where (badgeid=8 and userid=9) or (badgeid=17 and userid=129);
update userbadge set yr = 2012 where (badgeid=8 and userid=52) or (badgeid=17 and userid=130);
update userbadge set yr = 2013 where (badgeid=7 and userid=26) or (badgeid=8 and userid=121) or (badgeid=8 and userid=18) or (badgeid=8 and userid=100) or (badgeid=17 and userid=141);