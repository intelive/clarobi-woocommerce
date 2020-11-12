<?php

/**
 * Define APIs permission function.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/auth
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Define APIs permission function.
 *
 * This class is responsible for token and query param validation.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Auth extends WC_REST_Controller
{
    /** @var string Plugin namespace. */
    public $namespace = 'clarobi';

    protected $claro_config;
    protected $Clarobi_Encoder;

    /**
     * Derived classes common/shared properties.
     */
    protected $from_id = 0;
    protected $last_id = 0;

    /**
     * Clarobi_Auth constructor.
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'clarobi_info_route'));

        $this->claro_config = Clarobi_Utils::get_clarobi_configurations();
        $this->Clarobi_Encoder = new Clarobi_Encoder($this->claro_config);
    }

    /**
     * Clarobi info route definition.
     */
    public function clarobi_info_route()
    {
        // If rest_base is added as property and used will change derived classes routes
        register_rest_route($this->namespace, '/info', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'clarobi_info_callback'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
        ));
    }

    /**
     * Callback for the Clarobi Info API endpoint.
     *
     * @return mixed|WP_REST_Response
     */
    public function clarobi_info_callback()
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $data = [
            'active' => true,
            'version' => Clarobi()->version,
            'claribi_plugin' => $plugins['clarobi/clarobi.php'],
        ];

        $response = rest_ensure_response($data);
        $response->set_status(200);

        return $response;
    }

    /**
     * Verify is request param is set and integer and return value.
     *
     * @param $request
     * @throws Exception
     */
    protected function get_request_param($request)
    {
        // Verify if from_id query param is set
        if (!isset($request['from_id']) || !is_numeric($request['from_id'])) {
            // Bad request
            throw new Exception('Param \'from_id\' is empty, missing or has non numeric value!', 400);
        }

        $this->from_id = $request['from_id'];
    }

    /**
     * Check whether a given request has permission to read info.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request)
    {
        try {
            // Get token form request
            $token = $request->get_header('X-Claro-TOKEN');

            if (!$token) {
                throw new Exception('Missing token (X-Claro-TOKEN) from request.');
            }
            // Get token form db
            $claroToken = $this->claro_config['CLAROBI_API_KEY'];

            if (!$claroToken) {
                throw new Exception('API_KEY (token) not found in database (clarobi_configuration).');
            }

            // Verify if token is the same as the one saved in db
            if ($token !== $claroToken) {
                throw new Exception('Clarobi feed request with invalid security token: '
                    . $claroToken . ' compared to stored token: ' . $token
                );
            }
        } catch (Exception $exception) {
            Clarobi_Logger::errorLog($exception, __METHOD__);
            // Return errors
            return new WP_Error('500', $exception->getMessage());
        }

        return true;
    }
}

new Clarobi_Auth();
