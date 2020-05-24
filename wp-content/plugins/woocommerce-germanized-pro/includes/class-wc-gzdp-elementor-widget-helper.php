<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;

class WC_GZDP_Elementor_Widget_Helper {

    public static function register_controls( $widget ) {
        $widget->add_responsive_control(
            'text_align',
            [
                'label' => __( 'Alignment', 'woocommerce-germanized-pro' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __( 'Left', 'woocommerce-germanized-pro' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'woocommerce-germanized-pro' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'woocommerce-germanized-pro' ),
                        'icon' => 'fa fa-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}' => 'text-align: {{VALUE}}',
                ],
            ]
        );

        $widget->add_control(
            'notice_color',
            [
                'label' => __( 'Color', 'woocommerce-germanized-pro' ),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_1,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => 'color: {{VALUE}}',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'typography',
                'scheme'   => Scheme_Typography::TYPOGRAPHY_1,
                'selector' => '{{WRAPPER}}',
            ]
        );
    }

    public static function render( $product, $widget ) {
        echo do_shortcode( '[gzd_product_' . $widget->get_postfix() . ']' );
    }
}