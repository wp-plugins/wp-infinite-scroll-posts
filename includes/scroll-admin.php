<?php
class wp_easy_scroll_posts_Admin {

	private $parent;

	/* Register hooks with WordPress API */
	function __construct( &$parent ) {

		$this->parent = &$parent;
		add_action( 'admin_menu', array( &$this, 'register_my_custom_scroll_page' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue' ) );
		add_filter( 'get_media_item_args', array( &$this, 'send_to_editor'), 10, 1);
	}

/* Register our menu with WordPress menubar */
    function register_my_custom_scroll_page() {
        // This adds the main menu page
        add_menu_page( __('WP EasyScroll Posts', 'wp-easy-scroll-posts'), __('WP EasyScroll Posts', 'wp-easy-scroll-posts'), 'manage_options', 'wp_easy_scroll_posts_options', array( &$this, 'options' ));
    }

	/* Callback to load options template */
	function options() {

		$file = 'options';
		require dirname( $this->parent->file ) . '/templates/' . $file . '.php';
	}

	/* Enqueue admin JS on options page */
	function admin_enqueue() {
     	if ( get_current_screen()->id !='toplevel_page_wp_easy_scroll_posts_options' && !defined( 'IFRAME_REQUEST' ) )
			return;
		$suffix = '';
		$suffix = '.dev';
		$file = "/js/admin/wp-easy-scroll-posts{$suffix}.js";

		wp_enqueue_script( $this->parent->slug, plugins_url( $file, $this->parent->file ), array( 'jquery', 'media-upload', 'thickbox' ), $this->parent->version, true );
		wp_enqueue_style('thickbox');

		wp_localize_script( $this->parent->slug, $this->parent->slug_, array( 'confirm' => __( 'Are you sure you want to delete the preset "%s"?', 'wp-easy-scroll-posts' ) ) );
	}

	function send_to_editor( $args ) {
		global $wpdb;
		
		if ( $args['errors'] !== null )
			return $args;
			
		if ( isset( $_GET['attachment_id'] ) ) {
		
			$id = $_GET['attachment_id'];
		} else {
					
			$upload = $GLOBALS['HTTP_POST_FILES']['async-upload'];
			$title = substr( $upload['name'], 0, strrpos( $upload['name'], '.' ) );
			$id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type = '" . $upload['type'] . "' AND post_parent = '0' AND post_title = '$title' ORDER BY ID DESC LIMIT 1" );

			if ( !$id )
				return $args;
		
		}
		media_send_to_editor( wp_get_attachment_url( $id ) );
		return $args;
	}
   
	function editor( $field ) {
    
		//3.3
		if ( function_exists( 'wp_editor' ) )
			wp_editor( $this->parent->options->loading[ $field ], "wp_easy_scroll_posts[loading][{$field}]", array( 'media_buttons' => false, 'textarea_rows' => 5, 'teeny' => true ) );
		
		//3.2
		else
			the_editor( $this->parent->options->loading[ $field ], "wp_easy_scroll_posts[loading][{$field}]", null, false );
			
   
	}
}