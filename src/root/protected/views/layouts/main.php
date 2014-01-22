<?php
/*
 *  @var $this Controller
 *  
 *  This template is based off the twitter bootstrap suggested template:
 *  @see http://getbootstrap.com/getting-started/#template
 */
?><!DOCTYPE html>
<html ng-app>
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
    </head>
    <body>
        <?php echo $content; ?>
    </body>
</html>