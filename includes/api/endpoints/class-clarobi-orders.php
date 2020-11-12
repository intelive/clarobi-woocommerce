<?php

/**
 * Orders endpoint.
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
 * Orders endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/order endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Orders extends Clarobi_Auth
{
    /**
     * @var WC_REST_Orders_Controller
     */
    protected $WC_Rest_Orders_Controller;

    /**
     * @var WC_REST_Products_Controller
     */
    protected $WC_REST_Products_Controller;

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
        $this->WC_REST_Products_Controller = new WC_REST_Products_Controller();
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

                    $preparedData['line_items'] = $this->formant_items_details($preparedData['line_items'], $request);

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
    private function formant_items_details($line_items, $request)
    {
        $new_lines = [];
        foreach ($line_items as $item) {
            // Get product for categories and options
            $product = new WC_Product($item['product_id']);
            $product = $this->WC_REST_Products_Controller
                ->prepare_object_for_response($product, $request);
            $product = $this->WC_REST_Products_Controller->prepare_response_for_collection($product);

            $item = Clarobi_Mapper::set_parent_details($item);

            $item['categories'] = $this->mapProductCategories($product['categories']);
            $item['options'] = $this->mapProductOptions($product['attributes'], $item['product_id']);

            $new_lines[] = $item;
        }

        return $new_lines;
    }

    /**
     * Map product categories.
     *
     * @param $categories
     * @return array
     */
    private function mapProductCategories($categories)
    {
        $mappedCategories = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $mappedCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['name']
                ];
            }
        }
        return $mappedCategories;
    }

    /**
     * Map product options.
     *
     * @param $attributes
     * @param $product_id
     * @return array
     */
    private function mapProductOptions($attributes, $product_id)
    {
        $options = [];
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if (!empty($attribute['options'])) {
                    foreach ($attribute['options'] as $option) {
                        $options[] = [
                            'attribute_id' => $attribute['id'],
                            'label' => $attribute['name'],
                            'value' => $option,
                            'item_id' => $product_id
                        ];
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Get coupons codes.
     *
     * @param $preparedData
     * @return mixed
     */
    private function set_coupons_codes($preparedData)
    {
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
