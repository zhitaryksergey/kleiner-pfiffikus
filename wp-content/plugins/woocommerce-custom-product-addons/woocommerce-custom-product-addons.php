<?php

/**
 * Plugin Name: WooCommerce Custom Product Add-Ons
 * Plugin URI: http://www.rightpress.net/woocommerce-custom-product-addons
 * Description: Create custom add-ons for your WooCommerce products
 * Author: RightPress
 * Author URI: http://www.rightpress.net
 *
 * Text Domain: rp_wccf
 * Domain Path: /languages
 *
 * Version: 2.3.4
 *
 * Requires at least: 4.0
 * Tested up to: 5.4
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.3
 *
 * @package WooCommerce Custom Fields
 * @category Core
 * @author RightPress
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load plugin
 *
 * @return void
 */
function rp_wccf_custom_product_addons_load()
{

    // Define Constants
    define('WCCF_PLUGIN_KEY', 'woocommerce-custom-product-addons');
    define('WCCF_PLUGIN_NAME', 'WooCommerce Custom Product Add-Ons');
    define('WCCF_PLUGIN_PUBLIC_PREFIX', 'wccf_');
    define('WCCF_PLUGIN_PRIVATE_PREFIX', 'wccf_');
    define('WCCF_PLUGIN_PATH', plugin_dir_path(__FILE__));
    define('WCCF_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));
    define('WCCF_SUPPORT_PHP', '5.3');
    define('WCCF_SUPPORT_WP', '4.0');
    define('WCCF_SUPPORT_WC', '3.0');
    define('WCCF_VERSION', '2.3.4');

    // Set flag
    define('RP_WCCF_CUSTOM_PRODUCT_ADDONS_LOADED', true);

    // Load main plugin class
    require_once 'wccf.class.php';

    // Initialize automatic updates
    require_once(WCCF_PLUGIN_PATH . 'rightpress-updates/rightpress-updates.class.php');
    RightPress_Updates_24025527::init(__FILE__, WCCF_VERSION);
}

// Note: There are three related plugins available - WooCommerce Custom Fields,
// WooCommerce Custom Product Add-Ons and WooCommerce Custom Checkout Fields.
// They share exactly the same classes, settings, prefixes etc. To avoid any conflicts,
// only one instance of any of the three plugins could be loaded at once.

// State presence of current plugin
define('RP_WCCF_CUSTOM_PRODUCT_ADDONS_LOADING', true);

// Load this plugin if conflicting related plugins are not found on the system
add_action('plugins_loaded', 'rp_wccf_custom_product_addons_load_single_plugin', 0);

/**
 * Load this plugin if conflicting related plugins are not found on the system
 *
 * @return void
 */
function rp_wccf_custom_product_addons_load_single_plugin()
{

    // Check if conflicting plugins can be found on the system
    $custom_fields_found            = defined('WCCF_VERSION');
    $custom_checkout_fields_found   = defined('RP_WCCF_CUSTOM_CHECKOUT_FIELDS_LOADING') && RP_WCCF_CUSTOM_CHECKOUT_FIELDS_LOADING;

    // Do not load if WooCommerce Custom Fields is present
    if ($custom_fields_found) {

        // Display plugin conflict admin notice if WooCommerce Custom Checkout Fields is not going to display a notice for both plugins
        if (!$custom_checkout_fields_found) {
            add_action('admin_notices', 'rp_wccf_custom_product_addons_display_plugin_conflict_notice');
        }

        // Do not load
        return;
    }

    // Load plugin
    rp_wccf_custom_product_addons_load();
}

/**
 * Display plugin conflict admin notice
 *
 * @return void
 */
function rp_wccf_custom_product_addons_display_plugin_conflict_notice()
{

    // Format message
    $message = '<strong>WooCommerce Custom Product Add-Ons</strong> plugin is incompatible with <strong>WooCommerce Custom Fields</strong> plugin. Please use <strong>WooCommerce Custom Fields</strong> and disable the other one since <strong>WooCommerce Custom Fields</strong> covers functionality of the other plugin in full.';

    // Print notice
    printf('<div class="update-nag" style="display: block; border-left-color: #dc3232;"><h3 style="margin-top: 0.3em; margin-bottom: 0.6em;">Heads Up!</h3>' . $message . '<p><a href="%s">Contact RightPress Support</a></p></div>', 'http://url.rightpress.net/new-support-ticket');
}
