<?php
/*
 * Plugin Name: Ferrarello Flickr Justified Customizations
 * Description: Modifications to Flickr Justified for use on Ferrarello.com.
 * Version: 1.0.2
 * Author: Sal Ferrarello
 * Author URI: http://salferrarello.com/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Ferrarello_Flickr_Justified_Customizations {
	public $flickr_ids, $youtube_videos;

	function __construct() {
		add_filter( 'the_content', array( $this, 'the_content_filter' ) );
		add_action('wp_print_scripts', array( $this, 'replace_justifiedGallery_js' ), 1 );
	}

	/**
	 * Replace JustifiedGallery with my modified version which allows
	 * opting-out of sizeRangeSuffixes
	 */
	function replace_justifiedGallery_js() {
		wp_deregister_script( 'justifiedGallery' );
		wp_register_script('justifiedGallery', plugins_url('js/jquery.justifiedGallery-modified.min.js', __FILE__),
			array('jquery'), 'v3.6', true);
	}

	function the_content_filter( $content ) {
		if (
			! $this->is_flickr_post()
			|| ! function_exists( 'fjgwpp_flickr_set' )
		) {
			return $content;
		}

		$post_id = get_the_ID();

		$this->flickr_ids =     explode( ',', get_post_meta( $post_id, 'salogic_flickr_list',  true ) );

		$youtube_post_meta = get_post_meta( $post_id, 'salogic_youtube_list', true );
		if ( '' !== trim( $youtube_post_meta ) ) {
			$this->youtube_videos = explode( ',', $youtube_post_meta );
		} else {
			$this->youtube_videos = array();
		}

		$this->add_youtube_videos_filter_setup();

		foreach ( $this->flickr_ids as $flickr_id ) {

			if ( $flickr_id ) {
				$content .= fjgwpp_flickr_set( array(
					'id' => $flickr_id,
				) );
			}
		}

		return $content;
	}

	function add_youtube_videos_filter_setup() {
		add_filter( 'fjgwpp_add_entries', array( $this, 'fjgwpp_add_entries_filter' ), 10, 4 );
	}

	function fjgwpp_add_entries_filter( $add_entries_content, $id, $lightbox, $flickrGalID ) {
		foreach( $this->youtube_videos as $youtube_id ) {
			$add_entries_content .= $this->youtube_markup( $youtube_id, $id, $lightbox, $flickrGalID );
		}
		remove_filter( 'fjgwpp_add_entries', array( $this, 'fjgwpp_add_entries_filter' ) );
		return $add_entries_content;
	}

	function youtube_markup( $youtube_id, $id, $lightbox, $flickrGalID ) {
		return '<a class="fe-youtube-video ' . $lightbox.'-video"'
			. ' rel="' . $flickrGalID . '"'
			. ' href="https://www.youtube.com/watch?v=' . $youtube_id . '">'
			. '<img data-skip-size-range-suffixes="true"'
			. ' src="https://img.youtube.com/vi/' . $youtube_id . '/0.jpg"'
			. ' alt="Video"></a>';
	}

	function is_flickr_post() {
		$post_id = get_the_ID();
		return (
			'post' === get_post_type( $post_id )
			&& in_category( 'ferrarello-boys', $post_id )
		);
	}

}

new Ferrarello_Flickr_Justified_Customizations;
