<?php
/*
Plugin Name: WP Scripts & Styles Optimizer
Description: Improve your site-rendering speed by customizing the output of all scripts and styles of your site
Version: 0.4.5
Author: Hendrik Lersch
Author URI: https://profiles.wordpress.org/riddler84
Text Domain: wp-script-optimizer
Domain Path: /lang
*/

if ( ! defined('ABSPATH') )
	exit;


/**
 * Plugin constants
 */

define( 'PLUGIN_VERSION', '0.4.5' );

define( 'PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

define( 'DB_VERSION', '2.5' );
define( 'WPSO_DB_TABLE', 'script_optimizer' );


/**
 * Wpso class
 *
 * @since 	0.1.0
 */

class Wpso
{
	/**
	 * Handle types for use in loops and for naming
	 *
	 * @since 	0.1.0
	 * @var 	array
	 */

	public $handle_types = array(
		'script' => 'scripts',
		'style'  => 'styles'
	);


	/**
	 * Default options
	 *
	 * @since 	0.2.2
	 * @var 	array
	 */

	public static $default_options = array();


	/**
	 * Sanitized value of $_GET['page']
	 *
	 * @since 	0.4.0
	 * @var 	string
	 */

	public static $page_handle = '';


	/**
	 * Sanitized value of $_GET['qs']
	 *
	 * @since 	0.4.0
	 * @var 	string
	 */

	public static $query_string = 'global';


	/**
	 * Constructor
	 *
	 * @since 	0.1.0
	 */

	public function __construct()
	{
		$this->add_default_options();

		if ( get_option( 'wpso_db_version' ) != DB_VERSION )
		{
			$this->update_db();
			update_option( 'wpso_db_version', DB_VERSION );
		}

		self::$page_handle = sanitize_key( $_GET['page'] );

		if ( self::$page_handle == 'wpso_single' && isset( $_GET['qs'] ) && ! empty( $_GET['qs'] ) )
		{
			self::$query_string = wp_strip_all_tags( base64_decode( $_GET['qs'] ) );
		}

		add_action( 'init', 	  	 array( $this, 'load_textdomain' 	   ) );
		add_action( 'admin_menu', 	 array( $this, 'add_admin_menus' 	   ) );
		add_action( 'admin_notices', array( $this, 'general_admin_notices' ) );

		if ( isset( $_GET['wpso'] ) && sanitize_key( $_GET['wpso'] ) == 'show_qs' )
		{
			add_action( 'wp', array( $this, 'show_query_string' ) );
		}

		if ( isset( $_GET['wpso'] ) && in_array( sanitize_key( $_GET['wpso'] ), array( 'check', 'synccheck' ) ) )
		{
			add_action( 'wp_footer', array( $this, 'store_handles' ), 9998 );
			add_action( 'wp_footer', array( $this, 'check_handles' ), 9999 );
		}
		else
		{
			add_action( 'wp_head', 	 array( 'WpsoConditions', 'process_header_items' ), 6  );
			add_action( 'wp_footer', array( 'WpsoConditions', 'process_footer_items' ), 19 );
		}
	}


	/**
	 * Fires when activating the plugin
	 *
	 * @since 	0.1.0
	 */

	public static function activate()
	{
		if ( ! current_user_can( 'activate_plugins' ) )
            return;

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "activate-plugin_{$plugin}" );
	}


	/**
	 * Fires when deactivating the plugin
	 *
	 * @since 	0.1.0
	 */

	public static function deactivate()
	{
		if ( ! current_user_can( 'activate_plugins' ) )
            return;

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );

		self::delete_default_options();
	}


	/**
	 * Fires when uninstalling the plugin
	 *
	 * @since 	0.1.0
	 */

	public static function uninstall()
	{
		if ( ! current_user_can( 'activate_plugins' ) || __FILE__ != WP_UNINSTALL_PLUGIN )
            return;

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );

		self::delete_default_options();
	}


	/**
	 * Load translation files
	 *
	 * @since 	0.1.0
	 */

	public function load_textdomain()
	{
		load_plugin_textdomain( 'wp-script-optimizer', false, basename( dirname( __FILE__ ) ) . '/lang' );
	}


	/**
	 * Add admin menu and load all necessary scripts only on this page
	 * through menu page hook
	 *
	 * @since 	0.1.0
	 */

	public function add_admin_menus()
	{
		add_menu_page(
	        __( 'WP Scripts & Styles Optimizer -> Scripts', 'wp-script-optimizer' ),
	        __( 'WP Scripts & Styles Optimizer', 'wp-script-optimizer' ),
	        'manage_options',
	        'wpso_global',
	        array( $this, 'render_global_page' ),
	        'dashicons-media-code',
	        999
	    );

		$hook_global = add_submenu_page(
			'wpso_global',
			__( 'WP Scripts & Styles Optimizer -> Global', 'wp-script-optimizer' ),
			__( 'Global', 'wp-script-optimizer' ),
			'manage_options',
			'wpso_global',
			array( $this, 'render_global_page' )
		);

		$hook_single = add_submenu_page(
			'wpso_global',
			__( "WP Scripts & Styles Optimizer -> Single pages", 'wp-script-optimizer' ),
			__( "Single pages", 'wp-script-optimizer' ),
			'manage_options',
			'wpso_single',
			array( $this, 'render_single_page' )
		);

		add_action( 'load-' . $hook_global, array( $this, 'enqueue_scripts' ) );
		add_action( 'load-' . $hook_single,  array( $this, 'enqueue_scripts' ) );
		add_action( 'load-' . $hook_global, array( $this, 'add_help_page' 	 ) );
		add_action( 'load-' . $hook_single,  array( $this, 'add_help_page' 	 ) );
	}


	/**
	 * Render the global view template
	 *
	 * @since 	0.4.0
	 */

	public function render_global_page()
	{
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) )
    		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

    	include( sprintf( "%s/templates/admin_page_global.php", dirname( __FILE__ ) ) );
	}


	/**
	 * Render the single pages view template
	 *
	 * @since 	0.4.0
	 */

	public function render_single_page()
	{
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) )
    		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

    	include( sprintf( "%s/templates/admin_page_single.php", dirname( __FILE__ ) ) );
	}


	/**
	 * Get names and version of all installed plugins
	 *
	 * @since 	0.1.0
	 * @return 	array
	 */

	public function get_plugins_status()
	{
		if ( ! function_exists( 'get_plugins' ) )
		{
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array();

		foreach ( get_plugins() as $path => $data )
		{
			$plugins[$path]['name']    = $data['Name'];
			$plugins[$path]['version'] = $data['Version'];
			$plugins[$path]['active']  = is_plugin_active( $path ) ? 1 : 0;
		}

		return $plugins;
	}


	/**
	 * Check all handles if they exist anymore
	 *
	 * @since 	0.1.0
	 */

	public function check_handles()
	{
		if ( ! current_user_can( 'manage_options' ) )
			return;

		global $wpdb, $wp_scripts, $wp_styles;

		$handle_query_string = self::get_query_string();
		$stored_handles = array();

		foreach ( $this->handle_types as $type_s => $type_p )
		{
			$stored_handles[ $type_s ] = $wpdb->get_col( $wpdb->prepare(
				"
				SELECT handle
				FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
				WHERE type = %s
					AND query_string = %s
				",
				$type_s,
				$handle_query_string
			));

			foreach ( $stored_handles[ $type_s ] as $handle )
			{
				$handle_is = 'wp_' . $type_s . '_is';

				if ( ! $handle_is( $handle, 'registered' ) )
				{
					$this->delete_handle( self::get_handle_data( array( $handle, $type_s, $handle_query_string ), 'id' ) );

					$option_array = (array) get_option( 'wpso_deleted_' . $type_p );
					$option_array[] = $handle;
					update_option( 'wpso_deleted_' . $type_p, $option_array );
				}
			}
		}
	}


	/**
	 * Get list of all enqueued scripts on the frontpage
	 * and save them into the wp-script-optimzer db-table
	 *
	 * @since 	0.1.0
	 */

	public function store_handles()
	{
		if ( ! current_user_can( 'manage_options' ) )
			return;

		global $wp_scripts, $wp_styles;

		foreach ( $this->handle_types as $type_s => $type_p )
		{
			foreach ( ${'wp_' . $type_p}->groups as $handle => $group )
			{
				if ( in_array( $handle, array( 'jquery', 'jquery-core', 'jquery-migrate' ) ) )
				{
					$this->store_jquery();
					continue;
				}

				$this->store_handle( $handle, $type_s );
		    }
		}
	}


	/**
	 * Shows the query string of the global $wp object to read it via cURL.
	 *
	 * @since 	0.4.0
	 */

	public function show_query_string()
	{
		die( self::get_query_string() );
	}


	/**
	 * Store jquery handle data into DB
	 * "jquery" is only a trigger handle for "jquery-core" and "jquery-migrate"
	 *
	 * @since 	0.1.0
	 */

	public function store_jquery()
	{
		global $wpdb, $wp_scripts;

		$handle_query_string = self::get_query_string();

		if ( sanitize_key( $_GET['wpso'] ) == 'synccheck' )
		{
			if ( self::get_handle_data( array( 'jquery', 'script', 'global' ) ) && ! self::get_handle_data( array( 'jquery', 'script', $handle_query_string ) ) && $handle_query_string != 'global' )
			{
				$this->clone_global_handle( 'jquery', 'script', $handle_query_string );

				$option_array = (array) get_option( 'wpso_added_scripts' );
				$option_array[] = 'jquery';
				update_option( 'wpso_added_scripts', $option_array );

				return;
			}
		}

		$handle_handle 		   = 'jquery';
		$handle_group_original = $wp_scripts->groups['jquery-core'];
		$handle_group_new 	   = $wp_scripts->groups['jquery-core'];

		if ( ! self::get_handle_data( array( 'jquery', 'script', $handle_query_string ) ) )
		{
			$insert = $wpdb->insert(
				$wpdb->prefix . WPSO_DB_TABLE,
				array(
					'type'	 	   	 => 'script',
					'status'	   	 => 'active',
					'handle' 	   	 => $handle_handle,
					'query_string' 	 => $handle_query_string,
					'group_original' => (int) $handle_group_original,
					'group_new' 	 => (int) $handle_group_new,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d' )
			);

			if ( ! empty( $insert ) )
			{
				$option_array = (array) get_option( 'wpso_added_scripts' );
				$option_array[] = 'jquery';
				update_option( 'wpso_added_scripts', $option_array );
			}
		}
		else
		{
			$wpdb->update(
				$wpdb->prefix . WPSO_DB_TABLE,
				array(
					'group_original' => (int) $handle_group_original
				),
				array(
					'handle' 	   => 'jquery',
					'type' 		   => 'script',
					'query_string' => $handle_query_string
				),
				array( '%d' ),
				array( '%s', '%s', '%s' )
			);
		}
	}


	/**
	 * Store a single handle into the database. If it already exist, it will be updated
	 *
	 * @since 	0.1.0
	 *
	 * @param 	string 	$handle 	A handle name to look for
	 * @param 	string 	$type 		The handle type (script or style)
	 *
	 * @return 	bool 				Returns true if insert was successful. False otherwise.
	 */

	public function store_handle( $handle, $type )
	{
		global $wpdb, $wp_scripts, $wp_styles;

		$handle_query_string = self::get_query_string();

		if ( sanitize_key( $_GET['wpso'] ) == 'synccheck' )
		{
			if ( self::get_handle_data( array( $handle, $type, 'global' ) ) && ! self::get_handle_data( array( $handle, $type, $handle_query_string ) ) && $handle_query_string != 'global' )
			{
				$this->clone_global_handle( $handle, $type, $handle_query_string );

				$option_array = (array) get_option( 'wpso_added_' . $this->handle_types[$type] );
				$option_array[] = $handle;
				update_option( 'wpso_added_' . $this->handle_types[$type], $option_array );

				return;
			}
		}

		switch ( $type )
		{
			case 'script':
				$handle_handle 		   = $wp_scripts->registered[$handle]->handle;
				$handle_src_original   = $wp_scripts->registered[$handle]->src;
				$handle_version 	   = $wp_scripts->registered[$handle]->ver;
				$handle_deps 		   = $wp_scripts->registered[$handle]->deps;
				$handle_args		   = $wp_scripts->registered[$handle]->args;
				$handle_extra 		   = $wp_scripts->registered[$handle]->extra;
				$handle_group_original = $wp_scripts->groups[$handle];
				$handle_group_new 	   = $wp_scripts->groups[$handle];
				break;

			case 'style':
				$handle_handle 		   = $wp_styles->registered[$handle]->handle;
				$handle_src_original   = $wp_styles->registered[$handle]->src;
				$handle_version 	   = $wp_styles->registered[$handle]->ver;
				$handle_deps 		   = $wp_styles->registered[$handle]->deps;
				$handle_args		   = $wp_styles->registered[$handle]->args;
				$handle_extra 		   = $wp_styles->registered[$handle]->extra;
				$handle_group_original = 0;
				$handle_group_new 	   = 0;
				break;

			default:
				return false;
		}

		if ( ! self::get_handle_data( array( $handle, $type, $handle_query_string ) ) )
		{
			$insert = $wpdb->insert(
				$wpdb->prefix . WPSO_DB_TABLE,
				array(
					'type'	 	   	 => $type,
					'status'	   	 => 'active',
					'handle' 	   	 => $handle_handle,
					'query_string' 	 => $handle_query_string,
					'src_original' 	 => $handle_src_original,
					'version' 	   	 => empty( $handle_version ) ? '' : $handle_version,
					'deps' 	   	 	 => empty( $handle_deps ) ? '' : json_encode( $handle_deps ),
					'args' 			 => empty( $handle_args ) ? '' : $handle_args,
					'extra' 		 => empty( $handle_extra ) ? '' : json_encode( $handle_extra ),
					'group_original' => (int) $handle_group_original,
					'group_new' 	 => (int) $handle_group_new,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
			);

			if ( ! empty( $insert ) )
			{
				$option_array = (array) get_option( 'wpso_added_' . $this->handle_types[$type] );
				$option_array[] = $handle;
				update_option( 'wpso_added_' . $this->handle_types[$type], $option_array );
			}
		}
		else
		{
			$wpdb->update(
				$wpdb->prefix . WPSO_DB_TABLE,
				array(
					'src_original' 	 => $handle_src_original,
					'version' 		 => empty( $handle_version ) ? '' : $handle_version,
					'deps' 		 	 => empty( $handle_deps ) ? '' : json_encode( $handle_deps ),
					'args' 			 => empty( $handle_args ) ? '' : $handle_args,
					'extra'			 => empty( $handle_extra ) ? '' :  json_encode( $handle_extra ),
					'group_original' => (int) $handle_group_original
				),
				array(
					'handle' 	   		=> $handle,
					'type' 		   		=> $type,
					'query_string' 		=> $handle_query_string
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d' ),
				array( '%s', '%s', '%s' )
			);
		}

		return false;
	}


	/**
	 * Get the query string of the requested site
	 *
	 * @since 	0.4.0
	 * @return 	string 	Returns actual query string or "global" if it's empty
	 */

	public static function get_query_string()
	{
		global $wp;

		if ( empty( $wp->query_string ) )
		{
			$query_string = 'global';
		}
		else
		{
			$qs = urldecode( $wp->query_string );
			parse_str( $qs, $parsed_query_string );
			ksort( $parsed_query_string );
			$query_string = urldecode( http_build_query( $parsed_query_string ) );
		}

		return $query_string;
	}


	/**
	 * Checks a handle if there is a global version of it
	 *
	 * @since 	0.4.0
	 *
	 * @param 	string 		$handle 	Name of the handle
	 * @param 	string 		$type 		"script" or "style"
	 *
	 * @return 	bool
	 */

	public function global_handle_exist( $handle, $type )
	{
		global $wpdb;

		$data = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT handle
				FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
				WHERE `handle` = %s
					AND `type` = %s
					AND (`query_string` = 'global' OR `query_string` = '')
				",
				$handle,
				$type
			)
		);

		return ( $data ) ? true : false;
	}


	/**
	 * Clone a global handle and insert it into the database
	 *
	 * @since 	0.4.0
	 *
	 * @param 	string 	$handle 		Name of the handle
	 * @param 	string 	$type 			"script" or "style"
	 * @param 	string 	$query_string 	The query string
	 *
	 * @return 	int 					ID of inserted handle
	 */

	public function clone_global_handle( $handle, $type, $query_string )
	{
		global $wpdb;

		if ( $query_string != 'global' )
		{
			$columns = array();
			foreach ( $wpdb->get_col( "DESC " . $wpdb->prefix . WPSO_DB_TABLE, 0 ) as $column_name )
			{
				$columns[] = $column_name;
			}

			if ( ( $key = array_search( 'id', $columns ) ) !== false )
			{
			    unset( $columns[$key] );
			}

			if ( ! self::get_handle_data( array( $handle, $type, $query_string ) ) )
			{
				$wpdb->query(
					$wpdb->prepare(
						"
				        INSERT INTO " . $wpdb->prefix . WPSO_DB_TABLE . "
							(" . implode( ', ', $columns ) . ")
						SELECT " . implode( ', ', $columns ) . "
						FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
						WHERE `handle` = %s
							AND `type` = %s
							AND `query_string` = 'global'
						",
						$handle,
						$type
				    )
				);

				$wpdb->update(
					$wpdb->prefix . WPSO_DB_TABLE,
					array(
						'query_string' => $query_string
					),
					array( 'id' => $wpdb->insert_id	),
					array( '%s' ),
					array( '%d' )
				);
			}
		}

		return $wpdb->insert_id;
	}


	/**
	 * Checks if a handle differs from its original state
	 *
	 * @since 	0.2.1
	 *
	 * @param 	int 	$handle_id
	 * @return 	bool 				True if it differs, false if not
	 */

	public static function handle_has_changed( $handle_id )
	{
		if ( ! $handle_id )
			return;

		$original_group = self::get_handle_data( $handle_id, 'group_original' );
		$new_group 		= self::get_handle_data( $handle_id, 'group_new' );
		$status 		= self::get_handle_data( $handle_id, 'status' );
		$conditions 	= self::get_handle_data( $handle_id, 'conditions' );

		if ( $original_group != $new_group )
		{
			return true;
		}
		elseif ( $status == 'inactive' )
		{
			return true;
		}
		elseif ( ! empty( $conditions ) )
		{
			return true;
		}

		return false;
	}


	/**
	 * Set status of a handle to active or inactive
	 *
	 * @since 	0.1.0
	 *
	 * @param 	int 	$handle_id
	 * @param 	string 	$status 	Can be active or inactive
	 */

	public static function change_handle_status( $handle_id, $status = '' )
	{
		global $wpdb;

		if ( ! $handle_id || ! in_array( $status, array( 'active', 'inactive' ) ) )
			return;

		$wpdb->update(
			$wpdb->prefix . WPSO_DB_TABLE,
			array(
				'status' => $status,
			),
			array(
				'id' => $handle_id
			),
			array( '%s' ),
			array( '%d' )
		);
	}


	/**
	 * Set group for a handle and all of it's dependencys
	 *
	 * @since 	0.1.0
	 *
	 * @param 	int 	$handle_id
	 * @param 	int 	$group 		header = 0, footer = 1
	 */

	public static function change_handle_group( $handle_id, $group )
	{
		global $wpdb;

		if ( ! $handle_id )
			return;

		$orig_handle = self::get_handle_data( $handle_id );

		$handles = array( $orig_handle->handle );

		$group === 0
			?: $handles = array_merge( $handles, self::get_deps( $orig_handle->handle, $orig_handle->type, $orig_handle->query_string ) );

		update_option( 'wpso_handles_moved_down', $handles );

		foreach ( $handles as $handle )
		{
			$wpdb->update(
				$wpdb->prefix . WPSO_DB_TABLE,
				array(
					'group_new' => (int) $group,
				),
				array(
					'handle' 	   => $handle,
					'type' 		   => $orig_handle->type,
					'query_string' => $orig_handle->query_string
				),
				array( '%d' ),
				array( '%s', '%s', '%s' )
			);
		}
	}


	/**
	 * Reset handle to its original state
	 *
	 * @since 	0.2.1
	 * @param 	int 	$handle_id
	 */

	public static function reset_handle( $handle_id )
	{
		global $wpdb;

		if ( ! $handle_id )
			return;

		$original_group = self::get_handle_data( $handle_id, 'group_original' );

		$wpdb->update(
			$wpdb->prefix . WPSO_DB_TABLE,
			array(
				'status' => 'active',
				'group_new' => $original_group,
				'conditions' => ''
			),
			array( 'id' => $handle_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
	}


	/**
	 * Get dependencys for a given handle
	 *
	 * @since 	0.1.0
	 *
	 * @param 	string 	$handle 		name of the handle
	 * @param 	string 	$type 			script or style
	 * @param 	string 	$query_string 	query string for that handle
	 *
	 * @return 	array 					Single dimensional array with all dependencys
	 */

	public static function get_deps( $handle, $type, $query_string = 'global' )
	{
		global $wpdb;

		if ( empty( $handle ) )
			return;

		$results = array();

		$deps = $wpdb->get_results(
			"
			SELECT handle
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE `deps` LIKE '%$handle%'
				AND `type` = '$type'
				AND `query_string` = '$query_string'
			"
		);

		if ( $deps )
		{
			foreach ( $deps as $row )
			{
				$results[] = $row->handle;
			}
		}

		return $results;
	}


	/**
	 * Get db-values for a specific handle
	 *
	 * @since 	0.1.0
	 *
	 * @param 	int|array 		$handle 	Handle id or array with following structure: array( $handle, $type, $query_string )
	 * @param 	string 			$value 		The database field to get / Leave empty for object of all data
	 *
	 * @return 	mixed|false 				Returns an object of all data or the requested value or false if nothing found
	 */

	public static function get_handle_data( $handle, $value = '' )
	{
		global $wpdb;

		if ( empty( $handle ) )
			return false;

		if ( is_numeric( $handle ) )
		{
			$sql_where = 'WHERE id = %d';
			$var_array = array( $handle );
		}
		elseif ( is_array( $handle ) )
		{
			$sql_where = 'WHERE handle = %s AND type = %s AND query_string = %s';
			$var_array = array( $handle[0], $handle[1], $handle[2] );
		}
		else
		{
			return false;
		}

		if ( empty( $value ) )
		{
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
				" . $sql_where . "
				",
				$var_array
			));
		}
		else
		{
			$data = $wpdb->get_var( $wpdb->prepare(
				"
				SELECT $value
				FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
				" . $sql_where . "
				",
				$var_array
			));
		}

		return ( ! empty( $data ) ) ? $data : false;
	}


	/**
	 * Checks if handles with a specific query string exists in the database
	 *
	 * @since 	0.4.0
	 *
	 * @param 	string 	$query_string
	 * @return 	bool
	 */

	public static function query_string_exist( $query_string )
	{
		global $wpdb;

		if ( empty( $query_string ) )
			return;

		$qs_exist = $wpdb->get_results(
			"
			SELECT *
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE `query_string` = '".esc_sql( $query_string )."'
			"
		);

		return ( $qs_exist ) ? true : false;
	}


	/**
	 * Delete a handle from the database
	 *
	 * @since 	0.1.0
	 * @param 	int 	$handle_id
	 */

	public function delete_handle( $handle_id )
	{
		global $wpdb;

		if ( empty( $handle_id ) )
			return;

		if ( current_user_can( 'manage_options' ) )
		{
			$wpdb->delete(
				$wpdb->prefix . WPSO_DB_TABLE,
				array( 'id' => $handle_id ),
				array( '%d' )
			);
		}
	}


	/**
	 * Get the count of items for a type and query string
	 *
	 * @since 	0.4.0
	 *
	 * @param 	string 	$type 			script or style
	 * @param 	string 	$query_string
	 *
	 * @return 	int 	$item_count
	 */

	public function get_count( $type, $query_string = 'global' )
	{
		global $wpdb;

		if ( ! in_array( $type, array( 'script', 'style' ) ) )
			return;

		$item_count = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT COUNT(*)
				FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
				WHERE type = '%s'
					AND query_string = '%s'
				",
				$type,
				$query_string
			)
		);

		return $item_count;
	}


	/**
	 * Get admin_notices for displaying everywhere in the wordpress backend
	 *
	 * @since 	0.4.0
	 */

	public function general_admin_notices()
	{
		# Plugins changed
		if ( self::get_plugins_status() != get_option( 'wpso_plugins_status' ) ) :
			echo '
			<div class="notice notice-warning is-dismissible">
				<p>' . sprintf( __( 'Your plugins have been changed. Please click <a href="%s">here</a> to update and review all of your <b>WP Scripts & Styles Optimizer</b> lists.', 'wp-script-optimizer' ), esc_url( admin_url( 'admin.php?page=wpso_global' ) ) ) . '</p>
			</div>';
			update_option( 'wpso_plugins_status', self::get_plugins_status() );
		endif;

		# Theme switched
		if ( wp_get_theme() != get_option( 'wpso_current_theme' ) ) :
			echo '
			<div class="notice notice-warning is-dismissible">
				<p>' . sprintf( __( 'You have switched your theme. Please click <a href="%s">here</a> to update and review all of your <b>WP Scripts & Styles Optimizer</b> lists.', 'wp-script-optimizer' ), esc_url( admin_url( 'admin.php?page=wpso_global' ) ) ) . '</p>
			</div>';
			update_option( 'wpso_current_theme', wp_get_theme() );
		endif;
	}


	/**
	 * Get admin_notices and echo them
	 *
	 * @since 	0.4.0
	 */

	public function admin_notices()
	{
		# Scripts added
		if ( is_array( get_option( 'wpso_added_scripts' ) ) &&  ! empty( get_option( 'wpso_added_scripts' ) ) ) :
			echo '
			<div class="notice notice-success is-dismissible">
				<p>' . sprintf( _n( '<b>%s</b> script was added', '<b>%s</b> scripts were added', count( get_option( 'wpso_added_scripts' ) ), 'wp-script-optimizer' ), count( get_option( 'wpso_added_scripts' ) ) ) . '</p>
				<p><small>' . implode( ' | ', get_option( 'wpso_added_scripts' ) ) . '</small></p>
			</div>';
			update_option( 'wpso_added_scripts', array() );
		endif;

		# Styles added
		if ( is_array( get_option( 'wpso_added_styles' ) ) && ! empty( get_option( 'wpso_added_styles' ) ) ) :
			echo '
			<div class="notice notice-success is-dismissible">
				<p>' . sprintf( _n( '<b>%s</b> style was added', '<b>%s</b> styles were added', count( get_option( 'wpso_added_styles' ) ), 'wp-script-optimizer' ), count( get_option( 'wpso_added_styles' ) ) ) . '</p>
				<p><small>' . implode( ' | ', get_option( 'wpso_added_styles' ) ) . '</small></p>
			</div>';
			update_option( 'wpso_added_styles', array() );
		endif;

		# Scripts deleted
		if ( count( get_option( 'wpso_deleted_scripts' ) ) > 0 ) :
			echo '
			<div class="notice notice-error is-dismissible">
				<p>' . sprintf( _n( "<b>%s</b> script was deleted, because it's not registered anymore", "<b>%s</b> scripts were deleted, because they're not registered anymore", count( get_option( 'wpso_deleted_scripts' ) ), 'wp-script-optimizer' ), count( get_option( 'wpso_deleted_scripts' ) ) ) . '</p>
				<p><small>' . implode( ' | ', get_option( 'wpso_deleted_scripts' ) ) . '</small></p>
			</div>';
			update_option( 'wpso_deleted_scripts', array() );
		endif;

		# Styles deleted
		if ( count( get_option( 'wpso_deleted_styles' ) ) > 0 ) :
			echo '
			<div class="notice notice-error is-dismissible">
				<p>' . sprintf( _n( "<b>%s</b> style was deleted, because it's not registered anymore", "<b>%s</b> styles were deleted, because they're not registered anymore", count( get_option( 'wpso_deleted_styles' ) ), 'wp-script-optimizer' ), count( get_option( 'wpso_deleted_styles' ) ) ) . '</p>
				<p><small>' . implode( ' | ', get_option( 'wpso_deleted_styles' ) ) . '</small></p>
			</div>';
			update_option( 'wpso_deleted_styles', array() );
		endif;

		# Handles moved down
		if ( count( get_option( 'wpso_handles_moved_down' ) ) > 1 ) :
			$handles = get_option( 'wpso_handles_moved_down' );
			$affected = array_slice( $handles, 1 );
			echo '
			<div class="notice notice-warning is-dismissible">
				<p>' . sprintf( __( 'You have moved <b>%s</b> into footer. The following dependents were also moved:', 'wp-script-optimizer' ), $handles[0] ) . '</p>
				<p><small>' . implode( ' | ', $affected ) . '</small></p>
			</div>';
			update_option( 'wpso_handles_moved_down', array() );
		endif;
	}


	/**
	 * Enqueue and localize all plugin related scripts and styles.
	 * They itself won't be included in the list ;-)
	 *
	 * @since 	0.1.0
	 */

	public function enqueue_scripts()
	{
		# Javascript
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-effects-blind' );
		wp_enqueue_script( 'jquery-effects-highlight' );
		wp_enqueue_script( 'wpso-scripts', PLUGIN_URL . 'js/wpso.js', array( 'jquery', 'jquery-ui-core' ), PLUGIN_VERSION );
		wp_enqueue_script( 'wpso-sticky-kit', PLUGIN_URL . 'lib/sticky-kit/dist/sticky-kit.min.js', array( 'jquery' ), PLUGIN_VERSION );

		# CSS
		wp_enqueue_style ( 'wpso-styles', PLUGIN_URL . 'css/wpso.css', array(), PLUGIN_VERSION );
		wp_enqueue_style ( 'wpso-font-awesome', PLUGIN_URL . 'lib/font-awesome/css/font-awesome.min.css', array(), PLUGIN_VERSION );

		# AJAX
		WpsoAjax::enqueue();
	}


	/**
	 * Adds help pages
	 *
	 * @since 	0.2.1
	 */

	public function add_help_page()
	{
		$screen = get_current_screen();
	    $screen->add_help_tab( array(
	        'id'      => 'wpso-help-overview',
	        'title'   => __( 'Overview', 'wp-script-optimizer' ),
	        'content' => __( '<p>Improve your site-rendering speed by customizing the output of all scripts and styles of Your site. You can disable unwanted scripts or styles, move them to the footer or set conditionals to determine where files are included and where not.</p>', 'wp-script-optimizer' ),
	    ));

		$screen->add_help_tab( array(
	        'id'      => 'wpso-help-actions',
	        'title'   => __( 'Possible actions', 'wp-script-optimizer' ),
	        'content' => __( '<p>There are in general two ways, how you can modify the list items. Firstly, the item actions of each item (hover over them) and secondly the bulk actions at the top of each list.</p><p><ul><li><b>Activate / Deactivate</b> - Removes the item completely from your site\'s frontend or activate it.</li><li><b>Into Header / Into Footer</b> - Change the position of the selected items. If you move items with dependents to the footer, these files will moved too. (actually only for scripts)</li><li><b>Conditionals</b> - Here you can create a set of logical rules, to control where the item will show up. It uses Wordpress\'s conditional tags.</li><li><b>Reset to original state</b> - This option only shows up, if you have made any changes to an item. With one click you can remove all custom changes and get it back to it\'s original state.</li></ul></p>', 'wp-script-optimizer' ),
	    ));

		$screen->add_help_tab( array(
	        'id'      => 'wpso-help-notice',
	        'title'   => __( 'Important notice', 'wp-script-optimizer' ),
	        'content' => __( '<p>It may happen that some of your settings will be ignored!</p><p>This is because Wordpress has an internal logic to consider that no script or style is enqueued, which needed a file that is not enqueued with your current settings</p><p>The same applies to the positioning of scripts. <b>For example</b>: if you move a script to the footer, it still remains in the header, if there is any script left that need it above itself.</p>', 'wp-script-optimizer' ),
	    ));
	}


	/**
	 * Add default options to the database
	 *
	 * @since 	0.2.2
	 */

	public function add_default_options()
	{
		self::$default_options = array(
			'wpso_added_scripts'   	  => array(),
			'wpso_added_styles'    	  => array(),
			'wpso_deleted_scripts' 	  => array(),
			'wpso_deleted_styles'  	  => array(),
			'wpso_handles_moved_down' => array(),
			'wpso_plugins_status'  	  => $this->get_plugins_status(),
			'wpso_current_theme'   	  => wp_get_theme(),
			'wpso_db_version' 	  	  => 0,
			'wpso_wp_scripts_array'   => array(),
			'wpso_wp_styles_array'    => array()
		);

		foreach ( self::$default_options as $option => $value )
		{
			add_option( $option, $value );
		}
	}


	/**
	 * Delete default options from the database
	 *
	 * @since 	0.2.2
	 */

	public static function delete_default_options()
	{
		foreach ( self::$default_options as $option => $value )
		{
			delete_option( $option );
		}
	}


	/**
	 * Creates or updates the DB if needed
	 *
	 * @since 	0.1.0
	 */

	public function update_db()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . WPSO_DB_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id int(12) NOT NULL AUTO_INCREMENT,
			type varchar(6) NOT NULL,
			status varchar(8) NOT NULL,
			handle varchar(255) NOT NULL,
			query_string varchar(2000) NOT NULL DEFAULT 'global',
			src_original varchar(255) NOT NULL,
			src_new varchar(255) NOT NULL,
			version varchar(55) NOT NULL,
			deps text NOT NULL,
			args varchar(55) NOT NULL,
			extra text NOT NULL,
			group_original tinyint(1) unsigned NOT NULL,
			group_new tinyint(1) unsigned NOT NULL,
			conditions text NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}


	/**
	 * Instantiate Classes
	 *
	 * @since 	0.1.0
	 */

	public static function init()
	{
		new Wpso;
		new WpsoAjax;
	}
}


/**
 * Include additional classes
 */

require_once PLUGIN_PATH . 'classes/list.class.php';
require_once PLUGIN_PATH . 'classes/conditions.class.php';
require_once PLUGIN_PATH . 'classes/ajax_functions.class.php';


/**
 * Start here...
 */

register_activation_hook(   __FILE__, array( 'Wpso', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'Wpso', 'deactivate' ) );
register_uninstall_hook(    __FILE__, array( 'Wpso', 'uninstall'  ) );

Wpso::init();
