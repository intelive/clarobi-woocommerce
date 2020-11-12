<?php

/**
 * Contains different helping functions.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Contains different helping functions.
 *
 * This class defines util functions used in this plugin.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Utils
{
    /**
     * Get plugin configurations from db - clarobi_configurations.
     *
     * @return array
     * @deprecated Not used since configurations are set as options.
     */
    public static function get_clarobi_configurations_from_db()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'clarobi_configurations';

        $results = $wpdb->get_results(
            "SELECT `config_name`, `config_value` FROM $table_name 
                    WHERE `config_name` IN ('CLAROBI_API_KEY', 'CLAROBI_API_SECRET', 'CLAROBI_LICENSE_KEY')"
        );

        $configs = [];
        if (isset($results)) {
            foreach ($results as $result) {
                $configs[$result->config_name] = $result->config_value;
            }
        }

        return $configs;
    }

    /**
     * Return array of configurations set using wp-options in plugin setting page.
     *
     * @return array
     */
    public static function get_clarobi_configurations()
    {
        $options = get_option('clarobi_options');

        return [
            'CLAROBI_LICENSE_KEY' => ($options['license'] ? $options['license'] : ''),
            'CLAROBI_API_KEY' => ($options['api_key'] ? $options['api_key'] : ''),
            'CLAROBI_API_SECRET' => ($options['api_secret'] ? $options['api_secret'] : '')
        ];
    }

    /**
     * Prepare query args based on entity.
     *
     * @param string $entity
     * @return array
     */
    public static function prepare_query_args($entity = '')
    {
        $prepared_args = [];
        $prepared_args['orderby'] = 'ID';
        $prepared_args['order'] = 'ASC';

        switch ($entity) {
            case 'customer':
                $prepared_args['role'] = 'customer';
                $prepared_args['number'] = 50;
                break;
            case 'product':
                $prepared_args['limit'] = 50;
                break;
            case 'order':
                $prepared_args['type'] = 'shop_order';
                $prepared_args['posts_per_page'] = 50;
                break;
            case 'invoice':
                $prepared_args['type'] = 'shop_order';
                $prepared_args['status'] = 'completed';
                $prepared_args['posts_per_page'] = 50;
                break;
            case 'cart':
                $prepared_args['type'] = '';
                break;
            case 'creditmemo':
                $prepared_args['type'] = 'shop_order_refund';
                $prepared_args['posts_per_page'] = 50;
                break;
            case 'stock':
                $prepared_args['limit'] = -1;
                break;
            default:
                return [];
        }

        return $prepared_args;
    }
}