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
 *  - On the index page, add an interval that will poll for new board data and automatically update the model with changes
 *  - Graph of referrers for how people know each other
 *  - Allow likes/dislikes for trash talk -- include these in power ranking
 *  - Keep track of WHEN picks are made so we know how early people picked
 *  - Is there a way to not have to keep deactivating users every year?  Maybe allow logins forever, but they can only see the current board if they're playing this year?
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
 *  - New badges
 *  	- Skating by (ice skate) - lowest margin of defeat for those tied for first place in the overall record pool.
 *  	- Variety - longest to go without picking the same team twice in a season.
 *  	- Timing badge (stopwatch) - highest avg time choosing picks before the weekly deadline.
 *  	- Stretch goals
 *  		- Rivalry - most or least divisional games picked in a season.
 *  		- Prime time badge (picture of Deion) - most TNF + SNF + MNF games picked.
 *  - Power ranking formula
 *  	- less emphasis on longevity
 *  	- include points for likes/dislikes on trash talk
 *  	- make the formula public, with a page that shows how each user's score was calculated
 *  	- create the ability to re-calculate up to a given week (like, figure out what the power rankings would have been after week 5, 2010)
 *  		- use this ability to create a full history of power rankings by week
 */
?><!DOCTYPE html>
<html ng-app="loserpool">
    <head>
        <title><?php echo CHtml::encode($this->pageTitle); ?></title>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="language" content="en" />
        
        <!-- Bootstrap -->
        <link href="<?php echo baseUrl('/css/bootstrap.min.css'); ?>" rel="stylesheet" />
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
        
        <script src="<?php echo baseUrl('/js/conf.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/jquery.ba-getobject.min.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/angular.min.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/moment.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/oo.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/lib/types.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/globals.js'); ?>"></script>
        <script src="<?php echo baseUrl('/js/module/main.js'); ?>"></script>
    </head>
    <body>
        <?php echo $content; ?>
    </body>
</html>