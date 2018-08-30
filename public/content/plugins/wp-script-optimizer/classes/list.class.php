<?php
if ( ! defined('ABSPATH') )
	exit;


/**
 * WpsoList class
 *
 * @since 	0.2.1
 */

class WpsoList
{
	/**
	 * Type of list (script or style)
	 *
	 * @since 	0.2.1
	 * @var 	string
	 */

	public $type;


	/**
	 * List group | 0 = Header, 1 = Footer
	 *
	 * @since 	0.2.1
	 * @var 	int
	 */

	public $group;


	/**
	 * Array of items
	 *
	 * @since 	0.2.1
	 * @var 	array
	 */

	public $items = array();


	/**
	 * Number of found items in database
	 *
	 * @since 	0.2.1
	 * @var 	int
	 */

	public $found_items;


	/**
	 * Class constructor
	 *
	 * @since  	0.2.1
	 *
	 * @param 	string 	$type 	Can be script or style
	 * @param 	int 	$group 	0 = header, 1 = footer
	 */

	public function __construct( $type, $group = 0 )
	{
		$this->type  = (string) $type;
		$this->group = (int) $group;

		if ( $this->type === 'script' || $this->type === 'style' )
		{
			$this->process_actions();
			$this->get_items( $this->type, $this->group );
		}
	}


	/**
	 * Returns true if items were found
	 *
	 * @since  	0.2.1
	 * @return 	bool
	 */

	public function have_items()
	{
		return ( $this->found_items > 0 ) ? true : false;
	}


	/**
	 * Process all single and bulk actions
	 *
	 * @since  	0.2.1
	 */

	public function process_actions()
	{
		if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpso_item_action_nonce' ) )
		{
			$handle = Wpso::get_handle_data( (int) $_GET['handle_id'] );

			switch ( sanitize_key( $_GET['action'] ) )
			{
				case 'activate':
					Wpso::change_handle_status( $handle->id, 'active' );
					break;

				case 'deactivate':
					Wpso::change_handle_status( $handle->id, 'inactive' );
					break;

				case 'in_header':
					Wpso::change_handle_group( $handle->id, 0 );
					break;

				case 'in_footer':
					Wpso::change_handle_group( $handle->id, 1 );
					break;

				case 'delete':
					Wpso::delete_handle( $handle->id );
					break;

				case 'reset':
					Wpso::reset_handle( $handle->id );
					break;
			}
		}

		if ( wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'wpso_bulk_item_action_nonce' ) )
		{
			$bulk_ids = array_map( 'intval', $_POST['wpso-cb'] );

			switch ( sanitize_key( $_POST['action'] ) )
			{
				case 'bulk-activate':
					if ( ! empty( $bulk_ids ) )
					{
						foreach ( $bulk_ids as $bulk_id )
						{
							$handle = Wpso::get_handle_data( $bulk_id );
							Wpso::change_handle_status( (int) $handle->id, 'active' );
						}
					}
					break;

				case 'bulk-deactivate':
					if ( ! empty( $bulk_ids ) )
					{
						foreach ( $bulk_ids as $bulk_id )
						{
							$handle = Wpso::get_handle_data( $bulk_id );
							Wpso::change_handle_status( (int) $handle->id, 'inactive' );
						}
					}
					break;

				case 'bulk-in-header':
					if ( ! empty( $bulk_ids ) )
					{
						foreach ( $bulk_ids as $bulk_id )
						{
							$handle = Wpso::get_handle_data( $bulk_id );
							Wpso::change_handle_group( (int) $handle->id, 0 );
						}
					}
					break;

				case 'bulk-in-footer':
					if ( ! empty( $bulk_ids ) )
					{
						foreach ( $bulk_ids as $bulk_id )
						{
							$handle = Wpso::get_handle_data( $bulk_id );
							Wpso::change_handle_group( (int) $handle->id, 1 );
						}
					}
					break;

				case 'bulk-remove-conditions':
					if ( ! empty( $bulk_ids ) )
					{
						foreach ( $bulk_ids as $bulk_id )
						{
							$handle = Wpso::get_handle_data( $bulk_id );
							WpsoConditions::remove_conditions( (int) $handle->id );
						}
					}
					break;

				case 'bulk-reset':
					if ( ! empty( $bulk_ids ) )
					{
						foreach ( $bulk_ids as $bulk_id )
						{
							$handle = Wpso::get_handle_data( $bulk_id );
							Wpso::reset_handle( (int) $handle->id );
						}
					}
					break;
			}
		}

		$admin_url = untrailingslashit( admin_url( 'admin.php' ) );

		$admin_url = ( ! empty( Wpso::$page_handle ) )
			? add_query_arg( 'page', sanitize_title( Wpso::$page_handle ), $admin_url )
			: $admin_url;

		$admin_url = ( ! empty( Wpso::$query_string ) )
			? add_query_arg( 'qs', base64_encode( Wpso::$query_string ), $admin_url )
			: $admin_url;

		echo '
		<script>
			if ( typeof window.history.pushState == "function" ) {
				window.history.pushState({}, "Hide", "' . $admin_url . '");
			}
		</script>';
	}


	/**
	 * Process all data and print out the html list
	 *
	 * @since  	0.2.1
	 */

	public function display()
	{
		if ( ! $this->have_items() )
		{
			$this->no_items();
			return;
		}

		echo '
		<div class="tablenav bottom">
			<div class="wpso-item-checkbox-master alignleft">
				<input type="checkbox" />
			</div>
			<div class="alignleft actions bulkactions">
				' . $this->bulk_actions_select( $this->type, $this->group ) . '
				' . get_submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction-" . $this->type ) ) . '
			</div>
			<div class="tablenav-pages one-page">
				<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $this->found_items ), number_format_i18n( $this->found_items ) ) . '</span>
			</div>
		</div>

		<div class="wpso-item-list">';
			foreach ( $this->items as $item )
			{
				$this->single_item( $item );
			}
		echo '
		</div>';
	}


	/**
	 * Message, if no items were found
	 *
	 * @since  	0.2.1
	 */

	public function no_items()
	{
		echo '<strong style="font-size:larger;">' . __( 'No items found.', 'wp-script-optimizer' ) . '</strong>';
	}


	/**
	 * Get items of a specific type and group
	 *
	 * @since  	0.2.1
	 *
	 * @param 	string 	$type 	can be script or style
	 * @param 	int 	$group 	0 = Header, 1 = Footer
	 */

	private function get_items( $type, $group )
	{
		global $wpdb;

		$type  		  = wp_strip_all_tags( $type );
		$group 		  = (int) $group;
		$query_string = Wpso::$query_string;

		$results = $wpdb->get_results(
			"
			SELECT *
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE `type` = '$type'
				AND (`src_original` != '' OR `handle` = 'jquery')
				AND `group_new` = '$group'
				AND `query_string` = '$query_string'
			"
		);

		$this->found_items = count( $results );
		$this->items 	   = $results;
	}


	/**
	 * Get HTML for a single item
	 *
	 * @since  	0.2.1
	 * @param 	object 	$item 	Object holds all data for one item
	 */

	private function single_item( $item )
	{
		# is there a version?
		$version = ( ! empty( $item->version ) )
			? '[ver. ' . $item->version . ']'
			: '';

		# get correct script source
		$src_original 	  = str_ireplace( get_home_url(), '', $item->src_original );
		$src_original_url = ( stripos( $item->src_original, get_home_url() ) === false )
			? untrailingslashit( get_home_url() ) . $item->src_original
			: $item->src_original;

		# source html
		$source = ( $item->handle == 'jquery' )
			? __( 'Placeholder for "jquery-core" and "jquery-migrate"', 'wp-script-optimizer' )
			: '<i>' . $src_original . '</i>&nbsp;&nbsp;<a href="' . $src_original_url . '" target="_blank"><i class="fa fa-external-link" title="' . __( 'Open file in new tab', 'wp-script-optimizer' ) . '"></i></a>';

		# is file a wordpress core file?
		$in_default_dir = ( $item->type == 'script' )
			? wp_scripts()->in_default_dir( $item->src_original )
			: wp_styles()->in_default_dir( $item->src_original );

		$core_warning = ( $in_default_dir || $item->handle == 'jquery' )
			? '<i class="fa fa-exclamation-circle" style="color:#F44336;" title="' . __( 'Wordpress Core-File. Be careful, if you change it!', 'wp-script-optimizer' ) . '"></i> '
			: '';

		# get dependencys
		$item_deps = ( ! empty( $item->deps ) )
			? '<small><span style="color:red;">' . __( 'Requires:', 'wp-script-optimizer' ) . '</span> ' . implode( ', ', json_decode( $item->deps ) ) . '</small>'
			: '';

		# get status messages and css classes
		if ( $item->status == 'inactive' )
		{
			$item_status = '<span style="color:#F44336;"><strong>' . __( 'Deactivated', 'wp-script-optimizer' ) . '</strong></span>';
			$item_class  = ' wpso-item-inactive';
			$cb_disabled = '';
		}
		elseif ( ! empty( $item->conditions ) )
		{
			$item_status = '<span style="color:#4CAF50;"><strong>' . __( 'Conditions defined', 'wp-script-optimizer' ) . '</strong></span>';
			$item_class  = ' wpso-item-has-conditions';
			$cb_disabled = '';
		}
		else
		{
			$item_status = '';
			$item_class  = '';
			$cb_disabled = '';
		}

		echo '
		<div class="wpso-item-wrapper">
			<div id="wpso-item-' . $item->id . '" class="wpso-item' . $item_class . '">
				<div class="wpso-item-checkbox">
					<input type="checkbox" name="wpso-cb[]" value="' . $item->id . '" ' . $cb_disabled . ' />
				</div>
				<div class="wpso-item-content">
					<div>
						<span class="wpso-item-title">' . $core_warning . $item->handle . '</span>
						<small>' . $version . '</small>
					</div>
					<div>
						<small>' . $source . '</small>
					</div>
					<div>
						' . $item_deps . '
					</div>
				</div>
				<span class="wpso-item-actions">
					' . $this->item_actions( $item ) . '
				</span>
				<span class="wpso-item-status">' . $item_status . '</span>
			</div>

			<div class="hidden">
				<div class="wpso-item-edit">
					<p class="description">' . __( 'Create a set of rules that determine when this file is enqueued on your website.', 'wp-script-optimizer' ) . '</p>
					<hr>
					<div class="wpso-conditions">
						' . WpsoConditions::get_conditions( $item->id ) . '
					</div>
					<div class="wpso-conditions-options">
						' . get_submit_button( __( 'Remove all conditions', 'wp-script-optimizer' ), 'large wpso-delete-conditions', 'remove-conditions-' . $item->id, false, array( 'data-handle-id' => $item->id ) ) . '
						' . get_submit_button( __( 'Save & Close', 'wp-script-optimizer' ), 'primary large', 'save-conditions-' . $item->id, false, array( 'data-handle-id' => $item->id ) ) . '
					</div>
				</div>
			</div>
		</div>';
	}


	/**
	 * Get item actions based on item status
	 *
	 * @since  	0.2.1
	 *
	 * @param 	object 	$item
	 * @return 	string 	$actions
	 */

	private function item_actions( $item )
	{
		$actions 	  = array();
		$action_nonce = wp_create_nonce( 'wpso_item_action_nonce' );

		if ( Wpso::handle_has_changed( $item->id ) )
		{
			$actions[] = sprintf(
				'<a href="?page=%s&action=%s&handle_id=%s&qs=%s&_wpnonce=%s">
					<span class="fa-stack" title="%s">
						<i class="fa fa-square fa-stack-2x"></i>
						<i class="fa fa-undo fa-stack-1x fa-inverse"></i>
					</span>
				</a>',
				esc_attr( Wpso::$page_handle ),
				'reset',
				esc_attr( $item->id ),
				esc_attr( base64_encode( Wpso::$query_string ) ),
				esc_attr( $action_nonce ),
				__( 'Reset to original state', 'wp-script-optimizer' )
			);
		}

		( $item->status != 'active' )
			? $actions[] = sprintf(
				'<a href="?page=%s&action=%s&handle_id=%s&qs=%s&_wpnonce=%s">
					<span class="fa-stack" title="%s">
  						<i class="fa fa-square fa-stack-2x"></i>
  						<i class="fa fa-eye fa-stack-1x fa-inverse"></i>
					</span>
				</a>',
				esc_attr( Wpso::$page_handle ),
				'activate',
				esc_attr( $item->id ),
				esc_attr( base64_encode( Wpso::$query_string ) ),
				esc_attr( $action_nonce ),
				__( 'Activate', 'wp-script-optimizer' )
			)
			: $actions[] = sprintf(
				'<a href="?page=%s&action=%s&handle_id=%s&qs=%s&_wpnonce=%s">
					<span class="fa-stack" title="%s">
						<i class="fa fa-square fa-stack-2x"></i>
						<i class="fa fa-eye-slash fa-stack-1x fa-inverse"></i>
					</span>
				</a>',
				esc_attr( Wpso::$page_handle ),
				'deactivate',
				esc_attr( $item->id ),
				esc_attr( base64_encode( Wpso::$query_string ) ),
				esc_attr( $action_nonce ),
				__( 'Deactivate', 'wp-script-optimizer' )
			);

		if ( $item->status != 'inactive' )
		{
			( (int) $item->group_new === 1 )
				? $actions[] = sprintf(
					'<a href="?page=%s&action=%s&handle_id=%s&qs=%s&_wpnonce=%s">
						<span class="fa-stack" title="%s">
							<i class="fa fa-square fa-stack-2x"></i>
							<i class="fa fa-level-up fa-stack-1x fa-inverse"></i>
						</span>
					</a>',
					esc_attr( Wpso::$page_handle ),
					'in_header',
					esc_attr( $item->id ),
					esc_attr( base64_encode( Wpso::$query_string ) ),
					esc_attr( $action_nonce ),
					__( 'Into Header', 'wp-script-optimizer' )
				)
				: $actions[] = sprintf(
					'<a href="?page=%s&action=%s&handle_id=%s&qs=%s&_wpnonce=%s">
						<span class="fa-stack" title="%s">
							<i class="fa fa-square fa-stack-2x"></i>
							<i class="fa fa-level-down fa-stack-1x fa-inverse"></i>
						</span>
					</a>',
					esc_attr( Wpso::$page_handle ),
					'in_footer',
					esc_attr( $item->id ),
					esc_attr( base64_encode( Wpso::$query_string ) ),
					esc_attr( $action_nonce ),
					__( 'Into Footer', 'wp-script-optimizer' )
				);

			$actions[] = '
			<a href="#" class="wpso-item-edit-toggle">
				<span class="fa-stack" title="' . __( 'Change conditions', 'wp-script-optimizer' ) . '">
					<i class="fa fa-square fa-stack-2x"></i>
					<i class="fa fa-filter fa-stack-1x fa-inverse"></i>
				</span>
			</a>';
		}

		return implode( ' ', $actions );
	}


	/**
	 * Build a select field for bulk actions
	 *
	 * @since  	0.2.1
	 *
	 * @param 	string 	$type 	script or style
	 * @param 	int 	$group 	0 = Header, 1 = Footer
	 *
	 * @return 	string 	$select
	 */

	private function bulk_actions_select( $type, $group = 0 )
	{
		$select = '
		<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'wpso_bulk_item_action_nonce' ) . '" />
		<select name="action">
			<option value="-1">' . __( 'Bulk Actions' ) . '</option>
			<option value="bulk-activate">' . __( 'Activate', 'wp-script-optimizer' ) . '</option>
			<option value="bulk-deactivate">' . __( 'Deactivate', 'wp-script-optimizer' ) . '</option>
			<option value="bulk-remove-conditions">' . __( 'Remove conditions', 'wp-script-optimizer' ) . '</option>
			<option value="bulk-reset">' . __( 'Reset to original state', 'wp-script-optimizer' ) . '</option>';

			$select .= ( $group !== 0 )
				? '<option value="bulk-in-header">' . __( 'Into Header', 'wp-script-optimizer' ) . '</option>'
				: '<option value="bulk-in-footer">' . __( 'Into Footer', 'wp-script-optimizer' ) . '</option>';

		$select .= '
		</select>';

		return $select;
	}
}
