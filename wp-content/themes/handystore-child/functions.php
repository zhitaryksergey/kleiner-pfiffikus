<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

include 'lib/cron.php';

function theme_enqueue_styles()
{
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/cows.css', [], filemtime(get_stylesheet_directory(). '/cows.css'));
}

add_action('wp_enqueue_scripts', 'theme_enqueue_styles');

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri)
    {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css')) {
            $uri = get_template_directory_uri() . '/rtl.css';
        }
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('chld_thm_cfg_parent_css')):
    function chld_thm_cfg_parent_css()
    {
        wp_enqueue_style('chld_thm_cfg_parent', trailingslashit(get_template_directory_uri()) . 'style.css', ['pt-grid', 'pt-additional-styles', 'pt-icons']);
    }
endif;
add_action('wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10);

// END ENQUEUE PARENT ACTION

function filter_term_sort_by_latest_post_clauses($pieces, $taxonomies, $args)
{
    global $wpdb;
    if (in_array('product_cat', $taxonomies) && $args['orderby'] == 'category_order') {
        $pieces['orderby'] = "ORDER BY t.term_id";
        $pieces['order'] = "ASC"; // DESC or ASC
    }
    return $pieces;
}

add_filter('terms_clauses', 'filter_term_sort_by_latest_post_clauses', 10, 3);

//function customise_product_brand_slug ( $tax ) {
//    $tax['rewrite']['slug'] = 'hersteller';
//    return $tax;
//}
//add_filter( 'register_taxonomy_product_brand', 'customise_product_brand_slug' );

add_filter('woocommerce_display_product_attributes', 'mywoocommerce_display_product_attributes',99,2);
function mywoocommerce_display_product_attributes($product_attributes, $product)
{
    foreach ($product_attributes as $k => $v) {
        if (strpos($v['value'], '>No<')) {
            unset($product_attributes[$k]);
        }
    }
    return $product_attributes;
}

add_filter('register_taxonomy_product_brand', 'myregister_taxonomy_product_brand');
function myregister_taxonomy_product_brand($array)
{
    $array['labels']['singular_name'] = 'Marke';
    return $array;
}

function show_only_free_shipping_if_available($rates)
{
    $free = [];
    foreach ($rates as $rate_id => $rate) {
        if ('free_shipping' === $rate->method_id) {
            $free[$rate_id] = $rate;
            break;
        }
    }
    return !empty($free) ? $free : $rates;
}

add_filter('woocommerce_package_rates', 'show_only_free_shipping_if_available', 90);

add_action('woocommerce_before_main_content', 'mywoocommerce_breadcrumb', 6);

function mywoocommerce_breadcrumb()
{
    return strtr(WPSEO_Breadcrumbs::breadcrumb('<div class="col-md-8 col-sm-6 col-xs-12">', '</div>'), ['&' => '/']);
}

/** -------------------------------------------------------------------  */

add_filter('manage_edit-product_columns', 'add_columns_to_product_grid', 10, 1);

const BACKEND_PRODUCT_GRID_FIELD_SORTORDER = [
    'cb',
    'thumb',
    'name',
    'foerderung',
    'material',
    'warnhinweise',
    'alter',
    'sku',
    'is_in_stock',
    'price',
    'product_cat',
//        'product_tag',
    'featured',
    'product_type',
    'date',
    'stats',
    'likes'
];

/**
 * Registers new columns for the backend products grid of Woocommerce.
 * Additionally it sorts the fields after
 * self::BACKEND_PRODUCT_GRID_FIELD_SORTORDER. Fields not included in
 * self::BACKEND_PRODUCT_GRID_FIELD_SORTORDER will be attached to the end of
 * the array.
 *
 * @param array $aColumns - the current Woocommerce backend grid columns
 * @return array - the extended backend grid columns array
 */
function add_columns_to_product_grid($aColumns)
{
    $aColumns['foerderung'] = __('Förderung', 'kleiner');
    $aColumns['material'] = __('Material', 'kleiner');
    $aColumns['warnhinweise'] = __('Warnhinweise', 'kleiner');
    $aColumns['alter'] = __('Alter', 'kleiner');
    $aReturn = [];
    foreach (BACKEND_PRODUCT_GRID_FIELD_SORTORDER as $sKey) {
        if (isset($aColumns[$sKey])) {
            $aReturn[$sKey] = $aColumns[$sKey];
        }
    }

    /**
     * search additional unknown fields and attache them to the end
     */
    foreach ($aColumns as $sKey => $sField) {
        if (!isset($aReturn[$sKey])) {
            if ($sKey != 'product_tag') {
                $aReturn[$sKey] = $sField;
            }
        }
    }

    return $aReturn;
}

add_action('manage_product_posts_custom_column', 'add_columns_value_to_product_grid', 10, 2);

/**
 * Adds the respective value of the custom attribute to each row of the
 * column
 *
 * @param string $sAttributeCode
 * @param int $iPostId
 */
function add_columns_value_to_product_grid(
    $sAttributeCode,
    $iPostId
) {
    if ($sAttributeCode == 'foerderung') {
        $oProduct = new WC_Product($iPostId);
        $sSizeText = $oProduct->get_attribute('foerderung');
        echo esc_attr($sSizeText);
    }
    if ($sAttributeCode == 'material') {
        $oProduct = new WC_Product($iPostId);
        $sSizeText = $oProduct->get_attribute('material');
        echo esc_attr($sSizeText);
    }
    if ($sAttributeCode == 'warnhinweise') {
        $oProduct = new WC_Product($iPostId);
        $sSizeText = $oProduct->get_attribute('warnhinweise');
        echo esc_attr($sSizeText);
    }
    if ($sAttributeCode == 'alter') {
        $oProduct = new WC_Product($iPostId);
        $sSizeText = $oProduct->get_attribute('alter');
        echo esc_attr($sSizeText);
    }
}

add_filter('pt_more', function () {
    return 'Weiterlesen...';
}, 999);

//    add_filter( 'woocommerce_germanized_hide_shipping_costs_text', function ($boolean) {
//        if(is_single()) {
//            return $boolean;
//        }
//        return false;
//    });

//<editor-fold desc="Javascript hinzufügen">
/* Enqueue Javascript */
function child_theme_scripts()
{
    wp_enqueue_script('functions', get_stylesheet_directory_uri() . '/js/functions.js', array('jquery'), filemtime(get_stylesheet_directory(). '/js/functions.js'), true);
}

add_action('wp_enqueue_scripts', 'child_theme_scripts');
//</editor-fold>

add_filter( 'kirki_output_inline_styles', 'mykirki_output_inline_styles', 9999 );
function mykirki_output_inline_styles($boolean) {
    $boolean = false;
    return $boolean;
}

add_action( 'woocommerce_single_product_summary', 'wc_ninja_add_brand_to_product_page', 19 );
function wc_ninja_add_brand_to_product_page() {
	echo do_shortcode('[product_brand width="64px" height="64px" class="alignright"]');
}

/* Disable XMLRPC */
add_filter( 'xmlrpc_enabled', '__return_false' );

/* Remove XMLRPC, WLW, Generator and ShortLink tags from header */
remove_action('wp_head', 'rsd_link');