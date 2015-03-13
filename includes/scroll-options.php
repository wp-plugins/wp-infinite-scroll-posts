<?php
/**
 * @subpackage wp_easy_scroll_posts_Options
 * @package wp_easy_scroll_posts
 */
class wp_easy_scroll_posts_Options {
	
	public $defaults = array();
	private $parent;
	function __construct( &$parent ) {
		$this->parent = &$parent;
		add_action( 'admin_init', array( $this, 'options_init' ) );
		add_filter( $this->parent->prefix . 'options', array( $this, 'default_options_filter' ), 20 );
		add_filter( $this->parent->prefix . 'js_options', array( $this, 'db_version_filter' ) );
	}
	function options_init() {
		register_setting( $this->parent->slug_, $this->parent->slug_, array( $this, 'validate' ) );
	}

	function validate( $options ) {
		$js = array ( 'nextSelector', 'navSelector', 'itemSelector', 'contentSelector' );
		foreach ( $js as $field ) {
			if ( !isset( $options[$field] ) )
				continue;
			$options[$field] = addslashes( $options[ $field ] );
		}

		foreach ( array( 'finishedMsg', 'msgText', 'align' ) as $field ) {

			if ( !isset( $options['loading'][$field] ) )
				continue;

			$options['loading'][$field] = stripslashes(wp_filter_post_kses( addslashes($options['loading'][$field] )));
		}

		if ( isset( $_POST[ 'reset_default_image'] ) )
			$options["loading"]['img'] = $this->defaults["loading"]['img'];

		if ( empty( $options["loading"]['img'] ) )
			$options["loading"]['img']  = $this->loading["img"];
			
		
		return apply_filters( $this->parent->prefix . 'options_validate', $options );
	}

	function __get( $name ) {

		return $this->get_option( $name );

	}

	function __set( $name, $value ) {

		return $this->set_option( $name, $value );

	}

	function get_options( ) {

		if ( !$options = wp_cache_get( 'options', $this->parent->slug ) ) {
			$options = get_option( $this->parent->slug_ );
			wp_cache_set( 'options', $options, $this->parent->slug );
		}
		return apply_filters( $this->parent->prefix . 'options', $options );
	}

	function default_options_filter( $options ) {

		$options = wp_parse_args( $options,  $this->defaults );
		wp_cache_set( 'options', $options, $this->parent->slug );
		return $options;
	}

	function get_option( $option ) {
		$options = $this->get_options( );
		$value = ( isset( $options[ $option ] ) ) ? $options[ $option ] : false;
		return apply_filters( $this->parent->prefix . $option, $value );
	}

	function set_option( $key, $value ) {
		$options = array( $key => $value );
		$this->set_options( $options );
	}

	function set_options( $options, $merge = true ) {

		if ( $merge ) {
			$defaults = $this->get_options();
			$options = wp_parse_args( $options, $defaults );
		}
		wp_cache_set( 'options', $options, $this->parent->slug );
		return update_option( $this->parent->slug_, $options );
	}
	function db_version_filter( $options ) {
		unset( $options['db_version'] );
		return $options;
	}
}
?>