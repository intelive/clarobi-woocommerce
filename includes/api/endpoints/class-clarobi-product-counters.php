<?php

/**
 * Product counters endpoint.
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
 * Product counters endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/productCounters endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Product_Counters extends Clarobi_Auth
{
    protected $rest_base = 'productCounters';
    protected $entity_name = 'product_counter';

    /**
     * Clarobi_Product_Counters constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_products_counters_route'));
    }

    /**
     * ProductCounters route definition.
     */
    public function clarobi_products_counters_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'products_counters_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
        ));
    }

    /**
     * Callback for productCounters API endoint.
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function products_counters_api_callback($request)
    {
        try {
            global $wpdb;
            $results = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}" . CLAROBI_PRODUCTS_COUNTERS_TABLE
            );
            $counters[] = [];
            if ($results) {
                foreach ($results as $result) {
                    $counters[] = [
                        "product_id" => $result->product_id,
                        "event_name" => "catalog_product_view",
                        "viewed" => ($result->view ? $result->view : 0)
                    ];
                    $counters[] = [
                        "product_id" => $result->product_id,
                        "event_name" => "checkout_cart_add_product",
                        "viewed" => ($result->add_to_cart ? $result->add_to_cart : 0)
                    ];
                }
            }
            $data = [
                'date' => date('Y-m-d H:i:s', time()),
                'counters' => $counters
            ];
            $json = $this->Clarobi_Encoder->encodeJson($this->entity_name, $data, 0, 'PRODUCT_COUNTERS');

            return rest_ensure_response($json);
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
}

new Clarobi_Product_Counters();
