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
 *  - Sort "other riders" in the bandwagon by num weeks on it, descending
 *  - Add a setting to receive the reminder email always, never, or just if you haven't made a pick.
 *  - When left open for too long, the home page takes forever.  I think there's a memory leak.
 *  - Got lazy and am not showing trophies/badges on pick stats and previous winners pages.  Should I?
 *  - Should the avatarBubble (along with everywhere else) show the user's power rank?)
 *  - Need to mark the Chief of the Bandwagon badge as Unlocked
 *  - Have about pages link to each other, like in the power ranking page how it talks about the bandwagon.
 *  - With collapsed history, all records are reporting 0-18
 *  - Profile list page filter name should be case-insensitive
 *  - Look at Andy's max incorrect streak details on his profile page (has same start and end week)
 *  - Have a way to gray out pot #1 after it's decided
 *  - Still need to hide content from people that haven't paid (I think...)
 *  - Really need to work on the speed/performance of the home page.  It renders slowly on every redraw.  Perhaps certain unchanging things can be pre-rendered by the server?  Or maybe even just writing them with javascript as strings instead of jQuery constructs would help.
 *  - Pick a new style for table headers so they're contrasted more
 *  - On the home page, when showing the collapsable accordian sections, set a cookie to remember which they had open
 *  - If the user hasn't made a pick for the current week, we need a way to inform them like the header on the old site
 *  - When showing Talk posts, only get those where active = 1 (just added that new field to the database)
 *  - Graph of referrers for how people know each other
 *  - Allow dislikes for trash talk?
 *  - Figure out when to run the recalculation routines -- keep in mind bandwagon can be affected by EVERY PICK (so run on a cron or trigger via pick or something?)
 *  - For trash talk by me, have a flag I can set so it shows up as just a regular talk versus the yellow highlighted "admin" talk
 *  - Is there a way to not have to keep deactivating users every year?  Maybe allow logins forever, but they can only see the current board if they're playing this year?
 *  - For floating/losable badges, have a "badge history" page that shows where they belonged?  Maybe that's overkill
 *  - When showing all the badges, show floating badges as "year introduced" instead of "year unlocked", and show "current owner" instead of "unlocked by"
 *  - Add large power points for having uploaded a custom avatar
 *  - Make badges so they still have a title so that you can hover them for a name without having to click on them for the full details
 *  - Add a weiner badge for someone who lets the system pick x number of picks for them in a given season
 *  - Make an "against the flow" badge for users who stay off the bandwagon the most
 *  - Allow grouping of like-type badges so it only shows the badge once and then has a little counter if they have more than 1 of them.
 *  - Add a badge that takes away a power point for users who use IE
 *  - Does the show/hide trophies/badges setting need to apply on other pages (being the pick board)?  Or if not, make it known on the profile page that the setting only applies to the pick boards. 
 *  - When in production, re-run the recalc/maintenance page multiple times (letting it time out) until the power ranking table is filled
 *  - Ability to hide team logos setting does not work
 *  - Customized View options on the main page:
 *  	- Allow these to be changed from a settings page, or toggled directly on the home page
 *  		- Have "presets" that set each of them, like the "no frills" preset, with everything off, or the "full experience" preset, with everything on, etc
 *  	- Ability to show/hide user avatars
 *  	- Ability to show/hide the bandwagon section
 *  	- Ability to show/hide trash talk (still need admin messages to show)
 *  	- Ability to show/hide the season outlook (pots)
 *  	- Ability to show/hide power rank columns
 *  - Bandwagon
 *  	- Badges
 *  		- timely jumper
 *  		- longest time off bandwagon
 *  		- % pick stats on/off bandwagon
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
 *  
 *  
 */
?><!DOCTYPE html>
<html>
    <head>
        <!-- KDHTODO change the title per page -->
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
        <script src="<?php echo baseUrl('/js/lib/jquery.lightbox_me.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/moment.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/oo.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/types.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/badges.js'); ?>"></script>
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