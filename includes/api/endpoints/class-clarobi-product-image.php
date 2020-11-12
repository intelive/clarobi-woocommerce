<?php

/**
 * Product image endpoint.
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
 * Product image endpoint.
 *
 * This class is responsible for creating and implementing /clarobi/product/get-image endpoint.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api/endpoints
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Product_Image extends Clarobi_Auth
{
    const ERR = 'ERROR:';
    const HEADER_UNITYREPORTS = 'UnityReports: OK';
    const HEADER_CLAROBI = 'ClaroBI: OK';

    protected $rest_base = 'product/get-image';

    protected $id = 0;
    protected $width = 0;

    /**
     * Clarobi_Product_Image constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action('rest_api_init', array($this, 'clarobi_image_route'));
    }

    /**
     * Image API route definition.
     */
    public function clarobi_image_route()
    {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/id/(?P<id>\\d+)' . '/w/(?P<w>\\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'image_api_callback'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Display product main image based on product_id.
     * Resize the image based on w param to: w:w (1:1).
     *
     * Return error if no image was found or unknown type.
     *
     * @param WP_REST_Request $request
     */
    public function image_api_callback($request)
    {
        $this->id = $request['id'];
        $this->width = $request['w'];

        try {
            // Get product
            $product = wc_get_product($this->id);
            // If product with id exists
            if (!$product) {
                throw new Exception(self::ERR . 'Cannot load product');
            }

            /**
             * Array containing:
             * 0 => image full path
             * 1 => width
             * 2 => height
             * 3 => is_intermediate (true/false)
             *
             * Set width and height specifying in array in this order.
             *
             * @return array|false Above array structure or false, if no image is available.
             */
            $image = wp_get_attachment_image_src(
                get_post_thumbnail_id($product->get_id()),
                array($this->width, $this->width)
            );
            if (!$image) {
                throw new Exception(self::ERR . 'No image found for this product!');
            }
            $image_path = $image[0];
            // Content
            $content = file_get_contents($image_path);
            if (!$content) {
                throw new Exception(self::ERR . 'Could not load image');
            }

            $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
            switch ($ext) {
                case 'gif':
                    $type = 'image/gif';
                    break;
                case 'jpg':
                case 'jpeg':
                    $type = 'image/jpeg';
                    break;
                case 'png':
                    $type = 'image/png';
                    break;
                default:
                    $type = 'unknown';
                    break;
            }

            if ($type == 'unknown') {
                throw new Exception(self::ERR . 'Unknown type!');
            }

            header('Content-Type:' . $type);
            header(self::HEADER_UNITYREPORTS);
            header(self::HEADER_CLAROBI);
            echo $content;

            die();
        } catch (Exception $exception) {
            Clarobi_Logger::errorLog($exception->getMessage(), __METHOD__);

            die($exception->getMessage());
        }
    }
}

new Clarobi_Product_Image();
