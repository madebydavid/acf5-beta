<?php 

class acf_field_group {

	/*
	*  __construct
	*
	*  Initialize filters, action, variables and includes
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	5.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function __construct() {
		
		// actions
		add_action( 'admin_enqueue_scripts',							array( $this,'admin_enqueue_scripts' ) );
		add_action( 'save_post',										array( $this,'save_post' ) );
		
		// ajax
		add_action( 'wp_ajax_acf/field_group/render_field_settings',	array( $this, 'ajax_render_field_settings') );
		add_action( 'wp_ajax_acf/field_group/render_location_value',	array( $this, 'ajax_render_location_value') );
		add_action( 'wp_ajax_acf/field_group/move_field',				array( $this, 'ajax_move_field') );
		
		// filters
		add_filter( 'post_updated_messages',							array( $this, 'post_updated_messages') );
	}
	
	
	/*
	*  post_updated_messages
	*
	*  This function will customize the message shown when editing a field group
	*
	*  @type	function
	*  @date	30/04/2014
	*  @since	5.0.0
	*
	*  @param	$messages (array)
	*  @return	$messages
	*/
	
	function post_updated_messages( $messages ) {
		
		// append to messages
		$messages['acf-field-group'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __('Field group updated.', 'acf'),
			2 => __('Custom field updated.', 'acf'),
			3 => __('Custom field deleted.', 'acf'),
			4 => __('Field group updated.', 'acf'),
			5 => false, // field group does not support revisions
			6 => __('Field group published.', 'acf'),
			7 => __('Field group saved.', 'acf'),
			8 => __('Field group submitted.', 'acf'),
			9 => __('Field group scheduled for.', 'acf'),
			10 => __('Field group draft updated.', 'acf'),
		);
		
		
		// return
		return $messages;
	}
	
	
	/*
	*  validate_page
	*
	*  This function will loop at the current page and return true if it is the acf-field-groups edit page
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	3.2.6
	*
	*  @param	N/A
	*  @return	(boolean)
	*/
	
	function validate_page() {
		
		// global
		global $pagenow, $typenow;
		

		// vars
		$r = false;
		
		
		// validate page
		if( in_array( $pagenow, array('post.php', 'post-new.php') ) )
		{
		
			// validate post type
			if( $typenow == 'acf-field-group' )
			{
				$r = true;
			}
			
		}
		
		
		// return
		return $r;
	}
	
	
	/*
	*  admin_enqueue_scripts
	*
	*  This function will add the already registered css
	*
	*  @type	function
	*  @date	28/09/13
	*  @since	5.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function admin_enqueue_scripts() {
		
		// validate page
		if( ! $this->validate_page() )
		{
			return;
		}
		
		
		// no autosave
		wp_dequeue_script( 'autosave' );
		
		
		// custom scripts
		wp_enqueue_style( 'acf-field-group' );
		wp_enqueue_script( 'acf-field-group' );
    
		
		// disable JSON to avoid conflicts between DB and JSON
		acf_disable_local();
		
		
		// actions
		add_action( 'admin_head', array( $this,'admin_head' ) );
		
				
		// 3rd party hook
		do_action( 'acf/field_group/admin_enqueue_scripts' );
		
	}
	
	
	/*
	*  admin_head
	*
	*  This function will setup all functionality for the field group edit page to work
	*
	*  @type	action (admin_head)
	*  @date	23/06/12
	*  @since	3.1.8
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function admin_head() {
		
		// global
		global $post;
		
		
		// vars
		$l10n = apply_filters( 'acf/field_group/admin_l10n', array(
			'move_to_trash'			=>	__("Move to trash. Are you sure?",'acf'),
			'checked'				=>	__("checked",'acf'),
			'no_fields'				=>	__("No toggle fields available",'acf'),
			'title_is_required'		=>	__("Field group title is required",'acf'),
			'copy'					=>	__("copy",'acf'),
			'or'					=>	__("or",'acf'),
			'fields'				=>	__("Fields",'acf'),
			'parent_fields'			=>	__("Parent fields",'acf'),
			'sibling_fields'		=>	__("Sibling fields",'acf'),
			'hide_show_all'			=>	__("Hide / Show All",'acf'),
			'move_field'			=>	__("Move Custom Field",'acf'),
			'move_field_warning'	=>	__("This field cannot be moved until it's changes have been saved",'acf'),
			'null'					=>	__("Null",'acf'),
		));
		
		$o = array(
			'post_id'				=>	$post->ID,
			'nonce'					=>	wp_create_nonce( 'acf_nonce' ),
			'admin_url'				=>	admin_url(),
			'ajaxurl'				=>	admin_url( 'admin-ajax.php' )
		);
		
		?>
		<script type="text/javascript">
		(function($) {
			
			acf.o = <?php echo json_encode( $o ); ?>;
			acf.l10n = <?php echo json_encode( $l10n ); ?>;
			
		})(jQuery);	
		</script>
		<?php
		
		
		// metaboxes
		add_meta_box('acf-field-group-fields', __("Fields",'acf'), array($this, 'mb_fields'), 'acf-field-group', 'normal', 'high');
		add_meta_box('acf-field-group-locations', __("Location",'acf'), array($this, 'mb_locations'), 'acf-field-group', 'normal', 'high');
		add_meta_box('acf-field-group-options', __("Options",'acf'), array($this, 'mb_options'), 'acf-field-group', 'normal', 'high');
		
		
		// add screen settings
		add_filter('screen_settings', array($this, 'screen_settings'), 10, 1);
		
		
		// 3rd party hook
		do_action('acf/field_group/admin_head');
		
		
		// hidden $_POST data
		add_action( 'edit_form_after_title', array($this, 'edit_form_after_title') );
		
	}
	
	
	/*
	*  screen_settings
	*
	*  description
	*
	*  @type	function
	*  @date	26/01/13
	*  @since	3.6.0
	*
	*  @param	$current (string)
	*  @return	$current
	*/
	
	function screen_settings( $current ) {
		
		// heading
	    $current .= '<h5>' . __("Fields",'acf') . '</h5>';
	    
	    
	    // radio buttons
	    $current .= '<div class="show-field-keys">' . __('Show Field Keys','acf') . ':';
		$current .= '<label><input type="radio" value="1" name="show_field_keys" />' . __('Yes','acf') . '</label>';
		$current .= '<label><input checked="checked" type="radio" value="0" name="show_field_keys" />' . __('No','acf') . '</label>';
		$current .= '</div>';
	    
	    
	    // return
	    return $current;
	}
	
	
	/*
	*  edit_form_after_title
	*
	*  This action will allow ACF to render metaboxes after the title
	*
	*  @type	action
	*  @date	17/08/13
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function edit_form_after_title() {
		
		?>
		<div id="acf-form-data" class="acf-hidden">
			<input type="hidden" name="_acfnonce" value="<?php echo wp_create_nonce( 'field_group' ); ?>" />
			<input type="hidden" name="_acf_delete_fields" value="0" id="input-delete-fields" />
			<?php do_action('acf/field_group/form_data'); ?>
		</div>
		<?php

	}
	
	
	/*
	*  save_post
	*
	*  This function will save all the field group data
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	1.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function save_post( $post_id ) {
		
		// do not save if this is an auto save routine
		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		{
			return $post_id;
		}
		
		
		// only save once! WordPress save's a revision as well.
		if( wp_is_post_revision($post_id) )
		{
	    	return $post_id;
        }
        
        
		// verify nonce
		if( !acf_verify_nonce('field_group') )
		{
			return $post_id;
		}
        
        
        // disable local to avoid conflicts between DB and local
		acf_disable_local();
		
        
        // save fields
		unset( $_POST['acf_fields']['acfcloneindex'] );
		
		if( !empty($_POST['acf_fields']) )
		{
			foreach( $_POST['acf_fields'] as $field )
			{
				// vars
				$specific = false;
				$save = acf_extract_var( $field, 'save' );
				
				
				// only saved field if has changed
				if( $save == 'meta' ) {
				
					$specific = array(
						'menu_order',
						'post_parent',
					);
					
				}
				
				
				// set field parent
				if( empty($field['parent']) ) {
					
					$field['parent'] = $post_id;
					
				}
				
				
				// save field
				acf_update_field( $field, $specific );
			}
		}
		
		
		// delete fields
        if( $_POST['_acf_delete_fields'] ) {
        	
	    	$ids = explode('|', $_POST['_acf_delete_fields']);
	    	$ids = array_map( 'intval', $ids );
	    	
			foreach( $ids as $id ) {
			
				if( $id != 0 ) {
				
					acf_delete_field( $id );
					
				}
				
			}
			
        }
		
		
		// add args
        $_POST['acf_field_group']['ID'] = $post_id;
        $_POST['acf_field_group']['title'] = $_POST['post_title'];
        
        
		// save field group
        acf_update_field_group( $_POST['acf_field_group'] );
		
		
        // return
        return $post_id;
	}
	
	
	/*
	*  mb_fields
	*
	*  This function will render the HTML for the medtabox 'acf-field-group-fields'
	*
	*  @type	function
	*  @date	28/09/13
	*  @since	5.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function mb_fields() {
		
		// global
		global $post;

		
		// vars
		$field_group = acf_get_field_group( $post );
		
		
		// get fields
		$view = array(
			'fields' => acf_get_fields_by_id( $field_group['ID'] )
		);
		
		
		// load view
		acf_get_view('field-group-fields', $view);
		
	}
	
	
	/*
	*  mb_options
	*
	*  This function will render the HTML for the medtabox 'acf-field-group-options'
	*
	*  @type	function
	*  @date	28/09/13
	*  @since	5.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function mb_options() {
		
		include( acf_get_path('admin/views/field-group-options.php') );
		
	}
	
	
	/*
	*  mb_locations
	*
	*  This function will render the HTML for the medtabox 'acf-field-group-locations'
	*
	*  @type	function
	*  @date	28/09/13
	*  @since	5.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function mb_locations() {
		
		include( acf_get_path('admin/views/field-group-locations.php') );
		
	}
	
	
	/*
	*  render_location_value
	*
	*  This function will render out an input containing location rule values for the given args
	*
	*  @type	function
	*  @date	30/09/13
	*  @since	5.0.0
	*
	*  @param	$options (array)
	*  @return	N/A
	*/
	
	function render_location_value( $options ) {
		
		// vars
		$options = wp_parse_args( $options, array(
			'group_id'	=> 0,
			'rule_id'	=> 0,
			'value'		=> null,
			'param'		=> null,
		));
		
		
		// vars
		$choices = array();
		
		
		// some case's have the same outcome
		if( $options['param'] == "page_parent" )
		{
			$options['param'] = "page";
		}

		
		switch( $options['param'] )
		{
			/*
			*  Basic
			*/
			
			case "post_type" :
				
				// all post types except attachment
				$exclude = array('attachment');
				$choices = acf_get_post_types( $exclude );
				$choices = acf_get_pretty_post_types( $choices );

				break;
			
			
			case "user_type" :
				
				global $wp_roles;
				
				$choices = $wp_roles->get_names();

				if( is_multisite() )
				{
					$choices['super_admin'] = __('Super Admin');
				}
								
				break;
				
			
			/*
			*  Post
			*/
			
			case "post" :
				
				// get post types
				$exclude = array('page', 'attachment');
				$post_types = acf_get_post_types( $exclude );
				
						
				// get posts grouped by post type
				$groups = acf_get_posts(array(
					'post_type' => $post_types
				));
				
				
				if( !empty($groups) ) {
			
					foreach( array_keys($groups) as $group_title ) {
						
						// vars
						$posts = acf_extract_var( $groups, $group_title );
						
						
						// override post data
						foreach( array_keys($posts) as $post_id ) {
							
							// update
							$posts[ $post_id ] = acf_get_post_title( $posts[ $post_id ] );
							
						};
						
						
						// append to $choices
						$choices[ $group_title ] = $posts;
						
					}
					
				}
				
				break;
			
			
			case "post_category" :
				
				$terms = acf_get_taxonomy_terms( 'category' );
				
				if( !empty($terms) ) {
					
					$choices = array_pop($terms);
					
				}
				
				break;
			
			
			case "post_format" :
				
				$choices = get_post_format_strings();
								
				break;
			
			
			case "post_status" :
				
				$choices = array(
					'publish'	=> __( 'Publish' ),
					'pending'	=> __( 'Pending Review' ),
					'draft'		=> __( 'Draft' ),
					'future'	=> __( 'Future' ),
					'private'	=> __( 'Private' ),
					'inherit'	=> __( 'Revision' ),
					'trash'		=> __( 'Trash' )
				);
								
				break;
			
			
			case "post_taxonomy" :
				
				$choices = acf_get_taxonomy_terms();
				
				// unset post_format
				if( isset($choices['post_format']) )
				{
					unset( $choices['post_format']) ;
				}
							
				break;
			
			
			/*
			*  Page
			*/
			
			case "page" :
				
				
				// get posts grouped by post type
				$groups = acf_get_posts(array(
					'post_type' => 'page'
				));
				
				
				if( !empty($groups) ) {
			
					foreach( array_keys($groups) as $group_title ) {
						
						// vars
						$posts = acf_extract_var( $groups, $group_title );
						
						
						// override post data
						foreach( array_keys($posts) as $post_id ) {
							
							// update
							$posts[ $post_id ] = acf_get_post_title( $posts[ $post_id ] );
							
						};
						
						
						// append to $choices
						$choices = $posts;
						
					}
					
				}
				
				
				break;
				
			
			case "page_type" :
				
				$choices = array(
					'front_page'	=>	__("Front Page",'acf'),
					'posts_page'	=>	__("Posts Page",'acf'),
					'top_level'		=>	__("Top Level Page (parent of 0)",'acf'),
					'parent'		=>	__("Parent Page (has children)",'acf'),
					'child'			=>	__("Child Page (has parent)",'acf'),
				);
								
				break;
			
			
			case "page_parent" :
				
				// refer to "page"
				
				break;
			
			
			case "page_template" :
				
				$choices = array(
					'default'	=>	__("Default Template",'acf'),
				);
				
				$templates = get_page_templates();
				
				foreach( $templates as $k => $v )
				{
					$choices[ $v ] = $k;
				}
				
				break;
				
			
			/*
			*  User
			*/
			
			case "user_role" :
				
				global $wp_roles;
				
				$choices = array_merge( array('all' => __('All', 'acf')), $wp_roles->get_names() );
			
				break;
				
				
			case "user_form" :
				
				$choices = array(
					'all' 		=> __('All', 'acf'),
					'edit' 		=> __('Add / Edit', 'acf'),
					'register' 	=> __('Register', 'acf')
				);
			
				break;
				
			
			/*
			*  Forms
			*/
			
			case "attachment" :
				
				$choices = array('all' => __('All', 'acf'));
			
				break;
				
				
			case "taxonomy" :
				
				$choices = array_merge( array('all' => __('All', 'acf')), acf_get_taxonomies() );
				
								
				// unset post_format
				if( isset($choices['post_format']) )
				{
					unset( $choices['post_format']) ;
				}
							
				break;
				
				
			case "comment" :
				
				$choices = array('all' => __('All', 'acf'));
			
				break;
			
			
			case "widget" :
				
				global $wp_widget_factory;
				
				$choices = array(
					'all' 		=> __('All', 'acf'),
				);
				
				
				if( !empty( $wp_widget_factory->widgets ) )
				{
					foreach( $wp_widget_factory->widgets as $widget )
					{
						$choices[ $widget->id_base ] = $widget->name;
					}
					
				}
								
				break;
		}
		
		
		// allow custom location rules
		$choices = apply_filters( 'acf/location/rule_values/' . $options['param'], $choices );
							
		
		// create field
		acf_render_field(array(
			'type'		=> 'select',
			'prefix'	=> "acf_field_group[location][{$options['group_id']}][{$options['rule_id']}]",
			'name'		=> 'value',
			'value'		=> $options['value'],
			'choices'	=> $choices,
		));
		
	}
	
	
	/*
	*  ajax_render_location_value
	*
	*  This function can be accessed via an AJAX action and will return the result from the render_location_value function
	*
	*  @type	function (ajax)
	*  @date	30/09/13
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function ajax_render_location_value() {
		
		// validate
		if( ! wp_verify_nonce($_POST['nonce'], 'acf_nonce') )
		{
			die();
		}
		
		
		// call function
		$this->render_location_value( $_POST );
		
		
		// die
		die();
								
	}
	
	
	/*
	*  ajax_render_field_settings
	*
	*  This function can be accessed via an AJAX action and will return the result from the acf_render_field_settings function
	*
	*  @type	function (ajax)
	*  @date	30/09/13
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function ajax_render_field_settings() {
		
		// vars
		$options = array(
			'nonce'			=> '',
			'parent'		=> 0,
			'field_group'	=> 0,
			'prefix'		=> '',
			'type'			=> '',
		);
		
		
		// load post options
		$options = wp_parse_args($_POST, $options);
		
		
		// verify nonce
		if( ! wp_verify_nonce($options['nonce'], 'acf_nonce') )
		{
			die(0);
		}
		
		
		// required
		if( ! $options['type'] )
		{
			die(0);
		}
		
				
		// render options
		$field = acf_get_valid_field(array(
			'type'			=> $options['type'],
			'name'			=> 'temp',
			'prefix'		=> $options['prefix'],
			'parent'		=> $options['parent'],
			'field_group'	=> $options['field_group'],
		));
		
		
		// render
		acf_render_field_settings( $field );
		
		
		// die
		die();
								
	}
	
	/*
	*  ajax_move_field
	*
	*  description
	*
	*  @type	function
	*  @date	20/01/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function ajax_move_field() {
		
		// disable JSON to avoid conflicts between DB and JSON
		acf_disable_local();
		
		
		$args = acf_parse_args($_POST, array(
			'nonce'				=> '',
			'field_id'			=> 0,
			'field_group_id'	=> 0
		));
		
		
		// verify nonce
		if( ! wp_verify_nonce($args['nonce'], 'acf_nonce') )
		{
			die();
		}
		
		
		// confirm?
		if( $args['field_id'] && $args['field_group_id'] )
		{
			// vars 
			$field = acf_get_field($args['field_id']);
			$field_group = acf_get_field_group($args['field_group_id']);
			
			
			// update parent
			$field['parent'] = $field_group['ID'];
			
			
			// remove conditional logic
			$field['conditional_logic'] = 0;
			
			
			// update field
			acf_update_field($field);
			
			$v1 = $field['label'];
			$v2 = '<a href="' . admin_url("post.php?post={$field_group['ID']}&action=edit") . '" target="_blank">' . $field_group['title'] . '</a>';
			
			echo '<p><strong>' . __('Move Complete.', 'acf') . '</strong></p>';
			echo  sprintf( __('The %s field can now be found in the %s field group', 'acf'), $v1, $v2 ). '</p>';
			
			echo '<a href="#" class="acf-button blue acf-close-popup">' . __("Close Window",'acf') . '</a>';
			
			die();
			
		}
		
		
		// get all field groups
		$field_groups = acf_get_field_groups();
		$choices = array();
		
		
		if( !empty($field_groups) )
		{
			foreach( $field_groups as $field_group )
			{
				if( $field_group['ID'] )
				{
					$choices[ $field_group['ID'] ] = $field_group['title'];
				}
			}
		}
		
		// render options
		$field = acf_get_valid_field(array(
			'type'		=> 'select',
			'name'		=> 'acf_field_group',
			'choices'	=> $choices
		));
		
		
		echo '<p>' . __('Please select the destination for this field', 'acf') . '</p>';
		
		echo '<form id="acf-move-field-form">';
		
			// render
			acf_render_field_wrap( $field );
			
			echo '<button type="submit" class="acf-button blue">' . __("Move Field",'acf') . '</button>';
			
		echo '</form>';
		
		
		// die
		die();
		
	}
	
	
}


// initialize
new acf_field_group();

?>