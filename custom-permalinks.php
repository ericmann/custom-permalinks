<?php
/*
Plugin Name: Custom Permalinks
Plugin URI: http://michael.tyson.id.au/wordpress/plugins/custom-permalinks
Donate link: http://michael.tyson.id.au/wordpress/plugins/custom-permalinks
Description: Set custom permalinks on a per-post basis
Version: 0.2.2
Author: Michael Tyson
Author URI: http://michael.tyson.id.au
*/

/*  Copyright 2008 Michael Tyson <mike@tyson.id.au>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 ** Actions and filters
 **
 **/

/**
 * Filter to replace the post permalink with the custom one
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_post_link($permalink, $post) {
	$custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
	if ( $custom_permalink ) {
		return get_option('home')."/".$custom_permalink;
	}
	
	return $permalink;
}


/**
 * Filter to replace the term permalink with the custom one
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_term_link($permalink, $term) {
	$table = get_option('custom_permalink_table');
	if ( is_object($term) ) $term = $term->term_id;
	
	$custom_permalink = custom_permalinks_permalink_for_term($term);
	
	if ( $custom_permalink ) {
		return get_option('home')."/".$custom_permalink;
	}
	
	return $permalink;
}


/**
 * Action to redirect to the custom permalink
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_redirect() {
	
	// Get request URI, strip parameters
	$request = trim($_SERVER['REQUEST_URI'],'/');
	if ( ($pos=strpos($request, "?")) ) $request = substr($request, 0, $pos);
	
	global $wp_query;
	$post = $wp_query->post;
	if ( !$post ) return;
	
	if ( is_single() ) {
		$custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
		if ( $custom_permalink && $custom_permalink != $request ) {
			// There's a post with a matching custom permalink
			wp_redirect( get_option('home')."/".$custom_permalink, 301 );
			exit();
		}
	} else if ( is_tag() || is_category() ) {
		$theTerm = $wp_query->get_queried_object();
		$permalink = custom_permalinks_permalink_for_term($theTerm->term_id);
		
		if ( $permalink && $permalink != $request ) {
			// The current term has a permalink that isn't where we're currently at
			wp_redirect( get_option('home')."/".$permalink, 301 );
			exit();
		}
	}
}

/**
 * Filter to rewrite the query if we have a matching post
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_request($query) {
	
	// Get request URI, strip parameters and /'s
	$request = (($pos=strpos($_SERVER['REQUEST_URI'], '?')) ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI']);
	$request = preg_replace('@/+@','/', ltrim($request, '/'));
	
	if ( !$request ) return $query;
	
	$posts = get_posts( array('meta_key' => 'custom_permalink', 'meta_value' => $request) );
	if ( !$posts && $request{strlen($request)-1} != '/' ) // Try adding trailing /
		$posts = get_posts( array('meta_key' => 'custom_permalink', 'meta_value' => $request.'/') );
	if ( !$posts && $request{strlen($request)-1} == '/' ) // Try removing trailing /
		$posts = get_posts( array('meta_key' => 'custom_permalink', 'meta_value' => rtrim($request,'/')) );
	if ( $posts ) {
		// A post matches our request
		return array('p' => $posts[0]->ID);
	}
	
	$table = get_option('custom_permalink_table');
	if ( $table && ($term = $table[$request]) || ($term = $table[$request.'/']) || $term = $table[rtrim($request,'/')] ) {
		return array(($term['kind'] == 'category' ? 'category_name' : 'tag') => $term['slug']);
	}

	return $query;
}



/**
 ** Administration
 **
 **/


/**
 * Per-post options
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_post_options() {
	global $post;
	$post_id = $post;
	if (is_object($post_id)) {
		$post_id = $post_id->ID;
	}
	
	$permalink = get_post_meta( $post_id, 'custom_permalink', true );
	
	?>
	<div class="postbox closed">
	<h3><?php _e('Custom Permalink', 'custom-permalink') ?></h3>
	<div class="inside">
	<?php custom_permalinks_form($permalink, custom_permalinks_original_post_link($post_id)); ?>
	</div>
	</div>
	<?php
}


/**
 * Per-category/tag options
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_term_options($object) {

	$permalink = custom_permalinks_permalink_for_term($object->term_id);
	
	$originalPermalink = ($object->taxonomy == 'post_tag' ? 
								custom_permalinks_original_tag_link($object->term_id) :
								custom_permalinks_original_category_link($object->term_id) );
	
	custom_permalinks_form($permalink, $originalPermalink);

	// Move the save button to above this form
	wp_enqueue_script('jquery');
	?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		var button = jQuery('#custom_permalink_form').parent().find('.submit');
		button.remove().insertAfter(jQuery('#custom_permalink_form'));
	});
	</script>
	<?php
}

/**
 * Helper function to render form
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_form($permalink, $original="") {
	?>
	<input value="true" type="hidden" name="custom_permalinks_edit" />
	<table class="form-table" id="custom_permalink_form">
	<tr>
		<th scope="row"><?php _e('Custom Permalink', 'custom-permalink') ?></th>
		<td>
			<?php echo get_option('home') ?>/
			<input type="text" name="custom_permalink" class="text" value="<?php echo htmlspecialchars($permalink ? $permalink : $original) ?>" 
				style="width: 300px; <?php if ( !$permalink ) echo 'color: #ddd;' ?>"
			 	onfocus="if ( this.value == '<?php echo htmlspecialchars($original) ?>' ) { this.value = ''; this.style.color = '#000'; }" 
				onblur="if ( this.value == '' ) { this.value = '<?php echo htmlspecialchars($original) ?>'; this.style.color = '#ddd'; }"/><br />
			<small><?php _e('Leave blank to disable', 'custom-permalink') ?></small>
		</td>
	</tr>
	</table>
	<?php
}


/**
 * Save per-post options
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_save_post($id) {
	if ( !isset($_REQUEST['custom_permalinks_edit']) ) return;
	
	delete_post_meta( $id, 'custom_permalink' );
	if ( $_REQUEST['custom_permalink'] && $_REQUEST['custom_permalink'] != custom_permalinks_original_post_link($id) )
		add_post_meta( $id, 'custom_permalink', ltrim(stripcslashes($_REQUEST['custom_permalink']),"/") );
}


/**
 * Save per-tag options
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_save_tag($id) {
	if ( !isset($_REQUEST['custom_permalinks_edit']) ) return;
	$newPermalink = ltrim(stripcslashes($_REQUEST['custom_permalink']),"/");
	
	if ( $newPermalink == custom_permalinks_original_tag_link($id) )
		$newPermalink = ''; 
	
	$term = get_term($id, 'post_tag');
	custom_permalinks_save_term($term, $newPermalink);
}

/**
 * Save per-category options
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_save_category($id) {
	if ( !isset($_REQUEST['custom_permalinks_edit']) ) return;
	$newPermalink = ltrim(stripcslashes($_REQUEST['custom_permalink']),"/");
	
	if ( $newPermalink == custom_permalinks_original_category_link($id) )
		$newPermalink = ''; 
	
	$term = get_term($id, 'category');
	custom_permalinks_save_term($term, $newPermalink);
}

/**
 * Save term (common to tags and categories)
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_save_term($term, $permalink) {
	
	custom_permalinks_delete_term($term->term_id);
	$table = get_option('custom_permalink_table');
	if ( $permalink )
		$table[$permalink] = array(
			'id' => $term->term_id, 
			'kind' => ($term->taxonomy == 'category' ? 'category' : 'tag'),
			'slug' => $term->slug);

	update_option('custom_permalink_table', $table);
}


/**
 * Delete term
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_delete_term($id) {
	
	$table = get_option('custom_permalink_table');
	if ( $table )
	foreach ( $table as $link => $info ) {
		if ( $info['id'] == $id ) {
			unset($table[$link]);
			break;
		}
	}
	
	update_option('custom_permalink_table', $table);
}

/**
 * Options page
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_options_page() {
	
	// Handle revert
	if ( isset($_REQUEST['revertit']) && isset($_REQUEST['revert']) ) {
		check_admin_referer('custom-permalinks-bulk');
		foreach ( (array)$_REQUEST['revert'] as $identifier ) {
			list($kind, $id) = explode('.', $identifier);
			switch ( $kind ) {
				case 'post':
					delete_post_meta( $id, 'custom_permalink' );
					break;
				case 'tag':
				case 'category':
					custom_permalinks_delete_term($id);
					break;
			}
		}
		
		// Redirect
		$redirectUrl = $_SERVER['REQUEST_URI'];
		?>
		<script type="text/javascript">
		document.location = '<?php echo $redirectUrl ?>';
		</script>
		<?php
	}
	
	?>
	<div class="wrap">
	<h2><?php _e('Custom Permalinks', 'custom-permalinks') ?></h2>
	
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
	<?php wp_nonce_field('custom-permalinks-bulk') ?>
	
	<div class="tablenav">
	<div class="alignleft">
	<input type="submit" value="<?php _e('Revert', 'custom-permalinks'); ?>" name="revertit" class="button-secondary delete" />
	</div>
	<br class="clear" />
	</div>
	<br class="clear" />
	<table class="widefat">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col"><?php _e('Title', 'custom-permalinks') ?></th>
			<th scope="col"><?php _e('Type', 'custom-permalinks') ?></th>
			<th scope="col"><?php _e('Permalink', 'custom-permalinks') ?></th>
		</tr>
		</thead>
		<tbody>
	<?php
	$rows = custom_permalinks_admin_rows();
	foreach ( $rows as $row ) {
		?>
		<tr valign="top">
		<th scope="row" class="check-column"><input type="checkbox" name="revert[]" value="<?php echo $row['id'] ?>" /></th>
		<td><strong><a class="row-title" href="<?php echo htmlspecialchars($row['editlink']) ?>"><?php echo htmlspecialchars($row['title']) ?></a></strong></td>
		<td><?php echo htmlspecialchars($row['type']) ?></td>
		<td><a href="<?php echo $row['permalink'] ?>" target="_blank" title="Visit <?php echo htmlspecialchars($row['title']) ?>">
			<?php echo htmlspecialchars($row['permalink']) ?>
			</a>
		</td>
		</tr>
		<?php
	}
	?>
	</tbody>
	</table>
	</form>
	</div>
	<?php
}

/**
 * Get rows for management view
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_admin_rows() {
	$rows = array();
	
	// List tags/categories
	$table = get_option('custom_permalink_table');
	if ( $table && is_array($table) ) {
		foreach ( $table as $permalink => $info ) {
			$row = array();
			$term = get_term($info['id'], ($info['kind'] == 'tag' ? 'post_tag' : 'category'));
			$row['id'] = $info['kind'].'.'.$info['id'];
			$row['permalink'] = get_option('home')."/".$permalink;
			$row['type'] = ucwords($info['kind']);
			$row['title'] = $term->name;
			$row['editlink'] = ( $info['kind'] == 'tag' ? 'edit-tags.php?action=edit&tag_ID='.$info['id'] : 'categories.php?action=edit&cat_ID='.$info['id'] );
			$rows[] = $row;
		}
	}
	
	// List posts
	global $wpdb;
	$query = "SELECT $wpdb->posts.* FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE ".
	 			"$wpdb->postmeta.meta_key = 'custom_permalink' AND $wpdb->postmeta.meta_value != ''";
	$posts = $wpdb->get_results($query);
	foreach ( $posts as $post ) {
		$row = array();
		$row['id'] = 'post.'.$post->ID;
		$row['permalink'] = get_permalink($post->ID);
		$row['type'] = 'Post';
		$row['title'] = $post->post_title;
		$row['editlink'] = 'post.php?action=edit&post='.$post->ID;
		$rows[] = $row;
	}
	
	return $rows;
}


/**
 * Get original permalink
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_original_post_link($post_id) {
	remove_filter( 'post_link', 'custom_permalinks_post_link', 10, 2 );
	$originalPermalink = ltrim(str_replace(get_option('home'), '', get_permalink( $post_id )), '/');
	add_filter( 'post_link', 'custom_permalinks_post_link', 10, 2 );
	return $originalPermalink;
}



/**
 * Get original permalink for tag
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_original_tag_link($tag_id) {
	remove_filter( 'tag_link', 'custom_permalinks_term_link', 10, 2 );
	$originalPermalink = ltrim(str_replace(get_option('home'), '', get_tag_link($tag_id)), '/');
	add_filter( 'tag_link', 'custom_permalinks_term_link', 10, 2 );
	return $originalPermalink;
}

/**
 * Get original permalink for category
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_original_category_link($category_id) {
	remove_filter( 'category_link', 'custom_permalinks_term_link', 10, 2 );
	$originalPermalink = ltrim(str_replace(get_option('home'), '', get_category_link($category_id)), '/');
	add_filter( 'category_link', 'custom_permalinks_term_link', 10, 2 );
	return $originalPermalink;
}

/**
 * Get permalink for term
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_permalink_for_term($id) {
	$table = get_option('custom_permalink_table');
	if ( $table )
	foreach ( $table as $link => $info ) {
		if ( $info['id'] == $id ) {
			return $link;
		}
	}
	return false;
}

/**
 * Set up administration
 *
 * @package CustomPermalinks
 * @since 0.1
 */
function custom_permalinks_setup_admin() {
	add_management_page( 'Custom Permalinks', 'Custom Permalinks', 5, __FILE__, 'custom_permalinks_options_page' );
	if ( is_admin() )
		wp_enqueue_script('admin-forms');
}


add_action( 'template_redirect', 'custom_permalinks_redirect', 5 );
add_filter( 'post_link', 'custom_permalinks_post_link', 10, 2 );
add_filter( 'tag_link', 'custom_permalinks_term_link', 10, 2 );
add_filter( 'category_link', 'custom_permalinks_term_link', 10, 2 );
add_filter( 'request', 'custom_permalinks_request', 10, 1 );

add_action( 'edit_form_advanced', 'custom_permalinks_post_options' );
add_action( 'edit_tag_form', 'custom_permalinks_term_options' );
add_action( 'edit_category_form', 'custom_permalinks_term_options' );
add_action( 'save_post', 'custom_permalinks_save_post' );
add_action( 'edited_post_tag', 'custom_permalinks_save_tag' );
add_action( 'edited_category', 'custom_permalinks_save_category' );
add_action( 'delete_post_tag', 'custom_permalinks_delete_term' );
add_action( 'delete_post_category', 'custom_permalinks_delete_term' );
add_action( 'admin_menu', 'custom_permalinks_setup_admin' );

?>
