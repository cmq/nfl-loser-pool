<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
$_config = array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'NFL Loser Pool',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
	),

	'modules'=>array(
		// uncomment the following to enable the Gii tool
		/*
		'gii'=>array(
			'class'=>'system.gii.GiiModule',
			'password'=>'Enter Your Password Here',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			'ipFilters'=>array('127.0.0.1','::1'),
		),
		*/
	),

	// application components
	'components'=>array(
		'user'=>array(
			// enable cookie-based authentication
			'allowAutoLogin'=>true,
		),
		/*
		 * dough version
		'user'=>array(
			// enable cookie-based authentication
			'allowAutoLogin'=>true,
	        'class'=>'WebUser',    // use /protected/components/WebUser.php instead of the default -- allows us to refresh data from the DB
		),
		 */
		// uncomment the following to enable URLs in path-format
		'urlManager'=>array(
			'urlFormat'=>'path',
			'showScriptName'=>true,    // in production, this is false
			'rules'=>array(
				'<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		'db'=>array(
			'connectionString' => 'mysql:host=127.0.0.1;dbname=loserpool2',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 's0methIng.3atme',
			'charset' => 'utf8',
		    'enableParamLogging' => true,
		),
		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>'site/error',
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>include(dirname(__FILE__).'/settings.php'),
);


/**
 * Modify settings based on the environment
 */
// PRODUCTION
if (isProduction()) {
    // Apache!  Hide index.php script name
    $_config['components']['urlManager']['showScriptName'] = false;
    // Change database connection information
    $_config['components']['db'] = array(
		'connectionString' => 'mysql:host=localhost;dbname=kdhstuff_loserpool2',
		'emulatePrepare' => true,
		'username' => 'kdhstuff_loser',
		'password' => '6ccS&ND^w1*4',
		'charset' => 'utf8',
	);
}

return $_config;
