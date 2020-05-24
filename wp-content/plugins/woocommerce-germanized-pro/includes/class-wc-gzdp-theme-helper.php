<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

use \Vendidero\Germanized\Shopmarks;

class WC_GZDP_Theme_Helper {

	protected static $_instance = null;

	public $themes = array();

	public $theme;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->themes = array(
			'virtue', 
			'flatsome',
			'enfold',
			'storefront',
			'shopkeeper',
            'astra'
		);

		$current = wp_get_theme();

		if ( in_array( $current->get_template(), $this->themes ) ) {
			$this->load_theme( $current->get_template() );
        }

		add_action( 'after_switch_theme', array( $this, 'refresh_shopmark_options' ), 10 );
	}

	/**
	 * After switching theme: Loop through all shopmarks and make sure that custom-theme-hooks
	 * are removed and default hooks are loaded instead.
	 */
	public function refresh_shopmark_options() {
		foreach( Shopmarks::get_locations() as $location => $location_data ) {
			$shopmarks = Shopmarks::get( $location );

			foreach( $shopmarks as $shopmark ) {
				$filter = $shopmark->get_filter();

				update_option( $shopmark->get_option_name( 'filter' ), $filter );
			}
		}
	}

	public function load_theme( $template ) {
		if ( ! in_array( $template, $this->themes ) ) {
			return false;
        }

		$classname = 'WC_GZDP_Theme_' . str_replace( '-', '_', ucfirst( sanitize_title( $template ) ) );

		if ( class_exists( $classname ) ) {
			$this->theme = new $classname( $template );
        }
	}
}

return WC_GZDP_Theme_Helper::instance();