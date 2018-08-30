<?php
if ( ! defined('ABSPATH') )
	exit;


/**
 * WpsoAjax class
 *
 * @since 	0.1.0
 */

class WpsoAjax
{
	/**
	 * Constructor
	 *
	 * @since 	0.1.0
	 */

	public function __construct()
	{
		add_action( 'wp_ajax_get_value_select_ajax',   array( $this, 'get_value_select_ajax'   ) );
		add_action( 'wp_ajax_remove_conditions_ajax',  array( $this, 'remove_conditions_ajax'  ) );
		add_action( 'wp_ajax_save_conditions_ajax',    array( $this, 'save_conditions_ajax'    ) );
		add_action( 'wp_ajax_delete_handle_list_ajax', array( $this, 'delete_handle_list_ajax' ) );
		add_action( 'wp_ajax_sync_handle_list_ajax',   array( $this, 'sync_handle_list_ajax'   ) );
		add_action( 'wp_ajax_save_tab_session_data',   array( $this, 'save_tab_session_data'   ) );
		add_action( 'wp_ajax_process_page_request',    array( $this, 'process_page_request'    ) );
		add_action( 'wp_ajax_get_saved_urls_list',     array( $this, 'get_saved_urls_list'     ) );
	}


	/**
	 * Enqueue and localize all ajax related scripts.
	 *
	 * @since 	0.1.0
	 */

	public static function enqueue()
	{
		wp_enqueue_script( 'wpso-ajax', PLUGIN_URL . 'js/wpso_ajax.js', array( 'jquery', 'wpso-scripts' ), PLUGIN_VERSION );

		// Ajax
		wp_localize_script( 'wpso-ajax', 'wpso_ajax_object',
	        array(
				'ajax_url' 	 		 => esc_url( admin_url( 'admin-ajax.php' ) ),
				'ajax_nonce' 		 => wp_create_nonce( 'wpso_ajax_conditions_action' ),
				'home_url' 		 	 => esc_url( home_url( '/' ) ),
				'admin_url' 		 => esc_url( trailingslashit( admin_url() ) ),
				'screen_url' 		 => esc_url( admin_url( 'admin.php?page=' . sanitize_key( Wpso::$page_handle ) ) ),
				'query_string' 		 => base64_encode( Wpso::$query_string ),
				'delete_all_confirm' => __( 'All data and settings will be deleted! Continue?', 'wp-script-optimizer' ),
				'page_not_exist'	 => __( 'The page with the query string "%s" is not reachable. It will be removed...', 'wp-script-optimizer' ),
				'sync_page_confirm'	 => __( 'All scripts and styles of this page that do exist in the global tab, will be synchronized. Continue?', 'wp-script-optimizer' )
			)
		);
	}


	/**
	 * AJAX handler to delete handles with a specific query_string
	 *
	 * @since 	0.1.0
	 */

	public function delete_handle_list_ajax()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );
		global $wpdb;

		if ( current_user_can( 'manage_options' ) )
		{
			$sql_where = ( $_POST['query_string'] == 'all' )
				? array( '!=', 'global' )
				: array( '=', $_POST['query_string'] );

			$wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
					WHERE `query_string` ".$sql_where[0]." %s
					",
					$sql_where[1]
				)
			);
		}

		die();
	}


	/**
	 * AJAX handler to sync handles with a specific query_string with the global list
	 *
	 * @since 	0.4.0
	 */

	public function sync_handle_list_ajax()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );
		global $wpdb;

		if ( current_user_can( 'manage_options' ) )
		{
			$query_string = wp_strip_all_tags( $_POST['query_string'] );

			$to_sync = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT *
					FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
					WHERE `query_string` = %s
						AND `query_string` != 'global'
					",
					$query_string
				)
			);

			if ( $to_sync )
			{
				foreach ( $to_sync as $sync )
				{
					if ( Wpso::global_handle_exist( $sync->handle, $sync->type ) )
					{
						Wpso::delete_handle( $sync->id );
						Wpso::clone_global_handle( $sync->handle, $sync->type, $query_string );
					}
				}
			}
		}

		die();
	}


	/**
	 * AJAX handler to call the remove conditions function
	 *
	 * @since 	0.1.0
	 * @see 	WpsoConditions::remove_conditions()
	 */

	public function remove_conditions_ajax()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );

		WpsoConditions::remove_conditions( (int) $_POST['handle_id'] );

		die();
	}


	/**
	 * AJAX handler to store chosen conditions into the database
	 *
	 * @since 	0.1.0
	 */

	public function save_conditions_ajax()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );
		global $wpdb;

		$handle_id = (int) $_POST['handle_id'];
		$conditions = wp_strip_all_tags( json_encode( $_POST['conditions'] ) );

		if ( current_user_can( 'manage_options' ) )
		{
			$wpdb->update(
				$wpdb->prefix . WPSO_DB_TABLE,
				array(
					'conditions' => $conditions,
				),
				array(
					'id' => $handle_id
				),
				array( '%s' ),
				array( '%d' )
			);
		}

		die();
	}


	/**
	 * AJAX handler to get the right select field without reload
	 *
	 * @since 	0.1.0
	 * @see 	WpsoConditions::get_value_select()
	 */

	public function get_value_select_ajax()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );

		echo WpsoConditions::get_value_select( esc_attr( $_POST['param'] ) );

		die();
	}


	/**
	 * Process a page request and returns page info as JSON data
	 *
	 * @since 	0.4.0
	 */

	public function process_page_request()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );

		$data = array();

		# sanitize url
		$sanitized_url = filter_var( $_POST['page_request'], FILTER_SANITIZE_URL );

		# parsing sanitized url and home url
		$parsed_request_url = wp_parse_url( $sanitized_url );
		$parsed_home_url 	= wp_parse_url( home_url() );

		$clear_button = '<button type="button" id="wpso-clear-search-page-input" class="button button-large"><i class="fa fa-trash"></i>&nbsp;&nbsp;' . __( 'Reset field', 'wp-script-optimizer' ) . '</button>';

		# valid url?
		if ( filter_var( $sanitized_url, FILTER_VALIDATE_URL ) === false )
		{
			$data['error'] = __( "Please enter a valid URL.", 'wp-script-optimizer' );
			$data['html']  = $clear_button;
		}
		# same host?
		elseif ( $parsed_request_url['host'] != $parsed_home_url['host'] )
		{
			$data['error'] = __( "Only internal url's are allowed.", 'wp-script-optimizer' );
			$data['html']  = $clear_button;
		}
		# admin url?
		elseif ( strpos( $sanitized_url, 'wp-admin' ) !== false || strpos( $sanitized_url, 'wp-login.php' ) !== false )
		{
			$data['error'] = __( "Admin url's are not allowed.", 'wp-script-optimizer' );
			$data['html']  = $clear_button;
		}
		# or check url and grab query string
		else
		{
			$url_no_params = explode( '?', $sanitized_url );

			$response = wp_safe_remote_request( 
				esc_url_raw( add_query_arg( 'wpso', 'show_qs', trailingslashit( $url_no_params[0] ) ) ), 
				[ 
					'timeout' => 10 
				] 
			);

			$response_body 	  = wp_remote_retrieve_body( $response );
			$response_code 	  = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );
			
			if ( ! empty( $response_body ) && $response_body != 'global' && ( $response_code >= 200 && $response_code < 300 ) )
			{
				$data['html'] = '
				<p style="word-wrap:break-word;">Query-String: <b>' . esc_attr( $response_body ) . '</b></p>
				<button type="button" id="wpso-get-search-page-input" class="button button-primary button-large" data-querystring="' . esc_attr( base64_encode( $response_body ) ) . '"><i class="fa fa-arrow-right"></i>&nbsp;&nbsp;' . __( 'Get Scripts & Styles', 'wp-script-optimizer' ) . '</button>&nbsp;&nbsp;' . $clear_button . '
				<br>
				<p><input type="checkbox" id="wpso-get-search-page-sync" checked /><label for="wpso-get-search-page-sync">' . __( 'Sync with global settings', 'wp-script-optimizer' ) . '</label>&nbsp;&nbsp;<i class="fa fa-question-circle fa-lg" title="' . __( "If checked, every single script or style will take over the global settings, except it don't exist in the global scope.", 'wp-script-optimizer' ) . '"></i></p>';
			}
			else
			{
				$data['error'] = sprintf( __( "Url cannot be loaded or you have tried to check the front page - Statuscode: %d - Message: %s", 'wp-script-optimizer' ), $response_code, $response_message );
				$data['html'] = $clear_button;
			}
		}

		echo json_encode( $data );

		die();
	}


	/**
	 * Get list of all saved url's which have changed settings
	 *
	 * @since 	0.4.0
	 */

	public function get_saved_urls_list()
	{
		check_ajax_referer( 'wpso_ajax_conditions_action', 'ajax_nonce' );

		global $wpdb;

		$singles = $wpdb->get_results(
			"
			SELECT query_string
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE query_string != 'global'
			GROUP BY query_string
			"
		);

		if ( $singles )
		{
			if ( isset( $_POST['query_string'] ) && ! empty( $_POST['query_string'] ) )
			{
				$current_query_string = wp_strip_all_tags( base64_decode( $_POST['query_string'] ) );
			}

			foreach ( $singles as $single )
			{
				$active_class = ( $single->query_string == $current_query_string )
					? ' active'
					: '';

				echo '
				<div class="wpso-saved-url'. $active_class .'">
					<span class="wpso-saved-url-link" data-querystring="' . $single->query_string . '"><span title="' . $single->query_string . '">' . $single->query_string . '</span></span>
					<span class="wpso-saved-url-stats">
						<span title="' . __( 'Number of scripts', 'wp-script-optimizer' ) . '"><i class="fa fa-code"></i>&nbsp;' . Wpso::get_count( 'script', $single->query_string ) . '</span>&nbsp;&nbsp;&nbsp;
						<span title="' . __( 'Number of styles', 'wp-script-optimizer' ) . '"><i class="fa fa-css3"></i>&nbsp;' . Wpso::get_count( 'style', $single->query_string ) . '</span>
					</span>
				</div>';
			}
		}
		else
		{
			echo '
			<p>' . __( 'No saved query string settings found', 'wp-script-optimizer' ) . '</p>';
		}

		die();
	}
}
