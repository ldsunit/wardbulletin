<?php
if ( ! defined('ABSPATH') )
	exit;


/**
 * WpsoConditions class
 *
 * @since 	0.1.0
 */

class WpsoConditions
{
	/**
	 * Content of $wp_scripts
	 *
	 * @since 	0.4.3
	 * @var 	array
	 */

	public static $wpso_scripts = [];


	/**
	 * Content of $wp_styles
	 *
	 * @since 	0.4.3
	 * @var 	array
	 */

	public static $wpso_styles = [];


	/**
	 * Dummy constructor
	 *
	 * @since 	0.1.0
	 */

	public function __construct()
	{
		// do nothing here...
	}


	/**
	 * Get the HTML for displaying the condition groups of any handle
	 *
	 * @since 	0.1.0
	 *
	 * @param 	int 			$handle_id
	 * @return 	string|false 				The condition group HTML or false if no handle_id is set
	 */

	public static function get_conditions( $handle_id )
	{
		global $wpdb;

		if ( ! $handle_id )
			return false;

		$conditions = $wpdb->get_var($wpdb->prepare(
			"
			SELECT conditions
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE id = %d
			",
			$handle_id
		));

		$conditions = json_decode( $conditions, true );

		if ( empty( $conditions ) )
		{
			$render = '
			<p><strong>' . __( 'Will be integrated, if', 'wp-script-optimizer' ) . '</strong></p>
			<div class="wpso-condition-group" data-condition-group="1">
				<table class="wpso-table">
					<tbody>
						<tr class="wpso-condition-single" data-condition-single="1">
							<td class="condition">' . self::get_condition_select( 'page_type' ) . '</td>
							<td class="operator">' . self::get_operator_select( 'is' ) . '</td>
							<td class="value">' . self::get_value_select( 'page_type' ) . '</td>
							<td class="add-condition">' . self::get_button( 'add' ) . '</td>
							<td class="remove-condition">' . self::get_button( 'remove' ) . '</td>
						</tr>
					</tbody>
				</table>
				<p><strong>' . __( 'or', 'wp-script-optimizer' ) . '</strong></p>
			</div>
			<a href="#" class="button add-condition-group">' . __( 'Add condition group', 'wp-script-optimizer' ) . '</a>';
		}
		else
		{
			$render = '
			<p><strong>' . __( 'Will be integrated, if', 'wp-script-optimizer' ) . '</strong></p>';

			$i = 0;
			foreach ( $conditions as $group )
			{
				$render .= '
				<div class="wpso-condition-group" data-condition-group="' . ++$i . '">
					<table class="wpso-table">
						<tbody>';

				$j = 0;
				foreach ( $group as $single )
				{
					$render .= '
					<tr class="wpso-condition-single" data-condition-single="' . ++$j . '">
						<td class="condition">' . self::get_condition_select( $single['condition'] ) . '</td>
						<td class="operator">' . self::get_operator_select( $single['operator'] ) . '</td>
						<td class="value">' . self::get_value_select( $single['condition'], $single['value'] ) . '</td>
						<td class="add-condition">' . self::get_button( 'add' ) . '</td>
						<td class="remove-condition">' . self::get_button( 'remove' ) . '</td>
					</tr>';
				}

				$render .= '
						</tbody>
					</table>
					<p><strong>' . __( 'or', 'wp-script-optimizer' ) . '</strong></p>
				</div>';
			}

			$render .= '
			<a href="#" class="button add-condition-group">' . __( 'Add condition group', 'wp-script-optimizer' ) . '</a>';
		}

		return $render;
	}


	/**
	 * Remove conditions of a specific handle
	 *
	 * @since 	0.1.0
	 *
	 * @param 	int 		$handle_id
	 * @return 	void|false 				Returns nothing or false, when no id.
	 */

	public static function remove_conditions( $handle_id )
	{
		global $wpdb;

		if ( ! $handle_id || ! current_user_can( 'manage_options' ) )
			return;

		$wpdb->update(
			$wpdb->prefix . WPSO_DB_TABLE,
			array(
				'conditions' => '',
			),
			array(
				'id' => $handle_id
			),
			array( '%s' ),
			array( '%d' )
		);
	}


	/**
	 * Get the conditions select field HTML
	 *
	 * @since 	0.1.0
	 *
	 * @param 	string 	$selected 	The selected value
	 * @return 	string 				The select field HTML
	 */

	private static function get_condition_select( $selected )
	{
		$selected = ( empty( $selected ) )
			? ''
			: esc_attr( $selected );

		$select = '
		<select>
			<optgroup label="' . __( 'General', 'wp-script-optimizer' ) . '">
				<option value="page_type" ' . selected( $selected, 'page_type', false ) . '>' . __( 'Page Type', 'wp-script-optimizer' ) . '</option>
			</optgroup>
			<optgroup label="' . __( 'Posts', 'wp-script-optimizer' ) . '">
				<option value="is_singular" ' . selected( $selected, 'is_singular', false ) . '>' . __( 'Post Type', 'wp-script-optimizer' ) . '</option>
				<option value="get_post_format" ' . selected( $selected, 'get_post_format', false ) . '>' . __( 'Post Format', 'wp-script-optimizer' ) . '</option>
				<option value="in_category" ' . selected( $selected, 'in_category', false ) . '>' . __( 'Post Category', 'wp-script-optimizer' ) . '</option>
				<option value="has_term" ' . selected( $selected, 'has_term', false ) . '>' . __( 'Post Taxonomy', 'wp-script-optimizer' ) . '</option>
				<option value="is_single" ' . selected( $selected, 'is_single', false ) . '>' . __( 'Post', 'wp-script-optimizer' ) . '</option>
			</optgroup>
			<optgroup label="' . __( 'Page', 'wp-script-optimizer' ) . '">
				<option value="get_page_template_slug" ' . selected( $selected, 'get_page_template_slug', false ) . '>' . __( 'Page Template', 'wp-script-optimizer' ) . '</option>
				<option value="wp_get_post_parent_id" ' . selected( $selected, 'wp_get_post_parent_id', false ) . '>' . __( 'Parent Page', 'wp-script-optimizer' ) . '</option>
				<option value="is_page" ' . selected( $selected, 'is_page', false ) . '>' . __( 'Page', 'wp-script-optimizer' ) . '</option>
			</optgroup>
			<optgroup label="' . __( 'User', 'wp-script-optimizer' ) . '">
				<option value="current_user_can" ' . selected( $selected, 'current_user_can', false ) . '>' . __( 'Current User Role', 'wp-script-optimizer' ) . '</option>
			</optgroup>
		</select>';

		return $select;
	}


	/**
	 * Get the operators select field HTML
	 *
	 * @since 0.1.0
	 *
	 * @param 	string 	$selected 	The selected value
	 * @return 	string 				The select field HTML
	 */

	private static function get_operator_select( $selected )
	{
		$selected = ( empty( $selected ) )
			? ''
			: esc_attr( $selected );

		$select = '
		<select>
			<option value="is" ' . selected( $selected, 'is', false ) . '>' . __( 'Is equal', 'wp-script-optimizer' ) . '</option>
			<option value="is_not" ' . selected( $selected, 'is_not', false ) . '>' . __( 'Is not equal', 'wp-script-optimizer' ) . '</option>
		</select>';

		return $select;
	}


	/**
	 * Get the values select field HTML
	 *
	 * @since 	0.1.0
	 *
	 * @param 	string 	$field 		The conditions select field value from self::get_condition_select()
	 * @param 	string 	$selected 	The selected value
	 *
	 * @return 	string 				The select field HTML
	 */

	public static function get_value_select( $field, $selected = '' )
	{
		if ( ! $field )
		{
			return false;
		}

		$selected = ( empty( $selected ) )
			? ''
			: esc_attr( $selected );

		$select = null;

		switch ( $field )
		{
			case 'page_type':
				$select = '<select>';
				$select .= '
					<option value="is_home" ' . selected( $selected, 'is_home', false ) . '>' . __( 'Blog Homepage', 'wp-script-optimizer' ) . '</option>
					<option value="is_front_page" ' . selected( $selected, 'is_front_page', false ) . '>' . __( 'Front Page', 'wp-script-optimizer' ) . '</option>
					<option value="is_single" ' . selected( $selected, 'is_single', false ) . '>' . __( 'Post', 'wp-script-optimizer' ) . '</option>
					<option value="is_sticky" ' . selected( $selected, 'is_sticky', false ) . '>' . __( 'Sticky', 'wp-script-optimizer' ) . '</option>
					<option value="comments_open" ' . selected( $selected, 'comments_open', false ) . '>' . __( 'Comments open', 'wp-script-optimizer' ) . '</option>
					<option value="is_page" ' . selected( $selected, 'is_page', false ) . '>' . __( 'Page', 'wp-script-optimizer' ) . '</option>
					<option value="is_archive" ' . selected( $selected, 'is_archive', false ) . '>' . __( 'Archive', 'wp-script-optimizer' ) . '</option>
					<option value="is_search" ' . selected( $selected, 'is_search', false ) . '>' . __( 'Search Results', 'wp-script-optimizer' ) . '</option>
					<option value="is_404" ' . selected( $selected, 'is_404', false ) . '>' . __( '404', 'wp-script-optimizer' ) . '</option>
					<option value="is_attachment" ' . selected( $selected, 'is_attachment', false ) . '>' . __( 'Attachment', 'wp-script-optimizer' ) . '</option>';
				$select .= '</select>';
				break;

			case 'is_singular':
				$select = '<select>';
				foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type )
				{
					$select .= '<option value="' . esc_attr( $post_type->name ) . '" ' . selected( $selected, $post_type->name, false ) . '>' . $post_type->label . '</option>';
				}
				$select .= '</select>';
				break;

			case 'get_post_format':
				$select = '<select>';
				foreach ( get_post_format_strings() as $name => $label )
				{
					$select .= '<option value="' . esc_attr( $name ) . '" ' . selected( $selected, $name, false ) . '>' . $label . '</option>';
				}
				$select .= '</select>';
				break;

			case 'in_category':
				$select = wp_dropdown_categories( array( 'echo' => false, 'hierarchical' => true, 'hide_empty' => false, 'value_field' => 'slug', 'selected' => $selected ) );
				break;

			case 'has_term':
				$select = '<select>';
				foreach ( get_taxonomies( array(), 'objects' ) as $taxonomy )
				{
					if ( wp_count_terms( $taxonomy->name ) && $taxonomy->name != 'category' )
					{
						$select .= '<optgroup label="' . $taxonomy->label . '">';
						foreach ( get_terms( array( 'taxonomy' => $taxonomy->name, 'hierarchical' => true, 'hide_empty' => false ) ) as $term )
						{
							$value = esc_attr( $term->slug ) . '|' . esc_attr( $taxonomy->name );
							$select .= '<option value="' . $value . '" ' . selected( $selected, $value, false ) . '>' . $term->name . '</option>';
						}
						$select .= '</optgroup>';
					}
				}
				$select .= '</select>';
				break;

			case 'is_single':
				$select = '<select>';
				foreach ( get_posts( array( 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $post )
				{
					$select .= '<option value="' . esc_attr( $post->ID ) . '" ' . selected( $selected, $post->ID, false ) . '>' . $post->post_title . '</option>';
				}
				$select .= '</select>';
				break;

			case 'get_page_template_slug':
				$select = '<select>';
				$select .= '<option value="default">' . __( 'Default Template', 'wp-script-optimizer' ) . '</option>';
				foreach ( get_page_templates() as $name => $filename )
				{
					$select .= '<option value="' . esc_attr( $filename ) . '" ' . selected( $selected, $filename, false ) . '>' . $name . '</option>';
				}
				$select .= '</select>';
				break;

			case 'wp_get_post_parent_id':
				$select = wp_dropdown_pages( array( 'echo' => false, 'selected' => $selected ) );
				break;

			case 'is_page':
				$select = wp_dropdown_pages( array( 'echo' => false, 'selected' => $selected ) );
				break;

			case 'current_user_can':
				global $wp_roles;

				$select = '<select>';
				foreach ( $wp_roles->role_names as $name => $label )
				{
					$select .= '<option value="' . esc_attr( $name ) . '" ' . selected( $selected, $name, false ) . '>' . $label . '</option>';
				}
				$select .= '</select>';
				break;

			default:
				# code...
				break;
		}

		return $select;
	}


	/**
	 * Get add or remove button
	 *
	 * @since 	0.1.0
	 *
	 * @param 	string 	Can be 'add' or 'remove'
	 * @return 	string 	Button HTML
	 */

	private static function get_button( $type )
	{
		switch ( $type )
		{
			case 'add':
				$button = '<input type="button" class="button add-condition-single" value="' . __( 'and', 'wp-script-optimizer' ) . '" />';
				break;

			case 'remove':
				$button = '
				<a href="#" class="remove-condition-single">
					<span class="fa-stack">
						<i class="fa fa-circle fa-stack-2x"></i>
						<i class="fa fa-minus fa-stack-1x fa-inverse"></i>
					</span>
				</a>';
				break;

			default:
				break;
		}

		return $button;
	}


	/**
	 * Process all header handles stored in the database
	 * and enqueues them after checking the conditional statements
	 *
	 * Fires only when not in backend or in check-mode
	 *
	 * @since 	0.3.0
	 */

	public static function process_header_items()
	{
		if ( is_admin() )
			return;

		global $wpdb, $wp_scripts, $wp_styles;

		update_option( 'wpso_wp_scripts_array', $wp_scripts );
		update_option( 'wpso_wp_styles_array', $wp_styles );

		self::$wpso_scripts = get_option( 'wpso_wp_scripts_array' );
		self::$wpso_styles  = get_option( 'wpso_wp_styles_array' );

		$query_string = ( Wpso::query_string_exist( Wpso::get_query_string() ) )
			? Wpso::get_query_string()
			: 'global';

		self::deregister_handles( $query_string );

		$items = $wpdb->get_results(
			"
			SELECT *
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE `group_new` = 0
				AND `query_string` = '".esc_sql( $query_string )."'
			"
		);

		if ( $items )
		{
			self::process_items( $items );
		}
		
	}


	/**
	 * Process all footer handles stored in the database
	 * and enqueues them after checking the conditional statements
	 *
	 * Fires only when not in backend or in check-mode
	 *
	 * @since 	0.3.0
	 */

	public static function process_footer_items()
	{
		if ( is_admin() )
			return;

		global $wpdb;

		$query_string = ( Wpso::query_string_exist( Wpso::get_query_string() ) )
			? Wpso::get_query_string()
			: 'global';

		$items = $wpdb->get_results(
			"
			SELECT *
			FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
			WHERE `group_new` = 1
				AND `query_string` = '".esc_sql( $query_string )."'
			"
		);

		if ( $items )
		{
			self::process_items( $items );
		}
	}


	/**
	 * Process an array of handles and enqueue them if the conditionals match true
	 *
	 * @param 	$items 	Array of handles from the database
	 * @since 	0.3.0
	 */

	public static function process_items( $items )
	{
		if ( ! $items || ! is_array( $items ) )
			return;

		foreach ( $items as $item )
		{
			# Since every stored handle is deregistered at this point, we can now enqueue it again and check the conditions
			if ( $item->status == 'active' )
			{
				if ( empty( $item->conditions ) )
				{
					self::enqueue_handle( $item );
				}
				else
				{
					if ( self::check_conditions( json_decode( $item->conditions ) ) === true )
					{
						self::enqueue_handle( $item );
					}
				}
			}
		}
	}


	/**
	 * Enqueues a handle and add all extra data to it, i.e. localization variables or stylesheet conditionals
	 *
	 * @param 	$handle
	 * @since 	0.3.0
	 */

	public static function enqueue_handle( $handle )
	{
		if ( ! $handle || ! is_object( $handle ) )
			return;

		if ( $handle->handle == 'jquery' )
		{
			wp_register_script( 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.12.4', $handle->group_new );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-core', '/wp-includes/js/jquery/jquery.js', array(), '1.12.4', $handle->group_new );
			wp_enqueue_script( 'jquery-migrate', '/wp-includes/js/jquery/jquery-migrate.min.js', array(), '1.4.1', $handle->group_new );
		}
		else
		{
			if ( $handle->type == 'script' )
			{
				wp_register_script(
					$handle->handle,
					$handle->src_original,
					json_decode( $handle->deps ),
					( empty( $handle->version ) ) ? null : $handle->version,
					$handle->group_new
				);
				wp_enqueue_script( $handle->handle );
			}
			elseif ( $handle->type == 'style' )
			{
				wp_register_style(
					$handle->handle,
					$handle->src_original,
					json_decode( $handle->deps ),
					( empty( $handle->version ) ) ? null : $handle->version,
					( empty( $handle->args ) ) ? 'all' : $handle->args
				);
				wp_enqueue_style( $handle->handle );
			}
		}

		if ( ! empty( self::$wpso_scripts->registered[$handle->handle]->extra ) )
		{
			foreach ( self::$wpso_scripts->registered[$handle->handle]->extra as $key => $value )
			{
				if ( $key == 'group' )
					continue;

				wp_script_add_data( $handle->handle, $key, $value );
			}
		}

		if ( ! empty( self::$wpso_styles->registered[$handle->handle]->extra ) )
		{
			foreach ( self::$wpso_styles->registered[$handle->handle]->extra as $key => $value )
			{
				if ( $key == 'group' )
					continue;

				wp_style_add_data( $handle->handle, $key, $value );
			}
		}
	}


	/**
	 * Deregister all handles that are saved in the wpso db-table for a specific query string
	 * Will be fired in in wp_head right before handles are enqueued again
	 *
	 * @since 	0.3.0
	 * @param 	string 	$query_string
	 */

	public static function deregister_handles( $query_string )
	{
		global $wpdb;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT handle, type
				FROM " . $wpdb->prefix . WPSO_DB_TABLE . "
				WHERE `query_string` = '%s'
				",
				$query_string
			)
		); 

		if ( $items )
		{
			foreach ( $items as $item )
			{
				if ( $item->handle == 'jquery' )
				{ 
					wp_deregister_script( 'jquery' );
					wp_deregister_script( 'jquery-core' );
					wp_deregister_script( 'jquery-migrate' );
					continue;
				}

				$item->type == 'script'
					? wp_deregister_script( $item->handle )
					: wp_deregister_style( $item->handle );
			}
		}
	}


	/**
	 * Forms a logical statement out of the conditions that are set for a handle.
	 *
	 * @since 	0.1.0
	 *
	 * @param 	object 	Conditions
	 * @return 	bool
	 */

	private static function check_conditions( $conditions )
	{
		if ( count( $conditions ) > 0 && is_object( $conditions ) )
		{
			$logic = '';

			$operator = array(
				'is' 	 => array(
					' == ',
					' '
				),
				'is_not' => array(
					' != ',
					'! '
				)
			);

			$i = 0;
			foreach ( $conditions as $group )
			{
				if ( ++$i > 1 )
				{
					$logic .= ' || ';
				}

				$j = 0;
				$logic .= '( ';
				foreach ( $group as $single )
				{
					if ( ++$j > 1 )
					{
						$logic .= ' && ';
					}

					switch ( $single->condition )
					{
						case 'page_type':
							switch ( $single->value )
							{
								case 'is_home':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_front_page':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_single':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_sticky':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'comments_open':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_page':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_archive':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_search':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_404':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								case 'is_attachment':
									$logic .= $operator[$single->operator][1] . $single->value . '()';
									break;

								default:
									return false;
							}
							break;

						case 'is_singular':
							$logic .= $operator[$single->operator][1] . $single->condition . "( '" . $single->value . "' )";
							break;

						case 'get_post_format':
							$logic .= $single->condition . "()" . $operator[$single->operator][0] . "'" . $single->value . "'";
							break;

						case 'in_category':
							$logic .= $operator[$single->operator][1] . $single->condition . "( '" . $single->value . "' )";
							break;

						case 'has_term':
							$value = explode('|', $single->value);
							$logic .= $operator[$single->operator][1] . $single->condition . "( '" . $value[0] . "', '" . $value[1] . "' )";
							break;

						case 'is_single':
							$logic .= $operator[$single->operator][1] . $single->condition . "( " . $single->value . " )";
							break;

						case 'get_page_template_slug':
							$logic .= $single->condition . "()" . $operator[$single->operator][0] . "'" . $single->value . "'";
							break;

						case 'wp_get_post_parent_id':
							$logic .= $single->condition . "( get_the_ID() )" . $operator[$single->operator][0] . $single->value;
							break;

						case 'is_page':
							$logic .= $operator[$single->operator][1] . $single->condition . "( " . $single->value . " )";
							break;

						case 'current_user_can':
							$logic .= $operator[$single->operator][1] . $single->condition . "( '" . $single->value . "' )";
							break;

						default:
							return false;
					}
				}
				$logic .= ' )';
			}
		}

		return eval('return boolval( ' . $logic . ' );');
	}
}
