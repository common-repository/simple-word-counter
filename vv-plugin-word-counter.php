<?php
/*
Plugin Name: Simple Word Counter
Plugin URI: https://potceluev.com/wp-word-count
Description: WP Plugin to count words in posts for SEO analyze. It shows words counter on the posts page in admin panel (Posts->All posts)
Version: 1.0
Author: VirVit
Author URI: https://potceluev.com
Prefix: vvwc
*/

function wwvc_activate_word_counter( ) {
	// The Query
	$args = array(
		'posts_per_page'   => -1,
		'post_type'        => 'post',
	);
	$the_query = new WP_Query( $args );

	// The Loop
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			
			// Get words count
			$content = get_the_content();
			$content = wp_filter_nohtml_kses( $content );
			
			delete_post_meta($the_query->post->ID, 'vvwc_word_count');
			add_post_meta($the_query->post->ID, 'vvwc_word_count', vvwc_calc_word_count($content), true);
		}
		wp_reset_postdata();
	}	
}

register_activation_hook(__FILE__, 'wwvc_activate_word_counter');

function vvwc_deactivate_word_counter( ) {
	// The Query
	$args = array(
		'posts_per_page'   => -1,
		'post_type'        => 'post',
	);
	$the_query = new WP_Query( $args );

	// The Loop
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			
			// Remove our meta
			delete_post_meta($the_query->post->ID, 'vvwc_word_count');
		}
		wp_reset_postdata();
	}	
}

register_deactivation_hook(__FILE__, 'vvwc_deactivate_word_counter');

function vvwc_calc_word_count($content) {
	$count = str_word_count($content);
	return $count;
}

function vvwc_update_word_count( $post_id ) {

	// If this is just a revision, don't send the email.
	if ( wp_is_post_revision( $post_id ) )
		return;

	// Get words count
	$content_post = get_post($post_id);
	$content = $content_post->post_content;
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	$content = wp_filter_nohtml_kses( $content );
			
	delete_post_meta($post_id, 'vvwc_word_count');
	add_post_meta($post_id, 'vvwc_word_count', vvwc_calc_word_count($content), true);

}
add_action( 'save_post', 'vvwc_update_word_count' );

add_filter( 'manage_posts_columns', 'vvwc_filter_posts_columns' );
function vvwc_filter_posts_columns( $columns ) {
  $columns['vvwc_word_count'] = __( 'Word Counter' );
  return $columns;
}

add_action( 'manage_posts_custom_column', 'vvwc_word_count_column', 10, 2);
function vvwc_word_count_column( $column, $post_id ) {
  if ( 'vvwc_word_count' === $column ) {
    $wc = get_post_meta( $post_id, 'vvwc_word_count', true );

    if ( ! $wc ) {
      _e( 0 );  
    } else {
      echo number_format( $wc, 0, '.', ',' );
    }
  }
}

add_filter( 'manage_edit-post_sortable_columns', 'vvwc_sortable_columns');
function vvwc_sortable_columns( $columns ) {
  $columns['vvwc_word_count'] = 'vvwc_word_count';
  return $columns;
}

add_action( 'pre_get_posts', 'vvwc_posts_orderby' );
function vvwc_posts_orderby( $query ) {
  if( ! is_admin() || ! $query->is_main_query() ) {
    return;
  }

  if ( 'vvwc_word_count' === $query->get( 'orderby') ) {
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'meta_key', 'vvwc_word_count' );
    $query->set( 'meta_type', 'numeric' );
  }
}
