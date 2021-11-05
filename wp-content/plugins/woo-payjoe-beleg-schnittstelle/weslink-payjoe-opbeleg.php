<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://weslink.de
 * @since             1.0.0
 * @package           Weslink_Payjoe_Opbeleg
 *
 * @wordpress-plugin
 * Plugin Name:       Woo PayJoe Beleg Schnittstelle
 * Description:       Plugin zum Upload von Rechnungen zu PayJoe
 * Version:           1.6.2
 * Author:            WESLINK - Let's Web | Site Shop App
 * Author URI:        https://weslink.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       weslink-payjoe-opbeleg
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // Put your plugin code here
}

add_action('plugins_loaded', 'weslink_payjoe_load_textdomain');
function weslink_payjoe_load_textdomain()
{
    load_plugin_textdomain('weslink-payjoe-opbeleg', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Add settings link on plugin page
function weslink_payjoe_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=weslink-payjoe-opbeleg">' . __("Settings", "weslink-payjoe-opbeleg") . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

$plugin_slink = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin_slink", 'weslink_payjoe_plugin_settings_link');


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-weslink-payjoe-opbeleg-activator.php
 */
function weslink_payjoe_activate_opbeleg()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-weslink-payjoe-opbeleg-activator.php';
    Weslink_Payjoe_Opbeleg_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-weslink-payjoe-opbeleg-deactivator.php
 */
function weslink_payjoe_deactivate_opbeleg()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-weslink-payjoe-opbeleg-deactivator.php';
    Weslink_Payjoe_Opbeleg_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'weslink_payjoe_activate_opbeleg');
register_deactivation_hook(__FILE__, 'weslink_payjoe_deactivate_opbeleg');

/**
 * Handle a custom '_payjoe_invoice_number' query var to get orders with the '_payjoe_invoice_number' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function handle_payjoe_invoice_query_var( $query, $query_vars ) {
    if ( ! empty( $query_vars['_payjoe_invoice_number'] ) ) {
        $query_vars['_payjoe_invoice_number']['key'] = '_payjoe_invoice_number';
        $query['meta_query'][] = $query_vars['_payjoe_invoice_number'];
    }

    if ( ! empty( $query_vars['_payjoe_status'] ) ) {
        $query_vars['_payjoe_status']['key'] = '_payjoe_status';
        $query['meta_query'][] = $query_vars['_payjoe_status'];
    }

    return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_payjoe_invoice_query_var', 10, 2 );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-weslink-payjoe-opbeleg.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

function run_weslink_payjoe_opbeleg()
{

    $plugin = new Weslink_Payjoe_Opbeleg();
    $plugin->run();

}

run_weslink_payjoe_opbeleg();




