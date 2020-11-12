<?php

/**
 * Credit memos endpoint.
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
 * Credit memos endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/creditmemo endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Credit_Memos extends Clarobi_Auth
{
    /**
     * @var WC_REST_Order_Refunds_Controller
     */
    protected $WC_REST_Order_Refunds_Controller;

    protected $rest_base = 'creditmemo';
    protected $entity_name = 'creditmemo';

    /**
     * Clarobi_Credit_Memos constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_creditmemo_route'));

        $this->WC_REST_Order_Refunds_Controller = new WC_REST_Order_Refunds_Controller();
    }

    /**
     * Creditmemos route definition.
     */
    public function clarobi_creditmemo_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'creditmemos_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
            'args' => $this->WC_REST_Order_Refunds_Controller->get_collection_params()
        ));
    }

    /**
     * Filter to set condition for ID grater than from_id param.
     *
     * @param string $query
     * @return string
     */
    public function filter_posts_from_id($query = '')
    {
        $query .= " AND ID > " . $this->from_id;
        return $query;
    }

    /**
     * Callback for the creditmemos API endpoint.
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function creditmemos_api_callback($request)
    {
        try {
            $this->get_request_param($request);

            $args = Clarobi_Utils::prepare_query_args($this->entity_name);

            /**
             * Add filter to get orders with id > from_id param
             */
            add_filter('posts_where', array($this, 'filter_posts_from_id'));

            $query = new WC_Order_Query($args);
            $results = $query->get_orders();

            $creditmemos = [];
            if (!empty($results)) {
                /** @var WC_Order_Refund $creditmemo */
                foreach ($results as $creditmemo) {
                    // $request needs to have set order_id otherwise an WP_Error will be returned
                    $request['order_id'] = $creditmemo->get_parent_id();

                    $data = $this->WC_REST_Order_Refunds_Controller->prepare_object_for_response($creditmemo, $request);
                    $preparedData = $this->WC_REST_Order_Refunds_Controller->prepare_response_for_collection($data);

                    $preparedData['store_id'] = get_main_site_id();
                    $preparedData['order_id'] = $creditmemo->get_parent_id();
                    $preparedData['currency_code'] = $creditmemo->get_currency();

                    $preparedData['entity_name'] = 'sales_creditnote';

                    // Get associated order
                    $order = new WC_Order($preparedData['order_id']);
                    if ($order) {
                        // If order is fully refunded the status will be automatically changed to 'wc-refunded'
                        if ($order->get_status() === 'refunded') {
                            $preparedData['line_items'] = $this->get_items_from_order($order, $request);
                        }
                    }

                    $creditmemos[] = Clarobi_Mapper::ignore_entity_keys($this->entity_name, $preparedData);
                }
                $this->last_id = ($creditmemo ? $creditmemo->get_id() : 0);
            }
            $json = $this->Clarobi_Encoder->encodeJson('sales_creditnote', $creditmemos, $this->last_id, 'SYNC');

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
     * Return specific fields for order items as in order refund.
     *
     * @param WC_Order $order
     * @param WP_REST_Request $request
     * @return array
     */
    private function get_items_from_order($order, $request)
    {
        // Prepare order as for specific endpoint
        $wc_rest_order = new WC_REST_Orders_Controller();

        $preparedOrder = $wc_rest_order->prepare_object_for_response($order, $request);
        $preparedOrder = $wc_rest_order->prepare_response_for_collection($preparedOrder);

        $line_items = [];
        // For each item get fields explicit
        if (!empty($preparedOrder['line_items'])) {
            foreach ($preparedOrder['line_items'] as $item) {
                $line_items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    "subtotal" => $item['subtotal'],
                    "total" => $item['total'],
                    "total_tax" => $item['total_tax'],
                    "price" => $item['price']
                ];
            }
        }

        return $line_items;
    }
}

new Clarobi_Credit_Memos();
