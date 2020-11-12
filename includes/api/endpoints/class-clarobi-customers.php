<?php

/**
 * Customers endpoint.
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
 * Customers endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/customer endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Customers extends Clarobi_Auth
{
    /**
     * @var WC_REST_Customers_Controller
     */
    protected $WC_REST_Customers_Controller;

    protected $rest_base = 'customer';
    protected $entity_name = 'customer';

    /**
     * Clarobi_Customers constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_customer_route'));

        $this->WC_REST_Customers_Controller = new WC_REST_Customers_Controller();
    }

    /**
     * Customers route definition.
     */
    public function clarobi_customer_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'customers_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
            'args' => $this->WC_REST_Customers_Controller->get_collection_params()
        ));
    }

    /**
     * Filter to set condition for ID grater than from_id param.
     *
     * @param $u_query
     */
    public function filter_users_from_id($u_query)
    {
        global $wpdb;

        // Just str_replace() the SQL query for users
        $u_query->query_where = str_replace(
            'WHERE 1=1',
            "WHERE 1=1 AND {$wpdb->users}.ID > " . $this->from_id,
            $u_query->query_where
        );
    }

    /**
     * Callback for the customers API endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return mixed|WP_REST_Response
     */
    public function customers_api_callback($request)
    {
        try {
            $this->get_request_param($request);

            $args = Clarobi_Utils::prepare_query_args($this->entity_name);

            /**
             * Add filter to get users with id > from_id param
             */
            add_filter('pre_user_query', array($this, 'filter_users_from_id'));

            $query = new WP_User_Query($args);
            $results = $query->get_results();

            $customers = [];
            if (!empty($results)) {
                /** @var WP_User $customer */
                foreach ($results as $customer) {
                    // This way no mapping will be needed in unity-admin
                    $data = $this->prepare_users_for_response($customer, $request);
                    $preparedData = $this->prepare_response_for_collection($data);

                    $preparedData['entity_name'] = $this->entity_name;

                    $customers[] = $preparedData;
                }
                $this->last_id = ($customer ? $customer->ID : 0);
            }
            $json = $this->Clarobi_Encoder->encodeJson($this->entity_name, $customers, $this->last_id);

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
     * Prepare a single customer output for response.
     *
     * @param WP_User $customer Customer object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_users_for_response($customer, $request)
    {
        $data = [
            'id' => $customer->ID,
            'created_at' => $customer->user_registered,
            'store_id' => $customer->get_site_id(),
            'dob' => null,
            'name' => $customer->first_name . ' ' . $customer->last_name,
            'gender' => 3,
            'group' => $customer->roles,
            'bill_country' => $customer->billing_country,
            'ship_country' => $customer->shipping_country,
            'email' => $customer->user_email,
            'entity_name' => $this->entity_name
        ];

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        /*
         * Filter customer data returned from the REST API.
         *
         * @param WP_REST_Response $response  The response object.
         * @param WP_User          $customer  User object used to create response.
         * @param WP_REST_Request  $request   Request object.
         */
        return apply_filters('woocommerce_rest_prepare_customer', $response, $customer, $request);
    }
}

new Clarobi_Customers();
