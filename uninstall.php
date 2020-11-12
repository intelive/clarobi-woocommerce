<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * The class responsible for dropping all the plugin tables.
 */
require_once('includes/class-clarobi-sql-drop.php');

Clarobi_Sql_Drop::clarobi_drop_tables();

/**
 * Delete all options set on activation.
 */
$options = [
    'OPT_CLAROBI_OPTIONS',
    'OPT_CLAROBI_SHOW_ACTIVATION_NOTICE',
    'OPT_CLAROBI_P_C_DB_VERSION',
    'OPT_CLAROBI_CONFIG_DB_VERSION'
];
foreach ($options as $option) {
    delete_option($option);
}
