<?php

/**
 * Used for logging errors.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Used for logging errors..
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Logger
{
    /**
     * Logs errors in module file.
     *
     * @param string $message Error message to log.
     * @param string $path Class, function in witch occurred.
     */
    public static function errorLog($message, $path)
    {
        $completeMessage = $message . " at " . $path . "( " . date('Y-m-d H:i:s') . " )\n";
        error_log(
            $completeMessage,
            3,
            plugin_dir_path(dirname(__FILE__)) . 'logs/errors.log'
        );
    }
}