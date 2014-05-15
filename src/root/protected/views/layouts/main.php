<?php
/*
 *  @var $this Controller
 *  
 *  This template is based off the twitter bootstrap suggested template:
 *  @see http://getbootstrap.com/getting-started/#template
 *  
 *  
 *  KDHTODO larger items
 *  
 *  - When showing Talk posts, only get those where active = 1 (just added that new field to the database)
 *  - On the index page, add an interval that will poll for new board data and automatically update the model with changes
 *  - handle AJAX errors where user is logged out
 *  - Graph of referrers for how people know each other
 *  - Allow likes/dislikes for trash talk -- include these in power ranking
 *  - Keep track of WHEN picks are made so we know how early people picked
 *  - For trash talk by me, have a flag I can set so it shows up as just a regular talk versus the yellow highlighted "admin" talk
 *  - Is there a way to not have to keep deactivating users every year?  Maybe allow logins forever, but they can only see the current board if they're playing this year?
 *  - Make badges clickable to show the hover text in a tiny modal (since on tablets/phones, there's no such thing as hovering)
 *  - Re-add lightbox (I removed it so I could work on testing the profile page)
 *  - On past-year pages, show all talk messages from that year
 *  - Create a profile page for others to view, so I can click on a user's name and see their page which shows their history (seasons played, badges, wins, stats, rankings, etc)
 *  - Allow talk messages to be flagged by superadmin as "admin" to show up in a different color
 *  - For users viewing a talk message directed at them, show them in a different color
 *  - Use language to differentiate "winner TROPHIES" from "user BADGES"
 *  - Note somewhere that floating badges, despite their year of introduction, are applied retroactively to past years
 *  - Distinguish badges more.  Like, the 800 badge isn't "floating", it's earnable.  But some earnable badges are permanent and some aren't.
 *  - For floating/losable badges, have a "badge history" page that shows where they belonged?  Maybe that's overkill
 *  - When showing all the badges, show floating badges as "year introduced" instead of "year unlocked", and show "current owner" instead of "unlocked by"
 *  - Clean up the data fields for the userbadge and badge tables so the popovers look cooler
 *  - Add large power points for having uploaded a custom avatar
 *  - Make badges so they still have a title so that you can hover them for a name without having to click on them for the full details
 *  - Add a weiner badge for someone who lets the system pick x number of picks for them in a given season
 *  - Break the "about" nav item into a dropdown with multiple sections
 *  - Make an "against the flow" badge for users who stay off the bandwagon the most
 *  - Allow grouping of like-type badges so it only shows the badge once and then has a little counter if they have more than 1 of them.
 *  - On the homepage, show a bandwagon section that has the stats:
 *      - Week xx pick:  {team}
 *      - Current chief
 *      - Current members (with how long they've been on it)
 *      - Record this year
 *      - Record all time
 *  - Customized View options on the main page:
 *  	- Allow these to be changed from a settings page, or toggled directly on the home page
 *  		- Have "presets" that set each of them, like the "no frills" preset, with everything off, or the "full experience" preset, with everything on, etc
 *  	- Ability to hide old week columns (i.e. on week 13 there is a single column for weeks 1-12 for each user.  The column contains simply the user's record over that time)
 *  	- Ability to show/hide badges
 *  	- Ability to show/hide user avatars
 *  	- Ability to show/hide the bandwagon section
 *  	- Ability to show/hide trash talk (still need admin messages to show)
 *  	- Ability to show/hide the season outlook (pots)
 *  	- Ability to show/hide power rank columns
 *  	- Ability to show team short names instead of team icons?
 *  	- Anything else?
 *  - Bandwagon
 *  	- Each week, the bandwagon is the team who has been chosen by the most people.  The tiebreaker is the highest power-ranked player
 *  	- Icon:  https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcTmdunKxVARBylJY360KjkyC90ZMn0JnwuKPZCtcO2ABU_7PtXzFg
 *  	- Badges
 *  		- hopping off at right time
 *  		- chief = longest time on bandwagon (ties by power ranking)
 *  		- longest time off bandwagon
 *  		- % pick stats on/off bandwagon
 *  	- Bandwagon Column:
 *  		- If user is currently ON the bandwagon:  covered wagon icon & number of weeks on (+xxx)
 *  		- If user is currently OFF the bandwagon:  no covered wagon & number of weeks off (-xxx)
 *      - Track the number of times a user hops off the bandwagon at just the right time
 *  - New badges
 *  	- Skating by (ice skate) - lowest margin of defeat for those tied for first place in the overall record pool.
 *  	- Variety - longest to go without picking the same team twice in a season.
 *  	- Timing badge (stopwatch) - highest avg time choosing picks before the weekly deadline.
 *  	- Stretch goals
 *  		- Rivalry - most or least divisional games picked in a season.
 *  		- Prime time badge (picture of Deion) - most TNF + SNF + MNF games picked.
 *  - Power ranking formula
 *  	- less emphasis on longevity
 *  	- include points for likes on trash talk
 *          - Only give credits for posts made that were liked by at least 3 people so people can't team-spam
 *          - Give more credit for more likes, but diminishing returns
 *          - Give credit for liking other people's posts, but only if at least 2 other people liked the same post (otherwise you could simply spam likes)
 *  	- make the formula public, with a page that shows how each user's score was calculated
 *  	- create the ability to re-calculate up to a given week (like, figure out what the power rankings would have been after week 5, 2010)
 *  		- use this ability to create a full history of power rankings by week
 *      - We don't want to have to calculate power rank with weekly "talk", "like", and "badge" stats, so make note that points for these are only awarded at the end of the year!!
 *      - Adjust the power points of all the badges/trophies
 *  
 *  
 *  - A sample way of doing an animation (like Xenforo does, post-admin-login):
<script>
$(function() {
    $('#kirktest_wrap').height($(window).height() - $('#kirktest_wrap').position().top);    // do this any time the window size changes too
    $('#kirktest_reset').on('click', function() {
        $('#kirktest').css({
            width:  '1242px',
            height: '960px',
            top:    '10px'
        });
        $('#kirktest_wrap').css({
            'padding-top': 0
        });
        $('#kirktest_wrap').height($(window).height() - $('#kirktest_wrap').position().top);    // do this any time the window size changes too
    });
    $('#kirktest_go').on('click', function() {
        $('#kirktest').animate({
            width:  '310px',
            height: '240px',
            top:    '-50px'
        });
        $('#kirktest_wrap').animate({
            height: '100px',
            'padding-top': '-100px'
        });
    });
});
</script>

<style>
html, body {
    margin: 0;
    height: 100%;
}
</style>
<div id="kirktest_wrap" style="position:relative;overflow:hidden;">
    <button id="kirktest_reset">Reset</button>
    <button id="kirktest_go">Go</button><br />
    <img id="kirktest" src="/images/kirktest.jpg" style="width:1242px;height:960px;position:absolute;right:0;top:10px" />
</div>
 
 *  
 */
?><!DOCTYPE html>
<html>
    <head>
        <!-- KDHTODO change the title -->
        <title><?php echo CHtml::encode($this->pageTitle); ?></title>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="language" content="en" />
        
        <!-- Bootstrap -->
        <link href="<?php echo baseUrl('/css/bootstrap-spacelab-theme.min.css'); ?>" rel="stylesheet" />
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="//code.jquery.com/jquery.js"></script>
        <!-- Include all compiled plugins (below), or include individual files as needed -->
        <script src="<?php echo baseUrl('/js/bootstrap.min.js'); ?>"></script>
    
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
        
        <link href="<?php echo baseUrl('/css/main.css'); ?>" rel="stylesheet" />
        <link href="<?php echo baseUrl('/css/fileuploader.css'); ?>" rel="stylesheet" />
        <?php require(__DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array('..', '..', '..', 'js', 'conf.js.php')))?>
        <script src="<?php echo baseUrl('/js/lib/jquery.ba-getobject.min.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/moment.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/oo.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/types.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/globals.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/init.js'); ?>"></script>
    </head>
    <body>
        <?php
        $this->renderPartial('//_partials/navigation', array());
        echo $content;
        ?>
    </body>
</html>