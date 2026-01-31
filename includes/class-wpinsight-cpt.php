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
		$labels = [
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
		];

		$args = [
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
			'supports'           => [ 'title', 'custom-fields' ],
			'show_in_rest'       => false,
		];

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
		$labels = [
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
		];

		$args = [
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
			'supports'           => [ 'title', 'custom-fields' ],
			'show_in_rest'       => false,
		];

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
			[
				'post_type'      => self::PLUGIN_POST_TYPE,
				'name'           => $slug,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			]
		);

		if ( ! empty( $existing ) ) {
			return $existing[0];
		}

		// Create new post.
		$post_id = wp_insert_post(
			[
				'post_type'   => self::PLUGIN_POST_TYPE,
				'post_title'  => $name,
				'post_name'   => $slug,
				'post_status' => 'publish',
			]
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
			[
				'post_type'      => self::THEME_POST_TYPE,
				'name'           => $slug,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			]
		);

		if ( ! empty( $existing ) ) {
			return $existing[0];
		}

		// Create new post.
		$post_id = wp_insert_post(
			[
				'post_type'   => self::THEME_POST_TYPE,
				'post_title'  => $name,
				'post_name'   => $slug,
				'post_status' => 'publish',
			]
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
	 * @param int   $post_id Plugin post ID.
	 * @param array $data    Plugin data from WordPress.org API.
	 * @return bool True on success, false on failure.
	 */
	public static function save_plugin_meta( int $post_id, array $data ): bool {
		if ( empty( $post_id ) || empty( $data ) ) {
			return false;
		}

		// Map of API field => meta key.
		$meta_map = [
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
		];

		// Save scalar fields.
		foreach ( $meta_map as $api_field => $meta_key ) {
			if ( isset( $data[ $api_field ] ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $api_field ] );
			}
		}

		// Save array fields (serialized).
		$array_fields = [
			'sections' => '_wpinsight_sections',
			'tags'     => '_wpinsight_tags',
			'versions' => '_wpinsight_versions',
			'banners'  => '_wpinsight_banners',
			'icons'    => '_wpinsight_icons',
		];

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
	 * @param int   $post_id Theme post ID.
	 * @param array $data    Theme data from WordPress.org API.
	 * @return bool True on success, false on failure.
	 */
	public static function save_theme_meta( int $post_id, array $data ): bool {
		if ( empty( $post_id ) || empty( $data ) ) {
			return false;
		}

		// Map of API field => meta key.
		$meta_map = [
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
		];

		// Save scalar fields.
		foreach ( $meta_map as $api_field => $meta_key ) {
			if ( isset( $data[ $api_field ] ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $api_field ] );
			}
		}

		// Save array fields (serialized).
		$array_fields = [
			'tags'           => '_wpinsight_tags',
			'versions'       => '_wpinsight_versions',
			'screenshot_url' => '_wpinsight_screenshot_url',
		];

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
			return [];
		}

		$meta_keys = [
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
		];

		$meta = [];
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
			return [];
		}

		$meta_keys = [
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
		];

		$meta = [];
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
		add_filter( 'manage_' . self::PLUGIN_POST_TYPE . '_posts_columns', [ __CLASS__, 'plugin_columns' ] );
		add_action( 'manage_' . self::PLUGIN_POST_TYPE . '_posts_custom_column', [ __CLASS__, 'plugin_column_content' ], 10, 2 );

		// Theme columns.
		add_filter( 'manage_' . self::THEME_POST_TYPE . '_posts_columns', [ __CLASS__, 'theme_columns' ] );
		add_action( 'manage_' . self::THEME_POST_TYPE . '_posts_custom_column', [ __CLASS__, 'theme_column_content' ], 10, 2 );
	}

	/**
	 * Customize plugin admin columns.
	 *
	 * Modifies the columns displayed in the plugin CPT admin list table.
	 * Adds custom columns for slug, version, downloads, rating, etc.
	 *
	 * @since 0.1.0
	 * @param array $columns Default columns.
	 * @return array Modified columns.
	 */
	public static function plugin_columns( array $columns ): array {
		// Remove default columns we don't need.
		unset( $columns['date'] );

		// Build new column structure.
		$new_columns = [
			'cb'              => $columns['cb'], // Checkbox.
			'title'           => $columns['title'], // Title.
			'slug'            => __( 'Slug', 'cloudfest-wporgdownload' ),
			'version'         => __( 'Version', 'cloudfest-wporgdownload' ),
			'author'          => __( 'Author', 'cloudfest-wporgdownload' ),
			'downloads'       => __( 'Downloads', 'cloudfest-wporgdownload' ),
			'active_installs' => __( 'Active Installs', 'cloudfest-wporgdownload' ),
			'rating'          => __( 'Rating', 'cloudfest-wporgdownload' ),
			'last_updated'    => __( 'Last Updated', 'cloudfest-wporgdownload' ),
		];

		return $new_columns;
	}

	/**
	 * Customize theme admin columns.
	 *
	 * Modifies the columns displayed in the theme CPT admin list table.
	 * Adds custom columns for slug, version, downloads, rating, etc.
	 *
	 * @since 0.1.0
	 * @param array $columns Default columns.
	 * @return array Modified columns.
	 */
	public static function theme_columns( array $columns ): array {
		// Remove default columns we don't need.
		unset( $columns['date'] );

		// Build new column structure.
		$new_columns = [
			'cb'           => $columns['cb'], // Checkbox.
			'title'        => $columns['title'], // Title.
			'slug'         => __( 'Slug', 'cloudfest-wporgdownload' ),
			'version'      => __( 'Version', 'cloudfest-wporgdownload' ),
			'author'       => __( 'Author', 'cloudfest-wporgdownload' ),
			'downloads'    => __( 'Downloads', 'cloudfest-wporgdownload' ),
			'rating'       => __( 'Rating', 'cloudfest-wporgdownload' ),
			'last_updated' => __( 'Last Updated', 'cloudfest-wporgdownload' ),
		];

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
}
