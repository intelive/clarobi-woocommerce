<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Clarobi
 * @subpackage Clarobi/admin
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Admin
{
    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Clarobi_Admin constructor.
     *
     * @param $plugin_name
     * @param $version
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page.
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'ClaroBI',
            'manage_options',
            'clarobi',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback.
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option(OPT_CLAROBI_OPTIONS); // option_name
        ?>
        <div class="wrap">
            <h1>Clarobi Settings</h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('clarobi_option_group');
                do_settings_sections('clarobi-setting-admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings.
     */
    public function page_init()
    {
        register_setting(
            'clarobi_option_group', // Option group
            OPT_CLAROBI_OPTIONS, // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Configurations', // Title
            array($this, 'print_section_info'), // Callback
            'clarobi-setting-admin' // Page
        );

        add_settings_field(
            'license',
            'License',
            array($this, 'license_callback'),
            'clarobi-setting-admin',
            'setting_section_id'
        );

        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'clarobi-setting-admin',
            'setting_section_id'
        );

        add_settings_field(
            'api_secret',
            'API Secret',
            array($this, 'api_secret_callback'),
            'clarobi-setting-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize($input)
    {
        $new_input = array();

        if (isset($input['license']))
            $new_input['license'] = sanitize_text_field($input['license']);

        if (isset($input['api_key']))
            $new_input['api_key'] = sanitize_text_field($input['api_key']);

        if (isset($input['api_secret']))
            $new_input['api_secret'] = sanitize_text_field($input['api_secret']);

        return $new_input;
    }

    /**
     * Print the Section text.
     */
    public function print_section_info()
    {
        print 'Enter your Clarobi credentials below:';
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function license_callback()
    {
        printf(
            '<input type="text" id="license" name="clarobi_options[license]" value="%s" 
                        class="clarobi-settings-input"
                    />',
            isset($this->options['license']) ? esc_attr($this->options['license']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="clarobi_options[api_key]" value="%s" 
                        class="clarobi-settings-input"
                    />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function api_secret_callback()
    {
        printf(
            '<input type="text" id="api_secret" name="clarobi_options[api_secret]" value="%s" 
                        class="clarobi-settings-input"
                    />',
            isset($this->options['api_secret']) ? esc_attr($this->options['api_secret']) : ''
        );
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/clarobi-admin.css', array(), $this->version, 'all');
    }
}
