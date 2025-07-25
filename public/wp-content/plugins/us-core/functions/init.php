<?php

// Upsolution helper functions
require US_CORE_DIR . 'functions/helpers.php';
require US_CORE_DIR . 'builder/helpers.php';

add_action( 'after_setup_theme', 'uscore_after_setup_theme', 8 );
function uscore_after_setup_theme() {
	if ( ! defined( 'US_THEMENAME' ) ) {
		return;
	}

	// WP Background Process
	require US_CORE_DIR . 'vendor/wp-background-processing/wp-background-processing.php';

	// All methods that apply to Grid and Grid Filter
	require US_CORE_DIR . 'functions/grid.php';

	// Method for lists: List Search, Post List, Product List
	require US_CORE_DIR . 'functions/list.php';

	// Filter Indexer
	require US_CORE_DIR . 'admin/functions/filter-indexer.php';

	// Optimize assets
	require US_CORE_DIR . 'admin/functions/optimize-assets.php';

	// Used icons
	require US_CORE_DIR . 'admin/functions/used-icons.php';

	// UpSolution Header definitions
	require US_CORE_DIR . 'functions/header.php';

	// Post formats
	require US_CORE_DIR . 'functions/post.php';

	// Theme Options
	require US_CORE_DIR . 'functions/theme-options.php';

	// UpSolution Layout definitions
	require US_CORE_DIR . 'functions/layout.php';

	// Breadcrumbs function
	require US_CORE_DIR . 'functions/breadcrumbs.php';

	// Custom Post types
	require US_CORE_DIR . 'functions/post-types.php';

	// Page Meta Tags
	require US_CORE_DIR . 'functions/meta-tags.php';

	// Sidebars init
	require US_CORE_DIR . 'functions/widget_areas.php';

	// Header builder
	require US_CORE_DIR . 'admin/functions/header-builder.php';

	// Media Categories
	if ( us_get_option( 'media_category' ) ) {
		require US_CORE_DIR . 'functions/media.php';
	}

	// Load shortcodes
	require US_CORE_DIR . 'functions/shortcodes.php';

	// Perform migrations for compatibility with versions 6.8 and below
	require US_CORE_DIR . 'functions/migration.php';

	// Perform fallback for compatibility with old versions
	require US_CORE_DIR . 'functions/fallback.php';

	// Widgets
	require US_CORE_DIR . 'functions/widgets.php';

	// Regenerate Thumbnails
	require_once US_CORE_DIR . 'admin/functions/regenerate_thumbnails.php';

	// US Live Builder
	require US_CORE_DIR . 'builder/builder.php';

	if ( is_admin() OR ( defined( 'WP_CLI' ) AND WP_CLI ) ) {

		// Admin Enqueue
		require US_CORE_DIR . 'admin/functions/enqueue.php';

		// Grid Builder
		require US_CORE_DIR . 'admin/functions/grid-builder.php';

		// Modified Menu edit screen
		require US_CORE_DIR . 'admin/functions/nav-menu-edit.php';

		// Migration page
		require US_CORE_DIR . 'admin/functions/migration-page.php';

		// Customize TinyMCE and Gutenberg editors
		require US_CORE_DIR . 'admin/functions/customize-editors.php';

	} else {

		// Remove protocols from URLs for better compatibility with caching plugins and services if enabled
		global $us_template_directory_uri, $us_stylesheet_directory_uri;
		if ( ! us_get_option( 'keep_url_protocol', 1 ) ) {
			$us_template_directory_uri = us_remove_url_protocol( get_template_directory_uri() );
			$us_stylesheet_directory_uri = us_remove_url_protocol( get_stylesheet_directory_uri() );
		}

		// Frontent CSS and JS enqueue
		require US_CORE_DIR . 'functions/enqueue.php';

		// Cookie Notice
		require US_CORE_DIR . 'functions/cookie-notice.php';
	}

	// AJAX related functions
	if ( wp_doing_ajax() ) {
		require US_CORE_DIR . 'functions/ajax/header_builder.php';
		require US_CORE_DIR . 'functions/ajax/grid_builder.php';
		require US_CORE_DIR . 'functions/ajax/us_login.php';
		require US_CORE_DIR . 'functions/ajax/grid.php';
		require US_CORE_DIR . 'functions/ajax/cform.php';
		require US_CORE_DIR . 'functions/ajax/cart.php';
		require US_CORE_DIR . 'functions/ajax/cookie_notice.php';
		require US_CORE_DIR . 'functions/ajax/gallery.php';
		require US_CORE_DIR . 'functions/ajax/post_list.php';
		require US_CORE_DIR . 'functions/ajax/add_to_favs.php';
	}

	// Enable Text WP widget show shortcodes
	add_filter( 'widget_text', 'do_shortcode' );
}
