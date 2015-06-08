<?php

class acf_field_buckets extends acf_field
{
	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/

	function __construct()
	{
		// vars
		$this->name = 'buckets';
		$this->label = __("Buckets Sidebar",'acf');
		$this->category = __("Relational",'acf');
		$this->defaults = array(
			'post_type'			=>	'buckets',
			'max' 				=>	'',
			'taxonomy' 			=>	array('all'),
			'filters'			=>	array('search'),
			'result_elements' 	=>	array('post_title', 'post_type'),
			'return_format'		=>	'object'
		);
		$this->l10n = array(
			'max'		=> __("Maximum values reached ( {max} values )",'acf'),
			'tmpl_li'	=> '
							<li>
								<span class="edit" data-url="<?php echo get_admin_url() ?>post.php?post=<%= post_id %>&action=edit&popup=true&TB_iframe=1">Edit</span>
								<a href="#" data-post_id="<%= post_id %>"><%= title %><span class="acf-button-remove"></span></a>
								<input type="hidden" name="<%= name %>[]" value="<%= post_id %>" />
							</li>
							'
		);


		// do not delete!
    	parent::__construct();


    	// extra
		add_action('wp_ajax_acf/fields/buckets/query_posts', array($this, 'query_posts'));
		add_action('wp_ajax_nopriv_acf/fields/buckets/query_posts', array($this, 'query_posts'));
	}


	/*
	*  load_field()
	*
	*  This filter is appied to the $field after it is loaded from the database
	*
	*  @type filter
	*  @since 3.6
	*  @date 23/01/13
	*
	*  @param $field - the field array holding all the field options
	*
	*  @return $field - the field array holding all the field options
	*/

	function load_field( $field )
	{
		// validate post_type
		if( !$field['post_type'] || !is_array($field['post_type']) || in_array('', $field['post_type']) )
		{
			$field['post_type'] = array( 'all' );
		}


		// validate taxonomy
		if( !$field['taxonomy'] || !is_array($field['taxonomy']) || in_array('', $field['taxonomy']) )
		{
			$field['taxonomy'] = array( 'all' );
		}


		// validate result_elements
		if( !is_array( $field['result_elements'] ) )
		{
			$field['result_elements'] = array();
		}

		if( !in_array('post_title', $field['result_elements']) )
		{
			$field['result_elements'][] = 'post_title';
		}


		// filters
		if( !is_array( $field['filters'] ) )
		{
			$field['filters'] = array();
		}


		// return
		return $field;
	}


	/*
   	*  posts_where
   	*
   	*  @description:
   	*  @created: 3/09/12
   	*/

   	function posts_where( $where, &$wp_query )
	{
	    global $wpdb;

	    if ( $title = $wp_query->get('like_title') )
	    {
	        $where .= " AND " . $wpdb->posts . ".post_title LIKE '%" . esc_sql( like_escape(  $title ) ) . "%'";
	    }

	    return $where;
	}


	/*
	*  query_posts
	*
	*  @description:
	*  @since: 3.6
	*  @created: 27/01/13
	*/

	function query_posts()
   	{
   		// vars
   		$r = array(
   			'next_page_exists' => 1,
   			'html' => ''
   		);


   		// vars
		$options = array(
			'post_type'					=>	'buckets',
			'taxonomy'					=>	'all',
			'posts_per_page'			=>	10,
			'paged'						=>	1,
			'orderby'					=>	'title',
			'order'						=>	'ASC',
			'post_status'				=>	'any',
			'suppress_filters'			=>	false,
			's'							=>	'',
			'lang'						=>	false,
			'update_post_meta_cache'	=>	false,
			'field_key'					=>	'',
			'nonce'						=>	'',
			'ancestor'					=>	false,
		);

		$options = array_merge( $options, $_POST );


		// validate
		if( ! wp_verify_nonce($options['nonce'], 'acf_nonce') )
		{
			die();
		}


		// WPML
		if( $options['lang'] )
		{
			global $sitepress;

			$sitepress->switch_lang( $options['lang'] );
		}


		// convert types
		$options['post_type'] = explode(',', $options['post_type']);
		$options['taxonomy'] = explode(',', $options['taxonomy']);


		// load all post types by default
		if( in_array('all', $options['post_type']) )
		{
			$options['post_type'] = 'buckets';
		}


		// attachment doesn't work if it is the only item in an array???
		if( is_array($options['post_type']) && count($options['post_type']) == 1 )
		{
			$options['post_type'] = $options['post_type'][0];
		}


		// create tax queries
		if( ! in_array('all', $options['taxonomy']) )
		{
			// vars
			$taxonomies = array();
			$options['tax_query'] = array();

			foreach( $options['taxonomy'] as $v )
			{

				// find term (find taxonomy!)
				// $term = array( 0 => $taxonomy, 1 => $term_id )
				$term = explode(':', $v);


				// validate
				if( !is_array($term) || !isset($term[1]) )
				{
					continue;
				}


				// add to tax array
				$taxonomies[ $term[0] ][] = $term[1];

			}


			// now create the tax queries
			foreach( $taxonomies as $k => $v )
			{
				$options['tax_query'][] = array(
					'taxonomy' => $k,
					'field' => 'id',
					'terms' => $v,
				);
			}
		}

		unset( $options['taxonomy'] );


		// search
		if( $options['s'] )
		{
			$options['like_title'] = $options['s'];

			add_filter( 'posts_where', array($this, 'posts_where'), 10, 2 );
		}

		unset( $options['s'] );


		// load field
		$field = array();
		if( $options['ancestor'] )
		{
			$ancestor = apply_filters('acf/load_field', array(), $options['ancestor'] );
			$field = acf_get_child_field_from_parent_field( $options['field_key'], $ancestor );
		}
		else
		{
			$field = apply_filters('acf/load_field', array(), $options['field_key'] );
		}


		// get the post from which this field is rendered on
		$the_post = get_post( $options['post_id'] );


		// filters
		$options = apply_filters('acf/fields/buckets/query', $options, $field, $the_post);
		$options = apply_filters('acf/fields/buckets/query/name=' . $field['name'], $options, $field, $the_post );
		$options = apply_filters('acf/fields/buckets/query/key=' . $field['key'], $options, $field, $the_post );


		// query
		$wp_query = new WP_Query( $options );


		// global
		global $post;


		// loop
		while( $wp_query->have_posts() )
		{
			$wp_query->the_post();


			// right aligned info
			$title = '<span class="relationship-item-info">';

				if( in_array('post_type', $field['result_elements']) )
				{
					$title .= get_post_type();
				}

				// WPML
				if( $options['lang'] )
				{
					$title .= ' (' . $options['lang'] . ')';
				}

			$title .= '</span>';


			// featured_image
			if( in_array('featured_image', $field['result_elements']) )
			{
				$image = get_the_post_thumbnail( get_the_ID(), array(21, 21) );

				$title .= '<div class="result-thumbnail">' . $image . '</div>';
			}


			// title
			$title .= get_the_title();


			// status
			if( get_post_status() != "publish" )
			{
				$title .= ' (' . get_post_status() . ')';
			}


			// filters
			$title = apply_filters('acf/fields/buckets/result', $title, $post, $field, $the_post);
			$title = apply_filters('acf/fields/buckets/result/name=' . $field['name'] , $title, $post, $field, $the_post);
			$title = apply_filters('acf/fields/buckets/result/key=' . $field['key'], $title, $post, $field, $the_post);


			// update html
			$r['html'] .= '<li><a href="' . get_permalink() . '" data-post_id="' . get_the_ID() . '">' . $title .  '<span class="acf-button-add"></span></a></li>';
		}


		if( (int)$options['paged'] >= $wp_query->max_num_pages )
		{
			$r['next_page_exists'] = 0;
		}


		wp_reset_postdata();


		// return JSON
		echo json_encode( $r );

		die();

	}


	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function create_field( $field )
	{
		// global
		global $post;


		// no row limit?
		if( !$field['max'] || $field['max'] < 1 )
		{
			$field['max'] = 9999;
		}


		// class
		$class = '';
		if( $field['filters'] )
		{
			foreach( $field['filters'] as $filter )
			{
				$class .= ' has-' . $filter;
			}
		}

		$attributes = array(
			'max' => $field['max'],
			's' => '',
			'paged' => 1,
			'post_type' => 'buckets', //Force Buckets Post Type
			'taxonomy' => implode(',', $field['taxonomy']),
			'field_key' => $field['key']
		);


		// Lang
		if( defined('ICL_LANGUAGE_CODE') )
		{
			$attributes['lang'] = ICL_LANGUAGE_CODE;
		}


		// parent
		preg_match('/\[(field_.*?)\]/', $field['name'], $ancestor);
		if( isset($ancestor[1]) && $ancestor[1] != $field['key'])
		{
			$attributes['ancestor'] = $ancestor[1];
		}

		?>
<div class="acf_relationship acf_buckets<?php echo $class; ?>"<?php foreach( $attributes as $k => $v ): ?> data-<?php echo $k; ?>="<?php echo $v; ?>"<?php endforeach; ?>>


	<!-- Hidden Blank default value -->
	<input type="hidden" name="<?php echo $field['name']; ?>" value="" />


	<!-- Left List -->
	<div class="relationship_left">
		<table class="widefat">
			<thead>
				<?php if(in_array( 'search', $field['filters']) ): ?>
				<tr>
					<th>
						<input class="relationship_search" placeholder="<?php _e("Search...",'acf'); ?>" type="text" id="relationship_<?php echo $field['name']; ?>" />
					</th>
				</tr>
				<?php endif; ?>
				<?php if(in_array( 'post_type', $field['filters']) ): ?>
				<tr>
					<th>
						<?php

						// vars
						$choices = array(
							'all' => __("Filter by post type",'acf')
						);


						if( in_array('all', $field['post_type']) )
						{
							$post_types = apply_filters( 'acf/get_post_types', array() );
							$choices = array_merge( $choices, $post_types);
						}
						else
						{
							foreach( $field['post_type'] as $post_type )
							{
								$choices[ $post_type ] = $post_type;
							}
						}


						// create field
						do_action('acf/create_field', array(
							'type'	=>	'select',
							'name'	=>	'',
							'class'	=>	'select-post_type',
							'value'	=>	'',
							'choices' => $choices,
						));

						?>
					</th>
				</tr>
				<?php endif; ?>
			</thead>
		</table>
		<ul class="bl relationship_list bucket_list">
			<li class="load-more">
				<div class="acf-loading"></div>
			</li>
		</ul>
	</div>
	<!-- /Left List -->

	<!-- Right List -->
	<div class="relationship_right">
		<ul class="bl relationship_list">
		<?php

		if( $field['value'] )
		{
			foreach( $field['value'] as $p )
			{
				// right aligned info
				$title = '<span class="relationship-item-info">';

					if( in_array('post_type', $field['result_elements']) )
					{
						$title .= $p->post_type;
					}

					// WPML
					if( defined('ICL_LANGUAGE_CODE') )
					{
						$title .= ' (' . ICL_LANGUAGE_CODE . ')';
					}

				$title .= '</span>';


				// featured_image
				if( in_array('featured_image', $field['result_elements']) )
				{
					$image = get_the_post_thumbnail( $p->ID, array(21, 21) );

					$title .= '<div class="result-thumbnail">' . $image . '</div>';
				}


				// find title. Could use get_the_title, but that uses get_post(), so I think this uses less Memory
				$title .= apply_filters( 'the_title', $p->post_title, $p->ID );

				// status
				if($p->post_status != "publish")
				{
					$title .= " ($p->post_status)";
				}


				// filters
				$title = apply_filters('acf/fields/buckets/result', $title, $p, $field, $post);
				$title = apply_filters('acf/fields/buckets/result/name=' . $field['name'] , $title, $p, $field, $post);
				$title = apply_filters('acf/fields/buckets/result/key=' . $field['key'], $title, $p, $field, $post);


				echo '<li>
					<span class="edit" data-url="' . get_admin_url() . 'post.php?post=' . $p->ID . '&action=edit&popup=true&TB_iframe=1">Edit</span>
					<a href="' . get_permalink($p->ID) . '" class="" data-post_id="' . $p->ID . '">' . $title . '<span class="acf-button-remove"></span></a>
					<input type="hidden" name="' . $field['name'] . '[]" value="' . $p->ID . '" />
				</li>';


			}
		}

		?>
		</ul>
	</div>
	<!-- / Right List -->
	<a href="<?php echo bloginfo('url'); ?>/wp-admin/post-new.php?post_type=buckets&popup=true&TB_iframe=1" title="New Bucket" class="button-primary new-bucket thickbox">Add New</a>
	<div style="clear: both;"></div>
</div>
		<?php
	}



	/*
	*  create_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like bellow) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/

	function create_options( $field )
	{
		// vars
		$field = array_merge($this->defaults, $field);
		$key = $field['name'];

		?>


<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Maximum posts",'acf'); ?></label>
	</td>
	<td>
		<?php
		do_action('acf/create_field', array(
			'type'	=>	'text',
			'name'	=>	'fields['.$key.'][max]',
			'value'	=>	$field['max'],
		));
		?>
	</td>
</tr>
		<?php

	}




	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is passed to the create_field action
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/

	function format_value( $value, $post_id, $field, $output = false )
	{
		// empty?
		if( !$value )
		{
			return $value;
		}


		// Pre 3.3.3, the value is a string coma seperated
		if( is_string($value) )
		{
			$value = explode(',', $value);
		}


		// empty?
		if( !is_array($value) || empty($value) )
		{
			return $value;
		}


		// convert to integers
		$value = array_map('intval', $value);


		// convert into post objects
		$value = $this->get_posts( $value );

		//Output on Front end
		if ($output == true) {
			$buckets = false;
			foreach ($value as $v){
				$buckets .= get_bucket($v->ID);
			}

			// return
			return $buckets;
		}

		// return value
		return $value;

	}


	/*
	*  format_value_for_api()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is passed back to the api functions such as the_field
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/

	function format_value_for_api( $value, $post_id, $field )
	{
		return $this->format_value($value,$post_id,$field,true);
	}


	/*
	*  get_posts
	*
	*  This function will take an array of post_id's ($value) and return an array of post_objects
	*
	*  @type	function
	*  @date	7/08/13
	*
	*  @param	$post_ids (array) the array of post ID's
	*  @return	(array) an array of post objects
	*/

	function get_posts( $post_ids )
	{
		// validate
		if( empty($post_ids) )
		{
			return $post_ids;
		}


		// vars
		$r = array();


		// find posts (DISTINCT POSTS)
		$posts = get_posts(array(
			'numberposts'	=>	-1,
			'post__in'		=>	$post_ids,
			'post_type'		=>	'buckets',
			'post_status'	=>	'any',
		));


		$ordered_posts = array();
		foreach( $posts as $p )
		{
			// create array to hold value data
			$ordered_posts[ $p->ID ] = $p;
		}


		// override value array with attachments
		foreach( $post_ids as $k => $v)
		{
			// check that post exists (my have been trashed)
			if( isset($ordered_posts[ $v ]) )
			{
				$r[] = $ordered_posts[ $v ];
			}
		}


		// return
		return $r;
	}



	/*
	*  update_value()
	*
	*  This filter is appied to the $value before it is updated in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value - the value which will be saved in the database
	*  @param	$post_id - the $post_id of which the value will be saved
	*  @param	$field - the field array holding all the field options
	*
	*  @return	$value - the modified value
	*/

	function update_value( $value, $post_id, $field )
	{
		// array?
		if( is_array($value) ){ foreach( $value as $k => $v ){

			// object?
			if( is_object($v) && isset($v->ID) )
			{
				$value[ $k ] = $v->ID;
			}

		}}


		return $value;
	}

}

new acf_field_buckets();

?>