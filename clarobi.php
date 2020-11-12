<?php

/**
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area.
 *
 * @link              https://clarobi.com
 * @since             1.0.0
 * @package           Clarobi
 *
 * @wordpress-plugin
 * Plugin Name:       Clarobi
 * Plugin URI:
 * Description:       ClaroBi API helper for reports, statistics and analytics for WooCommerce stores.
 * Version:           1.0.0
 * Author:            Clarobi
 * Author URI:        https://clarobi.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clarobi
 * WC requires at least: 3.9.0
 * WC tested up to: 4.5.2.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('CLAROBI_VERSION', '1.0.0');
define('CLAROBI_DB_VERSION', '1.0.0');

/**
 * Tables names.
 */
define( 'CLAROBI_PRODUCTS_COUNTERS_TABLE', 'clarobi_products_counters' );

/**
 * Options name.
 */
define('OPT_CLAROBI_OPTIONS', 'clarobi_options');
define('OPT_CLAROBI_SHOW_ACTIVATION_NOTICE', 'clarobi_show_activation_notice');
define('OPT_CLAROBI_P_C_DB_VERSION', 'clarobi_products_counters_db_version');
define('OPT_CLAROBI_CONFIG_DB_VERSION', 'clarobi_configurations_db_version');


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-clarobi-activator.php
 */
function activate_clarobi()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-clarobi-activator.php';
    Clarobi_Activator::activate();
}

register_activation_hook(__FILE__, 'activate_clarobi');
register_uninstall_hook(__FILE__, 'uninstall.php');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-clarobi.php';

/**
 * Add/Update row in table based on product id and column to set/update.
 *
 * @param int $product_id
 * @param int $count
 * @param string $column Possible values: view, add_to_cart, add_to_wish_list
 * @param string $operation
 */
function update_clarobi_products_counters_table($product_id, $count, $column, $operation = '+')
{
    global $wpdb;
    $date = date('Y-m-d H:i:s', time());

    $sql = "INSERT INTO {$wpdb->prefix}" . CLAROBI_PRODUCTS_COUNTERS_TABLE . " (`product_id`,`{$column}`,`date_add`) 
    VALUES (%d,%d,%s) 
    ON DUPLICATE KEY 
    UPDATE `{$column}`  =  `{$column}` {$operation} %d, `date_update` = %s";

    $sql = $wpdb->prepare($sql, $product_id, $count, $date, $count, $date);
    $wpdb->query($sql);
}

// Register function for a hook on product page.
add_action('woocommerce_after_add_to_cart_button', 'clarobi_count_post_views');
function clarobi_count_post_views()
{
    global $product;
    $product_id = $product->get_id();

    update_clarobi_products_counters_table($product_id, 1, 'view');
}

// Update quantity for actions: on cart page, product page and products list
add_action('woocommerce_after_cart_item_quantity_update', 'clarobi_after_cart_item_quantity_update', 10, 4);
function clarobi_after_cart_item_quantity_update($cart_item_key, $quantity, $old_quantity, $cart)
{
    $product_id = $cart->cart_contents[$cart_item_key]['product_id'];
    $new_quantity = $quantity;
    $operation = '+';
    if ($old_quantity < $new_quantity) {
        $qty = $new_quantity - $old_quantity;
    }
    if ($new_quantity < $old_quantity) {
        $qty = $old_quantity - $new_quantity;
        $operation = '-';
    }
    update_clarobi_products_counters_table($product_id, $qty, 'add_to_cart', $operation);
}

// Update quantity on product removed from cart
add_action('woocommerce_remove_cart_item', 'clarobi_remove_cart_item', 10, 2);
function clarobi_remove_cart_item($cart_item_key, $cart)
{
    $product_id = $cart->cart_contents[$cart_item_key]['product_id'];
    $product_qty = $cart->cart_contents[$cart_item_key]['quantity'];

    update_clarobi_products_counters_table($product_id, $product_qty, 'add_to_cart', '-');
}

add_action('rest_api_init', function (){
    register_rest_route('r', '/i', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => function(){
            die('123456789');
        },
    ));
});


// Begins execution of the plugin.
function Clarobi()
{
    return Clarobi::instance();
}

Clarobi();
