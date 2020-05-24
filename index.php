<?php
    /**
     * Front to the WordPress application. This file doesn't do anything, but loads
     * wp-blog-header.php which does and tells WordPress to load the theme.
     *
     * @package WordPress
     */

    /**
     * Tells WordPress to load the WordPress theme and output it.
     *
     * @var bool
     */
    define('WP_USE_THEMES', true);

    function predb($array, $true = false)
    {
        $debug = debug_backtrace();
        if ($true) {
            echo '<pre>';
            foreach ($debug as $posts) {
                echo print_r($posts['file'] . ':' . $posts['line'], 1) . '<br />';
            }
            echo '</pre>';
        }
        $debugIndex = 0;
        echo "<font color=#8a2be2>" . $debug[$debugIndex]["file"] . "</font>:<font color='red'>" . $debug[$debugIndex]["line"];
        echo '<pre>' . print_r($array, 1) . '</pre>';
    }

    /** Loads the WordPress Environment and Template */
    require(dirname(__FILE__) . '/wp-blog-header.php');
