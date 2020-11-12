<?php

/**
 * Fired during plugin activation
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
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Activator
{
    /**
     * Run sql on activation.
     */
    public static function activate()
    {
        Clarobi_Sql_Create::clarobi_create_products_counters_table();

        // Set Clarobi show activation notice option to true if it isn't already false (only first time)
        if (get_option('clarobi_show_activation_notice', true)) {
            update_option('clarobi_show_activation_notice', true);
        }
    }
}
