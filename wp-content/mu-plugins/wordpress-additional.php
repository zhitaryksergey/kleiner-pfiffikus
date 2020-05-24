<?php
/**
 * Wordpress additional functionally
 *
 * @since 1.0
 *
 * @package ircbio\wordpress_additional
 */

/*
Plugin Name: Wordpress additional functionally
Description: Wordpress additional functionally (additional sort and other)
Version: 1.0.0
Author: kleiner-pfiffikus.de
*/

namespace kleiner\wordpress_additional;


if (!defined('ABSPATH')) {
    exit;
}

add_action( 'woocommerce_product_query', __NAMESPACE__ . '\product_query' );
function product_query( $q ){
    add_filter('posts_clauses', __NAMESPACE__ . '\order_by_stock_status', PHP_INT_MAX);
}

function order_by_stock_status($posts_clauses)
{
    global $wpdb;
    $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
    $posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
    $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];

    return $posts_clauses;
}

add_filter( 'woocommerce_shortcode_products_query', function( $query_args, $atts, $loop_name ) {
    $query_args['meta_query'] = array( array(
        'key'     => '_stock_status',
        'value'   => 'outofstock',
        'compare' => 'NOT LIKE',
    ) );

    return $query_args;
}, 10, 3);