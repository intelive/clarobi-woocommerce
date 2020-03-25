<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Class Clarobi_Abandoned_Carts
 */
class Clarobi_Abandoned_Carts extends Clarobi_Auth
{
    protected $rest_base = 'abandonedcart';
    protected $entity_name = 'abandonedcart';

    /**
     * How many entries to retrieve from db for one call.
     */
    protected $limit = 50;

    /**
     * Clarobi_Abandoned_Carts constructor.
     */
    public function __construct()
    {
        parent::__construct();
        add_action('rest_api_init', array($this, 'clarobi_abandonedcart_route'));
    }

    /**
     * Abandonedcarts route definition.
     */
    public function clarobi_abandonedcart_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'abandonedcarts_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
        ));
    }

    /**
     * Callback for abandonedcarts API endpoint.
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function abandonedcarts_api_callback($request)
    {
        try {
            parent::get_request_param($request);

            $keys = ['cart', 'customer', 'cart_totals'];

            global $wpdb;

            $table = $wpdb->prefix . 'woocommerce_sessions';
            $results = $wpdb->get_results(
                "SELECT *
                    FROM $table 
                    WHERE session_id > $this->from_id 
                    ORDER BY session_id ASC
                    LIMIT $this->limit"
            );

            /**
             * todo - not complete - data is empty
             */
            $abandonedcarts = [];
            if (!is_null($results)) {
                foreach ($results as $result) {
                    $unserialize_result = maybe_unserialize($result->session_value);

                    $data = [];
                    foreach ($unserialize_result as $key => $value) {
                        if (in_array($key, $keys)) {
                            $data[$key] = maybe_unserialize($value);
                        }
                    }
                    // $data['cart'] = []  if order was created
//                    if (!empty($data['cart']) && !empty($data['cart_totals']) && !empty($data['customer'])) {
                    /**
                     * @todo check sessions - at least 3 should have products
                     */
                    if (!empty($data['cart']) || !empty($data['cart_totals']) || !empty($data['customer'])) {
                        $prepared_data = Clarobi_Mapper::session_cart_mapper($data);

                        $prepared_data['id'] = $result->session_id;
                        $prepared_data['entity_name'] = $this->entity_name;
                        $abandonedcarts[] = $prepared_data;
                    }
                    $this->last_id = ($result->session_id ? $result->session_id : 0);
                }
            }
            $json = $this->Clarobi_Encoder->encodeJson($this->entity_name, $abandonedcarts, $this->last_id);

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

new Clarobi_Abandoned_Carts();
