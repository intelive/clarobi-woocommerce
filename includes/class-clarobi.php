<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi
{
    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;
    /**
     * The current version of the plugin.
     */
    public $version;
    /**
     * URL dir for plugin.
     */
    public $url;
    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Main Clarobi Instance. Ensures only one instance of the Clarobi is loaded or can be loaded.
     *
     * @return Clarobi|null - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function __construct()
    {
        if (defined('CLAROBI_VERSION')) {
            $this->version = CLAROBI_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'clarobi';
        $this->url = plugin_dir_url(__FILE__);

        $this->load_dependencies();
        $this->define_admin_hooks();

        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     * - Clarobi_Admin. Defines all hooks for the admin area.
     * - Clarobi_Sql_Create. Defines all the tables.
     * - Clarobi_Logger. Logs all the errors in this plugin.
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-clarobi-admin.php';

        /**
         * The class responsible for creating all the plugin tables.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-clarobi-sql-create.php';

        /**
         * The class responsible for logging all the plugin error in a specified file.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-clarobi-logger.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Clarobi_Admin($this->get_plugin_name(), $this->get_version());

        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
    }

    /**
     * Start plugin if WooCommerce is loaded.
     */
    public function init(){
        if (class_exists('WooCommerce')) {
            // Activate notice (shown once)
            add_action('admin_notices', array($this, 'activate_notice'));

            //  Require files for the plugin if WooCommerce is installed.
            require_once 'api/class-clarobi-api.php';
            require_once 'api/class-clarobi-utils.php';
            require_once 'api/class-clarobi-encoder.php';
            require_once 'api/class-clarobi-mapper.php';
        } else {
            add_action('admin_notices', array($this, 'no_wc'));
        }

        // Set Clarobi show activation notice option to true if it isn't already false (only first time)
        if (get_option('clarobi_show_activation_notice', true)) {
            update_option('clarobi_show_activation_notice', true);
        }
    }

    /**
     * Plugin WooCommerce not found notice.
     */
    public function no_wc()
    {
        echo '<div class="notice notice-error"><p>'.sprintf(__('Clarobi requires %s to be installed and active.', 'clarobi'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>').'</p></div>';
    }

    /**
     * Activate notice (if we should).
     */
    public function activate_notice()
    {
        if (get_option('clarobi_show_activation_notice', false)) {
            echo '<div class="notice notice-success"><p>'.sprintf(__('The ClaroBI is active! Go back to %s to complete the connection.', 'clarobi'), '<a href="https://clarobi.com/" target="_blank">ClaroBI</a>').'</p></div>';

            // Disable notice option
            update_option('clarobi_show_activation_notice', false);
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
