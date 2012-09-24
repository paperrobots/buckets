<?php
/* 
Plugin Name: Buckets
Plugin URI: http://www.matthewrestorff.com
Description: A Widget Alternative. Add reusable content inside of content. On a per page basis.
Author: Matthew Restorff
Version: 0.1.6
Author URI: http://www.matthewrestorff.com 
*/  


/*--------------------------------------------------------------------------------------
*
*	Buckets
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/
$version = '0.1.6';
add_action('init', 'init');
add_action('admin_menu', 'admin_menu');
add_action( 'admin_head', 'admin_head' );
add_shortcode( 'bucket', 'buckets_shortcode' );


/*--------------------------------------------------------------------------------------
*
*	init
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function init() 
{

	$labels = array(
	    'name' => __( 'Buckets', 'buckets' ),
		'singular_name' => __( 'Bucket', 'buckets' ),
	    'add_new' => __( 'Add New' , 'buckets' ),
	    'add_new_item' => __( 'Add New Bucket' , 'buckets' ),
	    'edit_item' =>  __( 'Edit Bucket' , 'buckets' ),
	    'new_item' => __( 'New Bucket' , 'buckets' ),
	    'view_item' => __('View Bucket', 'buckets'),
	    'search_items' => __('Search Buckets', 'buckets'),
	    'not_found' =>  __('No Buckets found', 'buckets'),
	    'not_found_in_trash' => __('No Buckets found in Trash', 'buckets'), 
	);

	register_post_type('buckets', array(
		'labels' => $labels,
		'public' => true,
		'show_ui' => true,
		'_builtin' =>  false,
		'capability_type' => 'page',
		'hierarchical' => true,
		'rewrite' => false,
		'query_var' => "buckets",
		'supports' => array(
			'title', 'editor', 'revisions',
		),
		'show_in_menu'	=> true,
	));
	
	// Make Sure ACF is loaded
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if (is_plugin_active('advanced-custom-fields/acf.php')) 
	{
		remove_post_type_support( 'buckets', 'editor' );
		load_first();
		register_field('Buckets_field', WP_PLUGIN_DIR . '/buckets/fields/buckets.php');
		create_field_groups();
	}

}



/*--------------------------------------------------------------------------------------
*
*	admin_menu
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function admin_menu()
{
	//add_submenu_page('edit.php?post_type=buckets', __('Manage','acf'), __('Manage','acf'), 'manage_options','manage-buckets', 'manage_buckets');
}


/*--------------------------------------------------------------------------------------
*
*	manage_buckets
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function manage_buckets()
{
	//include('admin/manage.php');
}




/*--------------------------------------------------------------------------------------
*
*	create_field_group
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function create_field_groups()
{

	$arr = (array)get_page_by_title('Buckets', OBJECT, 'acf');

	if (empty($arr)) {
		$buckets = array(
			'post_title'  => 'Buckets',
			'post_name'   => 'acf_buckets',
			'post_status' => 'publish',
			'post_type'   => 'acf',
			'comment_status' => 'closed',
			'ping_status' => 'closed'
		);
		$post_id = wp_insert_post($buckets);

		add_post_meta($post_id, '_edit_last', '1');
		add_post_meta($post_id, 'field_500f64ec049a0', 'a:10:{s:3:"key";s:19:"field_500f64ec049a0";s:5:"label";s:7:"Buckets";s:4:"name";s:7:"buckets";s:4:"type";s:16:"flexible_content";s:12:"instructions";s:0:"";s:8:"required";s:1:"0";s:7:"layouts";a:1:{i:0;a:4:{s:5:"label";s:13:"Visual Editor";s:4:"name";s:13:"visual_editor";s:7:"display";s:5:"table";s:10:"sub_fields";a:1:{i:0;a:7:{s:5:"label";s:7:"Content";s:4:"name";s:7:"content";s:4:"type";s:7:"wysiwyg";s:7:"toolbar";s:4:"full";s:12:"media_upload";s:3:"yes";s:3:"key";s:19:"field_50402dcb0fb1b";s:8:"order_no";s:1:"0";}}}}s:10:"sub_fields";a:1:{i:0;a:1:{s:3:"key";s:19:"field_50402dbe9787c";}}s:12:"button_label";s:12:"+ Add Bucket";s:8:"order_no";s:1:"0";}');
		add_post_meta($post_id, 'allorany', 'all');
		add_post_meta($post_id, 'rule', 'a:4:{s:5:"param";s:9:"post_type";s:8:"operator";s:2:"==";s:5:"value";s:7:"buckets";s:8:"order_no";s:1:"0";}');
		add_post_meta($post_id, 'position', 'normal');
		add_post_meta($post_id, 'layout', 'no_box');
		add_post_meta($post_id, 'hide_on_screen', 'a:9:{i:0;s:11:"the_content";i:1;s:7:"excerpt";i:2;s:13:"custom_fields";i:3;s:10:"discussion";i:4;s:8:"comments";i:5;s:4:"slug";i:6;s:6:"author";i:7;s:6:"format";i:8;s:14:"featured_image";}');

		$sidebars = array(
			'post_title'  => 'Sidebars',
			'post_name'   => 'acf_sidebars',
			'post_status' => 'publish',
			'post_type'   => 'acf',
			'comment_status' => 'closed',
			'ping_status' => 'closed'
		);
		$post_id = wp_insert_post($sidebars);

		add_post_meta($post_id, '_edit_last', '1');
		add_post_meta($post_id, 'field_500f64ec049a0', 'a:8:{s:3:"key";s:19:"field_500f653c5e213";s:5:"label";s:7:"Sidebar";s:4:"name";s:7:"sidebar";s:4:"type";s:13:"buckets_field";s:12:"instructions";s:0:"";s:8:"required";s:1:"0";s:3:"max";s:0:"";s:8:"order_no";s:1:"0";}');
		add_post_meta($post_id, 'allorany', 'all');
		add_post_meta($post_id, 'rule', 'a:4:{s:5:"param";s:9:"post_type";s:8:"operator";s:2:"==";s:5:"value";s:4:"page";s:8:"order_no";s:1:"0";}');
		add_post_meta($post_id, 'position', 'normal');
		add_post_meta($post_id, 'layout', 'no_box');
		add_post_meta($post_id, 'hide_on_screen', '');
	}

}





/*--------------------------------------------------------------------------------------
*
*	admin_head
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function admin_head()
{
	global $version;
	wp_enqueue_style('bucket-icons', plugins_url('',__FILE__) . '/css/icons.css?v=' . $version);
	if (isset($GLOBALS['post_type']) && $GLOBALS['post_type'] == 'buckets')
	{
		wp_enqueue_script('clipboard', plugins_url('',__FILE__) . '/js/zclip.js?v=' . $version);
		wp_enqueue_script('buckets', plugins_url('',__FILE__) . '/js/buckets.js?v=' . $version);
		wp_enqueue_style('buckets', plugins_url('',__FILE__) . '/css/buckets.css?v=' . $version);
		if ($GLOBALS['pagenow'] == 'post.php')
		{
			add_meta_box('buckets-shortcode', 'Shortcode', 'shortcode_meta_box', 'buckets', 'normal', 'high');
		}
	}
}



/*--------------------------------------------------------------------------------------
*
*	buckets_shortcode
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function buckets_shortcode($arg) 
{
	$return = get_bucket($arg['id'], true);
	return $return;
}



/*--------------------------------------------------------------------------------------
*
*	shortcode_meta_box
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function shortcode_meta_box()
{
	include('admin/shortcode.php');
}




/*--------------------------------------------------------------------------------------
*
*	get_bucket
*	outputs the bucket template
*
*	@author Matthew Restorff
*	@params id - post id of the bucket element
*	@params sc - if called from a shortcode the content needs to be put into a variable to output in the correct place
* 
*-------------------------------------------------------------------------------------*/

function get_bucket($id, $sc = false)
{

	$post = wp_get_single_post($id);
	$return = ($post->post_content != '') ? $post->post_content : '';

	//If ACF is Active perform some wizardry
	if (is_plugin_active('advanced-custom-fields/acf.php')) {
		while(has_sub_field("buckets", $id)) {
			$layout = get_row_layout();
		    if ($sc == true) { ob_start(); }

		    $file = str_replace(' ', '', $layout) . '.php';
		    $path = (file_exists(TEMPLATEPATH . '/buckets/' . $file)) ? TEMPLATEPATH . '/buckets/' . $file : WP_PLUGIN_DIR . '/buckets/templates/' . $file;
		    if (file_exists($path)) {
		    	include($path);
		    } else {
		    	return 'Bucket template does not exist.';
		    }

		    if ($sc == true) { $return .= ob_get_contents(); }
		    if ($sc == true) { ob_end_clean(); }
		}
	}
    return $return;
}



/*--------------------------------------------------------------------------------------
*
*	load_first
*	Loads the buckets plugin before the ACF plugin to ensure compatibility
*
*	@author Matthew Restorff
* 
*-------------------------------------------------------------------------------------*/

function load_first() 
{
	$this_plugin = 'buckets/buckets.php';
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_search($this_plugin, $active_plugins);
	if ($this_plugin_key) 
	{
		array_splice($active_plugins, $this_plugin_key, 1);
		array_unshift($active_plugins, $this_plugin);
		update_option('active_plugins', $active_plugins);
	}
}




?>