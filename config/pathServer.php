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
    define('ROOT_ABSTRACT', ROOT . 'Abstraction' . DS);

    /**
     *
     */
    define('RESTFULL', dirname(__DIR__) . DS . 'src' . DS);
}

/**
 *
 */
define('RESTFULL_FRAMEWORK', dirname(__DIR__) . DS . 'src' . DS);

/**
 *
 */
define('ROOT_NAMESPACE', ['Restfull', 'App']);

/**
 *
 */
define('PATH_NAMESPACE', 'src');

/**
 *
 */
define('MVC', ['Controller', 'View', ['app' => 'Model', 'restfull' => 'ORM']]);

/**
 *
 */
define('SUBMVC', ['Component', 'Helper', ['Behavior', 'Entity', 'Table', 'Migration', 'Validation', 'Query']]);

/**
 *
 */
define(
    'URL',
    $_SERVER['HTTP_PORT'] === "80" ? $_SERVER['REQUEST_SCHEME'] . ":" . DS . DS . $_SERVER['HTTP_HOST'] : $_SERVER['REQUEST_SCHEME'] . ":" . DS . DS . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['HTTP_PORT']
);
