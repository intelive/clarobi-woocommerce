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
 * This class defines all sql code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Sql_Create
{
    /**
     * Create products counters table.
     */
    public static function clarobi_create_products_counters_table()
    {
        global $wpdb;

        $clarobi_p_c_table_name = $wpdb->prefix . CLAROBI_PRODUCTS_COUNTERS_TABLE;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$clarobi_p_c_table_name} (
		        product_id INTEGER (11) NOT NULL,
		        view INTEGER (11) NOT NULL DEFAULT 0,
		        add_to_cart INTEGER (11) NOT NULL DEFAULT 0,
		        add_to_wish_list INTEGER (11) NOT NULL DEFAULT 0,
		        date_add DATETIME DEFAULT NULL,
		        date_update DATETIME DEFAULT NULL,
		        PRIMARY KEY  (product_id)
	    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option(OPT_CLAROBI_P_C_DB_VERSION, CLAROBI_DB_VERSION);
    }

    /**
     * Create configurations table.
     */
    public static function clarobi_create_configurations_table()
    {
        global $wpdb;

        $clarobi_config_table_name = $wpdb->prefix . 'clarobi_configurations';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$clarobi_config_table_name} (
		        id INTEGER (11) NOT NULL AUTO_INCREMENT,
		        config_name VARCHAR (250) DEFAULT NULL  COMMENT 'Clarobi credential name',
		        config_value VARCHAR (250) DEFAULT NULL COMMENT 'Clarobi credential value',
		        date_add DATETIME DEFAULT NULL,
		        date_update DATETIME DEFAULT NULL,
		        PRIMARY KEY  (id)
	    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option(OPT_CLAROBI_CONFIG_DB_VERSION, CLAROBI_DB_VERSION);
    }
}
