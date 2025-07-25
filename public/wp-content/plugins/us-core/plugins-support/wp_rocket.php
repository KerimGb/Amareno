<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

if ( ! function_exists( 'rocket_init' ) ) {
	return FALSE;
}

if ( ! function_exists( 'us_exclude_delayed_assets' ) ) {
	/**
	 * Exclude theme assets from "Delay JavaScript execution"
	 */
	add_filter( 'rocket_delay_js_exclusions', 'us_exclude_delayed_assets' );
	function us_exclude_delayed_assets( $excluded ) {
		$exclude = array(
			'/jquery-?[0-9.](.*)(.min|.slim|.slim.min)?.js',
			'maps.googleapis.com',
			'us_add_no_touch',
			'us-core-js',
		);

		if ( us_get_option( 'optimize_assets' ) ) {
			$exclude[] = us_get_asset_file( 'js', TRUE );
		} else {
			$exclude[] = 'us.core.min.js';
		}

		return array_merge( $excluded, $exclude );
	}
}

/**
 * Return default onclick values if "Delay JavaScript execution" is enabled
 * for correct work of the Theme Elements
 */
if ( function_exists( 'get_rocket_option' ) AND get_rocket_option( 'delay_js' ) === 1 ) {
	add_action( 'wp_enqueue_scripts', 'us_restore_onclick_for_wp_rocket' );
	function us_restore_onclick_for_wp_rocket() {
		$return_onclick = "
			! function() {
				Object.defineProperty( HTMLElement.prototype, 'onclick', {
					get() {
						if ( this.dataset['rocketOnclick'] ) {
							return new Function( this.dataset['rocketOnclick'] );
						}
						return this.onclick();
					},
				});
			}();
		";
		wp_add_inline_script( 'us-core', $return_onclick, 'before' );
	}
}
