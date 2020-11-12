<?php

/**
 * Stocks endpoint.
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
 * Stocks endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/stock endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Stock extends Clarobi_Auth
{
    protected $rest_base = 'stock';
    protected $entity_name = 'stock';

    protected $post_type = 'product';

    /**
     * Clarobi_Stock constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_stock_route'));
    }

    /**
     * Stock route definition.
     */
    public function clarobi_stock_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'stocks_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
        ));
    }

    /**
     * Callback for the stocks API endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return mixed|WP_REST_Response
     */
    public function stocks_api_callback($request)
    {
        try {
            $args = Clarobi_Utils::prepare_query_args($this->entity_name);

            $results = wc_get_products($args);

            $products = [];
            if (!empty($results)) {
                /** @var WC_Product $product */
                foreach ($results as $product) {
                    $data = $this->prepare_object_for_response($product, $request);
                    $products[] = $this->prepare_response_for_collection($data);
                }
                $this->last_id = ($product ? $product->get_id() : 0);
            }

            $stocks = [
                'date' => date('Y-m-d', time()),
                'stock' => $products
            ];

            $json = $this->Clarobi_Encoder->encodeJson($this->entity_name, $stocks, 0, 'STOCK');

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

    /**
     * Prepare a single product output for response.
     *
     * @param WC_Product $object
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function prepare_object_for_response($object, $request)
    {
        $context = !empty($request['context']) ? $request['context'] : 'view';
        $data = $this->get_product_data($object, $context);

        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);
        $response = rest_ensure_response($data);

        /**
         * Filter the data for a response.
         *
         * The dynamic portion of the hook name, $this->post_type,
         * refers to object type being prepared for the response.
         *
         * @param WP_REST_Response $response The response object.
         * @param WC_Data $object Object data.
         * @param WP_REST_Request $request Request object.
         */
        return apply_filters("woocommerce_rest_prepare_{$this->post_type}_object", $response, $object, $request);
    }

    /**
     * Get product data for stock.
     *
     * @param WC_Product $product Product instance.
     * @param string $context Request context.
     * @return array
     */
    protected function get_product_data($product, $context = 'view')
    {
        $data = [
            'id' => $product->get_id(),
            's' => $product->get_stock_quantity($context),
        ];

        return $data;
    }
}

new Clarobi_Stock();
