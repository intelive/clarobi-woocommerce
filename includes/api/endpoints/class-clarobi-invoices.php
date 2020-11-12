<?php

/**
 * Invoices endpoint.
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
 * Invoices endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/invoice endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Invoices extends Clarobi_Auth
{
    /**
     * @var WC_REST_Orders_Controller
     */
    protected $WC_Rest_Orders_Controller;

    protected $rest_base = 'invoice';
    protected $entity_name = 'invoice';

    /**
     * Clarobi_Invoices constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_invoice_route'));

        $this->WC_Rest_Orders_Controller = new WC_REST_Orders_Controller();
    }

    /**
     * Invoices route definition.
     */
    public function clarobi_invoice_route()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'invoices_api_callback'),
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
     * Callback for the invoices API endpoint.
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_REST_Response
     */
    public function invoices_api_callback($request)
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

            $invoices = [];
            if (!empty($results)) {
                /** @var WC_Order $invoice */
                foreach ($results as $invoice) {
                    $data = $this->WC_Rest_Orders_Controller->prepare_object_for_response($invoice, $request);
                    $preparedData = $this->WC_Rest_Orders_Controller->prepare_response_for_collection($data);

                    $preparedData['store_id'] = get_main_site_id();
                    $preparedData['entity_name'] = 'sales_' . $this->entity_name;

                    $preparedData['line_items'] = $this->formant_items_details_for_invoice($preparedData['line_items']);

                    $invoices[] = Clarobi_Mapper::ignore_entity_keys($this->entity_name, $preparedData);
                }
                $this->last_id = ($invoice ? $invoice->get_id() : 0);
            }
            $json = $this->Clarobi_Encoder->encodeJson(
                'sales_' . $this->entity_name,
                $invoices,
                $this->last_id
            );

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
    private function formant_items_details_for_invoice($line_items)
    {
        $new_lines = [];
        foreach ($line_items as $item) {
            $item = Clarobi_Mapper::set_parent_details($item);

            $new_lines[] = $item;
        }

        return $new_lines;
    }
}

new Clarobi_Invoices();
