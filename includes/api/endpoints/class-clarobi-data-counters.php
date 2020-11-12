<?php

/**
 * Data counters endpoint.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Data counters endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/dataCounters endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Data_Counters extends Clarobi_Auth
{
    protected $rest_base = 'dataCounters';

    /**
     * Clarobi_Data_Counters constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_data_counters_route'));
    }

    /**
     * Stock route definition.
     */
    public function clarobi_data_counters_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'data_counters_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
        ));
    }

    /**
     * Callback for the dataCounters API endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return mixed|WP_REST_Response
     */
    public function data_counters_api_callback($request)
    {
        try {
            /**
             * If there are more arguments that the default ones for each entity,
             * they will be ignored.
             */
            $data = [
                'product' => $this->get_product_id(),
                'customer' => $this->get_customer_id(),
                'order' => $this->get_order_id(),
                'invoice' => $this->get_order_id(true),
                'creditmemo' => $this->get_creditmemo_id(),
                'abandonedcart' => $this->get_abandonedcart_id()
            ];

            return rest_ensure_response($data);
        } catch (Exception $exception) {
            Clarobi_Logger::errorLog($exception->getMessage(), __METHOD__);

            $response = rest_ensure_response([
                'status' => 'error',
                'error' => $exception->getMessage()
            ]);
            $response->set_status(($exception->getCode() ? $exception->getCode() : 500));

            return $response;
        }
    }

    /**
     * Get last product id.
     *
     * @return int
     */
    protected function get_product_id()
    {
        $args = ['limit' => 1, 'orderby' => 'ID', 'order' => 'DESC'];
        $products = wc_get_products($args);

        $productId = 0;
        if ($products) {
            /** @var WC_Product $product */
            $product = $products[0];
            $productId = $product->get_id();
        }

        return ($productId ? $productId : 0);
    }

    /**
     * Get last customer id.
     *
     * @return int
     */
    protected function get_customer_id()
    {
        $args = ['limit' => 1, 'orderby' => 'ID', 'order' => 'DESC', 'role' => 'customer'];
        $customers = get_users($args);

        $customerId = 0;
        if ($customers) {
            /** @var WP_User $customer */
            $customer = $customers[0];
            $customerId = $customer->ID;
        }

        return ($customerId ? $customerId : 0);
    }

    /**
     * Get last order id.
     * Get last invoice id (order with status complete).
     *
     * @param bool $statusComplete
     * @return int
     */
    protected function get_order_id($statusComplete = false)
    {
        $args = ['limit' => 1, 'orderby' => 'ID', 'order' => 'DESC', 'post_type' => 'shop_order'];
        if ($statusComplete) {
            $args['post_status'] = 'wc-completed';
        }
        $orders = wc_get_orders($args);

        $orderId = 0;
        if ($orders) {
            /** @var WC_Order $order */
            $order = $orders[0];
            $orderId = $order->get_id();
        }

        return ($orderId ? $orderId : 0);
    }

    /**
     * Get last credit_memo id.
     *
     * @return int
     */
    protected function get_creditmemo_id()
    {
        $args = [
            'limit' => 1, 'orderby' => 'ID', 'order' => 'DESC',
            'post_type' => 'shop_order_refund', 'post_status' => 'any'
        ];
        $creditmemos = wc_get_orders($args);

        $creditmemoId = 0;
        if ($creditmemos) {
            /** @var WC_Order $order */
            $creditmemo = $creditmemos[0];
            $creditmemoId = $creditmemo->get_id();
        }

        return ($creditmemoId ? $creditmemoId : 0);
    }

    /**
     * Get last abandoned_cart id
     *
     * @return int
     */
    protected function get_abandonedcart_id()
    {
        $abandonedcartId = 0;

        global $wpdb;

        $table = $wpdb->prefix . 'woocommerce_sessions';
        $result = $wpdb->get_results(
            "SELECT session_id
                    FROM $table 
                    ORDER BY session_id DESC
                    LIMIT 1"
        );
        if ($result) {
            $abandonedcartId = ($result[0] ? (int)$result[0]->session_id : 0);
        }
        return $abandonedcartId;
    }
}

new Clarobi_Data_Counters();
