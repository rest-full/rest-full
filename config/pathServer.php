<?php

if (!defined('DS')) {
    /**
     * Use the DS to separate the directories in other defines
     */
    define('DS', "/");

    /**
     * Use the DS to separate the directories in other defines
     */
    define('DS_REVERSE', '\\');

    /**
     *
     */
    define('ROOT', dirname(__DIR__) . DS);

    /**
     *
     */
    define('ROOT_PATH', ROOT . 'webroot' . DS);

    /**
     *
     */
    define('ROOT_ABSTRACT', ROOT . 'abstraction' . DS);
}

/**
 *
 */
define('FRAMEWORK', dirname(__DIR__, 2) . DS);


/**
 *
 */
define('RESTFULL', dirname(__DIR__) . DS . 'src' . DS);

/**
 *
 */
define(
    'ROOT_NAMESPACE',
    substr(
        str_replace(
            substr(RESTFULL, stripos(RESTFULL, 'vendor') + strlen('vendor' . DS), -1),
            'Restfull',
            substr(RESTFULL, 0, -1)
        ),
        stripos(
            str_replace(
                substr(RESTFULL, stripos(RESTFULL, 'vendor') + strlen('vendor' . DS), -1),
                'Restfull',
                substr(RESTFULL, 0, -1)
            ),
            'vendor'
        ) + strlen('vendor' . DS)
    )
);

/**
 *
 */
define('PATH_NAMESPACE', substr(RESTFULL, 0, -1));

/**
 *
 */
define('ROOT_APP', ROOT . 'App' . DS);

/**
 *
 */
define('MVC', ['Controller', 'View', ['app' => 'Model', 'restfull' => 'ORM']]);

/**
 *
 */
define('SUBMVC', ['Component', 'Helper', ['Behavior', 'Entity', 'Table', 'Migration', 'Validate', 'Query']]);

/**
 *
 */
define(
    'URL',
    $_SERVER['SERVER_PORT'] == "80" ? $_SERVER['REQUEST_SCHEME'] . ":" . DS . DS . $_SERVER['SERVER_NAME'] : $_SERVER['REQUEST_SCHEME'] . ":" . DS . DS . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']
);