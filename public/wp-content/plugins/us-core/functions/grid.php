<?php
/**
 * All methods that apply to Grid, Grid Filter, Grid Order
 *
 */

if ( ! function_exists( 'us_get_grid_url_prefix' ) ) {
	/**
	 * Get URL param for grid_* shortcodes
	 *
	 * @param string $param_name
	 * @return string - If there is no value then the `param_name` will be returned as the default value
	 */
	function us_get_grid_url_prefix( $param_name ) {
		$value = (string) us_get_option( "grid_{$param_name}_url_prefix", $param_name );
		// The checking the parameter and leave only valid characters for the URL
		$value = preg_replace( '/[^a-z\d\_\-]+/', '', us_strtolower( $value ) );

		return ! empty( $value )
			? $value
			: $param_name;
	}
}

if ( ! function_exists( 'us_post_type_is_available' ) ) {
	/**
	 * Check if post type is available for usage in Grid
	 *
	 * @param string|array $post_types
	 * @param array $available_post_types
	 * @return bool
	 */
	function us_post_type_is_available( $post_types, $available_post_types = array() ) {
		if ( empty( $post_types ) OR empty( $available_post_types ) ) {
			return FALSE;
		}

		if ( is_string( $post_types ) ) {
			$post_types = array( $post_types );
		}

		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type, $available_post_types ) ) {
				return TRUE;
			}
		}

		return FALSE;
	}
}

if ( ! function_exists( 'us_grid_query_offset' ) ) {
	/**
	 * Adjust pagination count if offset is set
	 */
	function us_grid_query_offset( &$query ) {
		if ( ! isset( $query->query['_id'] ) OR $query->query['_id'] !== 'us_grid_with_offset' ) {
			return;
		}

		global $us_grid_query_offset;

		// First check if 'posts_per_archive_page' is set
		$posts_per_page = $query->query['posts_per_archive_page'] ?? 0;

		if ( empty( $posts_per_page ) ) {
			$posts_per_page = $query->query['posts_per_page'] ?? get_option( 'posts_per_page' );
		}

		if ( $query->is_paged ) {
			$page_offset = $us_grid_query_offset + ( ( $query->query_vars['paged'] - 1 ) * $posts_per_page );

			// Apply adjust page offset
			$query->set( 'offset', $page_offset );

		} else {
			// This is the first page. Just use the offset...
			$query->set( 'offset', $us_grid_query_offset );
		}

		remove_action( 'pre_get_posts', 'us_grid_query_offset' );
	}
}

if ( ! function_exists( 'us_grid_adjust_offset_pagination' ) ) {
	/**
	 * Adjust pagination count if offset is set
	 */
	function us_grid_adjust_offset_pagination( $found_posts, $query ) {
		if ( ! isset( $query->query['_id'] ) OR $query->query['_id'] !== 'us_grid_with_offset' ) {
			return $found_posts;
		}

		global $us_grid_query_offset;
		remove_filter( 'found_posts', 'us_grid_adjust_offset_pagination' );

		// Reduce WordPress's found_posts count by the offset...
		return $found_posts - $us_grid_query_offset;
	}
}

if ( ! function_exists( 'us_get_available_post_statuses' ) ) {
	/**
	 * Get a list of available statuses to display
	 *
	 * @return array
	 */
	function us_get_available_post_statuses() {
		static $post_statuses = array();
		if ( ! empty( $post_statuses ) ) {
			return (array) $post_statuses;
		}

		$post_statuses = get_post_stati( array( 'publicly_queryable' => TRUE ), /* output */ 'names' );
		$post_statuses = array_keys( $post_statuses );

		// If private posts are available, also add to the list
		if ( is_user_logged_in() AND current_user_can( 'read_private_posts' ) ) {
			$post_statuses[] = 'private';
		}

		// List of additional statuses
		$post_statuses = array_merge(
			$post_statuses, array(
				'inherit',
			)
		);

		$post_statuses = array_unique( $post_statuses );

		return $post_statuses;
	}
}

if ( ! function_exists( 'us_fix_grid_settings' ) ) {
	/**
	 * Make the provided grid settings value consistent and proper
	 *
	 * @param $value array
	 *
	 * @return array
	 */
	function us_fix_grid_settings( $value ) {
		if ( empty( $value ) OR ! is_array( $value ) ) {
			$value = array();
		}
		if ( ! isset( $value['data'] ) OR ! is_array( $value['data'] ) ) {
			$value['data'] = array();
		}

		$options_defaults = array();
		$elements_defaults = array();
		if ( function_exists( 'usof_get_default' ) ) {
			foreach ( us_config( 'grid-settings.options', array() ) as $option_name => $option_group ) {
				foreach ( $option_group as $option_name => $option_field ) {
					$options_defaults[ $option_name ] = usof_get_default( $option_field );
				}
			}

			foreach ( us_config( 'grid-settings.elements', array() ) as $element_name ) {
				$element_settings = us_config( 'elements/' . $element_name );
				$elements_defaults[ $element_name ] = array();
				foreach ( $element_settings['params'] as $param_name => $param_field ) {
					$elements_defaults[ $element_name ][ $param_name ] = usof_get_default( $param_field );
				}
			}
		}

		foreach ( $options_defaults as $option_name => $option_default ) {
			if ( ! isset( $value['default']['options'][ $option_name ] ) ) {
				$value['default']['options'][ $option_name ] = $option_default;
			}
		}
		foreach ( $value['data'] as $element_name => $element_values ) {
			$element_type = strtok( $element_name, ':' );
			if ( ! isset( $elements_defaults[ $element_type ] ) ) {
				continue;
			}
			foreach ( $elements_defaults[ $element_type ] as $param_name => $param_default ) {
				if ( ! isset( $value['data'][ $element_name ][ $param_name ] ) ) {
					$value['data'][ $element_name ][ $param_name ] = $param_default;
				}
			}
		}

		foreach ( array( 'default' ) as $state ) {
			if ( ! isset( $value[ $state ] ) OR ! is_array( $value[ $state ] ) ) {
				$value[ $state ] = array();
			}
			if ( ! isset( $value[ $state ]['layout'] ) OR ! is_array( $value[ $state ]['layout'] ) ) {
				if ( $state != 'default' AND isset( $value['default']['layout'] ) ) {
					$value[ $state ]['layout'] = $value['default']['layout'];
				} else {
					$value[ $state ]['layout'] = array();
				}
			}
			$state_elms = array();
			foreach ( $value[ $state ]['layout'] as $place => $elms ) {
				if ( ! is_array( $elms ) ) {
					$elms = array();
				}
				foreach ( $elms as $index => $elm_id ) {
					if ( ! is_string( $elm_id ) OR strpos( $elm_id, ':' ) == -1 ) {
						unset( $elms[ $index ] );
					} else {
						$state_elms[] = $elm_id;
						if ( ! isset( $value['data'][ $elm_id ] ) ) {
							$value['data'][ $elm_id ] = array();
						}
					}
				}
				$value[ $state ]['layout'][ $place ] = array_values( $elms );
			}
			if ( ! isset( $value[ $state ]['layout']['hidden'] ) OR ! is_array( $value[ $state ]['layout']['hidden'] ) ) {
				$value[ $state ]['layout']['hidden'] = array();
			}
			$value[ $state ]['layout']['hidden'] = array_merge( $value[ $state ]['layout']['hidden'], array_diff( array_keys( $value['data'] ), $state_elms ) );
			// Fixing options
			if ( ! isset( $value[ $state ]['options'] ) OR ! is_array( $value[ $state ]['options'] ) ) {
				$value[ $state ]['options'] = array();
			}
			$value[ $state ]['options'] = array_merge( $options_defaults,
				( $state != 'default' )
					? $value['default']['options']
					: array(), $value[ $state ]['options']
			);
		}

		return $value;
	}
}

if ( ! function_exists( 'us_grid_available_post_types' ) ) {
	/**
	 * Get post types for selection in Grid element
	 *
	 * @param bool $reload used when list of available post types should be reloaded
	 * because data that affects it was changed
	 *
	 * @return array
	 */
	function us_grid_available_post_types( $reload = FALSE, $show_slug = TRUE ) {
		static $available_posts_types = array();

		if ( empty( $available_posts_types ) OR $reload ) {
			$posts_types_params = array(
				'show_in_menu' => TRUE,
			);
			$skip_post_types = array(
				'us_header',
				'us_page_block',
				'us_content_template',
				'us_grid_layout',
				'shop_order',
				'shop_coupon',
			);
			foreach ( get_post_types( $posts_types_params, 'objects' ) as $post_type_name => $post_type ) {
				if ( in_array( $post_type_name, $skip_post_types ) ) {
					continue;
				}
				$_label = $post_type->labels->name;
				if ( $show_slug ) {
					$_label .= ' (' . $post_type_name . ')';
				}
				$available_posts_types[ $post_type_name ] = $_label;
			}
		}

		return apply_filters( 'us_grid_available_post_types', $available_posts_types );
	}
}

if ( ! function_exists( 'us_grid_available_post_types_for_import' ) ) {
	/**
	 * Get post types for selection in Grid element for import
	 * NOTE: Used when filtering imported shortcodes.
	 *
	 * @return array
	 */
	function us_grid_available_post_types_for_import() {
		// These types shoudn't be replaced to posts
		$grid_available_post_types = array(
			'attachment',
			'related',
			'current_query',
			'taxonomy_terms',
			'current_child_terms',
			'product_upsells',
			'product_crosssell',
		);
		// Get post types for selection in Grid element
		foreach ( array_keys( us_grid_available_post_types() ) as $post_type ) {
			if ( wp_count_posts( $post_type )->publish ) {
				$grid_available_post_types[] = $post_type;
			}
		}

		return $grid_available_post_types;
	}
}

if ( ! function_exists( 'us_grid_available_taxonomies' ) ) {
	/**
	 * Get post taxonomies for selection in Grid element
	 *
	 * @return array
	 */
	function us_grid_available_taxonomies() {
		static $available_taxonomies = array();
		if ( ! empty( $available_taxonomies ) ) {
			return (array) $available_taxonomies;
		}

		$available_posts_types = us_grid_available_post_types();

		foreach ( $available_posts_types as $post_type => $name ) {
			$post_taxonomies = array();
			$object_taxonomies = get_object_taxonomies( $post_type, 'objects' );
			foreach ( $object_taxonomies as $tax_object ) {
				if ( $tax_object->public AND $tax_object->show_ui ) {
					$post_taxonomies[] = $tax_object->name;
				}
			}
			if ( is_array( $post_taxonomies ) AND count( $post_taxonomies ) > 0 ) {
				$available_taxonomies[ $post_type ] = array();
				foreach ( $post_taxonomies as $post_taxonomy ) {
					$available_taxonomies[ $post_type ][] = $post_taxonomy;
				}
			}
		}

		return $available_taxonomies;
	}
}

if ( ! function_exists( 'us_get_filter_taxonomies' ) ) {
	/**
	 * Get grid filter params
	 * @param string|array $prefixes
	 * @param string|array $params (example: {prefix}_{param}={values}&...)
	 *
	 * @return array
	 */
	function us_get_filter_taxonomies( $prefixes = array(), $params = '' ) {
		// Parameters to check
		$prefixes = is_array( $prefixes )
			? $prefixes
			: array( $prefixes );

		// The resulting parameters as a string or array
		if ( ! empty( $params ) AND is_string( $params ) ) {
			parse_str( $params, $params );
		} else {
			// Get default params
			$params = $_REQUEST;
		}

		// Get all taxonomies
		$available_taxonomy = array();
		foreach ( array_keys( us_get_taxonomies( FALSE, TRUE, '' ) ) as $tax_name ) {
			$available_taxonomy[ $tax_name ] = 'tax';
		}

		// Add WooCommerce related fields
		$available_taxonomy['_price'] = 'cf';

		// Add fields from "Advanced Custom Fields" plugin
		if ( function_exists( 'acf_get_field' ) ) {
			foreach ( $params as $param_name => $param_value ) {
				if ( ! preg_match( '/(\w+)_(\d+)$/', $param_name, $matches ) ) {
					continue;
				}
				if ( $acf_field = acf_get_field( $matches[/* ACF Field ID */2] ) ) {
					$available_taxonomy[ sprintf( '%s_%s', strtolower( $acf_field['name'] ), $acf_field['ID'] ) ] = 'cf';
				}
			}
		}

		$result = array();
		static $_terms = array();

		// Get slugs from portfolio settings
		$portfolio_slugs = array();
		foreach ( us_get_portfolio_slugs_map() as $default_slug => $option_name ) {
			if (
				$slug = us_get_option( $option_name, $default_slug )
				AND $default_slug !== "us_" . $slug // check a `$slug` from a prefix
				AND $default_slug !== $slug
			) {
				$portfolio_slugs[ $slug ] = $default_slug;
			}
		}

		foreach ( $prefixes as $prefix ) {
			foreach ( $params as $param => $param_values ) {
				$param = strtolower( $param );
				if ( strpos( $param, $prefix ) !== 0 ) {
					continue;
				}

				// Remove prefix and get parameter name
				$param_name = substr( $param, strlen( $prefix . /* separator */ '_' ) );

				// Check the paran_name in the portfolio slugs
				if (
					isset( $portfolio_slugs[ $param_name ] )
					AND $new_param_name = us_arr_path( $portfolio_slugs, $param_name, $param_name )
				) {
					$param_name = $new_param_name;
				}

				if ( ! empty( $available_taxonomy[ $param_name ] ) ) {
					$source_prefix = $available_taxonomy[ $param_name ];
				} else {
					continue;
				}

				// The taxonomy validation
				if ( $source_prefix === 'tax' ) {
					if ( ! isset( $_terms[ $param_name ] ) ) {
						$terms_query = array(
							'taxonomy' => $param_name,
							'hide_empty' => TRUE,
							'update_term_meta_cache' => FALSE,
						);
						foreach ( get_terms( $terms_query ) as $term ) {
							$_terms[ $param_name ][ $term->term_id ] = $term->slug;
						}
					}
					if ( empty( $_terms[ $param_name ] ) OR ! is_string( $param_values ) ) {
						continue;
					}
				}

				// Formation of an array of parameters
				$param_values = explode( ',', $param_values );
				array_map( 'strtolower', $param_values );
				array_map( 'trim', $param_values );
				foreach ( $param_values as $item_value ) {
					if (
						(
							! empty( $_terms[ $param_name ] )
							AND in_array( $item_value, $_terms[ $param_name ] )
						)
						OR is_numeric( $item_value ) // for numeric value ZERO
						OR ! empty( $item_value )
					) {
						$result[ $source_prefix . '|' . $param_name ][] = ( string ) urldecode( $item_value );
					}
				}
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_grid_filter_parse_param' ) ) {
	/**
	 * Parse param for grid filter
	 *
	 * @param string $param_name
	 * @return array
	 */
	function us_grid_filter_parse_param( $param_name ) {
		$result = array();
		if ( strpos( $param_name, '|' ) !== FALSE ) {
			list( $source, $param_name ) = explode( '|', $param_name, 2 );
			$result['source'] = strtolower( $source );
			// The for Advanced Custom Fields
			if (
				$result['source'] === 'cf'
				AND $param_name !== '_price'
				AND preg_match( '/([\w+\-?]+)_(\d+)$/', $param_name, $matches )
			) {
				$result['param_name'] = $matches[1];
				$result['acf_field_id'] = (int) $matches[2];
			} else {
				$result['param_name'] = $param_name;
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_apply_grid_filters' ) ) {
	/**
	 * Apply grid filters to query_args
	 *
	 * @param array $query_args
	 * @param string $us_grid_filter_params
	 */
	function us_apply_grid_filters( &$query_args, $us_grid_filter_params = NULL ) {
		// Skip applying grid filters for grid inside Reusable Block in "no results"
		global $us_is_page_block_in_no_results;
		if ( $us_is_page_block_in_no_results ) {
			return;
		}

		// Get current post id
		$post_id = us_get_current_id();

		$us_grid_filter_atts = array();
		if ( $grid_filter_atts = us_get_grid_filter_from_page() ) {
			if ( isset( $grid_filter_atts['filter_items'] ) ) {
				$us_grid_filter_atts = json_decode( urldecode( $grid_filter_atts['filter_items'] ), TRUE );
			}
		}

		$allowed_taxonomies = array();
		foreach ( $us_grid_filter_atts as $filter_atts ) {
			if ( ! empty( $filter_atts['source'] ) AND strpos( $filter_atts['source'], '|' ) !== FALSE ) {
				$filter_atts_source = explode( '|', $filter_atts['source'] );
				$allowed_taxonomies[] = us_arr_path( $filter_atts_source, '1', NULL );
			}
		}

		// Get grid filter params
		$filter_ranges = array();
		$filter_taxonomies = us_get_filter_taxonomies( us_get_grid_url_prefix( 'filter' ), $us_grid_filter_params );

		foreach ( $filter_taxonomies as $item_name => $item_values ) {
			if ( is_string( $item_values ) ) {
				$filter_taxonomies[ $item_name ] = array( $item_values );
			}

			// Skip numeric WooCommerce attributes
			if ( count( $item_values ) === 1 AND ( strpos( $item_name, 'tax|pa_' ) !== FALSE ) ) {
				continue;
			}

			// The for range values
			if ( count( $item_values ) === 1 AND preg_match( '/^(\d+)-(\d+)$/', $item_values[0], $matches ) ) {
				$filter_ranges[ $item_name ] = array(
					$matches[1],
					$matches[2],
				);
				unset( $filter_taxonomies[ $item_name ] );
			}
		}

		// Delete the filter by category for the store, this filter is in the tax_query
		if ( ! empty( $query_args['product_cat'] ) ) {
			unset( $query_args['product_cat'] );
		}

		$current_tax_queries = $current_acf_filters = $ranges = array();

		// Adding parameters from the filter to the query request
		if ( ! empty( $filter_taxonomies ) ) {
			foreach ( $filter_taxonomies as $item_name => $item_values ) {

				// Get param_name
				$param = us_grid_filter_parse_param( $item_name );
				$item_source = us_arr_path( $param, 'source' );
				$item_name = us_arr_path( $param, 'param_name', $item_name );

				if (
					in_array( '*', $item_values )
					AND ! in_array( $item_name, $allowed_taxonomies )
				) {
					continue;
				}

				// The for taxonomies
				if ( $item_source === 'tax' ) {
					if ( ! isset( $current_tax_queries[ $item_name ] ) ) {
						$current_tax_queries[ $item_name ] = array();
					}
					$item_values = array_unique( array_merge( $current_tax_queries[ $item_name ], $item_values ) );
					$current_tax_queries[ $item_name ] = $item_values;


					// The for Advanced Custom Fields
				} elseif ( $item_source === 'cf' AND $item_name !== '_price' ) {
					$current_acf_filters[ $item_name ] = array(
						'field_id' => us_arr_path( $param, 'acf_field_id', NULL ),
						'values' => array_unique( $item_values ),
					);
				}
			}
		}

		// Creating conditions for taxonomies
		if ( empty( $query_args['tax_query'] ) AND ! empty( $current_tax_queries ) ) {
			$query_args['tax_query'] = array(
				'relation' => 'AND',
			);
		}
		foreach ( $current_tax_queries as $item_name => $item_values ) {
			$tax_query = array(
				'taxonomy' => $item_name,
				'field' => 'slug',
				'terms' => $item_values,
				'operator' => 'IN',
			);
			// At this stage, it is important to separate the is_int from is_number
			// The number in the string entry is the parameters from the filter
			if (
				isset( $tax_query['field'] )
				AND (
					is_int( $item_values )
					OR (
						isset( $item_values[0] )
						AND is_int( $item_values[0] )
					)
				)
			) {
				unset( $tax_query['field'] );
			}
			$query_args['tax_query'][] = $tax_query;
		}

		// If a category filter is installed on the category page, then delete `category_name`
		if (
			! empty( $current_tax_queries['category'] )
			AND isset( $query_args['category_name'] )
		) {
			unset( $query_args['category_name'] );
		}

		if ( empty( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(
				'relation' => 'AND',
			);
		}

		// Creating conditions for ranges
		foreach ( $filter_ranges as $item_name => $item_values ) {
			$param = us_grid_filter_parse_param( $item_name );
			if ( us_arr_path( $param, 'source' ) !== 'cf' ) {
				continue;
			}

			$param_name = us_arr_path( $param, 'param_name', $item_name );
			if ( $param_name === '_price' ) {
				// Private param
				$query_args['_us_product_meta_lookup_prices'] = array(
					'min_price' => us_arr_path( $item_values, '0' ),
					'max_price' => us_arr_path( $item_values, '1' ),
				);
			} else {
				$meta_query = array(
					'key' => $param_name,
					'type' => 'NUMERIC',
				);

				if ( /* min */ $item_values[0] === 0 ) {
					$meta_query = array_merge(
						array(
							'value' => $item_values[1],
							'compare' => '<=',
						), $meta_query
					);
				} elseif ( /* max */ $item_values[1] == 0 ) {
					$meta_query = array_merge(
						array(
							'value' => $item_values[0],
							'compare' => '>=',
						), $meta_query
					);
				} else {
					$meta_query = array_merge(
						array(
							'value' => $item_values,
							'compare' => 'BETWEEN',
						), $meta_query
					);
				}
				$query_args['meta_query'][] = $meta_query;
			}
		}

		// Creating conditions for Advanced Custom Fields ( select, radio and checkboxes )
		foreach ( $current_acf_filters as $acf_field_name => $acf_item ) {
			if ( ! function_exists( 'acf_get_field' ) ) {
				break;
			}
			$acf_values = array();
			$acf_field = acf_get_field( $acf_item['field_id'] );

			foreach ( array_keys( us_arr_path( $acf_field, 'choices', array() ) ) as $item ) {
				$item_key = preg_replace( '/\s/', '_', us_strtolower( $item ) );

				// Check the record type `value : label`
				if ( preg_match( '/(.*)\s:\s(.*)/', $item_key, $matches ) ) {
					$item_key = $matches[1];
				}

				if (
					(
						is_numeric( $item_key ) // Note: For numeric value ZERO
						OR ! empty( $item_key )
					)
					AND in_array( $item_key, us_arr_path( $acf_item, 'values', array() ) )
				) {
					$acf_values[] = $item;
				}
			}

			$acf_values = array_map( 'trim', $acf_values );
			$acf_values = array_unique( $acf_values );

			$meta_query = array( 'relation' => 'OR' );
			foreach ( $acf_values as $acf_value ) {
				$meta_query[] = array(
					'key' => $acf_field_name,
					'value' => '"' . $acf_value . '"',
					'compare' => 'LIKE',
					'type' => 'CHAR',
				);
				$meta_query[] = array(
					'key' => $acf_field_name,
					'value' => $acf_value,
					'compare' => '=',
					'type' => 'CHAR',
				);
			}

			$query_args['meta_query'][] = $meta_query;
		}
	}
}

if ( class_exists( 'woocommerce' ) AND ! function_exists( 'us_product_meta_lookup_prices' ) ) {
	/**
	 * Custom query used to filter products by price.
	 *
	 * @param array $args Query args.
	 * @param WC_Query $wp_query WC_Query object.
	 * @return array
	 */
	function us_product_meta_lookup_prices( $args, $wp_query ) {
		if ( empty( $wp_query->query_vars['_us_product_meta_lookup_prices'] ) ) {
			return $args;
		}

		$prices = array();
		if ( isset( $wp_query->query_vars['_us_product_meta_lookup_prices'] ) ) {
			$prices = $wp_query->query_vars['_us_product_meta_lookup_prices'];
			unset( $wp_query->query_vars['_us_product_meta_lookup_prices'] );
		}

		$current_min_price = isset( $prices['min_price'] )
			? (float) wp_unslash( $prices['min_price'] )
			: 0; // WPCS: input var ok, CSRF ok.
		$current_max_price = isset( $prices['max_price'] )
			? (float) wp_unslash( $prices['max_price'] )
			: PHP_INT_MAX; // WPCS: input var ok, CSRF ok.

		/**
		 * Adjust if the store taxes are not displayed how they are stored.
		 * Kicks in when prices excluding tax are displayed including tax.
		 */
		if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
			$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
			$tax_rates = WC_Tax::get_rates( $tax_class );
			if ( $tax_rates ) {
				$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
				$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
			}
		}

		global $wpdb;
		if ( ! strstr( $args['join'], 'wc_product_meta_lookup' ) ) {
			$args['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}

		$args['where'] .= $wpdb->prepare(
			' AND NOT ( %f > wc_product_meta_lookup.max_price OR %f < wc_product_meta_lookup.min_price ) ',
			$current_min_price,
			$current_max_price
		);


		return $args;
	}

	add_filter( 'posts_clauses', 'us_product_meta_lookup_prices', 100, 2 );
}

if ( class_exists( 'woocommerce' ) AND ! function_exists( 'us_wc_add_lookup_prices_to_query' ) ) {
	/**
	 * Add the settings of the price range from the filter to the query, if set.
	 * Note: `_us_product_meta_lookup_prices` - processing in the `posts_clauses` filter.
	 *
	 * @param WP_Query $wp_query The WP query
	 */
	function us_wc_add_lookup_prices_to_query( $wp_query ) {
		$post_type = us_arr_path( $wp_query->query_vars, 'post_type' );
		if ( wp_doing_ajax() OR ! us_post_type_is_available( $post_type, array( 'product' ) ) ) {
			return;
		}
		if (
			empty( $wp_query->query['_us_product_meta_lookup_prices'] )
			AND isset( $_GET['min_price'], $_GET['max_price'] )
		) {
			$wp_query->query['_us_product_meta_lookup_prices'] = array(
				'min_price' => (int) us_arr_path( $_GET, 'min_price' ),
				'max_price' => (int) us_arr_path( $_GET, 'max_price' ),
			);
		}
	}

	add_action( 'pre_get_posts', 'us_wc_add_lookup_prices_to_query', 2, 1 );
}

if ( ! function_exists( 'us_get_post_count_by_args' ) ) {
	/**
	 * Get post count by query_args
	 *
	 * @param array $query_args
	 * @return int Returns the number of posts
	 */
	function us_get_post_count_by_args( $query_args, $selected_taxonomies = array() ) {
		if ( empty( $query_args ) OR ! is_array( $query_args ) ) {
			return 0;
		}
		if (
			! empty( $query_args['post_type'] )
			AND (
				(
					is_array( $query_args['post_type'] )
					AND in_array( 'product', $query_args['post_type'] )
				)
				OR $query_args['post_type'] == 'product'
			)
			AND class_exists( 'woocommerce' )
			AND is_object( wc() )
		) {
			if ( ! isset( $query_args['tax_query'] ) ) {
				$query_args['tax_query'] = array();
			}
			$query_args['tax_query'] = wc()->query->get_tax_query( $query_args['tax_query'] );
		}

		// Remove duplicate fields in tax_query
		$tax_maps = array();
		foreach ( us_arr_path( $query_args, 'tax_query', array() ) as $index => $tax ) {
			$field = us_arr_path( $tax, 'taxonomy' );
			$taxonomy = us_arr_path( $tax, 'taxonomy' );
			if ( is_null( $taxonomy ) ) {
				continue;
			} elseif ( ! isset( $tax_maps[ $taxonomy ][ $field ] ) ) {
				$tax_maps[ $taxonomy ][ $field ] = $index;
				continue;
			} elseif ( isset( $query_args['tax_query'][ $index ] ) ) {
				unset( $query_args['tax_query'][ $index ] );
			}
		}

		// Remove duplicate fields in meta_query
		$meta_maps = array();
		foreach ( us_arr_path( $query_args, 'meta_query', array() ) as $index => $meta ) {
			if ( $index === 'relation' ) {
				continue;
			}

			if ( $key = us_arr_path( $meta, 'key' ) ) {
				if ( ! isset( $meta_maps[ $key ] ) ) {
					$meta_maps[ $key ] = $index;
				} elseif ( isset( $query_args['meta_query'][ $index ] ) ) {
					unset( $query_args['meta_query'][ $index ] );
				}
			} elseif ( isset( $meta[0] ) ) {
				$keys = array();
				array_walk_recursive(
					$meta, function ( $value, $key ) use ( &$keys ) {
					if ( $key === 'key' ) {
						$keys[] = $value;
					}
				}
				);
				$keys = array_unique( $keys );
				foreach ( $keys as $key ) {
					if ( isset( $meta_maps[ $key ] ) ) {
						unset( $query_args['meta_query'][ $index ] );
					} else {
						$meta_maps[ $key ] = $index;
					}
				}
			}
		}
		unset( $tax_maps, $meta_maps );

		foreach ( array( 'tax_query', 'meta_query' ) as $key ) {
			if ( ! empty( $selected_taxonomies[ $key ] ) AND is_array( $selected_taxonomies[ $key ] ) ) {
				$query_args[ $key ] = array_merge( $query_args[ $key ], $selected_taxonomies[ $key ] );
			}
		}

		if ( $query_args['post_type'] == 'current_child_pages' ) {
			$query_args['post_type'] = 'any';
		}

		return ( new WP_Query( $query_args ) )->post_count;
	}
}

if ( ! function_exists( 'us_grid_pre_get_posts' ) ) {
	add_action( 'pre_get_posts', 'us_grid_pre_get_posts', 10, 1 );
	/**
	 * The handler is called each time before the posts are received
	 *
	 * @param WP_Query $query
	 */
	function us_grid_pre_get_posts( $query ) {
		global $us_get_orderby, $pagenow, $us_grid_pre_get_posts_running;

		// Skip menu related queries
		if ( us_arr_path( $query->query, 'post_type' ) === 'nav_menu_item' ) {
			return;
		}

		/**
		 * Removing `orderby` and `order` from "GET" query for grid_order/grid_filter
		 */
		if (
			$us_order_key = us_get_grid_url_prefix( 'order' )
			AND is_search()
			AND isset( $_GET[ $us_order_key ] )
		) {
			$us_get_orderby = $_GET[ $us_order_key ];
			unset( $_GET[ $us_order_key ] );
		}

		// Prevent nesting calls of this function, which happens in rare cases
		if ( empty( $us_grid_pre_get_posts_running ) ) {
			$us_grid_pre_get_posts_running = TRUE;
		} else {
			return;
		}
		// Skip executing on post list pages in backend to avoid conflict with WooCommerce add-ons
		if ( is_admin() AND $pagenow == 'edit.php' ) {
			return;
		}

		// Apply filters to archive page
		if (
			$query->is_main_query()
			AND us_get_grid_filter_from_page()
			AND (
				$query->is_tax
				OR $query->is_tag
				OR $query->is_archive
				OR $query->is_search
			)
		) {
			// Update tax_query
			if ( $query->tax_query instanceof WP_Tax_Query ) {
				$query->query_vars['tax_query'] = $query->tax_query->queries;
			}

			// If the archive page has a "current_query" grid, then apply filters to all grids on the page
			$page_content = (string) us_get_page_content( us_get_current_id() );
			if ( strpos( $page_content, 'post_type="current_query"' ) !== FALSE ) {
				us_apply_grid_filters( $query->query_vars );

				// If the page has a current_query grid, then mark in the global variable
				global $us_is_page_has_current_query_grid;
				$us_is_page_has_current_query_grid = TRUE;
			}

			if ( class_exists( 'woocommerce' ) AND is_object( wc() ) ) {
				$current_tax_query = us_arr_path( $query->query_vars, 'tax_query', array() );
				$query->set( 'tax_query', wc()->query->get_tax_query( $current_tax_query ) );
			}
		}

		// Apply sorting params to archive or search page
		if (
			$query->is_main_query()
			AND (
				is_archive()
				OR is_search()
			)
			AND $get_orderby = us_arr_path( $_GET, us_get_grid_url_prefix( 'orderby' ), $us_get_orderby )
			AND $orderby_params = us_grid_orderby_str_to_params( $get_orderby )
		) {
			us_grid_set_orderby_to_query_args( $query->query_vars, $orderby_params );
		}
		$us_grid_pre_get_posts_running = FALSE;
	}
}

if ( ! function_exists( 'us_get_grid_filter_from_page' ) ) {
	/**
	 * Find the mesh filter on the page and get the attributes
	 */
	function us_get_grid_filter_from_page() {
		global $us_get_grid_filter_atts;
		if ( ! is_array( $us_get_grid_filter_atts ) ) {
			$us_get_grid_filter_atts = array();

			if (
				$page_content = us_get_page_content( us_get_current_id() )
				AND preg_match( '/' . get_shortcode_regex( array( 'us_grid_filter' ) ) . '/', $page_content, $matches )
			) {
				$us_get_grid_filter_atts = shortcode_parse_atts( $matches[ /*atts*/3 ] );
			}
		}
		return $us_get_grid_filter_atts;
	}
}

if ( ! function_exists( 'us_grid_get_selected_taxonomies' ) ) {
	/**
	 * Get selected taxonomies for $query_args
	 *
	 * @param array $shortcode_atts The shortcode attributes
	 * @return array Returns an array of query variables and their corresponding values
	 */
	function us_grid_get_selected_taxonomies( $shortcode_atts ) {
		if ( empty( $shortcode_atts ) OR ! is_array( $shortcode_atts ) ) {
			return;
		}
		$query_args = array();
		extract( $shortcode_atts );

		// If the post_type attribute is not set, use the default value from the grid config
		if ( empty( $post_type ) ) {
			$post_type = (string) us_config( 'elements/grid.params.post_type.std', /* default */'post' );
		}

		// Posts from selected taxonomies
		$known_post_type_taxonomies = us_grid_available_taxonomies();

		if ( ! empty( $post_type ) AND ! empty( $known_post_type_taxonomies[ $post_type ] ) ) {
			foreach ( $known_post_type_taxonomies[ $post_type ] as $taxonomy ) {
				$_taxonomy = str_replace( '-', '_', $taxonomy );
				if ( ! empty( ${'taxonomy_' . $_taxonomy} ) ) {
					if ( ! isset( $query_args['tax_query'] ) ) {
						$query_args['tax_query'] = array();
					}
					$terms = explode( ',', ${'taxonomy_' . $_taxonomy} );
					// Validating values to support identifiers
					foreach ( $terms as &$item ) {
						if ( is_numeric( $item ) AND $term = get_term( $item, $taxonomy ) ) {
							$item = $term->slug;
						}
					}
					unset( $item );
					$query_args['tax_query'][] = array(
						'taxonomy' => $taxonomy,
						'field' => 'slug',
						'terms' => $terms,
					);
				}
			}
		}

		return $query_args;
	}
}

if ( ! function_exists( 'us_check_grid_filter_url_prefix' ) ) {
	/**
	 * Remove illegal URL characters from the prefix name
	 *
	 * @param array $updated_options
	 * @return array $updated_options
	 */
	function us_check_grid_filter_url_prefix( $updated_options ) {
		if ( ! empty( $updated_options['grid_filter_url_prefix'] ) ) {
			$grid_filter_url_prefix = (string) $updated_options['grid_filter_url_prefix'];
			$updated_options['grid_filter_url_prefix'] = preg_replace( '/[^\dA-z\-]+/', '', $grid_filter_url_prefix );
		}

		return $updated_options;
	}

	add_filter( 'usof_updated_options', 'us_check_grid_filter_url_prefix', 100, 1 );
}

if ( ! function_exists( 'us_is_grid_products_defined_by_query_args' ) ) {
	/**
	 * The is a grid of products defined by query_args, useful when post type any or related
	 *
	 * @param array $query_args
	 * @return bool
	 */
	function us_is_grid_products_defined_by_query_args( $query_args ) {
		$result = FALSE;
		if ( ! empty( $query_args['tax_query'] ) ) {
			array_walk_recursive(
				$query_args['tax_query'], function ( $value, $key ) use ( &$result ) {
					if ( is_wp_error( $value ) ) {
						return;
					}
					if ( $result OR ! in_array( $key, array( 'taxonomy', 'terms' ) ) ) {
						return;
					}
					// Checking taxonomies
					if ( $key == 'taxonomy' AND strpos( $value, 'product_' ) === 0 ) {
						return $result = TRUE;
					}
					// Checking terms
					if ( $key == 'terms' AND function_exists( 'is_product_category' ) ) {
						foreach ( explode( ',', $value ) as $term ) {
							if ( is_product_category( $term ) ) {
								return $result = TRUE;
							}
						}
					}
				}
			);
		}

		return $result;
	}
}

if ( ! function_exists( 'us_get_portfolio_slugs_map' ) ) {
	/**
	 * Get portfolio taxonomies slugs map
	 *
	 * @return array
	 */
	function us_get_portfolio_slugs_map() {
		return array(
			// default_slug => option_key
			'us_portfolio_category' => 'portfolio_category_slug',
			'us_portfolio_tag' => 'portfolio_tag_slug',
		);
	}
}

if ( ! function_exists( 'us_grid_orderby_str_to_params' ) ) {
	/**
	 * Convert a orderby string to params
	 *
	 * @param string $string The string
	 * @return array
	 */
	function us_grid_orderby_str_to_params( $string ) {
		$result = array();
		if (
			! $string = trim( (string) $string )
			OR ! $params = explode( ',', $string )
		) {
			return $result;
		}

		// Remove extra spaces just in case
		array_map( 'trim', $params );

		// Get sorting key or custom field name
		$orderby = $result['orderby'] = us_arr_path( $params, '0', '' );

		// Check if the field is custom or not
		$options = (array) us_config( 'elements/grid_order.params.orderby_items.params.value.options' );
		if ( ! in_array( $orderby, array_keys( $options ) ) ) {
			$result['orderby'] = 'custom';
			$result['custom_field'] = $orderby;
		}

		// Check for additional parameters
		$result['invert'] = in_array( 'asc', $params );
		$result['custom_field_numeric'] = in_array( 'numeric', $params );

		return $result;
	}
}

if ( ! function_exists( 'us_grid_set_orderby_to_query_args' ) ) {
	/**
	 * Set orderby params to $query_args
	 *
	 * @param array $query_args
	 * @param array $params
	 */
	function us_grid_set_orderby_to_query_args( &$query_args, $params = array() ) {
		if ( empty( $params ) OR ! is_array( $params ) ) {
			return;
		}

		$params = array_merge(
			array(
				'orderby' => '',
				'invert' => FALSE,
				'custom_field' => NULL,
				'custom_field_numeric' => FALSE,
				'post_type' => array(),
			), $params
		);

		if ( ! is_array( $params['post_type'] ) ) {
			$params['post_type'] = array( $params['post_type'] );
		}

		$order = $params['invert']
			? 'ASC'
			: 'DESC';
		$order_reverse = $params['invert']
			? 'DESC'
			: 'ASC';

		// Add Orderby and Order arguments to query_args
		switch ( $params['orderby'] ) {
			case 'date':
				$query_args['orderby'] = array( 'date' => $order );
				break;
			case 'modified':
				// When sorting by modified date adding creation date in case of bulk post updating
				// First item in orderby array is main param to order by
				$query_args['orderby'] = array( 'modified' => $order, 'date' => $order );
				break;
			case 'title':
				$query_args['orderby'] = array( 'title' => $order_reverse );
				$query_args['order'] = $order_reverse;
				break;
			case 'post__in':
				$query_args['orderby'] = array( 'post__in' => $order_reverse );
				$query_args['order'] = $order_reverse;
				break;
			case 'menu_order':
				// Sort posts order for ids
				if ( in_array( 'ids', $params['post_type'] ) AND ! empty( $query_args['post__in'] ) ) {
					$query_args['orderby'] = 'post__in';
				} else {
					$query_args['orderby'] = array( 'menu_order' => $order_reverse );
					$query_args['order'] = $order_reverse;
				}
				break;
			case 'rand':
				$query_args['orderby'] = 'RAND(' . mt_rand() . ')';
				break;
			case 'custom':
				$orderby_params = $params['custom_field_numeric'] ? 'custom_field_num' : 'custom_field';
				$query_args['orderby'] = apply_filters( 'us_grid_orderby_in_posts_clauses_meta_key', $orderby_params, $params );
				$query_args['meta_key'] = $params['custom_field'];
				$query_args['order'] = $order;
				break;
			case 'price':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = '_price';
				$query_args['order'] = $order;
				break;
			case 'popularity':
				// When sorting by meta_value_num adding title in case of same values for meta_value_num
				// First item in orderby array is main param to order by
				$query_args['orderby'] = array( 'meta_value_num' => $order, 'title' => $order_reverse );
				$query_args['meta_key'] = 'total_sales';
				$query_args['order'] = $order;
				break;
			case 'rating':
				$query_args['orderby'] = 'meta_value_num';
				$query_args['meta_key'] = '_wc_average_rating';
				$query_args['order'] = $order;
				break;
			case 'post_views_counter':
			case 'post_views_counter_day':
			case 'post_views_counter_week':
			case 'post_views_counter_month':
				if ( class_exists( 'Post_Views_Counter' ) ) {
					$query_args = array_merge(
						$query_args, array(
							// required by PVC
							'suppress_filters' => FALSE,
							'orderby' => 'post_views',
							'fields' => '',
							'views_query' => array(
								'hide_empty' => FALSE,
							),
						)
					);
				} else {
					$query_args['orderby'] = array( $params['orderby'] => $order );
				}
				$query_args['order'] = $order; // add order reverse to post views
				break;
			default:
				$query_args['orderby'] = array( $params['orderby'] => $order );
		}

		// Order by views per month, week, day
		if (
			class_exists( 'Post_Views_Counter' )
			AND in_array(
				$params['orderby'], array(
					'post_views_counter_day',
					'post_views_counter_week',
					'post_views_counter_month',
				)
			)
		) {
			$views_query = array(
				'year' => date( 'Y' ),
				'month' => date( 'm' ),
				'week' => date( 'W' ),
				'day' => date( 'd' ),
			);
			switch ( $params['orderby'] ) {
				// Views for last day
				case 'post_views_counter_day':
					unset( $views_query['week'] );
					break;
				// Views for last week
				case 'post_views_counter_week':
					unset( $views_query['day'] );
					break;
				// Views for last month
				case 'post_views_counter_month':
					unset( $views_query['day'], $views_query['week'] );
					break;
			}
			$query_args['views_query'] = array_merge( $query_args['views_query'], $views_query );
			unset( $views_query );
		}
		unset( $params, $order, $order_reverse );
	}
}

if ( ! function_exists( 'us_grid_orderby_in_posts_clauses' ) ) {

	// Remove the requirement for a meta field in post
	add_filter( 'get_meta_sql', 'us_grid_orderby_in_posts_clauses_meta_sql', 501, 6 );
	function us_grid_orderby_in_posts_clauses_meta_sql( $clauses, $queries, $type, $primary_table, $primary_id_column, $wp_query ) {
		if ( ! ( $wp_query instanceof WP_Query ) ) {
			return $clauses;
		}

		$orderby = (array) $wp_query->get( 'orderby' );

		// only modify queries for order by custom_field
		if ( in_array( 'custom_field', $orderby ) OR in_array( 'custom_field_num', $orderby ) ) {
			$meta_key = (string) $wp_query->get( 'meta_key' );
			if ( $meta_key AND str_contains( $clauses['where'], $meta_key ) ) {
				global $wpdb;
				$clauses['where'] = str_replace( "{$wpdb->postmeta}.meta_key = '$meta_key'", '1=1', $clauses['where'] );
			}
		}

		return $clauses;
	}

	// Build "orderby" for custom field
	add_filter( 'posts_clauses', 'us_grid_orderby_in_posts_clauses', 501 , 2 );
	function us_grid_orderby_in_posts_clauses( $clauses, $wp_query ) {

		$orderby = (array) $wp_query->get( 'orderby' );

		// only modify queries for order by custom_field
		if ( in_array( 'custom_field', $orderby ) OR in_array( 'custom_field_num', $orderby ) ) {

			$meta_key = (string) $wp_query->get( 'meta_key' );
			$order = (string) $wp_query->get( 'order' );

			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT
					meta_value
				FROM
					{$wpdb->postmeta}
				WHERE
					{$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
					AND {$wpdb->postmeta}.meta_key = '%s'
				LIMIT 1
			", $meta_key );
			$clauses['fields'] .= ', (' . $query . ') AS custom_field_value';

			$end_orderby = $clauses['orderby'];

			// Posts have no meta_value or are empty are displayed at the end of list.
			$order_if = us_strtolower( $order ) == 'desc' ? '0,1' : '1,0';
			$clauses['orderby'] = "IF(custom_field_value IS NULL OR custom_field_value = '', $order_if) $order";

			if ( in_array( 'custom_field_num', $orderby ) ) {
				$clauses['orderby'] .= ', custom_field_value+0 ' . $order;

			} else {
				$clauses['orderby'] .= ', custom_field_value ' . $order;
			}

			if ( $end_orderby ) {
				$clauses['orderby'] .= ', ' . $end_orderby;
			}
		}

		return $clauses;
	}
}

if ( ! function_exists( 'us_grid_get_orderby_options' ) ) {
	/**
	 * Get sorting options for grid config
	 *
	 * @return array
	 */
	function us_grid_get_orderby_options() {
		$options = array(
			'date' => __( 'Date of creation', 'us' ),
			'modified' => __( 'Date of update', 'us' ),
			'title' => us_translate( 'Title' ),
			'rand' => us_translate( 'Random' ),
			'comment_count' => us_translate( 'Comments' ),
			'menu_order' => us_translate( 'Page Attributes' ) . ': ' . us_translate( 'Order' ),
			'post__in' => __( 'Manually for selected images and items', 'us' ),
		);

		// Additional values for WooCommerce products
		if ( class_exists( 'woocommerce' ) ) {
			$options = array_merge(
				$options, array(
					'popularity' => us_translate( 'Sales', 'woocommerce' ),
					'price' => us_translate( 'Price', 'woocommerce' ),
					'rating' => us_translate( 'Rating', 'woocommerce' ),
				)
			);
		}

		// Orders for Post Views Counter
		if ( class_exists( 'Post_Views_Counter' ) ) {
			$options = array_merge(
				$options, array(
					'post_views_counter' => __( 'Total views', 'us' ),
					'post_views_counter_day' => __( 'Views today', 'us' ),
					'post_views_counter_week' => __( 'Views this week', 'us' ),
					'post_views_counter_month' => __( 'Views this month', 'us' ),
				)
			);
		}

		// Add an option for custom settings
		$options = array_merge(
			$options, array(
				'custom' => __( 'Custom Field', 'us' ),
			)
		);

		return $options;
	}
}

if ( ! function_exists( 'us_grid_shows_no_results' ) ) {
	/**
	 * Outputs the HTML block for Grid with no results
	 */
	function us_grid_shows_no_results() {

		global $us_grid_no_results;

		$no_results_action = us_arr_path( $us_grid_no_results, 'action', /* default */ 'message' );

		// Output nothing
		if ( $no_results_action == 'hide_grid' ) {
			return;
		}

		$classes = $content = '';

		// Get relevant classes to hide the "No results" block according to "Hide on" settings
		global $us_grid_hide_on_states;
		if ( $hide_on_classes = us_get_specific_classes_by_shortcode( array( 'hide_on_states' => $us_grid_hide_on_states ) ) ) {
			$classes .= ' ' . $hide_on_classes;
		}

		// Show the message
		if ( $no_results_action == 'message' ) {
			$classes .= ' type_message';
			$content = us_arr_path( $us_grid_no_results, 'message', /* default */ us_translate( 'No results found.' ) );
			$content = strip_tags( $content, '<br><strong>' );
		}

		// Show the Reusable Block
		// DEV: also we avoid a possible reccursion: Reusable Block has a Grid with the same Reusable Block in its settings
		global $us_is_page_block_in_no_results;
		if ( $no_results_action == 'page_block' AND ! $us_is_page_block_in_no_results ) {
			$classes .= ' type_page_block';

			// Get specified Reusable Block ID
			$page_block_id = us_arr_path( $us_grid_no_results, 'page_block' );

			// Get translated version if exist
			if ( has_filter( 'us_tr_object_id' ) ) {
				$page_block_id = apply_filters( 'us_tr_object_id', $page_block_id, 'us_page_block', TRUE );
			}

			// Get the published Reusable Block
			if (
				$page_block = get_post( $page_block_id )
				AND $page_block->post_type == 'us_page_block'
				AND $page_block->post_status == 'publish'
			) {
				// Define if the content is showing via Reusable Block inside "w-grid-none" block
				$us_is_page_block_in_no_results = TRUE;

				us_add_to_page_block_ids( $page_block_id );

				$page_block_content = $page_block->post_content;

				us_open_wp_query_context();
				us_add_page_shortcodes_custom_css( $page_block_id );
				us_close_wp_query_context();

				// Remove [vc_row] and [vc_column]
				$page_block_content = str_replace(
					array(
						'[vc_row]',
						'[/vc_row]',
						'[vc_column]',
						'[/vc_column]',
					), '', $page_block_content
				);
				$page_block_content = preg_replace( '~\[vc_row (.+?)]~', '', $page_block_content );
				$page_block_content = preg_replace( '~\[vc_column (.+?)]~', '', $page_block_content );

				// Apply filters to Reusable Block content and echoing it out of us_open_wp_query_context
				$content = apply_filters( 'us_page_block_the_content', $page_block_content );

				us_remove_from_page_block_ids();

				$us_is_page_block_in_no_results = FALSE;
			}
		}

		echo '<div class="w-grid-none' . $classes . '">' . $content . '</div>';
	}
}

if ( ! function_exists( 'us_import_grid_layout' ) ) {
	/**
	 * This is a method to add a layout based on the passed data
	 *
	 * @param string $data The data
	 * @param string $post_type The post type
	 * @return int|string
	 */
	function us_import_grid_layout( $data, $post_type = 'us_grid_layout' ) {
		$result = 'blog_1'; // the default layout
		$data = explode( '|', $data );
		if ( count( $data ) != 2 ) {
			return $result;
		}
		$post_content = base64_decode( $data[1] );
		if ( json_decode( $post_content ) === NULL ) {
			$post_content = NULL;
		}
		if ( ! $post_content OR ! isset( $data[0] ) ) {
			return $result;
		}

		global $wpdb;

		// Preparing a query to find a duplicate us_grid_layout
		$sql = $wpdb->prepare(
			"SELECT id FROM $wpdb->posts WHERE post_type = %s AND TRIM(`post_content`) = %s LIMIT 1",
			$post_type,
			$post_content
		);
		if ( $post_id = $wpdb->get_var( $sql ) ) {
			// If the record exists, we get the identifier
			$result = $post_id;
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type' => $post_type,
					'post_content' => $post_content,
					'post_author' => get_current_user_id(),
					'post_title' => trim( base64_decode( (string) $data[0] ) ),
					'post_status' => 'publish',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
				)
			);
			if ( $post_id > 0 ) {
				$result = $post_id;
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_get_grid_layout_settings' ) ) {
	/**
	 * Get settings of the provided Grid Layout (post or predefined template)
	 *
	 * @param int|string $grid_layout Post ID or Template name
	 * @param array $default_template Template name which is used in case of incorrect $grid_layout
	 * @return array
	 */
	function us_get_grid_layout_settings( $grid_layout, $default_template = 'blog_1' ) {

		// Get Grid Layout templates
		$templates = us_config( 'grid-templates', array(), TRUE );

		// Use Grid Layout template if exists
		if ( isset( $templates[ $grid_layout ] ) ) {
			$result = us_fix_grid_settings( $templates[ $grid_layout ] );

			// If not, use the "Grid Layout" post
		} elseif (
			$grid_layout_post = get_post( $grid_layout )
			AND $grid_layout_post instanceof WP_Post
			AND $grid_layout_post->post_type == 'us_grid_layout'
		) {
			// If the post has translated version use it instead
			$translated_post_id = apply_filters( 'us_tr_object_id', $grid_layout_post->ID, 'us_grid_layout', TRUE );
			if ( $translated_post_id != $grid_layout_post->ID ) {
				$grid_layout_post = get_post( $translated_post_id );
			}

			// Get the post content as result settings
			if ( ! empty( $grid_layout_post->post_content ) AND strpos( $grid_layout_post->post_content, '{' ) === 0 ) {
				try {
					$result = json_decode( $grid_layout_post->post_content, TRUE );
				}
				catch ( Exception $e ) {
				}
			}
		}

		// In case of empty settings use the default template as fallback
		if ( empty( $result ) ) {
			$result = us_fix_grid_settings( $templates[ $default_template ] );
		}

		return apply_filters( 'us_grid_layout_settings', $result );
	}
}

if ( ! function_exists( 'us_get_grid_layouts_for_selection' ) ) {
	/**
	 * Get the Grid Layouts including templates divided by group
	 *
	 * @param array $template_prefixes Include templates with provided prefixes only
	 * @return array
	 */
	function us_get_grid_layouts_for_selection( $template_prefixes = array() ) {

		// Break further execution on the frotend to avoid extra DB queries
		if ( ! wp_doing_ajax() OR ! is_admin() ) {
			return array();
		}

		// Show Grid Layouts first
		$result = array(
			__( 'Grid Layouts', 'us' ) => us_get_posts_titles_for( 'us_grid_layout' ),
		);

		$current_group = '';

		// Get grid templates and divide them by group
		foreach ( us_config( 'grid-templates', array(), TRUE ) as $template_name => $template ) {

			if ( ! empty( $template['group'] ) AND $template['group'] != $current_group ) {
				$current_group = $template['group'];
			}

			// Include templates with provided prefixes only
			if ( $template_prefixes ) {
				foreach ( (array) $template_prefixes as $_prefix ) {
					if ( strpos( $template_name, $_prefix ) === 0 ) {
						$result[ $current_group ][ $template_name ] = $template['title'];
					}
				}

				// If prefixes are not provided include all templates
			} else {
				$result[ $current_group ][ $template_name ] = $template['title'];
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_process_grid_layout_dynamic_values' ) ) {
	/**
	 * Process grid layout dynamic values of the provided object ID
	 *
	 * @param string $object_type / post / term / user /
	 * @param int $object_ID
	 * @return array Updated collection of grid layout CSS properties
	 */
	function us_process_grid_layout_dynamic_values( $object_type, $object_ID ) {
		global $us_grid_layout_dynamic_values;
		$result = array();

		foreach ( (array) $us_grid_layout_dynamic_values as $screen => $jsoncss ) {
			foreach ( $jsoncss as $css_selector => $props ) {
				foreach ( $props as $prop_name => $prop_value ) {

					if ( strpos( $prop_name, 'color' ) !== FALSE ) {
						$prop_value = us_get_color( $prop_value, /* allow_gradient */TRUE, /* css_var */TRUE );
					} else {
						$prop_value = us_replace_dynamic_value( $prop_value, /* acf_format */FALSE );
					}
					if ( $prop_value != '' ) {
						$css_selector = str_replace( '{{grid-item-id}}', $object_type . '-' . $object_ID, $css_selector );
						$result[ $screen ][ $css_selector ][ $prop_name ] = $prop_value;
					}
				}
			}
		}

		return $result;
	}
}
