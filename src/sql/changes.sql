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

create table likes (
	id bigint not null auto_increment,
	talkid bigint not null,
	userid bigint not null,
	active tinyint(1) not null default 1,
	created datetime null,
	updated datetime null,
	yr int not null,
	primary key (id)
);

insert into loseruser (userid, paid, paidnote, yr) (
    select distinct loserpick.userid, 1, 'Pre-2009', loserpick.yr from loserpick where not exists (select * from loseruser where loseruser.userid= loserpick.userid and loserpick.yr = loseruser.yr)
);

alter table losertalk add active tinyint(1) not null default 1;
update losertalk set yr=2011 where id=538;

insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Current Streak', 'The current streak the user is on', null, 0, 'int', 300);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Posts By', 'The number of posts made by the user', null, 0, 'int', 310);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Posts At', 'The number of posts made AT the user', null, 0, 'int', 320);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Likes Given', 'The number of times the user has liked a post', null, 0, 'int', 330);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Posts Liked', 'The number of times the user''s post has been liked', null, 0, 'int', 340);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Referrals', 'The number of other users referred by the user', null, 0, 'int', 350);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('First Place Finishes', 'The number of times the user has finished in first place in one of the pots', null, 0, 'int', 360);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Second Place Finishes', 'The number of times the user has finished in second place in one of the pots', null, 0, 'int', 370);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Trophies', 'The number of times the user has finished in first or second place in one of the pots', null, 0, 'int', 380);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Badges', 'The number of badges the user has', null, 0, 'int', 390);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Times on Bandwagon', 'The number of times the user has been on the bandwagon', 'Does not include 2004, which was a sudden-death season', 0, 'int', 400);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Percentage of Time on Bandwagon', 'The frequency of picks for which the user is on the bandwagon', 'Does not include 2004, which was a sudden-death season', 0, 'percent', 410);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Times as Chief of the Bandwagon', 'The number of times the user has been Chief of the Bandwagon', 'Does not include 2004, which was a sudden-death season', 0, 'int', 420);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Percentage of Time as Chief of the Bandwagon', 'The frequency of all picks for which the user has been Chief of the Bandwagon', 'Does not include 2004, which was a sudden-death season', 0, 'percent', 430);
insert into stat (name, description, asterisk, reverse, `type`, zindex) values ('Timely Bandwagon Jumper', 'The number of times the user successfully rode the Bandwagon for 3 or more weeks, then jumped off and got their pick right, just as the Bandwagon was wrong', 'Does not include 2004, which was a sudden-death season', 0, 'int', 440);

insert into badge (name, img, `type`, display, power_points, description, zindex, internal, unlocked_userid, unlocked_year) values ('Chief of the Bandwagon', '/images/badges/bwchief.png', 'Floating', 'Chief of the Bandwagon', 0, 'Belongs to the player that has been on the bandwagon the longest (with ties being awarded to the player with the highest Power Rank)', 170, null, null, 2014);

create table bandwagon (
	yr int not null,
	week int not null,
	teamid int not null,
	chiefid bigint not null,
	incorrect tinyint(1) null,
	primary key (yr, week)
);

create table bandwagonjump (
	yr int not null,
	week int not null,
	userid int not null,
	previous_weeks int not null,
	primary key (yr, week, userid)
);

update loserpick, loserpick l2
	set loserpick.teamid = 0
where loserpick.yr = 2004 and loserpick.teamid > 0
	and l2.userid = loserpick.userid and l2.yr=2004 and l2.week < loserpick.week and l2.incorrect = 1;

update loserpick set incorrect = 0 where userid=136 and yr=2012 and week in (15,16);
update loserpick set incorrect = 1 where userid=136 and yr=2012 and week > 16;

insert into userbadge (badgeid, userid) values (19, 0);

create table power (
	yr int not null,
	week int not null,
	userid int not null,
	powerpoints decimal(18,3) not null default 0,
	powerrank int not null default 0,
	seasonPts decimal(18,3) not null default 0,
	correctPts decimal(18,3) not null default 0,
	badgePts decimal(18,3) not null default 0,
	moneyPts decimal(18,3) not null default 0,
	winPctPts decimal(18,3) not null default 0,
	movPts decimal(18,3) not null default 0,
	setBySystemPts decimal(18,3) not null default 0,
	talkPts decimal(18,3) not null default 0,
	referralPts decimal(18,3) not null default 0,
	likesByPts decimal(18,3) not null default 0,
	likesAtPts decimal(18,3) not null default 0,
	firstPlacePts decimal(18,3) not null default 0,
	secondPlacePts decimal(18,3) not null default 0,
	primary key (yr, week, userid)
);

update badge set power_points = 3 where id = 4;
update badge set power_points = 10 where id = 6;
update badge set power_points = 4 where id = 7;
update badge set power_points = 2 where id = 8;
update badge set power_points = 12 where id = 9;
update badge set power_points = 5 where id = 10;
update badge set power_points = 6 where id = 11;
update badge set power_points = 3 where id = 12;
update badge set power_points = 3 where id = 13;
update badge set power_points = 5 where id = 14;
update badge set power_points = 6 where id = 16;
update badge set power_points = 5 where id = 17;
update badge set power_points = 5 where id = 18;
update badge set power_points = 6 where id = 19;
