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
		self::setup_admin_columns();
		self::setup_meta_boxes();
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
			'menu_position'      => 26, // Below Comments menu.
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
			'menu_position'      => 27, // Below WP.org Plugins menu.
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

	/**
	 * Find or create a plugin post by slug.
	 *
	 * Searches for an existing plugin post with the given slug (post_name).
	 * If not found, creates a new post with the slug and name.
	 *
	 * @since 0.1.0
	 * @param string $slug Plugin slug from WordPress.org.
	 * @param string $name Plugin display name.
	 * @return int Post ID of found or created plugin post.
	 */
	public static function find_or_create_plugin( string $slug, string $name ): int {
		// Try to find existing post by slug (post_name).
		$existing = get_posts(
			array(
				'post_type'      => self::PLUGIN_POST_TYPE,
				'name'           => $slug,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			)
		);

		if ( ! empty( $existing ) ) {
			return $existing[0];
		}

		// Create new post.
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::PLUGIN_POST_TYPE,
				'post_title'  => $name,
				'post_name'   => $slug,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		return $post_id;
	}

	/**
	 * Find or create a theme post by slug.
	 *
	 * Searches for an existing theme post with the given slug (post_name).
	 * If not found, creates a new post with the slug and name.
	 *
	 * @since 0.1.0
	 * @param string $slug Theme slug from WordPress.org.
	 * @param string $name Theme display name.
	 * @return int Post ID of found or created theme post.
	 */
	public static function find_or_create_theme( string $slug, string $name ): int {
		// Try to find existing post by slug (post_name).
		$existing = get_posts(
			array(
				'post_type'      => self::THEME_POST_TYPE,
				'name'           => $slug,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			)
		);

		if ( ! empty( $existing ) ) {
			return $existing[0];
		}

		// Create new post.
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::THEME_POST_TYPE,
				'post_title'  => $name,
				'post_name'   => $slug,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		return $post_id;
	}

	/**
	 * Save plugin metadata to post meta.
	 *
	 * Takes data from WordPress.org API response and saves it as post meta.
	 * All meta keys are prefixed with _wpinsight_ and are private (start with _).
	 *
	 * @since 0.1.0
	 * @param int                  $post_id Plugin post ID.
	 * @param array<string, mixed> $data    Plugin data from WordPress.org API.
	 * @return bool True on success, false on failure.
	 */
	public static function save_plugin_meta( int $post_id, array $data ): bool {
		if ( empty( $post_id ) || empty( $data ) ) {
			return false;
		}

		// Map of API field => meta key.
		$meta_map = array(
			'slug'              => '_wpinsight_slug',
			'author'            => '_wpinsight_author',
			'version'           => '_wpinsight_version',
			'requires'          => '_wpinsight_requires_wp',
			'requires_php'      => '_wpinsight_requires_php',
			'rating'            => '_wpinsight_rating',
			'num_ratings'       => '_wpinsight_num_ratings',
			'active_installs'   => '_wpinsight_active_installs',
			'downloaded'        => '_wpinsight_downloaded',
			'last_updated'      => '_wpinsight_last_updated',
			'added'             => '_wpinsight_added',
			'homepage'          => '_wpinsight_homepage',
			'download_link'     => '_wpinsight_download_url',
			'short_description' => '_wpinsight_short_description',
			'description'       => '_wpinsight_description',
		);

		// Save scalar fields.
		foreach ( $meta_map as $api_field => $meta_key ) {
			if ( isset( $data[ $api_field ] ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $api_field ] );
			}
		}

		// Save array fields (serialized).
		$array_fields = array(
			'sections' => '_wpinsight_sections',
			'tags'     => '_wpinsight_tags',
			'versions' => '_wpinsight_versions',
			'banners'  => '_wpinsight_banners',
			'icons'    => '_wpinsight_icons',
		);

		foreach ( $array_fields as $api_field => $meta_key ) {
			if ( isset( $data[ $api_field ] ) && is_array( $data[ $api_field ] ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $api_field ] );
			}
		}

		return true;
	}

	/**
	 * Save theme metadata to post meta.
	 *
	 * Takes data from WordPress.org API response and saves it as post meta.
	 * All meta keys are prefixed with _wpinsight_ and are private (start with _).
	 *
	 * @since 0.1.0
	 * @param int                  $post_id Theme post ID.
	 * @param array<string, mixed> $data    Theme data from WordPress.org API.
	 * @return bool True on success, false on failure.
	 */
	public static function save_theme_meta( int $post_id, array $data ): bool {
		if ( empty( $post_id ) || empty( $data ) ) {
			return false;
		}

		// Map of API field => meta key.
		$meta_map = array(
			'slug'          => '_wpinsight_slug',
			'author'        => '_wpinsight_author',
			'version'       => '_wpinsight_version',
			'requires'      => '_wpinsight_requires_wp',
			'requires_php'  => '_wpinsight_requires_php',
			'rating'        => '_wpinsight_rating',
			'num_ratings'   => '_wpinsight_num_ratings',
			'downloaded'    => '_wpinsight_downloaded',
			'last_updated'  => '_wpinsight_last_updated',
			'homepage'      => '_wpinsight_homepage',
			'download_link' => '_wpinsight_download_url',
			'description'   => '_wpinsight_description',
		);

		// Save scalar fields.
		foreach ( $meta_map as $api_field => $meta_key ) {
			if ( isset( $data[ $api_field ] ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $api_field ] );
			}
		}

		// Save array fields (serialized).
		$array_fields = array(
			'tags'           => '_wpinsight_tags',
			'versions'       => '_wpinsight_versions',
			'screenshot_url' => '_wpinsight_screenshot_url',
		);

		foreach ( $array_fields as $api_field => $meta_key ) {
			if ( isset( $data[ $api_field ] ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $api_field ] );
			}
		}

		return true;
	}

	/**
	 * Get plugin metadata from post meta.
	 *
	 * Retrieves all plugin metadata and returns it as an associative array.
	 *
	 * @since 0.1.0
	 * @param int $post_id Plugin post ID.
	 * @return array<string, mixed> Associative array of plugin metadata.
	 */
	public static function get_plugin_meta( int $post_id ): array {
		if ( empty( $post_id ) ) {
			return array();
		}

		$meta_keys = array(
			'slug',
			'author',
			'version',
			'requires_wp',
			'requires_php',
			'rating',
			'num_ratings',
			'active_installs',
			'downloaded',
			'last_updated',
			'added',
			'homepage',
			'download_url',
			'short_description',
			'description',
			'sections',
			'tags',
			'versions',
			'banners',
			'icons',
		);

		$meta = array();
		foreach ( $meta_keys as $key ) {
			$meta_value = get_post_meta( $post_id, '_wpinsight_' . $key, true );
			if ( ! empty( $meta_value ) ) {
				$meta[ $key ] = $meta_value;
			}
		}

		return $meta;
	}

	/**
	 * Get theme metadata from post meta.
	 *
	 * Retrieves all theme metadata and returns it as an associative array.
	 *
	 * @since 0.1.0
	 * @param int $post_id Theme post ID.
	 * @return array<string, mixed> Associative array of theme metadata.
	 */
	public static function get_theme_meta( int $post_id ): array {
		if ( empty( $post_id ) ) {
			return array();
		}

		$meta_keys = array(
			'slug',
			'author',
			'version',
			'requires_wp',
			'requires_php',
			'rating',
			'num_ratings',
			'downloaded',
			'last_updated',
			'homepage',
			'download_url',
			'description',
			'tags',
			'versions',
			'screenshot_url',
		);

		$meta = array();
		foreach ( $meta_keys as $key ) {
			$meta_value = get_post_meta( $post_id, '_wpinsight_' . $key, true );
			if ( ! empty( $meta_value ) ) {
				$meta[ $key ] = $meta_value;
			}
		}

		return $meta;
	}

	/**
	 * Setup admin columns for CPTs.
	 *
	 * Hooks into WordPress filters and actions to customize the admin columns
	 * displayed in the post list tables for plugins and themes.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function setup_admin_columns(): void {
		// Plugin columns.
		add_filter( 'manage_' . self::PLUGIN_POST_TYPE . '_posts_columns', array( __CLASS__, 'plugin_columns' ) );
		add_action( 'manage_' . self::PLUGIN_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'plugin_column_content' ), 10, 2 );

		// Theme columns.
		add_filter( 'manage_' . self::THEME_POST_TYPE . '_posts_columns', array( __CLASS__, 'theme_columns' ) );
		add_action( 'manage_' . self::THEME_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'theme_column_content' ), 10, 2 );
	}

	/**
	 * Customize plugin admin columns.
	 *
	 * Modifies the columns displayed in the plugin CPT admin list table.
	 * Adds custom columns for slug, version, downloads, rating, etc.
	 *
	 * @since 0.1.0
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string> Modified columns.
	 */
	public static function plugin_columns( array $columns ): array {
		// Remove default columns we don't need.
		unset( $columns['date'] );

		// Build new column structure.
		$new_columns = array(
			'cb'              => $columns['cb'], // Checkbox.
			'title'           => $columns['title'], // Title.
			'slug'            => __( 'Slug', 'cloudfest-wporgdownload' ),
			'version'         => __( 'Version', 'cloudfest-wporgdownload' ),
			'author'          => __( 'Author', 'cloudfest-wporgdownload' ),
			'downloads'       => __( 'Downloads', 'cloudfest-wporgdownload' ),
			'active_installs' => __( 'Active Installs', 'cloudfest-wporgdownload' ),
			'rating'          => __( 'Rating', 'cloudfest-wporgdownload' ),
			'last_updated'    => __( 'Last Updated', 'cloudfest-wporgdownload' ),
		);

		return $new_columns;
	}

	/**
	 * Customize theme admin columns.
	 *
	 * Modifies the columns displayed in the theme CPT admin list table.
	 * Adds custom columns for slug, version, downloads, rating, etc.
	 *
	 * @since 0.1.0
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string> Modified columns.
	 */
	public static function theme_columns( array $columns ): array {
		// Remove default columns we don't need.
		unset( $columns['date'] );

		// Build new column structure.
		$new_columns = array(
			'cb'           => $columns['cb'], // Checkbox.
			'title'        => $columns['title'], // Title.
			'slug'         => __( 'Slug', 'cloudfest-wporgdownload' ),
			'version'      => __( 'Version', 'cloudfest-wporgdownload' ),
			'author'       => __( 'Author', 'cloudfest-wporgdownload' ),
			'downloads'    => __( 'Downloads', 'cloudfest-wporgdownload' ),
			'rating'       => __( 'Rating', 'cloudfest-wporgdownload' ),
			'last_updated' => __( 'Last Updated', 'cloudfest-wporgdownload' ),
		);

		return $new_columns;
	}

	/**
	 * Display plugin custom column content.
	 *
	 * Outputs the content for custom columns in the plugin CPT admin list table.
	 * Retrieves data from post meta and formats it for display.
	 *
	 * @since 0.1.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function plugin_column_content( string $column, int $post_id ): void {
		$meta = self::get_plugin_meta( $post_id );

		switch ( $column ) {
			case 'slug':
				echo esc_html( $meta['slug'] ?? get_post_field( 'post_name', $post_id ) );
				break;

			case 'version':
				echo esc_html( $meta['version'] ?? '—' );
				break;

			case 'author':
				echo esc_html( $meta['author'] ?? '—' );
				break;

			case 'downloads':
				$downloads = $meta['downloaded'] ?? 0;
				echo esc_html( number_format_i18n( (int) $downloads ) );
				break;

			case 'active_installs':
				$installs = $meta['active_installs'] ?? 0;
				echo esc_html( number_format_i18n( (int) $installs ) );
				break;

			case 'rating':
				$rating = $meta['rating'] ?? 0;
				if ( $rating > 0 ) {
					// Display rating as percentage with star icon.
					echo '<span class="dashicons dashicons-star-filled" style="color: #ffb900;"></span> ';
					echo esc_html( number_format_i18n( (int) $rating ) . '%' );
				} else {
					echo '—';
				}
				break;

			case 'last_updated':
				$last_updated = $meta['last_updated'] ?? '';
				if ( ! empty( $last_updated ) ) {
					// Convert to human-readable format.
					$timestamp = strtotime( $last_updated );
					if ( $timestamp ) {
						echo esc_html( human_time_diff( $timestamp, time() ) . ' ago' );
					} else {
						echo esc_html( $last_updated );
					}
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Display theme custom column content.
	 *
	 * Outputs the content for custom columns in the theme CPT admin list table.
	 * Retrieves data from post meta and formats it for display.
	 *
	 * @since 0.1.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function theme_column_content( string $column, int $post_id ): void {
		$meta = self::get_theme_meta( $post_id );

		switch ( $column ) {
			case 'slug':
				echo esc_html( $meta['slug'] ?? get_post_field( 'post_name', $post_id ) );
				break;

			case 'version':
				echo esc_html( $meta['version'] ?? '—' );
				break;

			case 'author':
				echo esc_html( $meta['author'] ?? '—' );
				break;

			case 'downloads':
				$downloads = $meta['downloaded'] ?? 0;
				echo esc_html( number_format_i18n( (int) $downloads ) );
				break;

			case 'rating':
				$rating = $meta['rating'] ?? 0;
				if ( $rating > 0 ) {
					// Display rating as percentage with star icon.
					echo '<span class="dashicons dashicons-star-filled" style="color: #ffb900;"></span> ';
					echo esc_html( number_format_i18n( (int) $rating ) . '%' );
				} else {
					echo '—';
				}
				break;

			case 'last_updated':
				$last_updated = $meta['last_updated'] ?? '';
				if ( ! empty( $last_updated ) ) {
					// Convert to human-readable format.
					$timestamp = strtotime( $last_updated );
					if ( $timestamp ) {
						echo esc_html( human_time_diff( $timestamp, time() ) . ' ago' );
					} else {
						echo esc_html( $last_updated );
					}
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Setup custom meta boxes for CPT detail views.
	 *
	 * Registers meta boxes that display plugin/theme information,
	 * ZIP downloads, and quick stats on CPT edit screens.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private static function setup_meta_boxes(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_cpt_meta_boxes' ) );
	}

	/**
	 * Add custom meta boxes to plugin and theme CPTs.
	 *
	 * Called by WordPress add_meta_boxes action hook.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function add_cpt_meta_boxes(): void {
		// Plugin Information meta box.
		add_meta_box(
			'wpinsight_plugin_info',
			__( 'Plugin Information', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_plugin_info_meta_box' ),
			self::PLUGIN_POST_TYPE,
			'normal',
			'high'
		);

		// Theme Information meta box.
		add_meta_box(
			'wpinsight_theme_info',
			__( 'Theme Information', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_theme_info_meta_box' ),
			self::THEME_POST_TYPE,
			'normal',
			'high'
		);

		// ZIP Downloads meta box (for both plugins and themes).
		add_meta_box(
			'wpinsight_zip_downloads',
			__( 'ZIP Downloads', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_zip_downloads_meta_box' ),
			array( self::PLUGIN_POST_TYPE, self::THEME_POST_TYPE ),
			'normal',
			'high'
		);

		// Quick Stats meta box (for both plugins and themes).
		add_meta_box(
			'wpinsight_quick_stats',
			__( 'Quick Stats', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_quick_stats_meta_box' ),
			array( self::PLUGIN_POST_TYPE, self::THEME_POST_TYPE ),
			'side',
			'default'
		);
	}

	/**
	 * Render Plugin Information meta box.
	 *
	 * Displays plugin metadata in read-only format.
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_plugin_info_meta_box( $post ): void {
		// Get plugin metadata.
		$description  = get_post_meta( $post->ID, 'short_description', true );
		$version      = get_post_meta( $post->ID, 'version', true );
		$author       = get_post_meta( $post->ID, 'author', true );
		$homepage     = get_post_meta( $post->ID, 'homepage', true );
		$requires     = get_post_meta( $post->ID, 'requires', true );
		$requires_php = get_post_meta( $post->ID, 'requires_php', true );
		$tested       = get_post_meta( $post->ID, 'tested', true );
		$tags         = get_post_meta( $post->ID, 'tags', true );

		?>
		<table class="form-table" role="presentation">
			<tbody>
				<?php if ( ! empty( $description ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Description', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $description ); ?></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $version ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Current Version', 'cloudfest-wporgdownload' ); ?></th>
					<td><strong><?php echo esc_html( $version ); ?></strong></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $author ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Author', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $author ); ?></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $homepage ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Homepage', 'cloudfest-wporgdownload' ); ?></th>
					<td><a href="<?php echo esc_url( $homepage ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $homepage ); ?></a></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $requires ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Requires WordPress', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $requires ); ?>+</td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $requires_php ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Requires PHP', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $requires_php ); ?>+</td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $tested ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Tested up to', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $tested ); ?></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $tags ) && is_array( $tags ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Tags', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( implode( ', ', $tags ) ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Theme Information meta box.
	 *
	 * Displays theme metadata in read-only format.
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_theme_info_meta_box( $post ): void {
		// Get theme metadata.
		$description = get_post_meta( $post->ID, 'description', true );
		$version     = get_post_meta( $post->ID, 'version', true );
		$author      = get_post_meta( $post->ID, 'author', true );
		$theme_uri   = get_post_meta( $post->ID, 'theme_uri', true );
		$tags        = get_post_meta( $post->ID, 'tags', true );

		?>
		<table class="form-table" role="presentation">
			<tbody>
				<?php if ( ! empty( $description ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Description', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $description ); ?></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $version ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Current Version', 'cloudfest-wporgdownload' ); ?></th>
					<td><strong><?php echo esc_html( $version ); ?></strong></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $author ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Author', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( $author ); ?></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $theme_uri ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Theme URI', 'cloudfest-wporgdownload' ); ?></th>
					<td><a href="<?php echo esc_url( $theme_uri ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $theme_uri ); ?></a></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $tags ) && is_array( $tags ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Tags', 'cloudfest-wporgdownload' ); ?></th>
					<td><?php echo esc_html( implode( ', ', $tags ) ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render ZIP Downloads meta box.
	 *
	 * Displays table of all versions with download status and public URLs.
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_zip_downloads_meta_box( $post ): void {
		$slug        = $post->post_name;
		$entity_type = ( self::PLUGIN_POST_TYPE === $post->post_type ) ? 'plugin' : 'theme';

		// Get all versions from post meta.
		$versions = get_post_meta( $post->ID, 'versions', true );

		if ( empty( $versions ) || ! is_array( $versions ) ) {
			echo '<p>' . esc_html__( 'No versions available.', 'cloudfest-wporgdownload' ) . '</p>';
			return;
		}

		// Get full API data for changelog.
		$sections = get_post_meta( $post->ID, 'sections', true );
		$changelog = isset( $sections['changelog'] ) ? $sections['changelog'] : '';

		// Get artifact data (downloaded ZIPs).
		$artifacts = self::get_artifact_data( $slug, $entity_type );

		// Get queue data (pending/failed downloads).
		$queue = self::get_queue_data( $slug, $entity_type );

		// Combine data.
		$version_data = array();
		foreach ( $versions as $version => $download_url ) {
			$status = 'not_queued';
			$size   = null;
			$date   = null;
			$path   = null;

			// Check if artifact exists.
			if ( isset( $artifacts[ $version ] ) ) {
				$status = 'downloaded';
				$size   = $artifacts[ $version ]['filesize'];
				$date   = $artifacts[ $version ]['downloaded_at'];
				$path   = $artifacts[ $version ]['path'];
			} elseif ( isset( $queue[ $version ] ) ) {
				// Check queue status.
				$status = $queue[ $version ]['status'];
				$date   = $queue[ $version ]['queued_at'];
			}

			$version_data[ $version ] = array(
				'status' => $status,
				'size'   => $size,
				'date'   => $date,
				'path'   => $path,
				'url'    => $download_url,
			);
		}

		// Sort by version (descending).
		uksort( $version_data, 'version_compare' );
		$version_data = array_reverse( $version_data, true );

		// Display accordion-style version history.
		?>
		<style>
			.wpinsight-version-accordion {
				margin-top: 10px;
			}
			.wpinsight-version-item {
				border: 1px solid #dcdcde;
				margin-bottom: 5px;
				background: #fff;
			}
			.wpinsight-version-header {
				padding: 12px 15px;
				cursor: pointer;
				display: flex;
				justify-content: space-between;
				align-items: center;
				background: #f6f7f7;
				transition: background-color 0.2s;
			}
			.wpinsight-version-header:hover {
				background: #f0f0f1;
			}
			.wpinsight-version-header-left {
				display: flex;
				align-items: center;
				gap: 12px;
				flex: 1;
			}
			.wpinsight-version-toggle {
				width: 20px;
				text-align: center;
				font-weight: bold;
				color: #2271b1;
			}
			.wpinsight-version-number {
				font-weight: 600;
				font-size: 14px;
				min-width: 80px;
			}
			.wpinsight-version-body {
				display: none;
				padding: 15px;
				border-top: 1px solid #dcdcde;
				background: #fff;
			}
			.wpinsight-version-body.active {
				display: block;
			}
			.wpinsight-changelog {
				margin: 10px 0;
				padding: 10px;
				background: #f9f9f9;
				border-left: 3px solid #2271b1;
				font-size: 13px;
				line-height: 1.6;
			}
			.wpinsight-changelog h4 {
				margin: 0 0 8px 0;
				font-size: 13px;
				color: #2271b1;
			}
			.wpinsight-changelog ul {
				margin: 0;
				padding-left: 20px;
			}
			.wpinsight-version-actions {
				margin-top: 12px;
				display: flex;
				gap: 8px;
			}
		</style>

		<p style="margin-bottom: 10px;">
			<strong><?php echo esc_html( sprintf( __( 'Total versions: %d', 'cloudfest-wporgdownload' ), count( $version_data ) ) ); ?></strong>
		</p>

		<div class="wpinsight-version-accordion">
			<?php
			$version_index = 0;
			foreach ( $version_data as $version => $data ) :
				$version_index++;
				$accordion_id = 'version-' . esc_attr( $slug . '-' . $version );

				// Extract changelog for this version.
				$version_changelog = self::extract_version_changelog( $changelog, $version );
				?>
				<div class="wpinsight-version-item">
					<div class="wpinsight-version-header" onclick="toggleVersionDetails('<?php echo esc_js( $accordion_id ); ?>')">
						<div class="wpinsight-version-header-left">
							<span class="wpinsight-version-toggle" id="toggle-<?php echo esc_attr( $accordion_id ); ?>">▶</span>
							<span class="wpinsight-version-number"><?php echo esc_html( $version ); ?></span>
							<?php echo wp_kses_post( self::get_status_badge( $data['status'] ) ); ?>
							<?php if ( $data['date'] ) : ?>
								<span style="color: #646970; font-size: 12px;">
									<?php echo esc_html( human_time_diff( strtotime( $data['date'] ), time() ) . ' ago' ); ?>
								</span>
							<?php endif; ?>
						</div>
						<div style="display: flex; gap: 8px; align-items: center;">
							<?php if ( $data['size'] ) : ?>
								<span style="color: #646970; font-size: 12px;">
									<?php echo esc_html( size_format( $data['size'], 2 ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<div class="wpinsight-version-body" id="<?php echo esc_attr( $accordion_id ); ?>">
						<?php if ( ! empty( $version_changelog ) ) : ?>
							<div class="wpinsight-changelog">
								<h4><?php esc_html_e( 'Changelog:', 'cloudfest-wporgdownload' ); ?></h4>
								<?php echo wp_kses_post( $version_changelog ); ?>
							</div>
						<?php else : ?>
							<div style="padding: 10px; color: #646970; font-style: italic;">
								<?php esc_html_e( 'No changelog available for this version.', 'cloudfest-wporgdownload' ); ?>
							</div>
						<?php endif; ?>

						<div class="wpinsight-version-actions">
							<?php if ( 'downloaded' === $data['status'] && $data['path'] ) : ?>
								<?php
								$public_url = self::get_public_url( $data['path'] );
								if ( $public_url ) :
									?>
									<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" class="button button-small">
										<?php esc_html_e( 'Download ZIP', 'cloudfest-wporgdownload' ); ?>
										<?php if ( $data['size'] ) : ?>
											(<?php echo esc_html( size_format( $data['size'], 2 ) ); ?>)
										<?php endif; ?>
									</a>
								<?php endif; ?>
							<?php endif; ?>

							<a href="<?php echo esc_url( 'https://wordpress.org/' . ( 'plugin' === $entity_type ? 'plugins' : 'themes' ) . '/' . $slug . '/' ); ?>"
							   target="_blank"
							   class="button button-small">
								<?php esc_html_e( 'View on WordPress.org', 'cloudfest-wporgdownload' ); ?>
							</a>

							<?php if ( 'failed' === $data['status'] ) : ?>
								<button type="button" class="button button-small" disabled>
									<?php esc_html_e( 'Retry (Not implemented)', 'cloudfest-wporgdownload' ); ?>
								</button>
							<?php elseif ( 'pending' === $data['status'] || 'queued' === $data['status'] ) : ?>
								<span style="color: #999; font-size: 12px; padding: 5px 10px;">
									<?php esc_html_e( 'In download queue...', 'cloudfest-wporgdownload' ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<script>
		function toggleVersionDetails(id) {
			var body = document.getElementById(id);
			var toggle = document.getElementById('toggle-' + id);

			if (body.classList.contains('active')) {
				body.classList.remove('active');
				toggle.textContent = '▶';
			} else {
				body.classList.add('active');
				toggle.textContent = '▼';
			}
		}
		</script>
		<?php
	}

	/**
	 * Extract changelog for a specific version.
	 *
	 * Parses the full changelog HTML and extracts content for a specific version.
	 *
	 * @since 1.5.0
	 * @param string $full_changelog Full changelog HTML from WordPress.org API.
	 * @param string $version        Version number to extract.
	 * @return string Changelog content for the version, or empty string if not found.
	 */
	private static function extract_version_changelog( string $full_changelog, string $version ): string {
		if ( empty( $full_changelog ) ) {
			return '';
		}

		// Try to find version-specific changelog.
		// WordPress.org changelogs typically use <h4>Version X.X.X</h4> format.
		$pattern = '/<h4>(?:Version\s+)?' . preg_quote( $version, '/' ) . '(?:\s+.+?)?<\/h4>(.*?)(?=<h4>|$)/is';

		if ( preg_match( $pattern, $full_changelog, $matches ) ) {
			return trim( $matches[1] );
		}

		// Fallback: Try simpler pattern.
		$pattern = '/##?\s+' . preg_quote( $version, '/' ) . '\s*(.*?)(?=##?\s+[\d\.]+|$)/is';

		if ( preg_match( $pattern, $full_changelog, $matches ) ) {
			// Convert to HTML if it's markdown-style.
			$content = trim( $matches[1] );
			$content = wpautop( $content );
			return $content;
		}

		return '';
	}

	/**
	 * Render Quick Stats meta box.
	 *
	 * Displays quick statistics from WordPress.org API data.
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_quick_stats_meta_box( $post ): void {
		// Get stats from post meta.
		$active_installs = get_post_meta( $post->ID, 'active_installs', true );
		$downloaded      = get_post_meta( $post->ID, 'downloaded', true );
		$rating          = get_post_meta( $post->ID, 'rating', true );
		$num_ratings     = get_post_meta( $post->ID, 'num_ratings', true );
		$last_updated    = get_post_meta( $post->ID, 'last_updated', true );

		?>
		<table class="form-table" role="presentation" style="margin: 0;">
			<tbody>
				<?php if ( ! empty( $active_installs ) ) : ?>
				<tr>
					<th scope="row" style="padding-left: 0;"><?php esc_html_e( 'Active Installs', 'cloudfest-wporgdownload' ); ?></th>
					<td><strong><?php echo esc_html( WPInsight_Admin::format_number_abbreviated( $active_installs ) ); ?>+</strong></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $downloaded ) ) : ?>
				<tr>
					<th scope="row" style="padding-left: 0;"><?php esc_html_e( 'Downloads', 'cloudfest-wporgdownload' ); ?></th>
					<td><strong><?php echo esc_html( WPInsight_Admin::format_number_abbreviated( $downloaded ) ); ?>+</strong></td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $rating ) && ! empty( $num_ratings ) ) : ?>
				<tr>
					<th scope="row" style="padding-left: 0;"><?php esc_html_e( 'Rating', 'cloudfest-wporgdownload' ); ?></th>
					<td>
						<?php
						$stars = round( $rating / 20 ); // Rating is 0-100, convert to 0-5.
						echo str_repeat( '★', $stars ) . str_repeat( '☆', 5 - $stars );
						?>
						(<?php echo esc_html( number_format_i18n( $rating / 20, 1 ) ); ?>)
						<br />
						<small><?php echo esc_html( sprintf( __( '%s reviews', 'cloudfest-wporgdownload' ), number_format_i18n( $num_ratings ) ) ); ?></small>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ( ! empty( $last_updated ) ) : ?>
				<tr>
					<th scope="row" style="padding-left: 0;"><?php esc_html_e( 'Last Updated', 'cloudfest-wporgdownload' ); ?></th>
					<td>
						<?php
						$timestamp = strtotime( $last_updated );
						if ( $timestamp ) {
							echo esc_html( human_time_diff( $timestamp, time() ) . ' ago' );
						} else {
							echo esc_html( $last_updated );
						}
						?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get artifact data for a slug.
	 *
	 * Queries the artifacts table for downloaded ZIPs.
	 *
	 * @since 1.2.0
	 * @param string $slug        Plugin or theme slug.
	 * @param string $entity_type Entity type ('plugin' or 'theme').
	 * @return array Array of artifacts keyed by version.
	 */
	private static function get_artifact_data( string $slug, string $entity_type ): array {
		global $wpdb;
		$table = WPInsight_DB::get_table_name( 'artifacts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT version, file_size as filesize, sha256, path, downloaded_at
				FROM %i
				WHERE slug = %s AND entity_type = %s
				ORDER BY downloaded_at DESC',
				$table,
				$slug,
				$entity_type
			),
			ARRAY_A
		);

		$artifacts = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$artifacts[ $row['version'] ] = $row;
			}
		}

		return $artifacts;
	}

	/**
	 * Get queue data for a slug.
	 *
	 * Queries the queue table for pending/failed downloads.
	 *
	 * @since 1.2.0
	 * @param string $slug        Plugin or theme slug.
	 * @param string $entity_type Entity type ('plugin' or 'theme').
	 * @return array Array of queue jobs keyed by version.
	 */
	private static function get_queue_data( string $slug, string $entity_type ): array {
		global $wpdb;
		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT version, status, queued_at, started_at, finished_at
				FROM %i
				WHERE slug = %s AND entity_type = %s
				AND status IN (%s, %s, %s, %s)
				ORDER BY queued_at DESC',
				$table,
				$slug,
				$entity_type,
				'pending',
				'queued',
				'processing',
				'failed'
			),
			ARRAY_A
		);

		$queue = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$queue[ $row['version'] ] = $row;
			}
		}

		return $queue;
	}

	/**
	 * Get status badge HTML.
	 *
	 * Returns colored badge for different statuses.
	 *
	 * @since 1.2.0
	 * @param string $status Status string.
	 * @return string HTML for status badge.
	 */
	private static function get_status_badge( string $status ): string {
		$badges = array(
			'downloaded'  => '<span style="display: inline-block; padding: 3px 8px; background: #00a32a; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600;">✓ ' . __( 'Downloaded', 'cloudfest-wporgdownload' ) . '</span>',
			'pending'     => '<span style="display: inline-block; padding: 3px 8px; background: #dba617; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600;">⏳ ' . __( 'Pending', 'cloudfest-wporgdownload' ) . '</span>',
			'queued'      => '<span style="display: inline-block; padding: 3px 8px; background: #dba617; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600;">⏳ ' . __( 'Queued', 'cloudfest-wporgdownload' ) . '</span>',
			'processing'  => '<span style="display: inline-block; padding: 3px 8px; background: #2271b1; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600;">⟳ ' . __( 'Processing', 'cloudfest-wporgdownload' ) . '</span>',
			'failed'      => '<span style="display: inline-block; padding: 3px 8px; background: #d63638; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600;">✗ ' . __( 'Failed', 'cloudfest-wporgdownload' ) . '</span>',
			'not_queued'  => '<span style="display: inline-block; padding: 3px 8px; background: #dcdcde; color: #50575e; border-radius: 3px; font-size: 11px; font-weight: 600;">—</span>',
		);

		return $badges[ $status ] ?? $badges['not_queued'];
	}

	/**
	 * Get public URL for a filesystem path.
	 *
	 * Converts absolute filesystem path to public URL.
	 *
	 * @since 1.2.0
	 * @param string $path Absolute filesystem path.
	 * @return string Public URL or empty string if invalid.
	 */
	private static function get_public_url( string $path ): string {
		// Get uploads directory info.
		$uploads = wp_upload_dir();

		// Validate path is within uploads directory.
		if ( ! str_starts_with( $path, $uploads['basedir'] ) ) {
			return '';
		}

		// Check if file exists.
		if ( ! file_exists( $path ) ) {
			return '';
		}

		// Convert path to URL.
		$relative_path = str_replace( $uploads['basedir'], '', $path );
		return $uploads['baseurl'] . $relative_path;
	}
}

