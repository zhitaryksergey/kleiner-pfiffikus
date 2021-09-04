<?php
if(isset($_GET['update'])) {
    add_action('init', function () {
        global $wpdb;

        $wpdb->query(
            "SELECT 
            ID 
         FROM 
            $wpdb->posts
         WHERE
            post_type='product'
     ");

        if(!empty($wpdb->last_result)) {
            foreach ($wpdb->last_result as $item) {
                if(!add_metadata('post',$item->ID,'_sale_price_label','old-price',true)) {
                    update_post_meta($item->ID,'_sale_price_label','old-price');
                }
                if(!add_metadata('post',$item->ID,'_sale_price_regular_label','new-price',true)) {
                    update_post_meta($item->ID,'_sale_price_regular_label','new-price');
                }
            }
        }
    });
}
