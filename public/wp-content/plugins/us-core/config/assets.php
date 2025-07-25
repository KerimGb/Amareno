<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Assets configuration (JS and CSS components)
 *
 * @filter us_config_assets
 *
 */

//
// Example config for auto asset optimization
//
//array(
//	'component_name' => array(
//		'title' => 'Component name',
//		'css' => 'file path',
//		'js' => ' file path',
//		...
//		/**
//		 * Structure function for checking dependencies
//		 */
//		'auto_optimize_callback' => array(
//			/**
//			 * Checking dependency on a shortcode or its attribute
//			 *
//			 * @param string $shortcode_name
//			 * @param array $atts
//			 * @param WP_Post $post
//			 * @return bool
//			 */
//			'shortcodes' => function( $shortcode_name, $atts, $post ) {
//				return FALSE;
//			}
//			/**
//			 * Header or grid layout check
//			 * NOTE: The function will be called only if the header or grid layout is used on the site.
//			 *
//			 * @param string $element_name
//			 * @param array $atts
//			 * @param WP_Post $post
//			 * @return bool
//			 */
//			'headers_or_grid_layouts' => function( $element_name, $atts, $post ) {
//				return FALSE;
//			}
//			/**
//			 * Check theme settings
//			 * NOTE: Always executed first and only once!
//			 *
//			 * @param string $options
//			 * @return bool
//			 */
//			'theme_options' => function( $options ) {
//				return FALSE;
//			},
//			/**
//			 * Check sidebars and widgets
//			 * NOTE: Widgets that are marked as inactive are excluded from the checking
//			 *
//			 * @param string $widget_name
//			 * @param array $atts
//			 * @param integer $widget_id
//			 * @return bool
//			 */
//			'sidebars_widgets' => function( $widget_name, $atts, $widget_id ) {
//				return FALSE;
//			},
//		),
//		/**
//		 * List of assets that will be enabled with the current asset
//		 * @var array | string
//		 */
//		'dependencies' => array( 'asset', 'asset1'... ),
//	),
//);

return array(

	// Global library that is used at all levels of the site
	'us-helper' => array(
		'js' => '/../../plugins/us-core/assets/js/us-helper.js',
		'include_if' => TRUE, // asset always is added to generated file
		'include_first' => TRUE, // asset is added to the top of generated file
	),
	'general' => array(
		'css' => '/common/css/base/_general.css',
		'js' => '/common/js/base/_general.js',
		'include_if' => TRUE,
		'include_first' => TRUE,
	),
	'font-awesome' => array(
		'title' => sprintf( __( '"%s" all icons', 'us' ), 'Font Awesome' ),
		'css' => '/common/css/base/fontawesome.css',
		'search_icons' => TRUE, // is used for search icons in the file `admin/functions/used-icons.php`
	),
	'font-awesome-duotone' => array(
		'title' => sprintf( __( '"%s" all icons', 'us' ), 'Font Awesome Duotone' ),
		'css' => '/common/css/base/fontawesome-duotone.css',
		'search_icons' => TRUE,
	),
	'font-awesome-in-use' => array(
		'title' => sprintf( '<a href="#icons">' . __( '"%s" used icons', 'us' ) . '</a>', 'Font Awesome' ),
		'auto_optimize_callback' => array(
			'shortcodes' => function () {
				global $_us_used_icons;
				return ! empty( $_us_used_icons );
			},
		)
	),

	'actionbox' => array(
		'title' => __( 'ActionBox', 'us' ), // if a title is not set, the asset won't be visible in UI
		'css' => '/common/css/elements/actionbox.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_cta' ) !== FALSE;
			},
		),
		/**
		 * @var array | string
		 */
		'dependencies' => 'buttons',
	),
	'animation' => array(
		'title' => __( 'Animation', 'us' ),
		'css' => '/common/css/base/animation.css',
		'js' => '/common/js/base/animation.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function( $shortcode_name, $atts, $post ) {
				if ( strpos( $post->post_content, '%22animation-name%22%3A' ) !== FALSE ) {
					return TRUE;
				}
				if (
					$shortcode_name === 'us_grid'
					AND ! empty( $atts['load_animation'] )
					AND $atts['load_animation'] !== 'none'
				) {
					return TRUE;
				}
				return FALSE;
			},
		),
	),
	'buttons' => array(
		'title' => __( 'Button', 'us' ),
		'css' => '/common/css/elements/buttons.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if ( class_exists( 'woocommerce' ) ) {
					return TRUE;
				}
				if (
					strpos( $post->post_content, '[us_btn' ) !== FALSE
					OR strpos( $post->post_content, '[us_cta' ) !== FALSE
					OR strpos( $post->post_content, '[us_cform' ) !== FALSE
					OR strpos( $post->post_content, '[us_pricing' ) !== FALSE
					OR strpos( $post->post_content, '[us_cart_totals' ) !== FALSE
				) {
					return TRUE;
				}
				if (
					$shortcode_name === 'us_popup'
					AND ! isset( $atts['show_on'] )
				) {
					return TRUE;
				}
				if (
					in_array( $shortcode_name, array( 'us_grid', 'us_carousel' ) )
					AND ! empty( $atts['pagination'] )
					AND $atts['pagination'] === 'ajax'
				) {
					return TRUE;
				}
				if (
					$shortcode_name === 'us_flipbox'
					AND ! empty( $atts['link_type'] )
					AND $atts['link_type'] === 'btn'
				) {
					return TRUE;
				}
				if (
					$shortcode_name === 'us_post_taxonomy'
					AND ! empty( $atts['style'] )
					AND $atts['style'] === 'badge'
				) {
					return TRUE;
				}

				return FALSE;
			},
			/**
			 * @return bool
			 */
			'theme_options' => function ( $options ) {
				if ( class_exists( 'woocommerce' ) ) {
					return TRUE;
				}

				return ! empty( $options['cookie_notice'] );
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return $element_name === 'btn';
			},
		),
	),
	'carousel' => array(
		'title' => __( 'Carousel', 'us' ),
		'css' => '/common/css/elements/carousel.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_carousel' ) !== FALSE;
			},
		),
		/**
		 * @var array | string
		 */
		'dependencies' => array( 'animation', 'grid' ),
	),
	'content_carousel' => array(
		'title' => __( 'Content Carousel', 'us' ),
		'js' => '/common/js/elements/content-carousel.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_content_carousel' ) !== FALSE;
			},
		),
		/**
		 * @var array | string
		 */
		'dependencies' => array( 'carousel' ),
	),
	'charts' => array(
		'title' => __( 'Charts', 'us' ),
		'css' => '/common/css/elements/charts.css',
		'js' => '/common/js/elements/charts.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[vc_line_chart' ) !== FALSE
					OR strpos( $post->post_content, '[vc_round_chart' ) !== FALSE
				);
			},
		),
	),
	'color_scheme_switch' => array(
		'title' => __( 'Color Scheme Switch', 'us' ),
		'css' => '/common/css/elements/color-scheme-switch.css',
		// 'js' => '/common/js/elements/color-scheme-switch.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_color_scheme_switch' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'color_scheme_switch'
				);
			},
		),
	),
	'columns' => array(
		'title' => us_translate( 'Columns' ),
		'css' => '/common/css/base/columns.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[vc_column' ) !== FALSE
					OR strpos( $post->post_content, '[vc_inner_column' ) !== FALSE
				);
			},
		),
	),
	'comments' => array(
		'title' => us_translate( 'Comments' ),
		'css' => '/common/css/elements/comments.css',
		'js' => '/common/js/elements/comments.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if ( $shortcode_name === 'us_post_comments' AND ! isset( $atts['layout'] ) ) {
					return TRUE;
				}

				return FALSE;
			},
			/**
			 * @return bool
			 */
			'theme_options' => function( $options ) {
				// Check the inclusion of comments and the availability of posts
				return (
					get_option( 'default_comment_status', 'open' ) == 'open'
					AND wp_count_posts()->publish
				);
			}
		),
		/**
		 * @var array | string
		 */
		'dependencies' => 'forms',
	),
	'contacts' => array(
		'title' => us_translate( 'Contact Info' ),
		'css' => '/common/css/elements/contacts.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_contacts' ) !== FALSE;
			},
		),
	),
	'counter' => array(
		'title' => __( 'Counter', 'us' ),
		'css' => '/common/css/elements/counter.css',
		'js' => '/common/js/elements/counter.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_counter' ) !== FALSE;
			},
		),
		'dependencies' => 'scroll',
	),
	'dropdown' => array(
		'title' => __( 'Dropdown', 'us' ),
		'css' => '/common/css/elements/dropdown.css',
		'js' => '/common/js/elements/dropdown.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'dropdown'
				);
			},
		),
	),
	'forms' => array(
		'title' => __( 'Forms', 'us' ),
		'css' => '/common/css/base/forms.css',
		'js' => '/common/js/base/forms.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[us_cform' ) !== FALSE
					OR strpos( $post->post_content, '[us_checkout_billing' ) !== FALSE
					OR strpos( $post->post_content, '[contact-form-7' ) !== FALSE
				);
			},
		),
		/**
		 * @var array | string
		 */
		'dependencies' => 'buttons',
	),
	'flipbox' => array(
		'title' => __( 'FlipBox', 'us' ),
		'css' => '/common/css/elements/flipbox.css',
		'js' => '/common/js/elements/flipbox.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_flipbox' ) !== FALSE;
			},
		),
	),
	'gallery' => array(
		'title' => us_translate( 'Gallery' ),
		'css' => '/common/css/elements/gallery.css',
		'js' => '/common/js/elements/gallery.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[gallery' ) !== FALSE
					OR strpos( $post->post_content, '[us_gallery' ) !== FALSE
				);
			},
			/**
			 * @return bool
			 */
			'sidebars_widgets' => function ( $widget_name, $atts, $widget_id ) {
				return $widget_name === 'media_gallery';
			},
		),
		'dependencies' => 'magnific_popup',
	),
	'gmaps' => array(
		'title' => sprintf( __( '%s Maps', 'us' ), 'Google' ),
		'css' => '/common/css/elements/maps.css',
		'js' => '/common/js/elements/gmaps.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				// Make sure that attribute isn't set to catch Google provider
				if ( $shortcode_name === 'us_gmaps' ) {
					return empty( $atts['provider'] );
				}

				return FALSE;
			},
		),
	),
	'grid' => array(
		'title' => __( 'Grid', 'us' ),
		'css' => '/common/css/elements/grid.css',
		'js' => '/common/js/elements/grid.js', // TODO: do not include for [us_post_list], [us_product_list], [us_user_list]
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[us_grid' ) !== FALSE
					OR strpos( $post->post_content, '[us_carousel' ) !== FALSE
					OR strpos( $post->post_content, '[us_user_list' ) !== FALSE
				);
			},
		),
		/**
		 * @var array | string
		 */
		'dependencies' => array( 'hwrapper', 'vwrapper', 'post_elements' ),
	),
	'grid_filter' => array(
		'title' => __( 'Grid Filter', 'us' ),
		'css' => '/common/css/elements/grid-filter.css',
		'js' => '/common/js/elements/grid-filter.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if ( $shortcode_name === 'us_grid' ) {
					return strpos( implode( ' ', array_keys( $atts ) ), 'filter_' ) !== FALSE;
				}

				return $shortcode_name === 'us_grid_filter';
			},
		),
		'dependencies' => 'forms',
	),
	'grid_order' => array(
		'title' => __( 'Grid Order', 'us' ),
		'css' => '/common/css/elements/grid-order.css',
		'js' => '/common/js/elements/grid-order.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return $shortcode_name === 'us_grid_order';
			},
		),
	),
	'grid_templates' => array(
		'title' => __( 'Grid Layout Templates', 'us' ),
		'css' => '/common/css/elements/grid-templates.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if (
					in_array( $shortcode_name, array( 'us_carousel', 'us_gallery', 'us_grid', 'us_post_list' ) )
					AND ! empty( $atts['items_layout'] )
					AND in_array(
						$atts['items_layout'], array(
							'testimonial_6',
							'portfolio_1',
							'portfolio_12',
							'portfolio_15',
							'portfolio_16',
						)
					)
				) {
					return TRUE;
				}

				return FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_grid_layout'
					AND strpos( $post->post_content, '"grid_corner_image"') !== FALSE
				);
			},
		),
	),
	'grid_pagination' => array(
		'title' => __( 'Grid Pagination', 'us' ),
		'css' => '/common/css/elements/grid-pagination.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if (
					in_array( $shortcode_name, array( 'us_gallery', 'us_grid', 'us_post_list', 'us_product_list' ) )
					AND ! empty( $atts['pagination'] )
					AND $atts['pagination'] !== 'none'
				) {
					return TRUE;
				}

				return FALSE;
			},
		),
	),
	'grid_popup' => array(
		'title' => __( 'Grid Popup', 'us' ),
		'css' => '/common/css/elements/grid-popup.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if (
					in_array( $shortcode_name, array( 'us_carousel', 'us_gallery', 'us_grid', 'us_post_list', 'us_product_list' ) )
					AND ! empty( $atts['overriding_link'] )
					AND strpos( $atts['overriding_link'], 'popup_post' ) !== FALSE
				) {
					return TRUE;
				}

				return FALSE;
			},
		),
		/**
		 * @var array | string
		 */
		'dependencies' => 'magnific_popup',
	),
	'header' => array(
		'title' => _x( 'Header', 'site top area', 'us' ),
		'css' => '/common/css/base/header.css',
		'js' => '/common/js/base/header.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				// It makes no sense to check something, since the call to this function will only be if the header is used on the site.
				return $post->post_type === 'us_header';
			},
		),
	),
	'hor_parallax' => array(
		'title' => __( 'Horizontal Parallax', 'us' ),
		'js' => '/common/js/base/parallax-hor.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, 'us_bg_parallax="horizontal"' ) !== FALSE;
			},
		),
	),
	'hwrapper' => array(
		'title' => __( 'Horizontal Wrapper', 'us' ),
		'css' => '/common/css/elements/hwrapper.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_hwrapper' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return $element_name === 'hwrapper';
			},
		),
	),
	'iconbox' => array(
		'title' => __( 'IconBox', 'us' ),
		'css' => '/common/css/elements/iconbox.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_iconbox' ) !== FALSE;
			},
		),
	),
	'image' => array(
		'title' => us_translate( 'Image' ),
		'css' => '/common/css/elements/image.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_image' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return $element_name === 'image';
			},
		),
	),
	'image_slider' => array(
		'title' => __( 'Image Slider', 'us' ),
		'css' => '/common/css/elements/image-slider.css',
		'js' => '/common/js/elements/image-slider.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if ( strpos( $post->post_content, '[us_image_slider' ) !== FALSE ) {
					return TRUE;
				}
				if ( strpos( $post->post_content, 'us_bg_show="img_slider"' ) !== FALSE ) {
					return TRUE;
				}
				if ( get_post_format( $post->ID ) === 'gallery' ) {
					return TRUE;
				}

				return FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_grid_layout'
					AND (
						$element_name === 'image_slider'
						OR strpos( $post->post_content, '"media_preview":"1"') !== FALSE
					)
				);
			}
		),
		/**
		 * NOTE: required for `fadeOut` animation used in image slider
		 */
		'dependencies' => 'animation',
	),
	'ibanner' => array(
		'title' => __( 'Interactive Banner', 'us' ),
		'css' => '/common/css/elements/ibanner.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_ibanner' ) !== FALSE;
			},
		),
	),
	'itext' => array(
		'title' => __( 'Interactive Text', 'us' ),
		'css' => '/common/css/elements/itext.css',
		'js' => '/common/js/elements/itext.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_itext' ) !== FALSE;
			},
		),
		/**
		 * NOTE: required for fadeIn and zoomIn animations used in itext
		 */
		'dependencies' => 'animation',
	),
	'login' => array(
		'title' => __( 'Login', 'us' ),
		'css' => '/common/css/elements/login.css',
		'js' => '/common/js/elements/login.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_login' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'sidebars_widgets' => function ( $widget_name, $atts, $widget_id ) {
				return $widget_name === 'us_login';
			},
			/**
			 * NOTE: required form styles for correct appearance
			 */
			'dependencies' => 'forms',
		),
	),
	'magnific_popup' => array(
		'title' => __( 'Popup styles', 'us' ),
		'css' => '/common/css/base/magnific-popup.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if (
					in_array( $shortcode_name, array( 'us_grid', 'us_carousel' ) )
					AND ! empty( $atts['overriding_link'] )
					AND is_string( $atts['overriding_link'] )
					AND strpos( $atts['overriding_link'], 'popup_' ) !== FALSE // matches 'popup_post', 'popup_post_image', 'popup_image'
				) {
					return TRUE;
				}

				// Check the Link of all shortocodes
				if (
					! empty( $atts['link'] )
					AND is_string( $atts['link'] )
					AND strpos( $atts['link'], 'popup_' ) !== FALSE
				) {
					return TRUE;
				}

				return FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {

				// Check the Link of all elements
				if (
					! empty( $atts['link'] )
					AND is_string( $atts['link'] )
					AND strpos( $atts['link'], 'popup_' ) !== FALSE
				) {
					return TRUE;
				}

				return FALSE;
			},
		),
	),
	'menu' => array(
		'title' => us_translate( 'Menu' ),
		'css' => '/common/css/elements/menu.css',
		'js' => '/common/js/elements/menu.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'menu'
				);
			},
		),
	),
	'message' => array(
		'title' => __( 'Message Box', 'us' ),
		'css' => '/common/css/elements/message.css',
		'js' => '/common/js/elements/message.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_message' ) !== FALSE;
			},
		),
	),
	'lmaps' => array(
		'title' => sprintf( __( '%s Maps', 'us' ), 'OpenStreetMap' ),
		'css' => '/common/css/vendor/leaflet.css',
		'js' => '/common/js/elements/lmaps.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				if ( $shortcode_name === 'us_gmaps' ) {
					return ! empty( $atts['provider'] ) AND $atts['provider'] === 'osm';
				}

				return FALSE;
			},
		),
	),
	'scroller' => array(
		'title' => __( 'Page Scroller', 'us' ),
		'css' => '/common/css/elements/page-scroller.css',
		'js' => '/common/js/elements/page-scroller.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_scroller' ) !== FALSE;
			},
		),
	),
	'person' => array(
		'title' => __( 'Person', 'us' ),
		'css' => '/common/css/elements/person.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_person' ) !== FALSE;
			},
		),
	),
	'preloader' => array(
		'title' => __( 'Preloader', 'us' ),
		'css' => '/common/css/base/preloader.css',
		'js' => '/common/js/base/preloader.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'theme_options' => function ( $options ) {
				return ! empty( $options['preloader'] ) AND $options['preloader'] !== 'disabled';
			},
		),
	),
	'print' => array(
		'title' => __( 'Print styles', 'us' ),
		'css' => '/common/css/base/print.css',
	),
	'popup' => array(
		'title' => __( 'Popup', 'us' ),
		'css' => '/common/css/elements/popup.css',
		'js' => '/common/js/elements/popup.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_popup' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'popup'
				);
			},
		),
	),
	'post_elements' => array(
		'title' => __( 'Post Elements', 'us' ),
		'css' => '/common/css/elements/post-elements.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[us_post_title' ) !== FALSE
					OR strpos( $post->post_content, '[us_post_image' ) !== FALSE
					OR strpos( $post->post_content, '[us_post_date' ) !== FALSE
					OR strpos( $post->post_content, '[us_post_taxonomy' ) !== FALSE
					OR strpos( $post->post_content, '[us_post_custom_field' ) !== FALSE
					OR strpos( $post->post_content, '[us_post_author' ) !== FALSE
					OR strpos( $post->post_content, '[us_post_comments' ) !== FALSE
				);
			},
		),
	),
	'post_list' => array(
		'title' => __( 'Post List', 'us' ),
		'js' => '/common/js/elements/post-list.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[us_post_list' ) !== FALSE
					OR strpos( $post->post_content, '[us_product_list' ) !== FALSE
				);
			},
		),
		'dependencies' => 'grid',
	),
	'list_search' => array(
		'title' => __( 'List Search', 'us' ),
		'js' => '/common/js/elements/list-search.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_list_search' ) !== FALSE;
			},
		),
		'dependencies' => 'search',
	),
	'list_order' => array(
		'title' => __( 'List Order', 'us' ),
		'js' => '/common/js/elements/list-order.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_list_order' ) !== FALSE;
			},
		),
		'dependencies' => 'grid_order',
	),
	'list_filter' => array(
		'title' => __( 'List Filter', 'us' ),
		'js' => '/common/js/elements/list-filter.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_list_filter' ) !== FALSE;
			},
		),
		'dependencies' => 'grid_filter',
	),
	'post_navigation' => array(
		'title' => __( 'Post Prev/Next Navigation', 'us' ),
		'css' => '/common/css/elements/post-navigation.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_post_navigation' ) !== FALSE;
			},
		),
	),
	'pricing' => array(
		'title' => __( 'Pricing Table', 'us' ),
		'css' => '/common/css/elements/pricing.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_pricing' ) !== FALSE;
			},
		),
	),
	'progbar' => array(
		'title' => __( 'Progress Bar', 'us' ),
		'css' => '/common/css/elements/progbar.css',
		'js' => '/common/js/elements/progbar.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_progbar' ) !== FALSE;
			},
		),
	),
	'scroll' => array(
		'title' => __( 'Scroll events', 'us' ),
		'js' => '/common/js/base/scroll.js',
		'include_first' => TRUE,
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {

				// Force this asset enabled by default after using "Auto Optimize"
				return TRUE;
			},
		),
	),
	'scroll-effects' => array(
		'title' => __( 'Scrolling Effects', 'us' ),
		'css' => '/common/css/base/scroll-effects.css',
		'js' => '/common/js/base/scroll-effects.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, 'scroll_effect="1"' ) !== FALSE;
			},
		),
		'dependencies' => 'general',
	),
	'search' => array(
		'title' => us_translate( 'Search' ),
		'css' => '/common/css/elements/search.css',
		'js' => '/common/js/elements/search.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[vc_wp_search' ) !== FALSE
					OR strpos( $post->post_content, '[us_search' ) !== FALSE
				);
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'search'
				);
			},
			/**
			 * @return bool
			 */
			'sidebars_widgets' => function ( $widget_name, $atts, $widget_id ) {
				return $widget_name === 'search';
			},
		),
		'dependencies' => 'buttons',
	),
	'separator' => array(
		'title' => __( 'Separator', 'us' ),
		'css' => '/common/css/elements/separator.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_separator' ) !== FALSE;
			},
		),
	),
	'sharing' => array(
		'title' => __( 'Sharing Buttons', 'us' ),
		'css' => '/common/css/elements/sharing.css',
		'js' => '/common/js/elements/sharing.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_sharing' ) !== FALSE;
			},
		),
	),
	'simple_menu' => array(
		'title' => __( 'Simple Menu', 'us' ),
		'css' => '/common/css/elements/simple-menu.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[us_additional_menu' ) !== FALSE
					OR strpos( $post->post_content, '[us_wc_account_navigation' ) !== FALSE
				);
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'additional_menu'
				);
			},
		),
	),
	'socials' => array(
		'title' => __( 'Social Links', 'us' ),
		'css' => '/common/css/elements/socials.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_socials' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_header'
					AND $element_name === 'socials'
				);
			},
			/**
			 * @return bool
			 */
			'sidebars_widgets' => function ( $widget_name, $atts, $widget_id ) {
				return $widget_name === 'us_socials';
			},
		),
	),
	'tabs' => array(
		'title' => __( 'Tabs', 'us' ) . ', ' . __( 'Vertical Tabs', 'us' ) . ', ' . us_translate( 'Accordion', 'js_composer' ),
		'css' => '/common/css/elements/tabs.css',
		'js' => '/common/js/elements/tabs.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[vc_tta_accordion' ) !== FALSE
					OR strpos( $post->post_content, '[vc_tta_tour' ) !== FALSE
					OR strpos( $post->post_content, '[vc_tta_tabs' ) !== FALSE
				);
			},
		),
	),
	'text' => array(
		'title' => us_translate( 'Text' ),
		'css' => '/common/css/elements/text.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_text' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return $element_name === 'text';
			},
		),
	),
	'video' => array(
		'title' => __( 'Video Player', 'us' ),
		'css' => '/common/css/elements/video.css',
		'js' => '/common/js/elements/video.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content, '[vc_video' ) !== FALSE
					OR get_post_format( $post->ID ) === 'video'
				);
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function( $element_name, $atts, $post ) {
				return (
					$post->post_type === 'us_grid_layout'
					AND strpos( $post->post_content, '"media_preview":"1"') !== FALSE
				);
			},
		),
	),
	'ver_parallax' => array(
		'title' => __( 'Vertical Parallax', 'us' ),
		'js' => '/common/js/base/parallax-ver.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, 'us_bg_parallax="vertical"' ) !== FALSE;
			},
		),
	),
	'vwrapper' => array(
		'title' => __( 'Vertical Wrapper', 'us' ),
		'css' => '/common/css/elements/vwrapper.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_vwrapper' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return $element_name === 'vwrapper';
			},
		),
	),
	'wp_widgets' => array(
		'title' => us_translate( 'Widgets' ),
		'css' => '/common/css/elements/wp-widgets.css',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return (
					strpos( $post->post_content , '[vc_widget_sidebar' ) !== FALSE
					// Search for next occurrences: vc_wp_meta, vc_wp_recentcomments, vc_wp_calendar, vc_wp_pages,
					// vc_wp_tagcloud, vc_wp_custommenu, vc_wp_categories, vc_wp_posts, vc_wp_archives, vc_wp_rss
					OR strpos( $post->post_content, '[vc_wp_' ) !== FALSE
				);
			},
			/**
			 * @return bool
			 */
			'sidebars_widgets' => function ( $widget_name, $atts, $widget_id ) {
				return TRUE;
			},
		),
	),
	'add_to_favs' => array(
		'title' => sprintf( __( '"%s" Button', 'us' ), __( 'Add to Favorites', 'us' ) ),
		'css' => '/common/css/elements/add-to-favs.css',
		'js' => '/common/js/elements/add-to-favs.js',
		'auto_optimize_callback' => array(
			/**
			 * @return bool
			 */
			'shortcodes' => function ( $shortcode_name, $atts, $post ) {
				return strpos( $post->post_content, '[us_add_to_favs' ) !== FALSE;
			},
			/**
			 * @return bool
			 */
			'headers_or_grid_layouts' => function ( $element_name, $atts, $post ) {
				return ( $element_name === 'add_to_favs' OR $element_name === 'favs_counter' );
			},
		),
		'dependencies' => 'buttons',
	),

	// Plugins
	'gravityforms' => array(
		'css' => '/common/css/plugins/gravityforms.css',
		'minify_separately' => TRUE, // component will be minified into a separate file via "US Minify" plugin
		'include_if' => class_exists( 'GFForms' ),
	),
	'tribe-events' => array(
		'css' => '/common/css/plugins/tribe-events.css',
		'minify_separately' => TRUE,
		'include_if' => class_exists( 'Tribe__Events__Main' ),
	),
	'ultimate-addons' => array(
		'css' => '/common/css/plugins/ultimate-addons.css',
		'js' => '/common/js/plugins/ultimate-addons.js',
		'include_if' => class_exists( 'Ultimate_VC_Addons' ),
	),
	'bbpress' => array(
		'css' => '/common/css/plugins/bbpress.css',
		'minify_separately' => TRUE,
		'include_if' => class_exists( 'bbPress' ),
	),
	'tablepress' => array(
		'css' => '/common/css/plugins/tablepress.css',
		'include_if' => class_exists( 'TablePress' ),
	),
	'woocommerce' => array(
		'css' => '/common/css/plugins/woocommerce.css',
		'js' => '/common/js/plugins/woocommerce.js',
		'minify_separately' => TRUE,
		'include_if' => class_exists( 'woocommerce' ),
	),
	'woocommerce-multi-currency' => array(
		'css' => '/common/css/plugins/us-multi-currency.css',
		'minify_separately' => TRUE,
		'include_if' => class_exists( 'WOOMULTI_CURRENCY' ),
	),
	'wpml' => array(
		'css' => '/common/css/plugins/wpml.css',
		'include_if' => class_exists( 'SitePress' ),
	),

	// Theme Customs
	'theme_options' => array(
		'css' => '/css/custom.css',
		'include_if' => TRUE,
	),
);
