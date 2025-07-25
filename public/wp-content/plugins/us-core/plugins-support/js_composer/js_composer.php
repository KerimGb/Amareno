<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * WPBakery Page Builder support
 *
 * @link http://codecanyon.net/item/visual-composer-page-builder-for-wordpress/242431?ref=UpSolution
 */

/**
 * IF WPBakery is inactive - add functions that we need ONLY in case it is inactive and abort following file execution
 */
if ( ! class_exists( 'Vc_Manager' ) ) {

	/**
	 * @param $width
	 *
	 * @return bool|string
	 * @since 4.2
	 */
	function us_wpb_translateColumnWidthToSpan( $width ) {
		preg_match( '/(\d+)\/(\d+)/', $width, $matches );
		if ( ! empty( $matches ) ) {
			$part_x = (int) $matches[1];
			$part_y = (int) $matches[2];
			if ( $part_x > 0 AND $part_y > 0 ) {
				$value = ceil( $part_x / $part_y * 12 );
				if ( $value > 0 AND $value <= 12 ) {
					$width = 'vc_col-sm-' . $value;
				}
			}
		}
		if ( preg_match( '/\d+\/5$/', $width ) ) {
			$width = 'vc_col-sm-' . $width;
		}

		return $width;
	}

	/**
	 * @param $column_offset
	 * @param $width
	 *
	 * @return mixed|string
	 */
	function us_vc_column_offset_class_merge( $column_offset, $width ) {
		if ( preg_match( '/vc_col\-sm\-\d+/', $column_offset ) ) {
			return $column_offset;
		}

		return $width . ( empty( $column_offset ) ? '' : ' ' . $column_offset );
	}

	return;
}

/**
 * Code from this line and to the end of the file should be executed ONLY with WPBakery active
 */
if ( ! function_exists( 'us_vc_set_as_theme' ) ) {
	add_action( 'vc_before_init', 'us_vc_set_as_theme' );
	function us_vc_set_as_theme() {
		vc_set_as_theme();
	}
}

if ( ! function_exists( 'us_disable_wpb_notice_list' ) ) {
	add_action( 'init', 'us_disable_wpb_notice_list', 99 );

	// Disable all WPBakery admin notices
	function us_disable_wpb_notice_list() {
		$value = array( 'empty_api_response' => TRUE );
		set_transient( 'wpb_notice_list', $value, 0 );
	}
}

if ( ! function_exists( 'us_vc_after_init' ) ) {
	add_action( 'vc_after_init', 'us_vc_after_init' );
	function us_vc_after_init() {

		// Disable WPBakery own updating hooks
		$updater = vc_manager()->updater();
		$updateManager = $updater->updateManager();

		remove_filter( 'upgrader_pre_download', array( $updater, 'preUpgradeFilter' ) );
		remove_filter( 'pre_set_site_transient_update_plugins', array( $updateManager, 'check_update' ) );
		remove_filter( 'plugins_api', array( $updateManager, 'check_info' ) );
		remove_action(
			'in_plugin_update_message-' . vc_plugin_name(), array(
				$updateManager,
				'addUpgradeMessageLink',
			)
		);

		// Remove the default 'fixPContent' filter, cause it adds an extra <section> in WPBakery 6.10.0
		remove_action( 'the_content', array( visual_composer(), 'fixPContent' ), 11 );

		// Disable standard animations for third-party shortcodes that support design options
		if ( function_exists( 'vc_remove_param' ) ) {
			$tags = us_config( 'shortcodes.added_design_options', array(), /* Reload */ TRUE );
			foreach ( $tags as $tag ) {
				vc_remove_param( $tag, 'css_animation' );
			}
		}
	}
}

if ( ! function_exists( 'us_vc_map_get_attributes' ) ) {
	add_filter( 'vc_map_get_attributes', 'us_vc_map_get_attributes', 1, 2 );
	/**
	 * VC Filter for getting attributes for shortcode
	 *
	 * @param array $atts The shortcode attributes
	 * @param string $tag The shortcode base
	 * @return array
	 */
	function us_vc_map_get_attributes( $atts, $tag ) {
		if ( ! in_array( $tag, us_config( 'shortcodes.added_design_options', array(), /* reload */TRUE ) ) ) {
			return $atts;
		}
		// Set an empty string as VC does not process the parameter correctly
		$atts['css_animation'] = '';
		return $atts;
	}
}

add_action( 'vc_after_set_mode', 'us_vc_after_set_mode' );
function us_vc_after_set_mode() {

	do_action( 'us_before_js_composer_mappings' );

	// Remove VC Font Awesome style in admin pages
	add_action( 'admin_head', 'us_wpb_remove_admin_assets', 1 );
	function us_wpb_remove_admin_assets() {
		foreach ( array( 'ui-custom-theme', 'vc_font_awesome_5_shims', 'vc_font_awesome_5', 'vc_font_awesome_6' ) as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
		if ( us_get_option( 'disable_extra_vc', 1 ) AND wp_style_is( 'vc_animate-css', 'registered' ) ) {
			wp_dequeue_style( 'vc_animate-css' );
			wp_deregister_style( 'vc_animate-css' );
		}
	}

	// Remove original VC styles and scripts
	if ( function_exists( 'vc_is_page_editable' ) AND vc_is_page_editable() ) {

		// Remove some of the shortcodes handlers to use native VC shortcodes instead for front-end compatibility
		US_Shortcodes::instance()->vc_front_end_compatibility();

		// Add theme CSS for frontend editor
		add_action( 'wp_enqueue_scripts', 'us_process_css_for_frontend_js_composer', 15 );
		function us_process_css_for_frontend_js_composer() {
			wp_enqueue_style( 'us_js_composer_front', US_CORE_URI . '/plugins-support/js_composer/css/us_frontend_editor.css' );
		}

	} else {

		// Remove original VC styles and scripts
		add_action( 'wp_enqueue_scripts', 'us_vc_remove_base_css_js', 15 );
		function us_vc_remove_base_css_js() {
			if ( wp_style_is( 'vc_font_awesome_5', 'registered' ) ) {
				wp_dequeue_style( 'vc_font_awesome_5' );
				wp_deregister_style( 'vc_font_awesome_5' );
			}
			if ( wp_style_is( 'vc_font_awesome_6', 'registered' ) ) {
				wp_dequeue_style( 'vc_font_awesome_6' );
				wp_deregister_style( 'vc_font_awesome_6' );
			}
			if ( us_get_option( 'disable_extra_vc', 1 ) ) {
				if ( wp_style_is( 'js_composer_front', 'registered' ) ) {
					wp_dequeue_style( 'js_composer_front' );
					wp_deregister_style( 'js_composer_front' );
				}
				if ( wp_script_is( 'wpb_composer_front_js', 'registered' ) ) {
					wp_deregister_script( 'wpb_composer_front_js' );
				}
				// Starting from version 6.1, id was removed from inline styles
				if ( defined( 'WPB_VC_VERSION' ) AND version_compare( WPB_VC_VERSION, '6.0.3', '<=' ) ) {
					// Add custom css
					( new Us_Vc_Base )->init();
				}
			}
		}
	}

	// Remove "Grid" admin menu item
	if ( is_admin() AND us_get_option( 'disable_extra_vc', 1 ) ) {

		add_action( 'admin_menu', 'us_vc_remove_grid_elements_submenu' );
		function us_vc_remove_grid_elements_submenu() {
			remove_submenu_page( VC_PAGE_MAIN_SLUG, 'edit.php?post_type=vc_grid_item' );
		}
	}

	// Disable Icon Picker assets
	if ( us_get_option( 'disable_extra_vc', 1 ) ) {
		// Plugin “All In One Addons for WPBakery Page Builder” used Iconpicker functionality,
		// for this reason we will disable "vc_backend_editor_enqueue_js_css" if there is no plugin.
		if ( ! class_exists( 'VC_Extensions_FlipBox' ) ) {
			remove_action( 'vc_backend_editor_enqueue_js_css', 'vc_iconpicker_editor_jscss' );
		}
		remove_action( 'vc_frontend_editor_enqueue_js_css', 'vc_iconpicker_editor_jscss' );
	}

	do_action( 'us_after_js_composer_mappings' );
}

if ( ! function_exists( 'us_vc_init_shortcodes' ) ) {
	add_action( 'wp_loaded', 'us_vc_init_shortcodes', 11 );
	function us_vc_init_shortcodes() {
		if (
			! function_exists( 'vc_mode' )
			OR ! function_exists( 'vc_map' )
			OR ! function_exists( 'vc_remove_element' )
		) {
			return;
		}

		// Gets configurations for shortcodes
		$shortcodes_config = us_config( 'shortcodes', array(), TRUE );

		if ( us_get_option( 'disable_extra_vc', 1 ) ) {
			// Removing the elements that are not supported at the moment by the theme
			if (
				is_admin()
				AND ! empty( $shortcodes_config['disabled'] )
				AND is_array( $shortcodes_config['disabled'] )
			) {
				foreach ( $shortcodes_config['disabled'] as $shortcode ) {
					vc_remove_element( $shortcode );
				}
			} else {
				add_action( 'template_redirect', 'us_vc_disable_extra_sc', 100 );
			}
		}

		if ( vc_mode() === 'page' ) {
			return;
		}

		// Mapping WPBakery Page Builder backend behaviour for used shortcodes
		global $pagenow;

		/**
		 * If the page for editing roles then the result will be TRUE
		 * @var bool
		 */
		$is_edit_vc_roles = (
			$pagenow === 'admin.php'
			AND us_arr_path( $_GET, 'page' ) === 'vc-roles'
		);

		// Receive data only on the edit page or create a record
		if (
			wp_doing_ajax()
			OR in_array( $pagenow, array( 'post.php', 'post-new.php' ) )
			OR $is_edit_vc_roles
			OR ( function_exists( 'vc_is_page_editable' ) AND vc_is_page_editable() )
		) {
			foreach ( $shortcodes_config['theme_elements'] as $elm_name ) {
				$is_vc_elm = strpos( $elm_name, 'vc_' ) === 0;

				// Add prefix "us_" for non "vc_" shortcodes
				$shortcode = us_get_shortcode_full_name( $elm_name );
				if (
					! $elm = us_config( "elements/{$elm_name}" )
					// Skip shortcodes with place_if set to `false`
					OR (
						isset( $elm['place_if'] )
						AND empty( $elm['place_if'] )
					)
				) {
					continue;
				}

				$vc_elm = array(
					'admin_enqueue_js' => $elm['admin_enqueue_js'] ?? NULL,
					'as_child' => $elm['as_child'] ?? NULL,
					'as_parent' => $elm['as_parent'] ?? NULL,
					'base' => $shortcode,
					'category' => $elm['category'] ?? us_translate( 'Content' ),
					'description' => $elm['description'] ?? '',
					'icon' => $elm['icon'] ?? '',
					'is_container' => $elm['is_container'] ?? NULL,
					'js_view' => $elm['js_view'] ?? NULL,
					'name' => $elm['title'] ?? $shortcode,
					'weight' => $elm['weight'] ?? 380, // elms go after "Text Block", which has the "390" weight
					'params' => array(),
				);

				// Added class to element that it is not a theme element,
				// this will help identify him among others
				if ( isset( $elm[ 'override_config_only' ] ) ) {
					$vc_elm['class'] = 'us_override_config_only';
				}

				// Global updates for the correct work of the shortcode in all editors
				foreach( array( 'allowed_container_element' ) as $prop ) {
					if ( array_key_exists( $prop, $elm ) ) {
						vc_map_update( $shortcode, array( $prop => $elm[ $prop ] ) );
					}
				}

				$vc_elm_params_names = array();
				if ( isset( $elm['params'] ) AND is_array( $elm['params'] ) ) {
					foreach ( $elm['params'] as $param_name => &$param ) {
						if (
							isset( $param['context'] )
							AND is_array( $param['context'] )
							AND ! in_array( 'shortcode', $param['context'] )
							OR (
								isset( $param['place_if'] )
								AND $param['place_if'] === FALSE
							)
						) {
							continue;
						}

						$vc_param = _us_vc_param( $param_name, $param );
						if ( $vc_param != NULL ) {
							$vc_elm['params'][] = $vc_param;
							$vc_elm_params_names[] = $param_name;
						}
					}
					unset( $param );
					unset( $vc_param );
				}

				// Add specified params as hidden fields, so js_composer processes them during fallback
				if ( ! empty( $elm['fallback_params'] ) ) {
					// Check if we need add a group for fallback params
					$_first_param = ( count( $vc_elm['params'] ) ) ? $vc_elm['params'][0] : array();
					$_first_param_group = ( isset( $_first_param['group'] ) ) ? $_first_param['group'] : FALSE;
					foreach ( $elm['fallback_params'] as $param_name ) {
						$vc_param = array(
							'type' => 'textfield',
							'param_name' => $param_name,
							'std' => '',
							'edit_field_class' => 'hidden',
						);
						if ( ! empty( $_first_param_group ) ) {
							$vc_param['group'] = $_first_param_group;
						}
						$vc_elm['params'][] = $vc_param;
						$vc_elm_params_names[] = $param_name;

						unset( $vc_param );
					}
				}

				// Adds US shortcode
				if ( ! $is_vc_elm ) {
					vc_map( $vc_elm );

					// Adds Visual Composer shortcode
				} else {

					// Get VC element default param names
					$original_params = vc_map_get_defaults( $shortcode );
					$original_params_names = ( ! empty( $original_params ) ) ? array_keys( $original_params ) : array();

					// Get params to remove, which set in config
					$params_to_remove = ( ! empty( $elm['vc_remove_params'] ) ) ? $elm['vc_remove_params'] : array();
					$params_to_remove = array_merge( $params_to_remove, array_keys( $elm['params'] ) );

					// Remove params with the same name as original
					foreach ( $params_to_remove as $param_name ) {
						if ( in_array( $param_name, $original_params_names ) ) {
							vc_remove_param( $shortcode, $param_name );
						}
					}

					// Add params as new
					foreach( $vc_elm['params'] as $vc_param ) {
						vc_add_param( $shortcode, $vc_param );
					}

					// Update category for VC element
					// Dev note: vc_map_update should go after vc_update_shortcode_param / vc_add_param here (otherwise WPBakery may glitch)
					foreach( array( 'category', 'weight' ) as $prop ) {
						if ( ! empty( $elm[ $prop ] ) ) {
							vc_map_update( $shortcode, array( $prop => $elm[ $prop ] ) );
						}
					}
				}

				// This is required for the access edit page on the vc-roles page
				if ( $is_edit_vc_roles AND ! $is_vc_elm ) {
					vc_lean_map( $shortcode, function() use( $vc_elm ) {
						return $vc_elm;
					} );
				}
			}
		}

		// Apply new design styles to VC shortcodes for which there is no map
		$shortcodes_with_design_options = $shortcodes_config['added_design_options'];
		foreach ( $shortcodes_with_design_options as $vc_shortcode_name ) {
			vc_remove_param( $vc_shortcode_name, 'css' ); // remove the old field and handlers for it
			vc_add_param(
				$vc_shortcode_name, array(
					'param_name' => 'css',
					'type' => 'us_design_options',
					'heading' => '',
					'params' => us_config( 'elements_design_options.css.params', array() ),
					'group' => __( 'Design', 'us' ),
				)
			);
		}
	}

	/**
	 * Formats US parameter to VC format
	 *
	 * @param string $param_name The param name
	 * @param array $param The params
	 * @return array
	 */
	function _us_vc_param( $param_name, $param ) {

		// Translation from our builder param types to WPBakery param types
		$related_types = array(

			// Basic field types from USOF
			'autocomplete' => 'us_autocomplete',
			'checkboxes' => 'us_checkboxes',
			'color' => 'us_color',
			'design_options' => 'us_design_options',
			'icon' => 'us_icon',
			'imgradio' => 'us_imgradio',
			'link' => 'us_link',
			'radio' => 'us_radio',
			'select' => 'us_select',
			'slider' => 'us_slider',
			'switch' => 'us_switch',
			'text' => 'us_text',
			'textarea' => 'us_textarea',
			'hidden' => 'us_hidden',
			'upload' => 'us_upload',
			'wrapper_start' => 'us_wrapper_start',
			'wrapper_end' => 'us_wrapper_end',
			'message' => 'us_message',

			// Basic field types from WPBakery
			'css_editor' => 'css_editor',
			'editor' => 'textarea_html',
			'group' => 'param_group',

			// Note: Added hard binding to textarea_raw_html for `$param_name == content`
			// name on Visual Composer side despite type changes in params
			'html' => 'textarea_raw_html',

			// Delete params (Specific types after from USOF)
			'heading' => 'param_to_delete',
		);

		if ( ! is_array( $param ) ) {
			$param = array();
		}

		if ( ! isset( $param['type'] ) ) {
			$param['type'] = 'text';
		}

		// Get current field type
		$type = us_arr_path( $related_types, $param['type'], /* default */'textfield' );

		// Check if param is not wanted in WPBakery builder, and if so, return nothing for it
		if ( $type == 'param_to_delete' ) {
			return;
		}

		/**
		 * Some attributes of params may be set for shortcodes exclusively,
		 * which is indicated by shortcode_ prefix in their names,
		 * checking if such attributes are present and adding them to the result array without prefix
		 */
		$attributes_with_prefixes = array(
			'title',
			'description',
			'options',
			'classes',
			'cols',
			'std',
			'show_if',
		);
		foreach ( $attributes_with_prefixes as $attribute ) {
			if ( isset( $param[ 'shortcode_' . $attribute ] ) ) {
				$param[ $attribute ] = $param[ 'shortcode_' . $attribute ];
			}
		}

		// Base structure of a param
		$vc_param = array(
			'admin_label' => $param['admin_label'] ?? FALSE,
			'_description' => $param['description'] ?? '', // output description via USOF
			'dynamic_values' => $param['dynamic_values'] ?? NULL,
			'edit_field_class' => $param['classes'] ?? NULL,
			'heading' => $param['title'] ?? '', // not used in `us_*` controls
			'holder' => $param['holder'] ?? NULL,
			'is_responsive' => $param['is_responsive'] ?? FALSE,
			'labels_as_icons' => $param['labels_as_icons'] ?? NULL, // specific setting for radio buttons
			'param_name' => $param_name, // this attribute must be non-empty
			'options_filtered_by_param' => $param['options_filtered_by_param'] ?? NULL,
			'settings' => $param['settings'] ?? NULL,
			'ajax_data' => $param['ajax_data'] ?? NULL,
			'std' => $param['std'] ?? '',
			'title' => $param['title'] ?? NULL,
			'type' => $type,
			'weight' => $param['weight'] ?? NULL,
			'params' => ( isset( $param['params'] ) AND $param['type'] == 'design_options' ) ? $param['params'] : NULL,
		);

		// Redefining the header for the field is necessary so that there is no duplication of the header,
		// since field `heading` is required for Visual Composer
		if ( strpos( $type, 'us_' ) === 0 AND isset( $vc_param['heading'] ) ) {
			$vc_param['title'] = $vc_param['heading'];
		}

		// USOF Field: Switch
		if ( $type == 'us_switch' ) {
			$vc_param['switch_text'] = us_arr_path( $param, 'switch_text', /* default */NULL );
			// Show IF on the Visual Composer side does not work with numeric values,
			// because it receives values from the field as strings, so we will
			// convert all values to string format.
			$vc_param['std'] = (string) $vc_param['std'];
		}

		// USOF Field: Autocomplete, Upload
		if ( in_array( $type, array( 'us_autocomplete', 'us_upload' ) ) ) {
			foreach ( array( 'is_multiple', 'is_sortable' ) as $prop ) {
				if ( isset( $param[ $prop ] ) ) {
					$vc_param[ $prop ] = (bool) $param[ $prop ];
				}
			}
		}

		// USOF Field: Color
		if ( $type == 'us_color' ) {
			foreach ( array( 'with_gradient', 'clear_pos', 'exclude_dynamic_colors' ) as $prop ) {
				if ( isset( $param[ $prop ] ) ) {
					$vc_param[ $prop ] = $param[ $prop ];
				}
			}
		}

		// USOF Field: Image Radio
		if ( $type == 'us_imgradio' AND ! empty( $param['preview_path'] ) ) {
			$vc_param['preview_path'] = (string) $param['preview_path'];
		}

		// USOF Field: select, radio, imgradio, autocomplete, slider
		if ( in_array( $type, array( 'us_select', 'us_radio', 'us_imgradio', 'us_checkboxes', 'us_autocomplete', 'us_slider' ) ) ) {
			$vc_param[ 'options' ] = (array) $param['options'];
		}

		// USOF Field: Select
		// Note: Important this condition should be after adding `$vc_param['options']`
		if ( $type == 'us_select' ) {
			// Add data to support the display if there is `admin_label`
			if ( TRUE === us_arr_path( $vc_param, 'admin_label', FALSE ) ) {
				$vc_value = (array) us_arr_path( $vc_param, 'options', array() );
				// Note: Visual Composer does not support multidimensional arrays,
				// so if the array is multidimensional, turn it into one-dimensional.
				if ( count( $vc_value, COUNT_RECURSIVE ) - count( $vc_value ) ) {
					$_vc_value = array();
					array_walk_recursive( $vc_value, function ( $value, $key ) use ( &$_vc_value ) {
						$_vc_value[ $key ] = $value;
					} );
					$vc_value = $_vc_value;
					unset( $_vc_value );
				}
				// Visual Composer supports `value => key`.
				$vc_param[ 'value' ] = array_flip( $vc_value );
			}
		}

		// USOF Field: Hidden
		if ( $type == 'us_hidden' AND isset( $param['auto_generate_value_by_switch_on'] ) ) {
			$vc_param['auto_generate_value_by_switch_on'] = (string) $param['auto_generate_value_by_switch_on'];
		}

		// Add option CSS classes based on "cols" param to WPBakery format
		if ( isset( $param['cols'] ) ) {
			$column_size = 'vc_col-sm-' . ( 12 / (int) $param['cols'] );
			$vc_param['edit_field_class'] .= ' ' . $column_size;
		}

		// Enable WPBakery AI feature for text fields
		if ( ! empty( $param['show_ai_icon'] ) ) {
			$vc_param['edit_field_class'] .= ' show_ai_icon';
		}

		// Add class for correct appearance of fields with custom classes
		if ( is_string( $vc_param['edit_field_class'] ) AND strpos( $vc_param['edit_field_class'], 'vc_col-' ) === FALSE ) {
			$vc_param['edit_field_class'] = 'vc_col-xs-12 ' . $vc_param['edit_field_class'];
		}

		// Setting group tab for a param
		if ( ! empty( $param['group'] ) ) {
			$vc_param['group'] = $param['group'];
		}

		// Translating value options for respective params to WPBakery format
		if ( $type == 'dropdown' AND isset( $param['options'] ) ) {
			$vc_param['value'] = array();
			foreach ( $param['options'] as $option_val => $option_name ) {
				if ( is_string( $option_name ) ) {
					$vc_param['value'][ $option_name . ' ' ] = $option_val . '';
				}
			}
		}

		// Proper dependency rules
		// Note: If dependency disables a field, then no matter what value
		// it contains, WPBakery will set it to default
		if ( isset( $param['show_if'] ) AND count( $param['show_if'] ) == 3 ) {
			$condition = $param['show_if'][/* condition */1];
			$condition_value = $param['show_if'][/* condition value */2];
			$vc_param['dependency'] = array(
				'element' => $param['show_if'][/* condition name */0],
			);

			if ( $condition === '=' AND $condition_value === '' ) {
				$vc_param['dependency']['is_empty'] = TRUE;
			} elseif ( $condition === '!=' AND $condition_value === '' ) {
				$vc_param['dependency']['not_empty'] = TRUE;
			} elseif ( $condition === '!=' AND ! empty( $condition_value ) ) {
				$vc_param['dependency']['value_not_equal_to'] = $condition_value;
			} elseif ( $condition === 'str_contains' AND ! empty( $condition_value ) ) {
				$vc_param['dependency']['callback'] = 'us_str_contains_callback';
				$vc_param['dependency']['callback_element'] = $param['show_if'][/* condition name */0]; // related attribute
				$vc_param['dependency']['needle'] = $condition_value; // what related attribute value should contain
				unset( $vc_param['dependency']['element'] );
			} else {

				// We convert the numerical parameters of us switch to a string type
				// for the correct operation of Show IF on the Visual Composer side
				if ( is_numeric( $condition_value ) AND in_array( (int) $condition_value, array( 1, 0 ) ) ) {
					$condition_value = (string) $condition_value;
				}

				$vc_param['dependency']['value'] = $condition_value;
			}
		}

		// Proper group rules
		if ( $type == 'param_group' ) {
			if ( isset( $param['params'] ) AND is_array( $param['params'] ) ) {
				$group_params = $param['params'];
				$param['params'] = array();
				foreach ( $group_params as $group_param_name => $group_param ) {

					// WPBakery, the fields in the group contain a prefix of the group name, so we adjust it for compatibility.
					// Example: `param_name` to `{group_name}|{param_name}`
					if ( isset( $group_param['options_filtered_by_param'] ) ) {
						$group_param['options_filtered_by_param'] = sprintf( '%s|%s', $param_name, $group_param['options_filtered_by_param'] );
					}

					$group_vc_param = _us_vc_param( $group_param_name, $group_param );
					if ( $group_vc_param != NULL ) {
						$vc_param['params'][] = $group_vc_param;
					}
				}
			}

			// Transform the array value to a string
			if ( isset( $vc_param['std'] ) AND is_array( $vc_param['std'] ) ) {
				$vc_param['std'] = rawurlencode( json_encode( $vc_param['std'] ) );
			}
		}

		// Changing type for attach_image with is_multiple setting to attach_images
		if ( $type == 'attach_image' AND us_arr_path( $param, 'is_multiple' ) === TRUE ) {
			$vc_param['type'] = 'attach_images';
		}

		return (array) $vc_param;
	}
}

if ( ! function_exists( 'us_vc_shortcodes_css_classes' ) ) {
	// Add a filter to the output of css styles for an element
	if ( defined( 'VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG' ) ) {
		add_filter( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, 'us_vc_shortcodes_css_classes', 10, 3 );
	}
	/**
	 * Add required classes for VC shortcodes for which there are no theme templates
	 *
	 * @param string $classes The classes
	 * @param string $tag The shortcode base
	 * @param array $atts The shortcode atts
	 * @return string
	 */
	function us_vc_shortcodes_css_classes( $classes, $tag, $atts = array() ) {
		if (
			empty( $atts[ 'css' ] )
			OR ! in_array( $tag, us_config( 'shortcodes.added_design_options', array(), /* reload */TRUE ) )
		) {
			return $classes;
		}
		// Add a unique class of design options
		if ( function_exists( 'us_get_unique_css_class_name' ) ) {
			$classes .= ' ' . us_get_unique_css_class_name( $atts['css'] );
		}
		// Add a class for the correct display of the border if any
		if ( us_design_options_has_property( $atts['css'], 'border-radius' ) ) {
			$classes .= ' has_border_radius';
		}
		// Add a class that activates animation, if any
		if ( us_design_options_has_property( $atts[ 'css' ], 'animation-name' ) ) {
			$classes .= ' us_animate_this';
		}
		return $classes;
	}
}

add_action( 'current_screen', 'us_wpb_disable_specific_elements' );
function us_wpb_disable_specific_elements() {
	if ( function_exists( 'get_current_screen' ) ) {
		global $pagenow;

		// Receive data only on the edit page or create a record
		if ( wp_doing_ajax() OR ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		$screen = get_current_screen();
		$shortcodes_config = us_config( 'shortcodes', array(), TRUE );

		foreach ( $shortcodes_config['theme_elements'] as $elm_name ) {

			// Add prefix "us_" for non "vc_" shortcodes
			$shortcode = us_get_shortcode_full_name( $elm_name );
			$elm = us_config( "elements/{$elm_name}", array() );

			if (
				! empty( $elm['show_for_post_types'] )
				AND ! empty( $screen->post_type )
				AND ! in_array( $screen->post_type, $elm['show_for_post_types'] )
			) {
				vc_remove_element( $shortcode );
			}

			if (
				! empty( $elm['hide_for_post_ids'] )
				AND ! empty( $_GET['post'] )
				AND in_array( $_GET['post'], $elm['hide_for_post_ids'] )
			) {
				vc_remove_element( $shortcode );
			}
		}
	}
}

/**
 * Disable WPBakery Frontend editor, when Live Editor is enabled
 */
if ( function_exists( 'vc_disable_frontend' ) AND us_get_option( 'live_builder' ) ) {
	vc_disable_frontend();
}

/**
 * Remove disabled WPB shortcodes
 */
if ( ! function_exists( 'us_vc_disable_extra_sc' ) ) {
	function us_vc_disable_extra_sc() {
		$disabled_shortcodes = us_config( 'shortcodes.disabled', array() );

		foreach ( $disabled_shortcodes as $shortcode ) {
			remove_shortcode( $shortcode );
		}
	}
}

// Disable redirect to VC welcome page
remove_action( 'init', 'vc_page_welcome_redirect' );

add_action( 'after_setup_theme', 'us_vc_init_vendor_woocommerce', 99 );
function us_vc_init_vendor_woocommerce() {
	remove_action( 'wp_enqueue_scripts', 'vc_woocommerce_add_to_cart_script' );
}

if ( ! function_exists( 'us_VC_fixPContent' ) ) {
	add_filter( 'us_page_block_the_content', 'us_VC_fixPContent', 11 );
	add_filter( 'us_content_template_the_content', 'us_VC_fixPContent', 11 );
	add_filter( 'the_content', 'us_VC_fixPContent', 11 );
	/**
	 * This is an adjusted copy of Vc_Base->fixPContent function.
	 * Original filter hook with this function usage is removed, since we need to make changes
	 * to the function and use it even if the WPBakery is not active
	 *
	 * Remove unwanted wrapping with p tags for content
	 * Also add WPBakery wrapper div for WPBakery live builder
	 *
	 * @param string|NULL $content The content
	 * @return mixed
	 */
	function us_VC_fixPContent( $content = NULL ) {
		if ( $content ) {
			$patterns = array(
				'/' . preg_quote( '</div>', '/' ) . '[\s\n\f]*' . preg_quote( '</p>', '/' ) . '/i',
				'/' . preg_quote( '<p>', '/' ) . '[\s\n\f]*' . preg_quote( '<div ', '/' ) . '/i',
				'/' . preg_quote( '<p>', '/' ) . '[\s\n\f]*' . preg_quote( '<section ', '/' ) . '/i',
				'/' . preg_quote( '</section>', '/' ) . '[\s\n\f]*' . preg_quote( '</p>', '/' ) . '/i',
			);
			$replacements = array(
				'</div>',
				'<div ',
				'<section ',
				'</section>',
			);
			$content = preg_replace( $patterns, $replacements, $content );

			// if content contains vc_row for a page view or
			// vc_welcome for a frontend editor
			// then wrap with '<div>'
			if (
				function_exists( 'vc_is_page_editable' )
				AND vc_is_page_editable()
				AND (
					preg_match( '/vc_row/', $content )
					OR preg_match( '/vc_welcome/', $content )
				)
			) {
				$content = '<div class="wpb-content-wrapper">' . $content . '</div>';
			}

			return $content;
		}

		return NULL;
	}
}

// Hide activation notice
add_action( 'admin_notices', 'us_wpb_hide_activation_notice', 100 );
function us_wpb_hide_activation_notice() {
	?>
	<script>
		( function( $ ) {
			var setCookie = function( c_name, value, exdays ) {
				var exdate = new Date();
				exdate.setDate( exdate.getDate() + exdays );
				var c_value = encodeURIComponent( value ) + ( ( null === exdays ) ? "" : "; expires=" + exdate.toUTCString() );
				document.cookie = c_name + "=" + c_value;
			};
			setCookie( 'vchideactivationmsg_vc11', '100', 30 );
			$( '#vc_license-activation-notice' ).remove();
		} )( window.jQuery );
	</script>
	<?php
}

// Set Backend Editor as default for post types
if ( function_exists( 'vc_set_default_editor_post_types' ) ) {
	$post_types_list = array(
		'page',
		'us_portfolio',
		'us_page_block',
		'us_content_template',
	);
	vc_set_default_editor_post_types( $post_types_list );
}

// Remove Backend Editor for Headers & Grid Layouts
add_filter( 'vc_settings_exclude_post_type', 'us_vc_settings_exclude_post_type' );
function us_vc_settings_exclude_post_type( $types ) {
	return array( 'us_header', 'us_grid_layout' );
}

add_filter( 'vc_is_valid_post_type_be', 'us_vc_is_valid_post_type_be', 10, 2 );
function us_vc_is_valid_post_type_be( $result, $type ) {
	if ( in_array( $type, array( 'us_header', 'us_grid_layout' ) ) ) {
		$result = FALSE;
	}

	return $result;
}

// For a text field of `us_text` will replace all hyphenation with tags for correct display in the edit field
if ( ! function_exists( 'us_vc_form_fields_render_field_us_text_text_param_value' ) ) {
	add_filter( 'vc_form_fields_render_field_us_text_text_param_value', 'us_vc_form_fields_render_field_us_text_text_param_value', 10, 1 );
	function us_vc_form_fields_render_field_us_text_text_param_value( $value ) {
		return nl2br( $value );
	}
}

add_action( 'current_screen', 'us_vc_header_check_post_type_validation_fix' );
function us_vc_header_check_post_type_validation_fix( $current_screen ) {
	global $pagenow;
	if ( $pagenow == 'post.php' AND $current_screen->post_type == 'us_header' ) {
		add_filter( 'vc_check_post_type_validation', '__return_false', 12 );
	}
}

if ( ! function_exists( 'us_vc_edit_form_enqueue_script' ) ) {
	add_filter( 'vc_edit_form_enqueue_script', 'us_vc_edit_form_enqueue_script', 1, 1 );
	/**
	 * Enqueue js scripts for attributes types.
	 *
	 * @param array $scripts The scripts.
	 * @return array Returns an array of enqueue scripts.
	 */
	function us_vc_edit_form_enqueue_script( array $scripts ) {

		// Add script for compatibility and support for USOF in WPBakery Page Builder.
		$script_url = '/plugins-support/js_composer/js/usof_compatibility.js';
		if ( file_exists( US_CORE_DIR . $script_url ) ) {
			array_unshift( $scripts, US_CORE_URI . $script_url . '?ver=' . US_CORE_VERSION );
		}

		return $scripts;
	}
}

if ( ! function_exists( 'us_vc_field' ) ) {

	$usof_field_types = array(
		'us_autocomplete',
		'us_checkboxes',
		'us_color',
		'us_design_options',
		'us_icon',
		'us_imgradio',
		'us_link',
		'us_radio',
		'us_select',
		'us_slider',
		'us_switch',
		'us_text',
		'us_textarea',
		'us_message',
		'us_hidden',
		'us_upload',
	);
	foreach( $usof_field_types as $field_type ) {
		vc_add_shortcode_param( $field_type, 'us_vc_field', /* script_url */NULL );
	}

	/**
	 * Single handler for usof fields
	 *
	 * @param array $vc_field The field settings from Visual Composer
	 * @param mixed $value The value
	 * @return string
	 */
	function us_vc_field( $vc_field, $value ) {
		if ( ! $param_name = us_arr_path( $vc_field, 'param_name' ) ) {
			return;
		}

		// The usof.field options
		$field = array(
			'classes' => '',
			'dynamic_values' => us_arr_path( $vc_field, 'dynamic_values' ),
			'std' => us_arr_path( $vc_field, 'std', '' ),
			'us_vc_field' => TRUE, // field used in Visual Composer
		);

		// Get field type
		$field['type'] = $type = us_arr_path( $vc_field, 'type', /* default */'text' );

		// Removing a prefix
		$prefix = 'us_';
		if ( strpos( $type, $prefix ) === 0 ) {
			$field['type'] = substr( $type, strlen( $prefix ) );
		}

		// Get title (used only for `us_*` controls and important for responsive params)
		if ( $title = us_arr_path( $vc_field, 'title' ) ) {
			$field['title'] = (string) $title;
		}

		if ( $description = (string) us_arr_path( $vc_field, '_description' ) ) {
			$field['description'] = $description;
		}

		// Get specific parameters for the switch field type
		if ( $type == 'us_switch' ) {
			if ( $switch_text = us_arr_path( $vc_field, 'switch_text' ) ) {
				$field['switch_text'] = (string) $switch_text;
			}
		}

		// Get classes
		if (
			$classes = us_arr_path( $vc_field, 'edit_field_class', '' )
			AND $classes = preg_replace( '/(\s?vc_col-[a-z]+-[0-9\/]+)/', '' , $classes )
		) {
			$field['classes'] = ' ' . (string) $classes;
		}

		// Get a list of parameters, for example, for select or radio
		if ( $options = us_arr_path( $vc_field, 'options' ) ) {
			$field['options'] = (array) $options;
		}

		// List of fields for groups
		if ( $params = us_arr_path( $vc_field, 'params' ) ) {
			$field['params'] = (array) $params;
		}

		// Data transfer for AJAX requests
		if ( $ajax_data = us_arr_path( $vc_field, 'ajax_data' ) ) {
			$field['ajax_data'] = (array) $ajax_data;
		}

		// Support setting parameter of different devices
		if ( $is_responsive = us_arr_path( $vc_field, 'is_responsive' ) ) {
			$field['is_responsive'] = (bool) $is_responsive;
		}

		// Get group name
		if ( $group = us_arr_path( $vc_field, 'group' ) ) {
			$field['group'] = (string) $group;
		}

		// Path to preview files
		if ( $type == 'us_imgradio' ) {
			$field['preview_path'] = (string) us_arr_path( $vc_field, 'preview_path' );
		}

		// Specific params that are used only in this type of field
		if ( $is_sortable = us_arr_path( $vc_field, 'is_sortable' ) ) {
			$field['is_sortable'] = (bool) $is_sortable;
		}
		if ( $is_multiple = us_arr_path( $vc_field, 'is_multiple' ) ) {
			$field['is_multiple'] = (bool) $is_multiple;
		}

		// End-to-end settings for example for Ajax requests
		if ( $settings = us_arr_path( $vc_field, 'settings' ) ) {
			$field['settings'] = (array) $settings;
		}

		// Get specific parameters for `us_color`
		if ( $type == 'us_color' ) {
			if ( $clear_pos = us_arr_path( $vc_field, 'clear_pos' ) ) {
				$field['clear_pos'] = (string) $clear_pos;
			}
			$field['with_gradient'] = us_arr_path( $vc_field, 'with_gradient', /* default */TRUE );
			$field['exclude_dynamic_colors'] = us_arr_path( $vc_field, 'exclude_dynamic_colors', /* default */'' );
		}

		// Specific setting for `us_radio`
		if ( $labels_as_icons = us_arr_path( $vc_field, 'labels_as_icons' ) ) {
			$field['labels_as_icons'] = (string) $labels_as_icons;
		}

		// Set the name of the related field
		if ( $options_filtered_by_param = us_arr_path( $vc_field, 'options_filtered_by_param' ) ) {
			$field['options_filtered_by_param'] = $options_filtered_by_param;
		}

		// Generate unique value when the switch is on
		if ( $type == 'us_hidden' AND isset( $vc_field['auto_generate_value_by_switch_on'] ) ) {
			$field['auto_generate_value_by_switch_on'] = (string) us_arr_path( $vc_field, 'auto_generate_value_by_switch_on' );
		}

		// Cleaning a line from unnecessary spaces
		$field['classes'] = trim( (string) $field['classes'] );

		// Context param states which builder is it
		$context = 'vc_shortcode';

		$usof_options = array(
			'context' => $context,
			'field' => $field,
			'id' => $context . '_' . $field[ 'type' ] . '_' . $param_name,
			'name' => $param_name,
			'values' => array(
				$param_name => $value,
			),
			// 'show_field' => '',
		);

		// Returns the html code of the usofField
		return us_get_template( 'usof/templates/field', $usof_options );
	}
}

if ( ! function_exists( 'us_vc_backend_scripts' ) ) {
	add_action( 'admin_enqueue_scripts', 'us_vc_backend_scripts' );
	/**
	 * Registers the backend scripts for functional extension of WPBakery Page Builder.
	 */
	function us_vc_backend_scripts() {
		global $pagenow;
		if (
			! in_array( $pagenow, array( 'post.php', 'post-new.php' ) )
			OR in_array( get_current_screen()->post_type, array( 'us_header', 'us_grid_layout' ) )
		) {
			return;
		}

		// Registers the backend scripts
		$src = US_CORE_URI . '/plugins-support/js_composer/js/us_vc_backend_scripts.js';
		wp_enqueue_script( 'us_vc_backend_scripts', $src, array( 'jquery' ), US_CORE_VERSION, /* footer */TRUE );
	}
}

if ( wp_doing_ajax() ) {
	// AJAX request handler import data for shortcode
	// TODO: do we need this in Live Builder?
	add_action( 'wp_ajax_us_import_shortcode_data', 'us_wpb_ajax_import_shortcode_data' );
	if ( ! function_exists( 'us_wpb_ajax_import_shortcode_data' ) ) {
		function us_wpb_ajax_import_shortcode_data() {
			if ( ! check_ajax_referer( 'us_ajax_import_shortcode_data', '_nonce', FALSE ) ) {
				wp_send_json_error(
					array(
						'message' => us_translate( 'An error has occurred. Please reload the page and try again.' ),
					)
				);
				wp_die();
			}

			// The response data
			wp_send_json_success( us_import_grid_layout(
				us_arr_path( $_POST, 'post_content' ),
				us_arr_path( $_POST, 'post_type', /* default */'us_grid_layout' )
			) );
		}
	}
}

// Add image preview for Image shortcode
if ( ! class_exists( 'WPBakeryShortCode_us_image' ) ) {
	class WPBakeryShortCode_us_image extends WPBakeryShortCode {
		public function singleParamHtmlHolder( $param, $value ) {
			$output = '';
			// Compatibility fixes
			$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';
			$type = isset( $param['type'] ) ? $param['type'] : '';
			$class = isset( $param['class'] ) ? $param['class'] : '';

			if ( $type == 'us_upload' AND $param_name == 'image' ) {
				$hidden_atts = array(
					'type' => 'hidden',
					'class' => implode( ' ', array( 'wpb_vc_param_value', $param_name, 'attach_image', $class ) ),
					'name' => $param_name,
					'value' => $value,
				);
				$output .= '<input ' . us_implode_atts( $hidden_atts ) . ' />';
				$element_icon = $this->settings( 'icon' );
				$img = wpb_getImageBySize(
					array(
						'attach_id' => (int) preg_replace( '/[^\d]/', '', $value ),
						'thumb_size' => 'thumbnail',
					)
				);
				$logo_html = '';
				if ( $img ) {
					$logo_html .= $img['thumbnail'];
				} else {
					$logo_html .= '<img width="150" height="150" class="attachment-thumbnail icon-wpb-single-image vc_element-icon" data-name="' . $param_name . '" alt="' . $param_name . '" style="display: none;" />';
				}
				$logo_html .= '<span class="no_image_image vc_element-icon' . ( ! empty( $element_icon ) ? ' ' . $element_icon : '' ) . ( $img && ! empty( $img['p_img_large'][0] ) ? ' image-exists' : '' ) . '" />';
				$this->setSettings( 'logo', $logo_html );
				$output .= $this->outputTitleTrue( $this->settings['name'] );

			} elseif ( ! empty( $param['holder'] ) ) {
				if ( $param['holder'] == 'input' ) {
					$output .= '<' . $param['holder'] . ' readonly="true" class="wpb_vc_param_value ' . $param_name . ' ' . $type . ' ' . $class . '" name="' . $param_name . '" value="' . $value . '">';
				} elseif ( in_array( $param['holder'], array( 'img', 'iframe' ) ) ) {
					$output .= '<' . $param['holder'] . ' class="wpb_vc_param_value ' . $param_name . ' ' . $type . ' ' . $class . '" name="' . $param_name . '" src="' . $value . '">';
				} elseif ( $param['holder'] !== 'hidden' ) {
					$output .= '<' . $param['holder'] . ' class="wpb_vc_param_value ' . $param_name . ' ' . $type . ' ' . $class . '" name="' . $param_name . '">' . $value . '</' . $param['holder'] . '>';
				}
			}
			if ( ! empty( $param['admin_label'] ) && $param['admin_label'] === TRUE ) {
				$output .= '<span class="vc_admin_label admin_label_' . $param['param_name'] . ( empty( $value ) ? ' hidden-label' : '' ) . '"><label>' . __( $param['heading'], 'js_composer' ) . '</label>: ' . $value . '</span>'; // TODO: gettext function won't work with variables
			}

			return $output;
		}

		protected function outputTitle( $title ) {
			return '';
		}

		protected function outputTitleTrue( $title ) {
			return '<h4 class="wpb_element_title">' . __( $title, 'us' ) . ' ' . $this->settings( 'logo' ) . '</h4>';
		}
	}
}

// Add column UX behavior for specific shortcodes
if ( ! class_exists( 'WPBakeryShortCode_us_content_carousel' ) ) {
	class WPBakeryShortCode_us_content_carousel extends WPBakeryShortCodesContainer {
	}
}
if ( ! class_exists( 'WPBakeryShortCode_us_hwrapper' ) ) {
	class WPBakeryShortCode_us_hwrapper extends WPBakeryShortCodesContainer {
	}
}
if ( ! class_exists( 'WPBakeryShortCode_us_vwrapper' ) ) {
	class WPBakeryShortCode_us_vwrapper extends WPBakeryShortCodesContainer {
	}
}

// Image source is a required parameter for get thumbnails for WPBakery since version 8.3.
if ( ! function_exists( 'us_vc_element_settings_filter' ) ) {
	add_filter( 'vc_element_settings_filter', 'us_vc_element_settings_filter', 501, 2 );
	function us_vc_element_settings_filter( $settings, $shortcode ) {

		if ( $shortcode == 'us_image' AND version_compare( WPB_VC_VERSION, '8.3', '>=' ) ) {
			$settings['params'][] = array(
				'type' => 'us_hidden',
				'param_name' => 'source',
				'std' => 'media_library', // possible values: "media_library|external_link|featured_image"
			);
		}

		return $settings;
	}
}

// Add "Paste Copied Section" feature
add_filter( 'vc_nav_controls', 'us_vc_nav_controls_add_paste_section_btn' );
add_action( 'admin_footer-post.php', 'us_vc_add_paste_section_html' );
add_action( 'admin_footer-post-new.php', 'us_vc_add_paste_section_html' );

// "Paste Copied Section" button
function us_vc_nav_controls_add_paste_section_btn( $control_list ) {
	$control_list[] = array(
		'paste_section',
		'<li><a href="javascript:void(0);" class="vc_icon-btn for_us_paste_section"><span>' . strip_tags( __( 'Paste Row/Section', 'us' ) ) . '</span></a></li>',
	);

	return $control_list;
}

// "Paste Copied Section" window
function us_vc_add_paste_section_html() {
	$data = array(
		'placeholder' => us_get_img_placeholder( 'full', TRUE ),
		'grid_post_types' => us_grid_available_post_types_for_import(),
		'post_type' => get_post_type(),
		'errors' => array(
			'empty' => us_translate( 'Invalid value.' ),
			'not_valid' => us_translate( 'Invalid value.' ),
		),
	);
	?>
	<div class="us-paste-section-window" style="display: none;" <?= us_pass_data_to_js( $data ) ?>
		 data-nonce="<?= wp_create_nonce( 'us_ajax_import_shortcode_data' ) ?>">
		<div class="vc_ui-panel-window-inner">
			<div class="vc_ui-panel-header-container">
				<div class="vc_ui-panel-header">
					<h3 class="vc_ui-panel-header-heading"><?= strip_tags( __( 'Paste Row/Section', 'us' ) ) ?></h3>
					<button type="button" class="vc_general vc_ui-control-button vc_ui-close-button" data-vc-ui-element="button-close">
						<i class="vc-composer-icon vc-c-icon-close"></i>
					</button>
				</div>
			</div>
			<div class="vc_ui-panel-content-container">
				<div class="vc_ui-panel-content vc_properties-list vc_edit_form_elements wpb_edit_form_elements">
					<div class="vc_column">
						<div class="edit_form_line">
							<textarea class="wpb_vc_param_value textarea_raw_html"></textarea>
							<span class="vc_description"><?= us_translate( 'Invalid value.' ) ?></span>
						</div>
					</div>
					<div class="vc_general vc_ui-button vc_ui-button-action vc_ui-button-shape-rounded">
						<?= strip_tags( __( 'Append Section', 'us' ) ) ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

if ( ! function_exists( '_usb_post_custom_css' ) ) {
	/**
	 * Filter for checking the presence of custom styles on the Visual Composer side.
	 *
	 * @param string|NULL $post_custom_css The post custom css.
	 * @param int $post_id The post ID.
	 * @return string Return the styles found, if any.
	 */
	function _usb_post_custom_css( $post_custom_css, $post_id ) {
		if ( empty( $post_custom_css ) AND is_numeric( $post_id ) ) {
			// Get custom css by meta keys from Visual Composer
			foreach ( array( '_wpb_post_custom_css', 'vc_post_custom_css' ) as $meta_key ) {
				if ( $post_custom_css = get_post_meta( $post_id, $meta_key, /* single */TRUE ) ) {
					update_post_meta( $post_id, usb_get_key_custom_css(), $post_custom_css );
					break;
				}
			}
		}
		return $post_custom_css;
	}
	add_filter( 'usb_post_custom_css', '_usb_post_custom_css', 1, 2 );
}

if ( ! function_exists( 'us_vc_post_custom_layout_name' ) ) {
	/**
	 * Always disable the "blank" custom layout from WPBakery 7.0
	 *
	 * @return string
	 */
	function us_vc_post_custom_layout_name( $layout_name ) {
		return 'default';
	}
	add_filter( 'vc_post_custom_layout_name', 'us_vc_post_custom_layout_name' );
}

if ( ! function_exists( 'us_vc_single_param_edit_holder_output' ) ) {
	add_filter( 'vc_single_param_edit_holder_output', 'us_vc_single_param_edit_holder_output', 501, 2 );
	/**
	 * Replaces output for wrappers.
	 *
	 * @param string $output The output.
	 * @param array $param The param.
	 * @return string Returns output for wrappers or params.
	 */
	function us_vc_single_param_edit_holder_output( $output, $param ) {
		$type = (string) us_arr_path( $param, 'type' );
		if ( $type == 'us_wrapper_start' OR $type == 'us_wrapper_end' ) {
			// Hidden field allows you to avoid adding param to the shortcode.
			$input_atts = array(
				'class' => 'wpb_vc_param_value',
				'name' => (string) $param['param_name'],
				'type' => 'hidden',
			);
			$input = '<input '. us_implode_atts( $input_atts ) .'>';
		}
		if ( $type == 'us_wrapper_start' AND preg_match( '/^<([^\>]+)>/', $output, $matches ) ) {
			return $matches[0] . $input;
		}
		if ( $type == 'us_wrapper_end' ) {
			return  $input . '</div>'; // .vc_column
		}
		return $output;
	}
}

if ( ! function_exists( 'us_output_wpbakery_page_template_custom_js_footer' ) AND class_exists( 'Vc_Custom_Js_Module' ) ) {
	add_action( 'wp_print_footer_scripts', 'us_output_wpbakery_page_template_custom_js_footer', 91 );
	/**
	 * Output custom wpbakery JS from Page Settings in Page Template before </body>
	 */
	function us_output_wpbakery_page_template_custom_js_footer () {
		$page_area_id = us_get_page_area_id( 'content' );
		if ( ! $page_area_id ) {
			return;
		}
		$post_footer_js = get_post_meta( $page_area_id, '_wpb_post_custom_js_footer', TRUE );
		if ( empty( $post_footer_js ) ) {
			return;
		}
		$vc_custom_js_module = new Vc_Custom_Js_Module();
		$vc_custom_js_module->output_custom_js( $post_footer_js, 'footer' );
	}
}

if ( ! function_exists( 'us_output_wpbakery_page_template_custom_js_header' ) AND class_exists( 'Vc_Custom_Js_Module' ) ) {
	add_filter( 'print_head_scripts', 'us_output_wpbakery_page_template_custom_js_header', 91, 1 );
	/**
	 * Output custom wpbakery JS from Page Settings in Page Template before </head>
	 * @param bool $is_print
	 * @return bool
	 */
	function us_output_wpbakery_page_template_custom_js_header ( $is_print ) {
		if ( is_admin() ) {
			return $is_print;
		}
		$page_area_id = us_get_page_area_id( 'content' );
		if ( ! $page_area_id ) {
			return $is_print;
		}
		$post_header_js = get_post_meta( $page_area_id, '_wpb_post_custom_js_header', TRUE );
		if ( empty( $post_header_js ) ) {
			return $is_print;
		}
		$vc_custom_js_module = new Vc_Custom_Js_Module();
		$vc_custom_js_module->output_custom_js( $post_header_js, 'header' );
		return $is_print;
	}
}
