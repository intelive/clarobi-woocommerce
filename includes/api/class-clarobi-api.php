<?php

/**
 * Loads api files.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Loads api files.
 *
 * This class is responsible for loading endpoints files.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_API
{
    /**
     * Clarobi_API constructor.
     */
    public function __construct()
    {
        // We must only include after this action since Woo 3.6+ uses it - https://github.com/woocommerce/woocommerce/commit/cd4039e07885b76d55dbccd4ab72edbe67c87628
        add_action('rest_api_init', array($this, 'includes'), 5);
    }

    /**
     * Include all the plugin endpoints.
     */
    public function includes()
    {
        require_once 'auth/class-clarobi-auth.php';
        require_once 'endpoints/class-clarobi-customers.php';
        require_once 'endpoints/class-clarobi-products.php';
        require_once 'endpoints/class-clarobi-orders.php';
        require_once 'endpoints/class-clarobi-invoices.php';
        require_once 'endpoints/class-clarobi-abandoned-carts.php';
        require_once 'endpoints/class-clarobi-credit-memos.php';
        require_once 'endpoints/class-clarobi-stock.php';
        require_once 'endpoints/class-clarobi-product-counters.php';
        require_once 'endpoints/class-clarobi-data-counters.php';
        require_once 'endpoints/class-clarobi-product-image.php';
    }
}

new Clarobi_API();
