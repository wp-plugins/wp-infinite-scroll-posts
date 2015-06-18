<?php
/*
* Plugin Name: WP EasyScroll Posts
* Plugin URI: http://www.vivacityinfotech.net
* Description:  Easy and fast load plugin to append next page of posts to your current page when a user scrolls to the bottom.
* Version: 1.0
* Author: Vivacity Infotech Pvt. Ltd.
* Author URI: http://www.vivacityinfotech.net
* Text Domain: wp-easy-scroll-posts
* Domain Path: /languages/
Copyright 2014  Vivacity InfoTech Pvt. Ltd.  (email : support@vivacityinfotech.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	require_once dirname( __FILE__ ) . '/includes/scroll-options.php';
	require_once dirname( __FILE__ ) . '/includes/scroll-admin.php';

class Wp_EasyScroll_Posts {
	static $instance;
	public $options;
	public $admin;
	public $submit;
	public $name      = 'WP EasyScroll Posts';
	public $slug      = 'wp-easy-scroll-posts'; 
	public $slug_     = 'wp_easy_scroll_posts'; 
	public $prefix    = 'wp_easy_scroll_posts_'; 
	public $file      = null;
	public $version   = '1.0';

	/**
	 * Construct the primary class and auto-load all child classes
	 */
	function __construct() {
		self::$instance = &$this;
		$this->file    = __FILE__;
		$this->admin   = new wp_easy_scroll_posts_Admin( $this );
		$this->options = new wp_easy_scroll_posts_Options( $this );

		add_action( 'admin_init', array( &$this, 'scroll_css' ) );
		add_action( 'admin_init', array( &$this, 'upgrade_check' ) );
		add_action( 'init', array( &$this, 'scroll_lang_support' ) );
		add_action( 'init', array( &$this, 'init_defaults' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_js' ) );
		add_action( 'wp_footer', array( &$this, 'footer' ), 100 ); 
		add_action( 'wp', array( &$this, 'paged_404_fix' ) );
	}

	function scroll_css() {
		wp_register_style('scroll_css', plugins_url('css/style.css',__FILE__ ));
		wp_enqueue_style('scroll_css');
	}
	/* default options */
	function init_defaults() {
		$this->options->defaults = array(
			'loading' => array(
				'msgText'         => '<em>' . __( 'Loading...', 'wp-easy-scroll-posts' ) . '</em>',
				'finishedMsg'     => '<em>' . __( 'No additional posts.', 'wp-easy-scroll-posts' ) . '</em>',
				'img'             => plugins_url( 'img/ajax-loader-1.gif', __FILE__ ),
				'align'				=> 'center'
			),
			'nextSelector'    => '.paging-navigation a:first',
			'navSelector'     => '.paging-navigation',
			'itemSelector'    => '.post',
			'contentSelector' => '#content'
						
		);
	}

	function enqueue_js() {
		if (!$this->shouldLoadJavascript()) {
			return;
		}

		$suffix = '.dev';
		$file = "/js/front-end/jquery.infinitescroll{$suffix}.js";
		wp_enqueue_script( $this->slug, plugins_url( $file, __FILE__ ), array( 'jquery' ), $this->version, true );
		$options = apply_filters( $this->prefix . 'js_options', $this->options->get_options() );
		wp_localize_script($this->slug, $this->slug_, json_encode($options));

	}

	function footer() {
		if (!$this->shouldLoadJavascript()) {
			return;
		}

		require dirname( __FILE__ ) . '/templates/footer.php';
	}

	/* scroll_lang_support files */
	function scroll_lang_support() {
		load_plugin_textdomain( $this->slug, false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	function upgrade_check() {
		if ($this->options->db_version == $this->version) {
			return;
		}

		$this->upgrade( $this->options->db_version, $this->version );

		do_action( $this->prefix . 'upgrade', $this->version, $this->options->db_version );

		$this->options->db_version = $this->version;
	}

	function upgrade( $from , $to ) {
		if ($from < "1.0") {
			//array of option conversions in the form of from => to
			$map = array(
				'js_calls' => 'callback',
				'image' => 'img',
				'text' => 'msgText',
				'donetext' => 'finishedMsg',
				'content_selector' => 'contentSelector',
				'post_selector' => 'itemSelector',
				'nav_selector' => 'navSelector',
				'next_selector' => 'nextSelector',
				'debug' => 'debug',
			);

			$old = get_option( 'vivascroll_options' );
			$new = array();

			if ( !$old ) {
				//loop through options and attempt to find
				foreach ( array_keys( $map ) as $option ) {
					$legacy = get_option( 'vivascroll_' . $option );

					if ( !$legacy )
						continue;

					//move to new option array and delete old
					$new[ $map[ $option ] ] = $legacy;
					delete_option( 'vivascroll_' . $option );
				}
			}

			foreach ( $map as $from => $to ) {

				if ( !$old || !isset( $old[ 'vivascroll_' . $from ] ) )
					continue;

				$new[ $to ] = $old[ 'vivascroll_' . $from ];

			}

			foreach ( array( 'contentSelector', 'itemSelector', 'navSelector', 'nextSelector' ) as $field ) {
				if ( isset( $new[$field] ) ) {
					$new[$field] = html_entity_decode($new[$field]);
				}
			}

			$new['loading'] = array( );

			foreach ( array( 'finishedMsg', 'msgText', "img" ) as $field ) {
				if ( isset( $new[$field] ) ) {
					$new['loading'][$field] = $new[$field];
					unset( $new[$field] );
				}
			}

			if( isset($new["loading"]['img']) && !strstr($new["loading"]["img"], "/img/ajax-loader-1.gif") )
				$new["loading"]['img'] = str_replace("/ajax-loader-1.gif", "/img/ajax-loader-1.gif", $new["loading"]['img']);

			//don't pass an empty array so the default filter can properly set defaults
			if ( empty( $new['loading'] ) )
				unset( $new['loading'] ); 

			$this->options->set_options( $new );
			delete_option( 'vivascroll_options' );

		}

		if ($from < '1.0') {
			$old = get_option("wp_easy_scroll_posts");
			$new = $old;
			$new["loading"]["img"] = $old["img"];
			unset($new["img"]);

			$this->options->set_options($new);
		}
	}

	function paged_404_fix( ) {
		global $wp_query;

		if ( is_404() || !is_paged() || 0 != count( $wp_query->posts ) )
			return;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	function shouldLoadJavascript() {
		if (is_singular()) {
			return false;
		}
		return true;
	}
}
$wp_easy_scroll_posts = new Wp_EasyScroll_Posts();
?>