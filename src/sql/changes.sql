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
update userbadge set yr = 2008 where badgeid=1;
update userbadge set yr = 2010 where badgeid=2;
update userbadge set yr = 2011 where badgeid=3;
update userbadge set yr = 2005 where badgeid=4;
update userbadge set yr = 2006 where badgeid=5;
update userbadge set yr = 2012 where badgeid=15;

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
	updated datetime null,
	primary key (yr, userid)
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

alter table user drop previous_power_ranking;
alter table user drop previous_power_points;
alter table user drop last_week_set;
alter table user drop best_power_ranking;


create table statgroup (
	id int not null auto_increment,
	name varchar(50) not null,
	description text null,
	zindex int not null,
	primary key (id)
);
insert into statgroup (name, zindex) values ('Season Stats', 10);
insert into statgroup (name, zindex) values ('Money/Reward Stats', 20);
insert into statgroup (name, zindex) values ('Pick Stats', 30);
insert into statgroup (name, zindex) values ('Margin of Defeat Stats', 40);
insert into statgroup (name, zindex) values ('Streaks/Averages Stats', 50);
insert into statgroup (name, zindex) values ('Social Stats', 60);
insert into statgroup (name, zindex) values ('Bandwagon Stats', 70);
insert into statgroup (name, zindex) values ('Power Rank Stats', 80);

alter table stat add statgroupid int;
update stat set statgroupid = 1 where id in (1);
update stat set statgroupid = 2 where id in (2,3,4,22,36,37,38,39);
update stat set statgroupid = 3 where id in (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21);
update stat set statgroupid = 4 where id in (23,24);
update stat set statgroupid = 5 where id in (25,26,27,28,29,30);
update stat set statgroupid = 6 where id in (31,32,33,34,35);
update stat set statgroupid = 7 where id in (40,41,42,43,44);

update stat set zindex = 10 where id = 5;
update stat set zindex = 40 where id = 6;
update stat set zindex = 50 where id = 7;
update stat set zindex = 20 where id = 8;
update stat set zindex = 30 where id = 9;
update stat set zindex = 60 where id = 10;
update stat set zindex = 80 where id = 11;
update stat set zindex = 70 where id = 12;
update stat set zindex = 90 where id = 13;
update stat set zindex = 120 where id = 14;
update stat set zindex = 130 where id = 15;
update stat set zindex = 100 where id = 16;
update stat set zindex = 110 where id = 17;
update stat set zindex = 140 where id = 18;
update stat set zindex = 160 where id = 19;
update stat set zindex = 150 where id = 20;
update stat set zindex = 170 where id = 21;

update stat set description = 'The total number of seasons in which the player has participated' where id = 1;
update stat set description = 'The total sum of entry fees paid by the player across all seasons' where id = 2;
update stat set description = 'The total amount of money the player has won' where id = 3;
update stat set description = 'The amount of money won per dollar invested' where id = 4;
update stat set description = 'The total number of picks made across all seasons' where id = 5;
update stat set description = 'The total number of picks made manually by the player across all seasons' where id = 6;
update stat set description = 'The total number of picks that were set by the system across all seasons' where id = 7;
update stat set description = 'The total number of picks that were correct across all seasons' where id = 8;
update stat set description = 'The total number of picks that were incorrect across all seasons' where id = 9;
update stat set description = 'The total number of picks that were correct and were made manually by the player across all seasons' where id = 10;
update stat set description = 'The total number of picks that were incorrect and were made manually by the player across all seasons' where id = 11;
update stat set description = 'The total number of picks that were correct but were set by the system across all seasons' where id = 12;
update stat set description = 'The total number of picks that were incorrect but were set by the system across all seasons' where id = 13;
update stat set description = 'The percentage of the player''s total picks which were made manually' where id = 14;
update stat set description = 'The percentage of the player''s total picks which were set by the system' where id = 15;
update stat set description = 'The percentage of the player''s total picks which were correct' where id = 16;
update stat set description = 'The percentage of the player''s total picks which were incorrect' where id = 17;
update stat set description = 'The percentage of the player''s total picks which were made manually and were correct' where id = 18;
update stat set description = 'The percentage of the player''s total picks which were made manually and were incorrect' where id = 19;
update stat set description = 'The percentage of the player''s total picks which were set by the system and were correct' where id = 20;
update stat set description = 'The percentage of the player''s total picks which were set by the system and were incorrect' where id = 21;
update stat set description = 'The player''s power ranking' where id = 22;
update stat set description = 'The sum of the margin of defeat for all the player''s picks across all seasons' where id = 23;
update stat set description = 'The average margin of defeat for all the player''s picks across all seasons' where id = 24;
update stat set description = 'The player''s longest streak of correct picks, including picks spanned across seasons' where id = 25;
update stat set description = 'The player''s longest streak of incorrect picks, including picks spanned across seasons' where id = 26;
update stat set description = 'The average number of times per season the player makes a correct pick' where id = 27;
update stat set description = 'The average number of times per season the player makes an incorrect pick' where id = 28;
update stat set description = 'The average week in which the player makes their first incorrect pick in a season' where id = 29;
update stat set description = 'The current streak the player is on (negative numbers represent how many incorrect in a row, positive numbers represent how many correct)' where id = 30;
update stat set description = 'The number of messages posted by the player' where id = 31;
update stat set description = 'The number of messages posted by other players directed AT the player' where id = 32;
update stat set description = 'The number of times the player has liked a message' where id = 33;
update stat set description = 'The number of times a player''s message has been liked' where id = 34;
update stat set description = 'The number of other players referred by the player' where id = 35;
update stat set description = 'The number of times the player has finished in first place in one of the pots' where id = 36;
update stat set description = 'The number of times the player has finished in second place in one of the pots' where id = 37;
update stat set description = 'The number of times the player has finished in first or second place in one of the pots' where id = 38;
update stat set description = 'The number of badges the player currently holds' where id = 39;
update stat set description = 'The number of times the player has been on the bandwagon' where id = 40;
update stat set description = 'The frequency of picks for which the player is on the bandwagon' where id = 41;
update stat set description = 'The number of times the player has been Chief of the Bandwagon' where id = 42;
update stat set description = 'The frequency of all picks for which the player has been Chief of the Bandwagon' where id = 43;
update stat set description = 'The number of times the player successfully rode the Bandwagon for 3 or more weeks, then jumped off and got their pick correct, just as the Bandwagon was incorrect' where id = 44;

alter table loserpick add weeks_on_bandwagon int not null default 0;
