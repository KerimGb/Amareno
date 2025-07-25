<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * The Events Calendar Support
 *
 * @link https://docs.theeventscalendar.com/
 */

if ( ! class_exists( 'Tribe__Events__Query' ) ) {
	return;
}

if ( ! function_exists( 'us_enqueue_the_events_calendar_styles' ) ) {
	/**
	 * Enqueue css file
	 */
	function us_enqueue_the_events_calendar_styles() {
		if (
			defined( 'US_DEV' )
			OR ! us_get_option( 'optimize_assets' )
			OR usb_is_post_preview()
		) {
			global $us_template_directory_uri;
			$src = '/common/css/plugins/tribe-events' . ( ! defined( 'US_DEV' ) ? '.min' : '' ) . '.css';
			wp_register_style( 'us-tribe-events', $us_template_directory_uri . $src, array(), US_THEMEVERSION, 'all' );
			wp_enqueue_style( 'us-tribe-events' );
		}
	}
	add_action( 'wp_enqueue_scripts', 'us_enqueue_the_events_calendar_styles', 14 );
}

if ( ! function_exists( 'the_events_calendar_us_grid_listing_query_args' ) ) {
	add_filter( 'us_grid_listing_query_args', 'the_events_calendar_us_grid_listing_query_args', 501, 2 );
	/**
	 * Argument handler when requesting TEC events
	 *
	 * @link https://docs.theeventscalendar.com/reference/functions/tribe_get_events/
	 *
	 * @param array $query_args Array of query variables and their corresponding values
	 * @param array $defined_vars Array of all defined variables in request context
	 * @return array Returns a modified list of query arguments
	 */
	function the_events_calendar_us_grid_listing_query_args( $query_args, $defined_vars ) {
		// Force update of parameters generated by The Events Calendar tools
		if (
			! empty( $query_args['post_type'] )
			AND in_array( 'tribe_events', (array) $query_args['post_type'] )
			AND isset( $defined_vars['events_calendar_show_past'] )
			AND function_exists( 'tribe_get_events' )
		) {
			// Set how events are reflected
			$query_args['eventDisplay'] = (
				! empty( $defined_vars['events_calendar_show_past'] )
					? 'custom'
					: 'list'
			);

			// Replacing aliases with column names
			if ( isset( $query_args['orderby'] ) AND is_array( $query_args['orderby'] ) ) {
				$column_aliases = array(
					'date' => 'event_date',
					'title' => 'post_title',
				);
				foreach( $query_args['orderby'] as $param => $value ) {
					if ( isset( $column_aliases[ $param ] ) ) {
						$query_args['orderby'][ $column_aliases[ $param ] ] = $value;
						unset( $query_args['orderby'][ $param ] );
					}
				}
			}

			// Save sorting options as `tribe_get_events()` removes them
			$orderby = us_arr_path( $query_args, 'orderby', '' );

			// Get WP_Query object
			$wp_query = tribe_get_events( $query_args, /* return WP_Query */TRUE );
			if ( $wp_query instanceof WP_Query ) {
				$query_args = (array) $wp_query->query;
				if ( $orderby ) {
					$query_args['orderby'] = $orderby;
				}
			}
		}

		return $query_args;
	}
}

if ( ! function_exists( 'the_events_calendar_us_grid_filter_main_query_args' ) ) {
	add_filter( 'us_grid_filter_main_query_args', 'the_events_calendar_us_grid_filter_main_query_args', 501, 2 );
	/**
	 * Filter main query arguments for each filter element
	 *
	 * @link https://docs.theeventscalendar.com/reference/functions/tribe_get_events/
	 *
	 * @param array $query_args Array of query variables and their corresponding values
	 * @param array $grids_found An array of grids found on the page
	 * @return array Returns a modified list of query arguments
	 */
	function the_events_calendar_us_grid_filter_main_query_args( $query_args, $grids_found ) {
		if ( is_archive() ) {
			return $query_args; // for archive pages, return the result unchanged
		}
		// Include events whose date has expired
		foreach ( $grids_found as $grid_found ) {
			// Note: The presence of the variable `$events_calendar_show_past` is important for `get_defined_vars()`
			if ( $events_calendar_show_past = us_arr_path( $grid_found, 'events_calendar_show_past' ) ) {
				return the_events_calendar_us_grid_listing_query_args( $query_args, get_defined_vars() );
			}
		}
		return $query_args;
	}
}

if ( ! function_exists( 'us_get_page_area_id_for_events_calendar' ) ) {
	add_filter( 'us_get_page_area_id', 'us_get_page_area_id_for_events_calendar', 10, 2 );
	/**
	 * Returns area ID for Events Calendar specific pages (based on settings)
	 */
	function us_get_page_area_id_for_events_calendar( $area_id, $area ) {

		global $us_page_args;

		// Singular post of Event, Venue, Organizer
		// We must use get_query_var( 'post_type' ) instead of get_post_type(), which returns 'page' on event posts
		if (
			$post_type = get_query_var( 'post_type' )
			AND in_array( $post_type, array( 'tribe_events', 'tribe_venue', 'tribe_organizer' ) )
			AND is_singular( $post_type )
		) {
			$current_post_ID = get_queried_object_id();

			$area_id = us_get_option( $area . '_' . $post_type . '_id', '__defaults__' );

			// Area ID based on "Page Layout" settings in "Edit" admin screen of certain term
			if (
				in_array( $area, array( 'header', 'content', 'footer' ) )
				AND ! empty( get_post_taxonomies( $current_post_ID ) )
			) {
				foreach ( get_post_taxonomies( $current_post_ID ) as $taxonomy_slug ) {
					if (
						$terms = get_the_terms( $current_post_ID, $taxonomy_slug )
						AND is_array( $terms )
					) {
						foreach ( $terms as $term ) {
							if ( is_numeric( $pages_content_id = get_term_meta( $term->term_id, 'pages_' . $area . '_id', TRUE ) ) ) {
								$area_id = $pages_content_id;
								break 2;
							}
						}
					}
				}
			}

			// Area ID based on "Page Layout" settings of certain post
			if ( $current_post_ID AND metadata_exists( 'post', $current_post_ID, 'us_' . $area . '_id' ) ) {

				$singular_area_id = get_post_meta( $current_post_ID, 'us_' . $area . '_id', TRUE );

				if (
					$singular_area_id == '' // corresponds to "Do not display" value for theme version 8.14 and below
					OR $singular_area_id == '0' // corresponds to "Do not display" value for theme versions above 8.14
					OR is_registered_sidebar( $singular_area_id ) // checks existence of sidebar by slug
					OR get_post_status( $singular_area_id ) != FALSE // checks existence of Reusable Block by ID (avoid cases of deleted blocks)
				) {
					$area_id = $singular_area_id;
				}
			}

			if ( empty( $us_page_args['page_type'] ) ) {
				$us_page_args['page_type'] = 'post';
			}

			if ( empty( $us_page_args['post_type'] ) ) {
				$us_page_args['post_type'] = $post_type;
			}
		}

		// Archives Events Calendar
		if ( is_post_type_archive( 'tribe_events' ) ) {

			// Archive: Events
			if ( get_queried_object() instanceof WP_Post_Type ) {
				$area_id = us_get_option( $area . '_tax_tribe_events_id', '__defaults__' );
			} else {
				$area_id = us_get_option( $area . '_tax_tribe_events_cat_id', '__defaults__' );
			}

			if ( $area_id == '__defaults__' ) {
				$area_id = us_get_option( $area . '_archive_id', '' );
			}

			// Area ID based on "Archive Layout" settings in "Edit" admin screen of certain term
			if (
				in_array( $area, array( 'header', 'content', 'footer' ) )
				AND $current_taxonomy_ID = get_queried_object_id()
			) {
				if (
					$archive_area_id = get_term_meta( $current_taxonomy_ID, 'archive_' . $area . '_id', TRUE )
					AND is_numeric( $archive_area_id )
				) {
					$area_id = $archive_area_id;

					if ( empty( $us_page_args['taxonomy_ID'] ) ) {
						$us_page_args['taxonomy_ID'] = $current_taxonomy_ID;
					}
				}
			}

			if ( empty( $us_page_args['page_type'] ) ) {
				$us_page_args['page_type'] = 'tribe_events';
			}
		}

		if ( $area_id == '__defaults__' ) {
			$area_id = us_get_option( $area . '_id', '' );
		}

		return $area_id;
	}
}

if ( ! function_exists( 'us_add_tribe_events_orderby_params' ) ) {
	add_filter( 'us_get_list_orderby_params', 'us_add_tribe_events_orderby_params' );
	/**
	 * Append Events params to List Orderby options
	 */
	function us_add_tribe_events_orderby_params( $params ) {
		$params['_EventStartDate'] = array(
			'label' => us_translate( 'Start Date', 'the-events-calendar' ),
			'group' => us_translate( 'Events', 'the-events-calendar' ),
			'orderby_param' => 'meta_value',
			'meta_key' => '_EventStartDate',
		);
		$params['_EventEndDate'] = array(
			'label' => us_translate( 'End Date', 'the-events-calendar' ),
			'group' => us_translate( 'Events', 'the-events-calendar' ),
			'orderby_param' => 'meta_value',
			'meta_key' => '_EventEndDate',
		);
		return $params;
	}
}

if ( ! function_exists( 'us_grid_orderby_in_events_clauses_meta_key' ) ) {
	add_filter( 'us_grid_orderby_in_posts_clauses_meta_key', 'us_grid_orderby_in_events_clauses_meta_key', 10, 2 );
	/**
	 * Return standard orderby by custom field for Events in Grid
	 *
	 * @param  string $orderby_params
	 * @param  array  $params
	 * @return string
	 */
	function us_grid_orderby_in_events_clauses_meta_key( $orderby_params, $params ) {
		if ( in_array( $params['custom_field'], array( '_EventStartDate', '_EventEndDate', '_EventStartDateUTC', '_EventEndDateUTC' ) ) ) {
			$orderby_params = $params['custom_field'];
		}

		return $orderby_params;
	}
}
