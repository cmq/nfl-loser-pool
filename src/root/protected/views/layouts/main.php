<?php
/*
 *  @var $this Controller
 *  
 *  This template is based off the twitter bootstrap suggested template:
 *  @see http://getbootstrap.com/getting-started/#template
 *  
 *  
 *  KDHTODO remaining items
 *  
 *  BUGS
 *  
 *  PERFORMANCE ENHANCEMENTS
 *  - When left open for too long, the home page takes forever.  I think there's a memory leak.
 *  - Really need to work on the speed/performance of the home page.  It renders slowly on every redraw.  Perhaps certain unchanging things can be pre-rendered by the server?  Or maybe even just writing them with javascript as strings instead of jQuery constructs would help.
 *  
 *  DISPLAY ISSUES/FEATURES
 *  - !!! make sure you can only see your own picks (on the pick board) if you haven't paid
 *  - Make sure admins can't see future picks on the corrections page unless they are also superadmins
 *  - Several pages (profiles list, pick stats, etc) still need a once-over in mobile/tablet
 *  - Payout breakdown is crap on mobile
 *  - Need to check pages on tablets as well
 *  - Have about pages link to each other, like in the power ranking page how it talks about the bandwagon.
 *  - Have a way to gray out pot #1 after it's decided
 *  - Pick a new style for table headers so they're contrasted more
 *  - If the user hasn't made a pick for the current week, we need a way to inform them like the header on the old site
 *  - When showing all the badges, show floating badges as "year introduced" instead of "year unlocked", and show "current owner" instead of "unlocked by"
 *  - Make badges so they still have a title so that you can hover them for a name without having to click on them for the full details
 *  - Test out the Profile pages in other devices, as heavy use of rows/columns was used
 *  - Update the page title from page to page
 *  - On the Bandwagon about page, show the bandwagon icons inline where they are being talked about
 *  - On the Settings page, make save routines have consistent ways to show completion and errors (errors above the field in a div that takes up space whether or not it's empty, and successes as little save checkmarks to the left of each field that fade away)
 *          the checkmark should default to being present, and then should disappear when the user changes the field value or while an AJAX request is pending.  Once successful, it will reappear next to the field.
 *          clean up avatar upload section (general look, as well as error block)
 *          the change-password-static needs about a 9px padding maybe?  Depending on how much we refactor the appearance
 *          test page in other devices
 *          test placeholders in other devices... fields don't have labels by default, so are placeholders sufficient?
 *          viewsetting-saved should use the help-block instead maybe?
 *  - In the $.ajax() call from the Talk view, handle errors some way other than alerting them.
 *  - Clean up the display of the talk page?
 *  - On the settings page, need to re-issue a call to lightboxAvatars after a successful upload
 *  
 *  NEW FEATURES
 *  - Add a setting to receive the reminder email always, never, or just if you haven't made a pick.
 *  - Figure out when to run the recalculation routines -- keep in mind bandwagon can be affected by EVERY PICK (so run on a cron or trigger via pick or something?)
 *  - Add View Option to show/hide user avatars
 *  - Add View Option to show/hide the bandwagon column/icon/row
 *  
 *  UNDECIDED FEATURES
 *  - Make the Pick Board view options toggleable in real-time?
 *  - Got lazy and am not showing trophies/badges on pick stats and previous winners pages.  Should I?
 *  - Does the show/hide trophies/badges setting need to apply on other pages (being the pick board)?  Or if not, make it known on the profile page that the setting only applies to the pick boards. 
 *  - Should the avatarBubble (along with everywhere else) show the user's power rank?
 *  
 *  SUPERADMIN FUNCTIONS
 *  - In AdminController remove superadmintest action and use in the SuperadminFilter -- just there for an example
 *  - Do something with the indexAction
 *  - Clean up the MaintenanceController so random functions aren't hanging around all over cluttering things up
 *  - Give Maintenance page a layout so the navigation is still present, etc.
 *  - Show debug/timing output on the screen during Maintenance?
 *  - Comment MaintenanceController better, especially _recalcPower()
 *  - Test that a Superadmin is able to modify settings/names/avatars/etc on another user's profile page
 *  - Allow Superadmins to delete talk messages
 *  - Allow Superadmins to edit talk messages inline
 *  - Clean up the styling of the corrections page, especially of the saved-status fields
 *  - In the views/pick/index.php, the data that's built always uses the current user (userId()).  Change that so that superadmins can make picks for other users.
 *  - From a user's profile page, give a Superadmin links to change that user's profile/picks.  (Only do picks if the user is currently active, obviously)
 *  
 *  BADGES/POWER STUFF
 *  - Add large power points for having uploaded a custom avatar
 *  - Add a weiner badge for someone who lets the system pick x number of picks for them in a given season
 *  - Make an "against the flow" badge for users who stay off the bandwagon the most
 *  - Allow grouping of like-type badges so it only shows the badge once and then has a little counter if they have more than 1 of them.
 *  - Add a badge that takes away a power point for users who use IE
 *  - Add a Timely Jumper badge
 *  - Add a Badge for Lowest Percentage of time on Bandwagon lifetime (with a min # picks)
 *  - Skating By (ice skates) - Lowest margin of Defeat for the user tied in first for overall record
 *  - Variety badge - longest to go without picking the same team twice in a season
 *  - Timing badge (stopwatching - Highest avg time choosing picks before the weekly deadline
 *  - Power Rank - less emphasis on longevity (tried to reduce this, but still need more!)
 *  - Add a badge for anyone with a ROI over 1 (maybe the "Paid to Play" badge)
 *  
 *  GO-LIVE
 *  - Need to mark the Chief of the Bandwagon badge as Unlocked
 *  - When in production, re-run the recalc/maintenance page multiple times (letting it time out) until the power ranking table is filled
 *  - Removing the hardcoding of week 19 from the getCurrentWeek() function in globals-domain.php
 *  - On the Profile page, use @media query to adjust how pie chart appears (or hide it completely?) for small screens (http://stackoverflow.com/questions/21241862/twitter-bootstrap-3-rowspan-and-reorder)
 *  - Trophies/badges have a negative margin to make them look nice on the Pick Board -- test this out in other devices
 *  - Decide how to upload (either via IDE or git)
 *  - Get maintenance script running
 *  - The profile page shows a 404 in firebug even though everything seems to load fine
 *  
 *  STRETCH GOALS
 *  - Graph of referrers for how people know each other
 *  - Allow dislikes for trash talk?
 *  - Is there a way to not have to keep deactivating users every year?  Maybe allow logins forever, but they can only see the current board if they're playing this year?
 *  - For floating/losable badges, have a "badge history" page that shows where they belonged?  Maybe that's overkill
 *  - Rivalry Badge - most or least divisional games picked in a season.
 *  - Prime time badge (picture of Deion) - most TNF + SNF + MNF games picked.
 *  - Power Ranking - Only give credits for posts made that were liked by at least 3 people so people can't team-spam
 *  - Power Ranking - Give more credit for more likes, but diminishing returns
 *  - Power Ranking - Give credit for liking other people's posts, but only if at least 2 other people liked the same post (otherwise you could simply spam likes)
 *  - Can we make more use of Yii partials, such as when drawing badges in globals.buildBadgePopovers()?
 *  - Add some common "action" methods to the base Controller module, per Dough.
 *  - In the User Model, implement the delete() and changepw() methods?  (Note: changepw logic is in the controller now, but the model is a more appropriate spot for it)
 *  
 *  
 */
?><!DOCTYPE html>
<html>
    <head>
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
        <script src="<?php echo baseUrl('/js/lib/jquery.cookie.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/moment.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/oo.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/types.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/badges.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/globals.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/init.js'); ?>"></script>
    </head>
    <body>
        <?php
        $this->renderPartial('//_partials/Navigation', array());
        echo $content;
        ?>
    </body>
</html>