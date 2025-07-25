<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: grid_order
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

// Get sorting options for grid config
$orderby_values = (array) us_grid_get_orderby_options();
if ( isset( $orderby_values['post__in'] ) ) {
	unset( $orderby_values['post__in'] );
}

/**
 * @return array
 */
return array(
	'title' => __( 'Grid Order', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'fas fa-sort-amount-down',
	'params' => us_set_params_weight(

		// General section
		array(
			'orderby_items' => array(
				'title' => __( 'Order by', 'us' ),
				'type' => 'group',
				'show_controls' => TRUE,
				'is_sortable' => TRUE,
				'is_accordion' => TRUE,
				'accordion_title' => 'value',
				'params' => array(
					'value' => array(
						'type' => 'select',
						'options' => $orderby_values,
						'std' => 'date',
						'admin_label' => TRUE,
					),
					'custom_field' => array(
						'description' => __( 'Enter custom field name to order items by its value', 'us' ),
						'type' => 'text',
						'std' => '',
						'placeholder' => 'my_custom_field',
						'classes' => 'for_above',
						'admin_label' => TRUE,
						'show_if' => array( 'value', '=', 'custom' ),
					),
					'custom_field_numeric' => array(
						'type' => 'switch',
						'switch_text' => __( 'Order by numeric values', 'us' ),
						'std' => 0,
						'classes' => 'for_above',
						'show_if' => array( 'value', '=', 'custom' ),
					),
					'invert' => array(
						'type' => 'switch',
						'switch_text' => __( 'Invert order', 'us' ),
						'std' => 0,
						'classes' => 'for_above',
					),
					'label' => array(
						'title' => us_translate( 'Title' ),
						'description' => __( 'Leave blank to use the default.', 'us' ),
						'type' => 'text',
						'std' => '',
						'admin_label' => TRUE,
					),
				),
				'std' => array(
					array(
						'value' => 'date',
						'custom_field' => '',
						'custom_field_numeric' => 0,
						'invert' => 0,
						'label' => '',
					),
				),
				'usb_preview' => TRUE,
			),
		),

		// Appearance section
		array(
			'first_label' => array(
				'title' => __( 'First Value Label', 'us' ),
				'type' => 'text',
				'std' => us_translate( 'Default' ),
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'elm' => '.w-order-select > select > option:first-child',
					'attr' => 'html',
				),
			),
			'text_before' => array(
				'title' => __( 'Text before dropdown', 'us' ),
				'type' => 'text',
				'std' => '',
				'admin_label' => TRUE,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					array(
						'elm' => '.w-order-label',
						'toggle_class_inverse' => 'hidden',
					),
					array(
						'elm' => '.w-order-label',
						'attr' => 'text',
					),
				),
			),
			'width_full' => array(
				'switch_text' => __( 'Stretch to the full width', 'us' ),
				'type' => 'switch',
				'std' => 0,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'toggle_class' => 'width_full',
				),
			),
			'us_field_style' => array(
				'title' => __( 'Field Style', 'us' ),
				'description' => $misc['desc_field_styles'],
				'type' => 'select',
				'options' => us_get_field_styles(),
				'std' => 'default',
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'mod' => 'us-field-style',
				),
			),
		),

		$conditional_params,
		$design_options_params
	),

	'usb_init_js' => '$elm.wGridOrder()',
);
