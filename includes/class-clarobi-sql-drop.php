<?php

/**
 * Fired during plugin deactivation
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
 * Fired during plugin deactivation.
 *
 * This class defines all sql code necessary to run during the plugin deactivation.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Sql_Drop
{
    const CLAROBI_TABLES = [CLAROBI_PRODUCTS_COUNTERS_TABLE, 'clarobi_configurations'];

    /**
     * Drop all plugin tables.
     */
    public static function clarobi_drop_tables()
    {
        global $wpdb;

        $sql = [];
        foreach (self::CLAROBI_TABLES as $table_name) {
            $sql[] = "DROP TABLE IF EXISTS " . $wpdb->prefix . $table_name;
        }
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);
    }
}
