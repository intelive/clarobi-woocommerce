<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Class Clarobi_Orders
 */
class Clarobi_Orders extends Clarobi_Auth
{
    /**
     * @var WC_REST_Orders_Controller
     */
    protected $WC_Rest_Orders_Controller;

    protected $rest_base = 'order';
    protected $entity_name = 'order';

    /**
     * Clarobi_Orders constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_order_route'));

        $this->WC_Rest_Orders_Controller = new WC_REST_Orders_Controller();
    }

    /**
     * Customers route definition.
     */
    public function clarobi_order_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'orders_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
            'args' => $this->WC_Rest_Orders_Controller->get_collection_params()
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
     * Callback for the orders API endpoint.
     *
     * $order_statuses = [
     *      "any", "trash", "pending", "processing", "on-hold",
     *      "completed", "cancelled", "refunded", "failed"
     * ];
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function orders_api_callback($request)
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

            $orders = [];
            if (!empty($results)) {
                /** @var WC_Order $order */
                foreach ($results as $order) {
                    $data = $this->WC_Rest_Orders_Controller->prepare_object_for_response($order, $request);
                    $preparedData = $this->WC_Rest_Orders_Controller->prepare_response_for_collection($data);

                    $customer = new WP_User((int)$order->get_customer_id());
                    $preparedData['customer_name'] = $customer->user_firstname . ' ' . $customer->user_lastname;
                    $preparedData['customer_email'] = $customer->user_email;
                    $preparedData['customer_group'] = $customer->roles;

                    $preparedData['store_id'] = get_main_site_id();
                    $preparedData['entity_name'] = 'sales_' . $this->entity_name;

                    $preparedData['line_items'] = $this->formant_items_details($preparedData['line_items']);

                    $preparedData = $this->set_coupons_codes($preparedData);
                    $all_formatted_data = Clarobi_Mapper::ignore_entity_keys($this->entity_name, $preparedData);

                    $orders[] = $all_formatted_data;
                }
                $this->last_id = ($order ? $order->get_id() : 0);
            }
            $json = $this->Clarobi_Encoder->encodeJson('sales_' . $this->entity_name, $orders, $this->last_id);

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
     * Format products output in order.
     *
     * @param $line_items
     * @return array
     */
    private function formant_items_details($line_items)
    {
        $new_lines = [];
        foreach ($line_items as $item) {
            $item = Clarobi_Mapper::set_parent_details($item);

//            $product = new WC_Product($item['product_id']);
//            $item['categories'] = $this->get_categories_tree($product->get_category_ids());
            $item['categories'] = [];

            $new_lines[] = $item;
        }

        return $new_lines;
    }

    /**
     * Return an array containing a mapping for product categories.
     * @todo delete
     * @param array $categories Array with product categories ids.
     * @return array
     */
    private function get_categories_tree($categories)
    {
        $categories_tree = [];
        if (is_array($categories)) {
            foreach ($categories as $category) {
                // get category name
                // add to tree
                $categories_tree[] = [
                    'id' => $category,
                    'name' => ''
                ];
            }
        }
        // todo how to get category name????
//        var_dump(get_category(20,ARRAY_A, 'no'));
//        var_dump(get_the_category(185));

        return $categories_tree;
    }

    private function set_coupons_codes($preparedData){
        // no need for all coupons info
        $coupons_codes = [];
        if (isset($preparedData['coupon_lines'])) {
            if (!empty($preparedData['coupon_lines'])) {
                foreach ($preparedData['coupon_lines'] as $coupon_line) {
                    $coupons_codes[] = $coupon_line['code'];
                }
            }
            unset($preparedData['coupon_lines']);
        }
        $preparedData['coupons_codes'] = implode('-', $coupons_codes);

        return $preparedData;
    }
}

new Clarobi_Orders();
