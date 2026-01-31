<?php
/**
 * Custom Post Types Registration Class
 *
 * Handles registration of Custom Post Types for plugins and themes.
 *
 * This class registers two CPTs:
 * - wpinsight_plugin: Stores WordPress.org plugin metadata
 * - wpinsight_theme: Stores WordPress.org theme metadata
 *
 * Each CPT post represents one plugin/theme from WordPress.org. The post title
 * is the display name, post_name is the slug, and post_meta stores all metadata
 * from the WordPress.org API (versions, ratings, downloads, etc.).
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Includes
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Types registration class.
 *
 * This class uses static methods only and is never instantiated.
 * It registers CPTs for storing WordPress.org plugin and theme metadata.
 *
 * @since 0.1.0
 */
final class WPInsight_CPT {

	/**
	 * Plugin CPT name.
	 *
	 * @since 0.1.0
	 * @var string PLUGIN_POST_TYPE Post type name for plugins.
	 */
	private const PLUGIN_POST_TYPE = 'wpinsight_plugin';

	/**
	 * Theme CPT name.
	 *
	 * @since 0.1.0
	 * @var string THEME_POST_TYPE Post type name for themes.
	 */
	private const THEME_POST_TYPE = 'wpinsight_theme';

	/**
	 * Register all Custom Post Types.
	 *
	 * This method is called on the 'init' hook. It registers both the plugin
	 * and theme CPTs with WordPress.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		self::register_plugin_cpt();
		self::register_theme_cpt();
	}

	/**
	 * Get plugin post type name.
	 *
	 * Returns the post type name used for WordPress.org plugins.
	 *
	 * @since 0.1.0
	 * @return string Plugin post type name ('wpinsight_plugin').
	 */
	public static function get_plugin_post_type(): string {
		return self::PLUGIN_POST_TYPE;
	}

	/**
	 * Get theme post type name.
	 *
	 * Returns the post type name used for WordPress.org themes.
	 *
	 * @since 0.1.0
	 * @return string Theme post type name ('wpinsight_theme').
	 */
	public static function get_theme_post_type(): string {
		return self::THEME_POST_TYPE;
	}

	/**
	 * Register plugin Custom Post Type.
	 *
	 * Registers the 'wpinsight_plugin' CPT for storing WordPress.org plugin data.
	 *
	 * Configuration:
	 * - Not public (no frontend display)
	 * - Shows in admin UI
	 * - Supports title and custom fields
	 * - No archive or single pages
	 * - No rewrite rules needed
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function register_plugin_cpt(): void {
		$labels = array(
			'name'                  => _x( 'Plugins', 'Post type general name', 'cloudfest-wporgdownload' ),
			'singular_name'         => _x( 'Plugin', 'Post type singular name', 'cloudfest-wporgdownload' ),
			'menu_name'             => _x( 'WP.org Plugins', 'Admin Menu text', 'cloudfest-wporgdownload' ),
			'name_admin_bar'        => _x( 'Plugin', 'Add New on Toolbar', 'cloudfest-wporgdownload' ),
			'add_new'               => __( 'Add New', 'cloudfest-wporgdownload' ),
			'add_new_item'          => __( 'Add New Plugin', 'cloudfest-wporgdownload' ),
			'new_item'              => __( 'New Plugin', 'cloudfest-wporgdownload' ),
			'edit_item'             => __( 'Edit Plugin', 'cloudfest-wporgdownload' ),
			'view_item'             => __( 'View Plugin', 'cloudfest-wporgdownload' ),
			'all_items'             => __( 'All Plugins', 'cloudfest-wporgdownload' ),
			'search_items'          => __( 'Search Plugins', 'cloudfest-wporgdownload' ),
			'not_found'             => __( 'No plugins found.', 'cloudfest-wporgdownload' ),
			'not_found_in_trash'    => __( 'No plugins found in Trash.', 'cloudfest-wporgdownload' ),
			'filter_items_list'     => _x( 'Filter plugins list', 'Screen reader text', 'cloudfest-wporgdownload' ),
			'items_list_navigation' => _x( 'Plugins list navigation', 'Screen reader text', 'cloudfest-wporgdownload' ),
			'items_list'            => _x( 'Plugins list', 'Screen reader text', 'cloudfest-wporgdownload' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'WordPress.org plugins from the plugin repository', 'cloudfest-wporgdownload' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position'      => 58, // Below Plugins menu.
			'menu_icon'          => 'dashicons-admin-plugins',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( self::PLUGIN_POST_TYPE, $args );
	}

	/**
	 * Register theme Custom Post Type.
	 *
	 * Registers the 'wpinsight_theme' CPT for storing WordPress.org theme data.
	 *
	 * Configuration:
	 * - Not public (no frontend display)
	 * - Shows in admin UI
	 * - Supports title and custom fields
	 * - No archive or single pages
	 * - No rewrite rules needed
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function register_theme_cpt(): void {
		$labels = array(
			'name'                  => _x( 'Themes', 'Post type general name', 'cloudfest-wporgdownload' ),
			'singular_name'         => _x( 'Theme', 'Post type singular name', 'cloudfest-wporgdownload' ),
			'menu_name'             => _x( 'WP.org Themes', 'Admin Menu text', 'cloudfest-wporgdownload' ),
			'name_admin_bar'        => _x( 'Theme', 'Add New on Toolbar', 'cloudfest-wporgdownload' ),
			'add_new'               => __( 'Add New', 'cloudfest-wporgdownload' ),
			'add_new_item'          => __( 'Add New Theme', 'cloudfest-wporgdownload' ),
			'new_item'              => __( 'New Theme', 'cloudfest-wporgdownload' ),
			'edit_item'             => __( 'Edit Theme', 'cloudfest-wporgdownload' ),
			'view_item'             => __( 'View Theme', 'cloudfest-wporgdownload' ),
			'all_items'             => __( 'All Themes', 'cloudfest-wporgdownload' ),
			'search_items'          => __( 'Search Themes', 'cloudfest-wporgdownload' ),
			'not_found'             => __( 'No themes found.', 'cloudfest-wporgdownload' ),
			'not_found_in_trash'    => __( 'No themes found in Trash.', 'cloudfest-wporgdownload' ),
			'filter_items_list'     => _x( 'Filter themes list', 'Screen reader text', 'cloudfest-wporgdownload' ),
			'items_list_navigation' => _x( 'Themes list navigation', 'Screen reader text', 'cloudfest-wporgdownload' ),
			'items_list'            => _x( 'Themes list', 'Screen reader text', 'cloudfest-wporgdownload' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'WordPress.org themes from the theme repository', 'cloudfest-wporgdownload' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position'      => 59, // Below WP.org Plugins menu.
			'menu_icon'          => 'dashicons-admin-appearance',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( self::THEME_POST_TYPE, $args );
	}
}
