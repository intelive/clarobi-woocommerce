<?php

/**
 * Products endpoint.
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
 * Products endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/product endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Products extends Clarobi_Auth
{
    /**
     * @var WC_REST_Products_Controller
     */
    protected $WC_REST_Products_Controller;

    protected $rest_base = 'product';
    protected $entity_name = 'product';

    protected $post_type = 'product';

    /**
     * Clarobi_Products constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_product_route'));

        $this->WC_REST_Products_Controller = new WC_REST_Products_Controller();
    }

    /**
     * Products route definition.
     */
    public function clarobi_product_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'products_api_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
            'args' => $this->WC_REST_Products_Controller->get_collection_params()
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
     * Callback for the products API endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return mixed|WP_REST_Response
     */
    public function products_api_callback($request)
    {
        try {
            $this->get_request_param($request);

            $args = Clarobi_Utils::prepare_query_args($this->entity_name);

            /**
             * Add filter to get products with id > from_id param
             */
            add_filter('posts_where', array($this, 'filter_posts_from_id'));

            $query = new WC_Product_Query($args);
            $results = $query->get_products();

            $products = [];
            if (!empty($results)) {
                /** @var WC_Product $product */
                foreach ($results as $product) {
                    $data = $this->WC_REST_Products_Controller->prepare_object_for_response($product, $request);
                    $preparedData = $this->WC_REST_Products_Controller->prepare_response_for_collection($data);

                    $preparedData['entity_name'] = $this->entity_name;

                    $products[] = Clarobi_Mapper::ignore_entity_keys($this->entity_name, $preparedData);
                }
                $this->last_id = ($product ? $product->get_id() : 0);
            }

            $json = $this->Clarobi_Encoder->encodeJson($this->entity_name, $products, $this->last_id);

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
     * Get product variations data.
     *
     * @param array $variations Array of variations ids.
     * @return array
     */
    public function get_product_variations_details(array $variations)
    {
        $options = [];

        if (is_array($variations)) {
            foreach ($variations as $variation) {
                $productVariation = new WC_Product_Variation($variation);
                $options[] = [
                    'id' => $variation,
                    'name' => $productVariation->get_name(),
                    'sku' => $productVariation->get_sku(),
                    'status' => '',
                    'created_at' => ($productVariation->get_date_created() ? $productVariation->get_date_created()->date('Y-m-d H:i:s') : ''),
                    'updated_at' => ($productVariation->get_date_modified() ? $productVariation->get_date_modified()->date('Y-m-d H:i:s') : ''),
                    'active' => $productVariation->variation_is_active(),
                    'visibility' => $productVariation->variation_is_visible(),
                    'variation_attributes' => $productVariation->get_variation_attributes(),
                    'attributes' => $productVariation->get_attributes()
                ];
            }
        }

        return $options;
    }
}

new Clarobi_Products();
