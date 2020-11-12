<?php

/**
 * Maps different data.
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
 * Maps different data.
 *
 * This class is responsible for mapping data, by excluding redundant fields.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Mapper
{
    const IGNORED_KEYS = array(
        'customer' => array(
            'date_created_gmt', 'date_modified_gmt', 'username', 'password', 'billing', 'shipping',
            'is_paying_customer', 'avatar_url', 'meta_data', '_links'
        ),
        'product' => array(
            'slug', 'permalink', 'date_created_gmt', 'date_modified_gmt', 'featured', 'catalog_visibility',
            'description', 'short_description', 'date_on_sale_from', 'date_on_sale_from_gmt', 'date_on_sale_to',
            'date_on_sale_to_gmt', 'price_html', 'on_sale', 'purchasable', 'total_sales', 'virtual', 'downloadable',
            'downloads', 'download_limit', 'download_expiry', 'external_url', 'button_text', 'tax_status', 'tax_class',
            'manage_stock', 'stock_status', 'backorders', 'backorders_allowed', 'backordered', 'sold_individually',
            'weight', 'dimensions', 'shipping_required', 'shipping_taxable', 'shipping_class', 'shipping_class_id',
            'reviews_allowed', 'average_rating', 'rating_count', 'related_ids', 'upsell_ids', 'cross_sell_ids',
            'purchase_note', 'categories', 'tags', 'images', 'default_attributes', 'variations', 'grouped_products',
            'menu_order', 'meta_data', '_links'
        ),
        'order' => array(
            'version', 'date_created_gmt', 'date_modified', 'date_modified_gmt', 'prices_include_tax',
            'customer_ip_address', 'customer_user_agent', 'customer_note', 'transaction_id', 'date_paid',
            'date_paid_gmt', 'date_completed', 'date_completed_gmt', 'cart_hash', 'meta_data', 'tax_lines',
            'fee_lines', 'refunds', 'set_paid', '_links'
        ),
        'invoice' => array(
            'created_via', 'version', 'status', 'date_created', 'date_created_gmt', 'date_modified',
            'date_modified_gmt', 'discount_tax', 'cart_tax', 'prices_include_tax', 'customer_id',
            'customer_ip_address', 'customer_user_agent', 'customer_note', 'billing', 'shipping', 'payment_method',
            'payment_method_title', 'transaction_id', 'date_paid_gmt', 'date_completed_gmt', 'cart_hash', 'meta_data',
            'tax_lines', 'fee_lines', 'coupon_lines', 'refunds', 'set_paid', '_links'
        ),
        'creditmemo' => array('date_created_gmt', 'reason', 'refunded_by', 'meta_data', '_links'),
        'abandonedcart' => array(
            'cart' => array(
                'key', 'data_hash', 'line_tax_data', 'variation', 'line_subtotal', 'line_subtotal_tax', 'line_total',
                'line_tax'
            ),
            'cart_totals' => array(
                'shipping_taxes', 'cart_contents_taxes', 'fee_taxes', 'subtotal', 'subtotal_tax', 'shipping_total',
                'shipping_tax', 'discount_total', 'discount_tax', 'cart_contents_total', 'cart_contents_tax',
                'fee_total', 'fee_tax', 'total', 'total_tax'
            ),
            'customer' => array(
                'date_modified', 'postcode', 'city', 'address_1', 'address', 'address_2', 'state', 'country',
                'shipping_postcode', 'shipping_city', 'shipping_address_1', 'shipping_address', 'shipping_address_2',
                'shipping_state', 'shipping_country', 'is_vat_exempt', 'calculated_shipping', 'first_name', 'last_name',
                'company', 'phone', 'shipping_first_name', 'shipping_last_name', 'shipping_company',
            )
        )
    );

    const IGNORE_KEYS_IN_LINE_ITEMS = array(
        'order' => array('meta_data', 'taxes'),
        'invoice' => array(
            'meta_data', 'taxes', 'id', 'name', 'variation_id', 'tax_class', 'subtotal_tax', 'total', 'total_tax',
            'sku', 'price'
        ),
        'creditmemo' => array(
            'meta_data', 'taxes', 'id', 'name', 'sku', 'variation_id', 'tax_class', 'subtotal_tax'
        ),
        'product' => array(), // Do not delete. Contains array and will give error if not set to empty array.
    );

    /**
     * Ignore keys from entity data for entities: customer, product, order, invoice and creditmemo.
     *
     * @param string $entity Entity name
     * @param array $data Entity data
     * @return array
     */
    public static function ignore_entity_keys($entity, $data)
    {
        $return = [];
        foreach ($data as $key => $value) {
            // Skip if this is an ignored keys
            if (in_array($key, self::IGNORED_KEYS[$entity])) {
                continue;
            }
            $return[$key] = $value;

            if (is_array($return[$key]) && count($return[$key])) {
                // Remove from each line_items lines fields that contain to mush data
                foreach ($return[$key] as $key2 => $line) {
                    foreach (self::IGNORE_KEYS_IN_LINE_ITEMS[$entity] as $ignore_line_key) {
                        if (isset($return[$key][$key2][$ignore_line_key])) {
                            unset($return[$key][$key2][$ignore_line_key]);
                        }
                    }
                }
            }
        }
        switch ($entity) {
            case 'customer':
                // Add bill and ship country without other fields from billing or shipping default fields
                $return['bill_country'] = $data['billing']['country'];
                $return['ship_country'] = $data['shipping']['country'];
                break;
        }
        return $return;
    }

    /**
     * Ignore keys and map cart array from session to abandonedcart structure.
     *
     * @param array $data
     * @return array
     */
    public static function session_cart_mapper($data)
    {
        $mapped_data = [
            'store_id' => get_main_site_id()
        ];
        $items = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'cart' :
                    foreach ($value as $cartKey => $cart_product) {
                        $product = new WC_Product($cart_product['product_id']);
                        $item = [
                            'name' => $product->get_name(),
                            'sku' => $product->get_sku(),
                            'price' => $product->get_price()
                        ];

                        foreach ($cart_product as $product_key => $product_value) {
                            if (in_array($product_key, self::IGNORED_KEYS['abandonedcart'][$key])) {
                                continue;
                            }
                            $item[$product_key] = $product_value;
                        }
                        $items[] = $item;
                    }
                    break;
                default:
                    foreach ($value as $key2 => $value2) {
                        if (in_array($key2, self::IGNORED_KEYS['abandonedcart'][$key])) {
                            continue;
                        }
                        $mapped_data[$key2] = $value2;
                    }
            }
        }
        // Get customer id from email
        $customer = WP_User::get_data_by('email', $mapped_data['email']);
        if ($customer) {
            $mapped_data['customer_id'] = $customer->ID;
        } else {
            $mapped_data['customer_id'] = null;
        }

        $mapped_data['items'] = $items;

        return $mapped_data;
    }


    /**
     * Set product_id to variation_id if not 0.
     * Product is 'parent' or type 'simple' if variation is 0.
     *
     * @param array $item
     * @return array
     */
    public static function set_parent_details(array $item)
    {
        $product = new WC_Product($item['product_id']);

        $item['type'] = $product->get_type();
        $item['parent_id'] = null;
        $item['parent_sku'] = null;

        if ($item['variation_id']) {
            $variation_id = $item['variation_id'];
            $product_id = $item['product_id'];

            $product_parent = new WC_Product($product_id);
            $item['product_id'] = $variation_id;
            $item['parent_id'] = $product_id;
            $item['parent_sku'] = $product_parent->get_sku();
        }

        return $item;
    }
}