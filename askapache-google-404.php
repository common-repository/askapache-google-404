<?php
/**
 * Plugin Name: AskApache Google 404
 * Short Name: AA Google 404
 * Description: Displays unbeatable information to site visitors arriving at a non-existant page (from a bad link).  Major SEO with Google AJAX, Recent Posts, etc..
 * Author: askapache
 * Contributors: askapache
 * Version: 5.1.2
 * Updated: 10/28/2017
 * Requires at least: 3.3
 * Tested up to: 4.8.2
 * Tags: google, 404, 404-1, 0-404, 0404, not-found, missing, lost, error, htaccess, ErrorDocument, notfound, ajax, search, seo, mistyped, redirect, notify, url, news, videos, images, blogs, optimized, askapache, admin, ajax, template, traffic, oops
 * WordPress URI: https://wordpress.org/extend/plugins/askapache-google-404/
 * Author URI: https://www.askapache.com/
 * Donate URI: https://www.askapache.com/about/donate/
 * Plugin URI: https://www.askapache.com/seo/404-google-wordpress-plugin/
 *
 *
 * AskApache Google 404 - Intelligent SEO-Based 404 Error Handling
 * Copyright (C) 2010	AskApache.com
 *
 * This program is free software - you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 */

// don't load directly - exit if add_action or plugins_url functions do not exist
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'add_action' ) || ! function_exists( 'plugins_url' ) ) {
	die();
}





if ( ! class_exists( 'AA_G404' ) ) :




	/**
	 * Defines whether ISCLOG class for custom logging exists
	 *
	 * This can not be defined elsewhere
	 *
	 * @since 5.0.1
	 * @var bool
	 */
	define( 'AA_G404_HAS_ISCLOG', (bool) ( defined( 'ISC_DEBUG_AA' ) && ISC_DEBUG && class_exists( 'ISCLOG' ) ) );


	/**
	 * Defines whether debugging is enabled or not.
	 *
	 * This can be defined in wp-config.php which overwrites it here.
	 *
	 * @since 5.0.1
	 * @var bool
	 */
	! defined( 'AA_G404_DEBUG' ) && define( 'AA_G404_DEBUG', AA_G404_HAS_ISCLOG );


	/**
	 * Defines whether to debug start and stop of functions
	 *
	 * This can be defined in wp-config.php which overwrites it here.
	 *
	 * @since 5.0.1
	 * @var bool
	 */
	! defined( 'AA_G404_DEBUG_FUNCTIONS' ) && define( 'AA_G404_DEBUG_FUNCTIONS', AA_G404_HAS_ISCLOG );


	/**
	 * Defines whether to debug
	 *
	 * This can not be defined elsewhere and requires ISCLOG and AA_G404_DEBUG_FUNCTIONS==true
	 *
	 * @since 5.0.1
	 * @var bool
	 */
	define( 'AA_G404D_F', (bool) ( AA_G404_HAS_ISCLOG && AA_G404_DEBUG_FUNCTIONS ) );


	/**
	 * Defines whether to debug
	 *
	 * This can not be defined elsewhere and requires ISCLOG and AA_G404_DEBUG==true
	 *
	 * @since 5.0.1
	 * @var bool
	 */
	define( 'AA_G404D', (bool) ( AA_G404_HAS_ISCLOG && AA_G404_DEBUG ) );




	/**
	 * AA_G404
	 *
	 * @package WordPress
	 * @author AskApache
	 * @version 4.10
	 * @copyright Copyright (C) www.askapache.com
	 * @access public
	 * @link http://googlesystem.blogspot.com/2008/02/google-toolbar-and-404-error-pages.html
	 */
	class AA_G404 {

		// an array of options and values
		var $options = array();

		// array to hold plugin information
		var $plugin = array();

		// array to hold the css and html
		var $code = array(
			'css' => '',
			'html' => '',
		);



		/**
		 * Loads the options into the class vars.
		 * Adds this plugins 'load' function to the 'load-plugin' hook.
		 *
		 * @return void
		 */
		function init() {
			AA_G404D_F && ISCLOG::ti();

			// load $this->plugin, $this->options
			$this->load_options();

			if ( is_admin() ) {

				// sorry, gotta force a reset
				if ( ! isset( $this->options['cse_id'] ) ) {
					$this->reset_options();
				}

				// add options page
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

				// add load
				add_action( "load-{$this->plugin['hook']}", array( &$this, 'load' ) );

			} else {

				// hook 404_template to show our 404 template, but only if enabled
				if ( ( '1' === $this->options['enabled'] ) ) {
					add_filter( '404_template', array( &$this, 'get_404_template' ), 2000, 1 );
				}

			}

			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * The load function executed by the load-plugin hook.  Passes control of request handling to the 'handle_post' function.
		 * Adds the meta-boxes and the contextual help.
		 * Enqueues the neccessary js and css files for plugin adminstration.
		 *
		 * @return void
		 */
		function load() {
			AA_G404D_F && ISCLOG::ti();
			AA_G404D && ISCLOG::tw( ( is_404() ? 'is_404: TRUE' : ' is_404: FALSE' ) );

			// load code
			$this->load_code();

			// add contextual help
			add_action( "admin_head-{$this->plugin['hook']}", array( &$this, 'add_help' ) );

			// enqueue css - wp_enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' )
			wp_enqueue_style( $this->plugin['pagenice'], plugins_url( 'f/admin.css', __FILE__ ), array( 'wp-jquery-ui-dialog' ), $this->plugin['version'], 'all' );

			// enqueue javascript - wp_enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false )
			wp_enqueue_script( $this->plugin['pagenice'], plugins_url( 'f/admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-progressbar', 'postbox' ), $this->plugin['version'], true );


			// parse and handle post requests to plugin
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				$this->handle_post();
			}

			AA_G404D && ISCLOG::tw( ( is_404() ? 'is_404: TRUE' : ' is_404: FALSE' ) );
			AA_G404D_F && ISCLOG::ti();
		}




		/**
		 * The main function that lets this plugin handle errors instead of WP's builtin error handling.
		 *
		 * @param string $template The template file
		 *
		 * @return string    absolute path of php template
		 */
		function get_404_template( $template ) {
			AA_G404D_F && ISCLOG::ti();
			AA_G404D && ISCLOG::tw( ( is_404() ? 'is_404: TRUE' : ' is_404: FALSE' ) );

			// load code
			$this->load_code();

			// construct handler class
			global $AA_G404_Handler;
			if ( ! is_object( $AA_G404_Handler ) ) {
				AA_G404D && ISCLOG::tw( 'AA_G404_Handler NOT AN OBJECT' );
				$AA_G404_Handler = aa_g404_get_handler_object();
			}

			// Now handle the incoming request with AA_G404_Handler::handle_it
			$AA_G404_Handler->handle_it();

			// Loads the 404 error template specified by the 404_handler option
			if ( file_exists( $this->options['404_handler'] ) ) {
				AA_G404D && ISCLOG::tw( 'loading: ' . $this->options['404_handler'] );
				AA_G404D_F && ISCLOG::ti();
				return $this->options['404_handler'];
			}

			// return for the template_redirect
			AA_G404D && ISCLOG::tw( ( is_404() ? 'is_404: TRUE' : ' is_404: FALSE' ) );
			AA_G404D_F && ISCLOG::ti();

			return $template;
		}




		/**
		 * currently code is not saved across upgrades due to a potential security issue
		 *
		 * @return void
		 */
		function upgrade_settings() {
			AA_G404D_F && ISCLOG::ti();


			// upgrade plugin settings
			$this->plugin = $this->get_plugin_data( true );


			// current code
			$code = get_option( 'askapache_google_404_code' );

			// code base64_decoded
			$codeb64 = ( ( $code !== false && strlen( $code ) > 2 ) ? base64_decode( $code ) : '' );

			// if 46 empty
			$codeb64_len = strlen( $codeb64 );


			if ( substr( $codeb64, 0, 2 ) === 'a:' ) {
				AA_G404D && ISCLOG::tw( 'code looks to be uncompressed: ' . $codeb64 );
				$code = base64_encode( gzdeflate( $codeb64, 1 ) );

				if ( strlen( $code ) > 46 ) {
					AA_G404D && ISCLOG::tw( 'saving askapache_google_404_code: ' . $code );

					$this->code = $code;
					update_option( 'askapache_google_404_code', $code );
				}

			} else {
				AA_G404D && ISCLOG::tw( 'code is compressed: ' . $codeb64_len );
				$this->code = unserialize( gzinflate( $codeb64 ) );
			}



			// first check $code is valid
			if ( $code === false || strlen( $code ) < 20 ) {
				AA_G404D && ISCLOG::epx( array( 'code === false', $code ) );

				// set code to defaults
				$this->code = $this->get_default_code();

			} elseif ( @unserialize( $codeb64 ) !== false ) {
				AA_G404D && ISCLOG::tw( 'IS OLD VERSION' );

				$old_code = unserialize( $codeb64 );
				$new_code_compressed = base64_encode( gzdeflate( serialize( $old_code ), 1 ) );
				$new_code_uncompressed = unserialize( gzinflate( base64_decode( $new_code_compressed ) ) );

				if ( $new_code_uncompressed === $old_code ) {
					AA_G404D && ISCLOG::tw( 'SWITCHING TO NEW VERSION' );

					$this->code = $old_code;

					delete_option( 'askapache_google_404_code' );
					add_option( 'askapache_google_404_code', $new_code_compressed, '', 'no' );


				} else {
					AA_G404D && ISCLOG::tw( 'NOT SWITCHING TO NEW VERSION' );
				}

			} else {
				AA_G404D && ISCLOG::tw( 'NEW VERSION ALREADY' );
				$this->code = $code;
			}



			// default options
			$default_options = $this->get_default_options();

			// current options
			$options = get_option( 'askapache_google_404_options' );


			// first check $options is valid or set to defaults
			if ( $options === false || ! is_array( $options ) ) {
				AA_G404D && ISCLOG::epx( 'options === false or not array!' );
				$options = $default_options;
			} else {

				// the default_options keys
				$default_options_keys = array_keys( $default_options );
				sort( $default_options_keys );

				// keys to current options
				$options_keys = array_keys( $options );
				sort( $options_keys );

				if ( $default_options_keys != $options_keys ) {
					AA_G404D && ISCLOG::epx( 'default_options_keys != options_keys' );

					foreach ( $options as $k => $v ) {
						AA_G404D && ISCLOG::tw( "{$k} => {$v}" );
						if ( array_key_exists( $k, $default_options ) ) {
							AA_G404D && ISCLOG::epx( "{$k} => {$v}" );
							$default_options[ $k ] = $v;
						}
					}

					// no set the options to the newly updated default_options
					$options = $default_options;
				}
			}



			// get legacy analytics_key and save to options
			if ( ! array_key_exists( 'analytics_key', $options ) || empty( $options['analytics_key'] ) ) {
				AA_G404D && ISCLOG::tw( 'searching for analytics_key' );
				$analytics_key = get_option( 'aa_google_404_analytics_key' ); // UA-732153-7
				$options['analytics_key'] = ( $analytics_key !== false && strlen( $analytics_key ) > 3 ) ? $analytics_key : '';
			}



			// update 404_handler in case of __DIR__ changed
			if ( strpos( $options['404_handler'], 'plugins/askapache-google-404/404.php' ) !== false ) {
				$options['404_handler'] = __DIR__ . '/404.php';
			}


			// now set this->options to newly created options
			$this->options = $options;
			// ------------------------------------------------------------------------------------------------------


			// delete these unused options
			delete_option( 'aa_google_404_api_key' );
			delete_option( 'aa_google_404_adsense_key' );
			delete_option( 'aa_google_404_analytics_key' );


			// Save all these variables to database
			$this->save_options();

			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * Loads this->code
		 *
		 * @return void
		 */
		function load_code() {
			AA_G404D_F && ISCLOG::ti();

			// get code
			$code = get_option( 'askapache_google_404_code' );

			// code decoded
			$code_decoded = ( $code !== false ) ? base64_decode( $code ) : '';

			// if 46 empty
			$code_decoded_len = strlen( $code_decoded );

			if ( $code_decoded_len === 46 ) {
				AA_G404D && ISCLOG::tw( 'code is empty!  Getting and saving default code' );

				// original code that comes with plugin
				$this->code = $this->get_default_code();
				$this->save_options();

				AA_G404D_F && ISCLOG::ti();
				return;
			}


			// check if code is serialized already, indicating it is not using the newer compression
			if ( substr( $code_decoded, 0, 2 ) === 'a:' ) {
				AA_G404D && ISCLOG::tw( 'code looks to be uncompressed: ' . $code_decoded );
				$code = base64_encode( gzdeflate( $code_decoded, 1 ) );

				if ( strlen( $code ) > 4 ) {
					$this->code = $code;
					update_option( 'askapache_google_404_code', $code );
				}

			} else {
				AA_G404D && ISCLOG::tw( 'code is compressed: ' . $code_decoded_len );
				$this->code = unserialize( gzinflate( $code_decoded ) );
				//AA_G404D && ISCLOG::epx( $this->code );
			}


			AA_G404D_F && ISCLOG::ti();
		}

		/**
		 * Loads options named by opts array into correspondingly named class vars
		 *
		 * @return void
		 */
		function load_options() {
			AA_G404D_F && ISCLOG::ti();

			// get options
			$this->options = get_option( 'askapache_google_404_options' );

			// first try get_option, then parse this __FILE__
			$this->plugin = $this->get_plugin_data();

			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * Saves options from class vars passed in by opts array and the adsense key and api key
		 *
		 * @return void
		 */
		function save_options() {
			AA_G404D_F && ISCLOG::ti();

			// save options
			update_option( 'askapache_google_404_options', $this->options );

			// save plugin
			update_option( 'askapache_google_404_plugin', $this->plugin );

			// save code
			if ( ! empty( $this->code ) && is_array( $this->code ) && array_key_exists( 'css', $this->code ) ) {
				//$code = base64_encode( serialize( $this->code ) );
				$code = base64_encode( gzdeflate( serialize( $this->code ), 1 ) );

				if ( strlen( $code ) > 46 ) {
					AA_G404D && ISCLOG::tw( "saving askapache_google_404_code as: {$code}" );
					update_option( 'askapache_google_404_code', $code );
				} else {
					AA_G404D && ISCLOG::tw( "NOT saving askapache_google_404_code as: {$code}" );
				}

			} else {
				AA_G404D && ISCLOG::tw( 'this->code is empty!  not saving' );
			}

			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * this plugin has to protect the code as it is displayed live on error pages, a prime target for malicious crackers and spammers
		 * can someone help me add the proper code to make sure everything is escaped correctly?
		 *
		 * @return void
		 */
		function handle_post() {
			AA_G404D_F && ISCLOG::ti();

			// if current user does not have manage_options rights, then DIE
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
			}

			// verify nonce, if not verified, then DIE
			if ( isset( $_POST[ "_{$this->plugin['nonce']}" ] ) ) {
				wp_verify_nonce( $_POST[ "_{$this->plugin['nonce']}" ], $this->plugin['nonce'] ) || wp_die( __( '<strong>ERROR</strong>: Incorrect Form Submission, please try again.' ) );
			} elseif ( isset( $_POST['ag4_action_reset'] ) ) {
				wp_verify_nonce( $_POST['ag4_action_reset'], 'ag4_action_reset_nonce' ) || wp_die( __( '<strong>ERROR</strong>: Incorrect Form Submission, please try again.' ) );
			}


			// resets options to default values
			if ( isset( $_POST['ag4_action_reset'] ) ) {
				$this->reset_options();
				return;
			}

			// load up the current options from the database
			$this->load_options();


			// process absolute integer options
			foreach ( array( 'recent_num', 'tag_cloud_num' ) as $k ) {
				$this->options[ $k ] = ( ( isset( $_POST[ "ag4_{$k}" ] ) ) ? absint( $_POST[ "ag4_{$k}" ] ) : absint( $this->options[ $k ] ) );
			}


			// process options of type string
			foreach ( array( 'cse_id', 'analytics_key', 'robots_tag', '404_handler' ) as $k ) {
				$this->options[ $k ] = ( ( isset( $_POST[ "ag4_{$k}" ] ) && ! empty( $_POST[ "ag4_{$k}" ] ) ) ? $_POST[ "ag4_{$k}" ] : $this->options[ $k ] );
			}


			// process on ('1' ) or off ('0' ) options
			$on_off_options = array(
				'enabled',
				'robots_meta',
				'recent_posts',
				'tag_cloud',
				'analytics_log',
			);
			foreach ( $on_off_options as $k ) {
				$this->options[ $k ] = ( ( ! isset( $_POST[ "ag4_{$k}" ] ) ) ? '0' : '1' );
			}


			// TODO: Nothing :)
			foreach ( array( 'analytics_url' ) as $k ) {
				if ( isset( $_POST[ "ag4_{$k}" ] ) ) {
					$this->options[ $k ] = stripslashes( $_POST[ "ag4_{$k}" ] );
				}
			}


			// process incoming unfiltered code
			foreach ( array( 'css', 'html' ) as $k ) {
				if ( isset( $_POST[ "ag4_{$k}" ] ) && strlen( $_POST[ "ag4_{$k}" ] ) > 10 ) {
					$this->code[ $k ] = stripslashes( $_POST[ "ag4_{$k}" ] );
				}
			}

			// Save code and options arrays to database
			$this->save_options();

			AA_G404D_F && ISCLOG::ti();
		}

		/**
		 * Gets and sets the default values for the plugin options, then saves them
		 *
		 * @return void
		 */
		function reset_options() {
			AA_G404D_F && ISCLOG::ti();

			// get all the plugin array data
			$this->plugin = $this->get_plugin_data( true );

			// original code that comes with plugin
			$this->code = $this->get_default_code();

			// get default options
			$this->options = $this->get_default_options();

			// Save all these variables to database
			$this->save_options();

			AA_G404D_F && ISCLOG::ti();
		}

		/**
		 * Gets the default $this->options
		 *
		 * @return array   Array of options
		 */
		function get_default_options() {
			AA_G404D_F && ISCLOG::ti();

			$handler = file_exists( TEMPLATEPATH . '/404.php' ) ? TEMPLATEPATH . '/404.php' : __DIR__ . '/404.php';

			$ga = isset( $this->options['analytics_key'], $this->options['analytics_key'][5] ) ? $this->options['analytics_key'] : '';
			$cse = isset( $this->options['cse_id'], $this->options['cse_id'][15] ) ? $this->options['cse_id'] : 'partner-pub-4356884677303281:hcqlgw-sn16';

			// default options
			$options = array(
				'cse_id' 	=> $cse,	// partner-pub-4356884677303281:hcqlgw-sn16
				'analytics_key'	=> $ga,	// UA-732153-7
				'analytics_url' => '"/404/?page=" + document.location.pathname + document.location.search + "&from=" + document.referrer',

				'enabled' 		=> '1',	// 404 error handling is ON by default

				'analytics_log' => '1',	//

				'robots_meta' 	=> '1', // adding noindex,follow robot meta tag to error pages is ON by default
				'robots_tag' => 'noindex,follow',	// the value of the robot meta on error pages

				'recent_posts' 	=> '1', // showing recent posts on error pages is ON by default
				'recent_num' 	=> 6,	// number of recent posts to show

				'tag_cloud' 	=> '1', // showing a tag cloud on error pages is ON by default
				'tag_cloud_num'	=> 30,	// number tags used to create cloud

				'404_handler' => $handler,	// the file location of 404 template
			);

			AA_G404D_F && ISCLOG::ti();

			return $options;
		}

		/**
		 * Gets the default code for css and html
		 *
		 * @return array  original_code with 3 keys
		 */
		function get_default_code() {
			AA_G404D_F && ISCLOG::ti();

			$original_code = array(
				'css' => '#g404ajax {width:99%;overflow:hidden;margin-left:2px;}' . "\n"
				. '#g404ajax .gsc-control-cse,' . "\n" . '#g404ajax .gsc-webResult.gsc-result,' . "\n" . '#g404ajax .gsc-results .gsc-imageResult,' . "\n"
				. '#g404ajax .cse .gsc-webResult.gsc-result,' . "\n" . '#g404ajax .gsc-imageResult-column,' . "\n" . '#g404ajax .gsc-imageResult-classic {border:0};',

				'html' => '<div id="g404ajax">' . "\n" . '%google%' . "\n\n" . '<p style="clear:both;"></p>' . "\n\n" . '</div><!--g404ajax-->'
				. "\n\n" . '<h3>Recent Posts</h3>' . "\n" . '%recent_posts%' . "\n\n" . '<h3>Popular topics:</h3>' . "\n" . '%tag_cloud%',
			);

			AA_G404D_F && ISCLOG::ti();
			return $original_code;
		}


		/**
		 * Add options page to admin menu
		 * @return void
		 */
		function admin_menu() {
			AA_G404D_F && ISCLOG::ti();

			// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
			add_options_page( $this->plugin['plugin-name'], $this->plugin['short-name'], 'manage_options', $this->plugin['page'], array( &$this, 'options_page' ) );

			AA_G404D_F && ISCLOG::ti();
		}

		/**
		 * The main options page
		 * @return void
		 */
		function options_page() {
			AA_G404D_F && ISCLOG::ti();

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
			}


			echo '<div class="wrap" id="ag4">';

			if ( function_exists( 'screen_icon' ) ) {
				screen_icon();
			}

			echo '<h2>' . $this->plugin['plugin-name'] . '</h2>';
			echo '<form action="' . admin_url( $this->plugin['action'] ) . '" method="post" id="ag4_form">';

			//ISCLOG::pdh( $this->options );

			// print form nonce
			echo '<p style="display:none;"><input type="hidden" id="_' . $this->plugin['nonce'] . '" name="_' . $this->plugin['nonce'] . '" value="' . wp_create_nonce( $this->plugin['nonce'] ) . '" />';
			echo '<input type="hidden" name="_wp_http_referer" value="' . ( esc_attr( $_SERVER['REQUEST_URI'] ) ) . '" /></p>';


			$section_names = array(
				'general' => 'General',
				'output' => '404 Output Options',
				'tracking' => 'Tracking/Logging',
				'css' => 'CSS Editor',
				'html' => 'HTML Editor',
			);
			echo '<div id="ag4-tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all">';
			?><ul id="ag4_tabs_ul" class="hide-if-no-js"  style="display:none"><?php foreach ( $section_names as $section_id => $section_name ) {
				printf( '<li><a href="#section-%s">%s</a></li>', esc_attr( $section_id ), $section_name );
			} ?></ul><?php

			echo '<div id="section-general" class="ag4-section"><h3 class="hide-if-js">General</h3>';
			echo '<table class="form-table"><tbody><tr><th scope="row">Enable/Disable Plugin</th><td><fieldset><legend class="screen-reader-text"><span>Enable/Disable handling errors</span></legend>';
			echo '<label for="ag4_enabled" style="font-weight:bold" title="Handle Erorrs"><input type="radio"' . checked( $this->options['enabled'], '1', false ) . ' value="1" name="ag4_enabled" id="ag4_enabled_on" /> Enable plugin to handle 404s, immediately</label><br />';
			echo '<label for="ag4_enabled" title="Turn off this plugin"><input type="radio"' . checked( $this->options['enabled'], '0', false ) . ' value="0" name="ag4_enabled" id="ag4_enabled_off" /> Disable this plugin from handling 404s</label><br />';
			echo '</fieldset></td></tr>';

			echo '<tr><th scope="row">404.php Template File</th><td>';
			echo '<fieldset><legend class="screen-reader-text"><span>404.php Template File</span></legend>';

			$error_templates = array(
				__DIR__ . '/404.php',
				get_404_template(),
				TEMPLATEPATH . '/404.php',
				dirname( TEMPLATEPATH ) . '/default/404.php',
				'Custom File Location',
			);

			$error_templates = array_unique( $error_templates );
			$can_edit = current_user_can( 'edit_files' );

			foreach ( $error_templates as $v => $k ) {
				if ( $k === 'Custom File Location' ) {
					echo '<label for="ag4_404_handler" title="' . $k . '"><input type="radio"' . checked( $this->options['404_handler'], $k, false );
					echo ' value="' . $k . '" name="ag4_404_handler" id="ag4_other_file" /> ';
					echo '<input type="text" value="Custom File Location" class="code" id="ag4_404_choose_file" style="min-width:35em;" name="ag4_404_choose_file" title="File Path"></label><br />';
				} elseif ( file_exists( $k ) ) {
					echo '<label for="ag4_404_handler" title="' . $k . '"><input type="radio"' . checked( $this->options['404_handler'], $k, false );
					echo ' value="' . $k . '" name="ag4_404_handler" id="ag4_404_handler_' . $v . '" /> <code>' . $k . '</code> ';
					echo ( $can_edit ? '<a href="' . admin_url( "theme-editor.php?file={$k}" ) . '">(EDIT)</a>' : '' ) . '</label><br />';
				}
			}
			echo '</fieldset></td></tr></tbody></table>';
			echo '<p class="binfo">Add to existing 404.php theme file';
			echo ' in your template directory:  <br />Add <code style="font-size:11px;">if ( function_exists( \'aa_google_404\' ) ) { aa_google_404(); }</code> in the body and save as';
			echo ' 404.php in your template folder.<br /> - See the included 404.php file for a simple working example.</p>';


			/*$this->form_field( 2, 'Google API Key <a href="http://code.google.com/apis/ajaxsearch/signup.html">Get One</a>', 'api_key', 'This identifies your blog to Google.' );
			echo '<p class="binfo">You need a Google API Key for this site to display the ajax results.  Go ahead and add your AdSense ID as future versions of this plugin will allow you to incorporate AdSense setups compliant with Google Guidelines.</p>';			*/


			echo '</div><!--section-->';





			echo '<div class="ag4-section" id="section-output"><h3 class="hide-if-js">404 Output Options</h3>';

			$this->form_field( 1, 'Show Recent Posts', 'recent_posts', 'Displays List of Recent Posts' );
			$this->form_field( 3, 'Recent Posts # to Show', 'recent_num', 'How many recent posts to show..' );
			echo '<p class="binfo">Shows a list of Recent Posts on your blog.</p>';

			$this->form_field( 1, 'Show Popular Tag Cloud', 'tag_cloud', 'Displays Popular Tag Cloud' );
			$this->form_field( 3, 'Tag # to Use', 'tag_cloud_num', 'How many tags to use, otherwise ALL tags..' );
			echo '<p class="binfo">Displays a tag cloud (heatmap) from provided data. of your popular tags where each tag is displayed with a font-size showing how popular the tag is, more popular tags are larger.</p>';



			$this->form_field( 1, 'Add <a target="_blank" href="http://www.askapache.com/seo/updated-robotstxt-for-wordpress.html">robots meta</a> to prevent indexing', 'robots_meta', 'Prevent 404 pages from being indexed. This prevents your error pages from being indexed by Google and other search engines, which saves your PageRank for your non-error pages.  Highly recommended, Google recommended.' );
			$this->form_field( 2, 'Robots meta tag value <a target="_blank" href="http://www.askapache.com/seo/updated-robotstxt-for-wordpress.html">(?)</a>', 'robots_tag', 'Value of robots meta tag.' );
			echo '</div><!--section-->';




			echo '<div class="ag4-section" id="section-tracking"><h3 class="hide-if-js">404 Tracking/Logging</h3>';

			echo '<p class="binfo">Use Google Analytics to <a target="_blank" href="http://www.askapache.com/seo/tip-google-analytics-404-error-page.html">Track/Log Errors</a>.</p>';
			$this->form_field( 1, '<a href="http://www.google.com/support/googleanalytics/bin/answer.py?hl=en&answer=86927">Track</a> <a target="_blank" href="http://www.google.com/support/forum/p/Google+Analytics/thread?tid=09386ba811b3e7d8&hl=en">Errors</a> with Google Analytics', 'analytics_log', 'Use Google Analytics to Track/Log Errors' );

			$this->form_field( 2, 'Google Analytics Key <small>UA-733153-7</small> <a target="_blank" href="https://www.google.com/adsense/support/bin/answer.py?answer=45465">Get One</a>', 'analytics_key', 'The tracking ID for this site.' );

			echo '<p class="binfo">This is clever as instead of using your server and database to store 404s, which results in crazy additional server-load, this method uses javascript so google machines will do all the work.  <code>"/404.html?page=" + document.location.pathname + document.location.search + "&amp;from=" + document.referrer</code></p>';
			$this->form_field( '2j', 'Tracking URL for reports <a target="_blank" href="http://www.google.com/support/googleanalytics/bin/answer.py?answer=75129">Get One</a>', 'analytics_url', 'Lets you view errors in analytics!' );

			$this->form_field( 2, 'Your Google CSE ID <small>partner-pub-4356884677303281:hcqlgw-sn16</small> <a target="_blank" href="https://support.google.com/adsense/answer/1055578?hl=en">(?)</a>', 'cse_id', 'Get one using AdSense account or a CSE' );
			echo '</div><!--section-->';



			echo '<div class="ag4-section" id="section-html"><h3 class="hide-if-js">HTML Editor</h3>';
			$this->form_field( 5, '', 'html','This controls the output of the plugin.  Move stuff around, change what you want, and load the default if you mess up too much.' );
			echo '<p class="binfo">This lets you determine the placement and any extra html you want output by this plugin.<br /><br /><code>%google%</code> - required!  This will be the search box and search results<br /><code>%error_title%</code> - replaced with the status code and error phrase - 404 Not Found<br /><code>%tag_cloud%</code> - replaced with your tag cloud if enabled<br /><code>%recent_posts%</code> - replaced with the recent posts html if enabled</p>';
			echo '</div><!--section-->';



			echo '<div class="ag4-section" id="section-css"><h3 class="hide-if-js">CSS Editor</h3>';
			$this->form_field( 5, '', 'css','The css that controls the google ajax search results.. (and anything else on the page)' );
			echo '<p class="binfo">Modify the css that is output (inline) on your 404 error pages.  Changes the appearance of, well, everything.</p>';
			echo '</div><!--section-->';

			echo '</div><!--ag4-tabs-->';


			echo '<p class="submit hide-if-js"><input type="submit" class="button-primary" name="ag4_action_save" id="ag4_action_save" value="Save Changes &raquo;" />  <br /><br /><br /><br />&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<input type="submit" class="button button-primary button-large" name="ag4_action_reset" id="ag4_action_reset" value="Revert to Defaults &raquo;" /></p>';
			echo '</form><br style="clear:both;" />';


			// just a temp solution.. will be cleaned for next release
			echo "<form style='display: none' method='post' action='" . admin_url( $this->plugin['action'] ) . "' id='ag4_reset'><p>";
			echo "<input type='hidden' name='ag4_action_reset' id='ag4_action_reset' value='" . wp_create_nonce( 'ag4_action_reset_nonce' ) . "' /></p></form>";



			echo "<p><a id='aamainsubmit' title='Save Changes' href='#' class='button button-primary button-large hide-if-no-js ag4submit aasubmit-button'>Save</a><br /><br /><br /><br /></p>";


			echo '<p class="hide-if-no-js">';
			echo "<a title='Reset all options EXCEPT CSE ID and Analytics KEY - including code to the default values' href='#' class='ag4reset'><em class='aasubmit-b'>RESET TO DEFAULTS</em></a>";
			echo '</p><hr style="margin-top:2em;" />';



			echo '<div style="width:300px;float:left;"><p><br class="clear" /></p>
			<h3>Articles from AskApache</h3>';
			echo '<ul><li><a target="_blank" href="http://www.askapache.com/seo/seo-secrets.html">SEO Secrets of AskApache.com</a></li>';
			echo '<li><a target="_blank" href="http://www.askapache.com/seo/seo-advanced-pagerank-indexing.html">Controlling Pagerank and Indexing</a></li>';
			echo '<li><a target="_blank" ref="http://www.askapache.com/htaccess/htaccess.html">Ultimate .htaccess Tutorial</a></li>';
			echo '<li><a target="_blank" href="http://www.askapache.com/seo/updated-robotstxt-for-wordpress.html">Robots.txt Info for WordPress</a></li></ul></div>';

			echo '</div>';

			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * Add Help
		 * @return void
		 */
		function add_help() {
			AA_G404D_F && ISCLOG::ti();

			$current_screen = get_current_screen();

			$help = '<h4>Fixing Status Headers</h4>';
			$help .= '<p>For super-advanced users, or those with access and knowledge of Apache <a target="_blank" href="http://www.askapache.com/htaccess/htaccess.html">.htaccess/httpd.conf files</a>';
			$help .= ' you should check that your error pages are correctly returning a <a target="_blank" href="http://www.askapache.com/htaccess/apache-status-code-headers-errordocument.html"><code>404 Not Found</code>';
			$help .= ' HTTP Header</a> and not a <code>200 OK</code> Header which appears to be the default for many WP installs, this plugin attempts to fix this using PHP, but the best way I have found';
			$help .= ' is to add the following to your <a target="_blank" href="http://www.askapache.com/htaccess/htaccess.html">.htaccess</a> file.</p>';
			$help .= '<pre>ErrorDocument 404 /index.php?error=404' . "\n" . 'Redirect 404 /index.php?error=404</pre>';
			$help .= '<p>You can check your headers by requesting a bad url on your site using my online tool <a target="_blank" href="http://www.askapache.com/online-tools/http-headers-tool/">Advanced HTTP Headers</a>.</p>';
			$help .= '<h4>Future Awesomeness</h4>';
			$help .= '<p>The goal of this plugin is to boost your sites SEO by telling search engines to ignore your error pages, with the focus on human users to increase people staying on your site and being';
			$help .= ' able to find what they were originally looking for on your site.  Because I am obsessed with fast web pages, many various speed/efficiency improvements are also on the horizon.</p>';
			$help .= '<p>Another feature that I am using with beta versions of this plugin, is tracking information for you to go over at your leisure, to fix recurring problems.  The information is collected';
			$help .= ' is the requested url that wasnt found, the referring url that contains the invalid link.</p>';
			$help .= '<p>The reason I didnt include it in this release is because for sites like AskApache with a very high volume of traffic (and thus 404 requests) this feature can create a bottleneck and ';
			$help .= 'slow down or freeze a blog if thousands of 404 errors are being requested and saved to the database.  This could also very quickly be used by malicious entities as a Denial of Service ';
			$help .= 'attack.  So I am figuring out and putting into place limits.. like once a specific requested url resulting in a not found error has been requested 100x in a day, an email is sent to the ';
			$help .= 'blog administrator.  But to prevent Email DoS and similar problems with the number and interval of emails allowed by your email provider other considerations on limits need to be examined.</p>';
			$help .= '<h5>Comments/Questions</h5><p><strong>Please visit <a target="_blank" href="http://www.askapache.com/">AskApache.com</a> or send me an email at <code>webmaster@askapache.com</code></strong></p>';

			add_contextual_help( $current_screen, $help );

			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * Clean way to add html for form fields
		 *
		 * @return void
		 */
		function form_field( $w = 1, $title = '', $id = '', $desc = '' ) {

			echo '<div>';
			switch ( $w ) :
				case 1:
					echo "<p class='c4r'><input title='{$desc}' name='ag4_{$id}' size='10' ";
					echo "type='checkbox' id='ag4_{$id}' value='{$this->options[$id]}' " . checked( '1', $this->options[ $id ], false ) . ' />';
					echo "<label title='{$desc}' for='ag4_{$id}'> {$title}</label><br style='clear:both;' /></p>";
				break;
				case 2:
					echo "<p class='c4r'><label title='{$desc}' for='ag4_{$id}'> {$title}:</label><br style='clear:both;' />";
					echo "<input title='{$desc}' name='ag4_{$id}' type='text' id='ag4_{$id}' value='" . ( isset( $this->options[ $id ] ) ? $this->options[ $id ] : '' ) . "' /><br style='clear:both;' /></p>";
				break;
				case '2j':
					echo "<p class='c4r'><label title='{$desc}' for='ag4_{$id}'> {$title}:</label><br style='clear:both;' />";
					echo "<input title='{$desc}' name='ag4_{$id}' type='text' id='ag4_{$id}' value='" . ( isset( $this->options[ $id ] ) ? stripslashes( $this->options[ $id ] ) : '' ) . "' /><br style='clear:both;' /></p>";
				break;

				case '2h':
					echo "<p class='c4r hide-if-js'><label title='{$desc}' for='ag4_{$id}'> {$title}:</label><br style='clear:both;' />";
					echo "<input title='{$desc}' name='ag4_{$id}' type='text' id='ag4_{$id}' value='" . ( isset( $this->options[ $id ] ) ? $this->options[ $id ] : '' ) . "' /><br style='clear:both;' /></p>";
				break;
				case 3:
					echo "<p class='c4r'><input title='{$desc}' name='ag4_{$id}' style='float:left;margin-right:5px;' size='4' type='text' id='ag4_{$id}' ";
					echo "value='" . ( isset( $this->options[ $id ] ) ? $this->options[ $id ] : '' ) . "' /><label title='{$desc}' for='ag4_{$id}'> {$title}:</label><br style='clear:both;' /></p>";
				break;
				case 5:
					echo "<div><label for='ag4_{$id}'>{$desc}<br /></label><br />{$title}</div>";
					echo "<div><textarea title='{$desc}' cols='70' rows='12' name='ag4_{$id}' id='ag4_{$id}' class='codepress {$id}'>" . htmlspecialchars( $this->code[ $id ] ) . '</textarea></div>';
				break;
			endswitch;
			echo '</div>';
		}

		/**
		 * Get all plugin data by reading it
		 *
		 * @return array Plugin data
		 */
		function get_plugin_data( $force = false, $type = 'settings' ) {
			AA_G404D_F && ISCLOG::ti();
			AA_G404D && ISCLOG::tw( ( $force ? 'force: TRUE' : 'force: FALSE' ) );

			$plugin = get_option( 'askapache_google_404_plugin' );

			if ( $force === true || $plugin === false || ! is_array( $plugin ) || ! array_key_exists( 'file', $plugin ) || "{$plugin['file']}" !== __FILE__ ) {
				clearstatcache();

				$data = $this->read_file( __FILE__, 1450 );

				$mtx = $plugin = array();
				preg_match_all( '/[^a-z0-9]+((?:[a-z0-9]{2,25})(?:\ ?[a-z0-9]{2,25})?(?:\ ?[a-z0-9]{2,25})?)\:[\s\t]*(.+)/i', $data, $mtx, PREG_SET_ORDER );

				$valids = array(
					'plugin-name',
					'short-name',
					'author',
					'version',
					'wordpress-uri',
					'author-uri',
					'plugin-uri',
					'file',
					'title',
					'page',
					'pagenice',
					'nonce',
					'hook',
					'action',
				);

				foreach ( $mtx as $m ) {
					$mm = trim( str_replace( ' ', '-', strtolower( $m[1] ) ) );
					if ( in_array( $mm, $valids, true ) ) {
						$plugin[ $mm ] = str_replace( array( "\r", "\n", "\t" ), '', trim( $m[2] ) );
					}
				}

				$plugin['file'] = __FILE__;
				$plugin['title'] = '<a href="' . $plugin['plugin-uri'] . '" title="Visit plugin homepage">' . esc_attr( $plugin['plugin-name'] ) . '</a>';
				$plugin['author'] = '<a href="' . $plugin['author-uri'] . '" title="Visit author homepage">' . esc_attr( $plugin['author'] ) . '</a>';
				$plugin['page'] = basename( __FILE__ );
				$plugin['pagenice'] = rtrim( $plugin['page'], '.php' );
				$plugin['nonce'] = 'form_' . $plugin['pagenice'];
				$plugin['hook'] = $type . '_page_' . $plugin['pagenice'];
				$plugin['action'] = ( ( $type === 'settings' ) ? 'options-general' : $type ) . '.php?page=' . $plugin['page'];
			}


			if ( array_key_exists( 'short-name', $plugin ) && strpos( $plugin['short-name'], '<' . 'img' ) === false ) {
				$plugin['short-name'] = '<img style="position:relative; bottom:-3px" src="' . plugins_url( 'f/i/icon-menu.png', __FILE__ ) . '" alt="" />&nbsp;' . $plugin['short-name'];
			}

			AA_G404D && ISCLOG::tw( ( $force ? 'force: TRUE' : 'force: FALSE' ) );
			AA_G404D_F && ISCLOG::ti();

			return $plugin;
		}

		/**
		 * Reads a file with fopen and fread for a binary-safe read.  $f is the file and $b is how many bytes to return, useful when you dont want to read the whole file (saving mem)
		 *
		 * @return string|bool - the content of the file or false on error
		 */
		function read_file( $f, $b = false ) {
			AA_G404D_F && ISCLOG::ti();

			// file pointer
			$fp = null;

			// data read
			$d = '';

			// if no size specified, read entire file
			if ( $b === false ) {
				$b = @filesize( $f );

				// in case filesize failed
				if ( $b === false ) {
					$b = 4098;
				}
			}

			// return false on failures
			if ( ! ( $b > 0 ) || ! false === ( $fp = fopen( $f, 'rb' ) ) || ! is_resource( $fp ) ) {
				AA_G404D && ISCLOG::tw( 'ERROR OPENING FILE!!! ' . $f );
				AA_G404D_F && ISCLOG::ti();
				return false;
			}


			// if read_length greater than 512 bytes,
			if ( $b > 8192 ) {
				AA_G404D && ISCLOG::tw( basename( $f ) . ': READ/REQUESTED = ' . $b . '/' . strlen( $d ) . '    read size is > 8192 bytes so read in the data in 128 byte increments' );
				// Read in the data in 128 byte increments
				while ( ! feof( $fp ) && strlen( $d ) < $b ) {
					$d .= fread( $fp, 128 );
				}

			} else {
				AA_G404D && ISCLOG::tw( basename( $f ) . ': READ/REQUESTED = ' . $b . '/' . strlen( $d ) . '    read size is < 8192 bytes' );
				// if read size is < than 8192 bytes, read it all in straight
				$d = fread( $fp, $b );
			}

			// close file pointer if still open
			if ( is_resource( $fp ) ) {
				fclose( $fp );
			}


			AA_G404D_F && ISCLOG::ti();

			// return read data
			return $d;
		}

	}







	/**
	 * AA_G404_Handler
	 *
	 * @author AskApache
	 * @copyright AskApache
	 * @version 2009
	 * @access public
	 */
	class AA_G404_Handler {

		var $reason = '';

		var $uri = '';

		var $sc = 404;

		var $req_method = 'UNKNOWN';

		var $protocol = 'HTTP/1.1';

		var $msg = 'The server encountered an internal error or misconfiguration and was unable to complete your request.';


		function __construct() {
			AA_G404D_F && ISCLOG::ti();

			// Adds the AA_G404_Handler::output_head function to the wp_head action
			add_action( 'wp_head', array( &$this, 'output_head' ) );

			// Modifies the title for error pages to be descriptive (in describing the error)
			add_filter( 'wp_title', array( &$this, 'wp_title' ), 99999, 1 );


			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * Modifies the title for error pages to be descriptive (in describing the error)
		 *
		 * @param string $title The title
		 *
		 * @return string the title
		 */
		function wp_title( $title ) {
			AA_G404D_F && ISCLOG::ti();

			$title = $this->sc . ' ' . $this->reason;

			AA_G404D_F && ISCLOG::ti();

			return $title;
		}


		/**
		 * Handle the actual request
		 * @return void
		 */
		function handle_it() {
			AA_G404D_F && ISCLOG::ti();
			//AA_G404D && ISCLOG::epx( array( 'SERVER'=>$_SERVER, 'REQUEST'=>$_REQUEST ) );

			// status code
			$this->sc = (int) ( isset( $_SERVER['REDIRECT_STATUS'] ) && (int) $_SERVER['REDIRECT_STATUS'] !== 200 ) ? $_SERVER['REDIRECT_STATUS'] : ( ! isset( $_REQUEST['error'] ) ? 404 : $_REQUEST['error'] );

			// set server protocol and check version
			if ( ! in_array( $_SERVER['SERVER_PROTOCOL'], array( 'HTTP/1.1', 'HTTP/1.0' ), true ) ) {

				// use 1.0 since this is indicative of a malicious request
				$this->protocol = 'HTTP/1.0';

				// 505 HTTP Version Not Supported
				$this->sc = 505;
			}

			//AA_G404D && ISCLOG::epx( $_SERVER, get_object_vars( $this ) );

			// description of status code
			$this->reason = get_status_header_desc( $this->sc );

			// requested uri
			$this->uri = esc_attr( stripslashes( $_SERVER['REQUEST_URI'] ) );


			// request_method or UNKNOWN
			if ( in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'PUT', 'HEAD', 'POST', 'OPTIONS', 'TRACE' ), true ) ) {
				$this->req_method = $_SERVER['REQUEST_METHOD'];
			}


			// set error message
			if ( ! in_array( $this->sc, array( 402, 409, 425, 500, 505 ), true ) ) {
				$asc = array(
					400 => 'Your browser sent a request that this server could not understand.',
					401 => 'This server could not verify that you are authorized to access the document requested.',
					403 => 'You don\'t have permission to access %U% on this server.',
					404 => 'We couldn\'t find <abbr title="%U%">that uri</abbr> on our server, though it\'s most certainly not your fault.',
					405 => 'The requested method %M% is not allowed for the URL %U%.',
					406 => 'An appropriate representation of the requested resource %U% could not be found on this server.',
					407 => 'An appropriate representation of the requested resource %U% could not be found on this server.',
					408 => 'Server timeout waiting for the HTTP request from the client.',
					410 => 'The requested resource %U% is no longer available on this server and there is no forwarding address. Please remove all references to this resource.',
					411 => 'A request of the requested method GET requires a valid Content-length.',
					412 => 'The precondition on the request for the URL %U% evaluated to false.',
					413 => 'The requested resource %U% does not allow request data with GET requests, or the amount of data provided in the request exceeds the capacity limit.',
					414 => 'The requested URL\'s length exceeds the capacity limit for this server.',
					415 => 'The supplied request data is not in a format acceptable for processing by this resource.',
					416 => 'Requested Range Not Satisfiable',
					417 => 'The expectation given in the Expect request-header field could not be met by this server. The client sent <code>Expect:</code>',
					422 => 'The server understands the media type of the request entity, but was unable to process the contained instructions.',
					423 => 'The requested resource is currently locked. The lock must be released or proper identification given before the method can be applied.',
					424 => 'The method could not be performed on the resource because the requested action depended on another action and that other action failed.',
					426 => 'The requested resource can only be retrieved using SSL. Either upgrade your client, or try requesting the page using https://',
					501 => '%M% to %U% not supported.',
					502 => 'The proxy server received an invalid response from an upstream server.',
					503 => 'The server is temporarily unable to service your request due to maintenance downtime or capacity problems. Please try again later.',
					504 => 'The proxy server did not receive a timely response from the upstream server.',
					506 => 'A variant for the requested resource <code>%U%</code> is itself a negotiable resource. This indicates a configuration error.',
					507 => 'The method could not be performed.	There is insufficient free space left in your storage allocation.',
					510 => 'A mandatory extension policy in the request is not accepted by the server for this resource.',
				);

				$this->msg = ( array_key_exists( $this->sc, $asc ) ? str_replace( array( '%U%', '%M%' ), array( $this->uri, $this->req_method ), $asc[ $this->sc ] ) : 'Error' );

				unset( $asc );
			}


			// send headers
			@header( "{$this->protocol} {$this->sc} {$this->reason}", 1, $this->sc );
			@header( "Status: {$this->sc} {$this->reason}", 1, $this->sc );

			// Always close connections
			@header( 'Connection: close', 1 );

			if ( in_array( $this->sc, array( 400, 403, 405 ), true ) || $this->sc > 499 ) {

				// Method Not Allowed
				if ( $this->sc === 405 ) {
					@header( 'Allow: GET,HEAD,POST,OPTIONS,TRACE', 1, 405 );
				}


				echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>" . esc_html( $this->sc . ' ' . $this->reason ) . "</title>\n</head>\n";
				echo "<body>\n<h1>" . esc_html( $this->reason ) . "</h1>\n<p>" . esc_html( $this->msg ) . "<br />\n</p>\n</body></html>";


				// die here and now, skip loading template
				AA_G404D_F && ISCLOG::ti();
				die();
			}


			AA_G404D_F && ISCLOG::ti();
		}


		/**
		 * Output the html
		 * @return void
		 */
		function output() {
			AA_G404D_F && ISCLOG::ti();

			global $AA_G404;
			if ( ! is_object( $AA_G404 ) ) {
				AA_G404D && ISCLOG::tw( 'AA_G404 NOT AN OBJECT' );
				$AA_G404 = aa_g404_get_object();
			}

			// load code
			$AA_G404->load_code();

			// if aa_google_404 function called from within template but plugin not enabled, ditch
			if ( '1' !== $AA_G404->options['enabled'] ) {
				AA_G404D_F && ISCLOG::ti();
				return '';
			}


			$host = WP_SITEURL . '/';
			$pu = parse_url( $host );
			$host = $pu['host'];


			$google = '<gcse:searchbox enableHistory="true" image_as_sitesearch="' . $host . '" as_sitesearch="' . $host . '" autoCompleteMaxCompletions="50" autoCompleteMatchType="any"
			autoSearchOnLoad="false"></gcse:searchbox><gcse:searchresults autoSearchOnLoad="false" imageSearchResultSetSize="large" webSearchResultSetSize="filtered_cse"></gcse:searchresults>';

			$recent = ( ( $AA_G404->options['recent_posts'] === '1' ) ? '<ul>' . wp_get_archives( array( 'echo' => false, 'type' => 'postbypost', 'limit' => absint( $AA_G404->options['recent_num'] ) ) ) . '</ul>' : '' );


			$tag_cloud = ( ( $AA_G404->options['tag_cloud'] === '1' ) ? '<p>' . wp_tag_cloud( array( 'echo' => false ) ) . '</p>' : '' );

			$sr = array(
				  '%error_title%' => $this->sc . ' ' . $this->reason,
				 '%recent_posts%' => $recent,
					   '%google%' => $google,
					'%tag_cloud%' => $tag_cloud,
			);

			echo str_replace( array_keys( $sr ), array_values( $sr ), $AA_G404->code['html'] );

			AA_G404D_F && ISCLOG::ti();
		}




		/**
		 * Output code in the header
		 * @return void
		 */
		function output_head() {
			AA_G404D_F && ISCLOG::ti();

			global $AA_G404;
			if ( ! is_object( $AA_G404 ) ) {
				AA_G404D && ISCLOG::tw( 'AA_G404 NOT AN OBJECT' );
				$AA_G404 = aa_g404_get_object();
			}

			if ( $AA_G404->options['analytics_log'] === '1' ) : ?>

				<script type="text/javascript">
				/* <![CDATA[ */
					var _gaq=_gaq||[];_gaq.push(['_setAccount','<?php echo preg_replace( '/[^A-Z0-9\-]*/', '', $AA_G404->options['analytics_key'] );?>']);
					_gaq.push(['_trackPageview', <?php echo $AA_G404->options['analytics_url'];?>]);
					(function(){var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;
					ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';
					var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);})();
				/* ]]> */
				</script>

				<script type="text/javascript">
				/* <![CDATA[ */
				(function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date;a=s.createElement(o),m=s.getElementsByTagName(o)[0];
				a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,"script","//www.google-analytics.com/analytics.js","ga");
				ga("create",'<?php echo preg_replace( '/[^A-Z0-9\-]*/', '', $AA_G404->options['analytics_key'] );?>',"auto");
				ga("send","pageview", <?php echo $AA_G404->options['analytics_url']; ?>);
				/* ]]> */
				</script>

			<?php endif; ?>


			<!-- Google 404 Plugin by www.AskApache.com -->
			<?php

			$pu = parse_url( WP_SITEURL );
			$host = $pu['host'];

			// robots meta
			if ( $AA_G404->options['robots_meta'] === '1' ) {
				echo '<meta name="robots" content="' . esc_attr( $AA_G404->options['robots_tag'] ) . '" />' . "\n";
			}

			?>
			<style type="text/css">
			<?php
			echo preg_replace(
				array( '/\0+/', '/(\\\\0)+/', '/\s\s+/', "/(\r\n|\n|\r)/", '/\/\*(.*?)\*\//', '/(:|,|;) /', '# +{#', '#{ +#', '#} +#', '# +}#', '#;}#', '#,+#', '# +,#' ),
				array( '', '', ' ',"\n",'',"\\1",'{', '{', '}', '}', '}', ',', ',' ),
				$AA_G404->code['css']
			);
			?>
			</style>


			<script type="text/javascript">
			var aa_js_debug = true;
			var aa_XX = askapache_get_aa_XX();

			function askapache_get_aa_XX() {
				console.debug( '!!! EXEC askapache_get_aa_XX()');

				function get_html_translation_table(e,f){var a={},g={},c,b={},d={};b[0]="HTML_SPECIALCHARS";b[1]="HTML_ENTITIES";d[0]="ENT_NOQUOTES";d[2]="ENT_COMPAT";d[3]="ENT_QUOTES";b=isNaN(e)?e?e.toUpperCase():"HTML_SPECIALCHARS":b[e];d=isNaN(f)?f?f.toUpperCase():"ENT_COMPAT":d[f];if("HTML_SPECIALCHARS"!==b&&"HTML_ENTITIES"!==b)throw Error("Table: "+b+" not supported");a["38"]="&amp;";"HTML_ENTITIES"===b&&(a["160"]="&nbsp;",a["161"]="&iexcl;",a["162"]="&cent;",a["163"]="&pound;",a["164"]="&curren;",a["165"]=
				"&yen;",a["166"]="&brvbar;",a["167"]="&sect;",a["168"]="&uml;",a["169"]="&copy;",a["170"]="&ordf;",a["171"]="&laquo;",a["172"]="&not;",a["173"]="&shy;",a["174"]="&reg;",a["175"]="&macr;",a["176"]="&deg;",a["177"]="&plusmn;",a["178"]="&sup2;",a["179"]="&sup3;",a["180"]="&acute;",a["181"]="&micro;",a["182"]="&para;",a["183"]="&middot;",a["184"]="&cedil;",a["185"]="&sup1;",a["186"]="&ordm;",a["187"]="&raquo;",a["188"]="&frac14;",a["189"]="&frac12;",a["190"]="&frac34;",a["191"]="&iquest;",a["192"]="&Agrave;",
				a["193"]="&Aacute;",a["194"]="&Acirc;",a["195"]="&Atilde;",a["196"]="&Auml;",a["197"]="&Aring;",a["198"]="&AElig;",a["199"]="&Ccedil;",a["200"]="&Egrave;",a["201"]="&Eacute;",a["202"]="&Ecirc;",a["203"]="&Euml;",a["204"]="&Igrave;",a["205"]="&Iacute;",a["206"]="&Icirc;",a["207"]="&Iuml;",a["208"]="&ETH;",a["209"]="&Ntilde;",a["210"]="&Ograve;",a["211"]="&Oacute;",a["212"]="&Ocirc;",a["213"]="&Otilde;",a["214"]="&Ouml;",a["215"]="&times;",a["216"]="&Oslash;",a["217"]="&Ugrave;",a["218"]="&Uacute;",
				a["219"]="&Ucirc;",a["220"]="&Uuml;",a["221"]="&Yacute;",a["222"]="&THORN;",a["223"]="&szlig;",a["224"]="&agrave;",a["225"]="&aacute;",a["226"]="&acirc;",a["227"]="&atilde;",a["228"]="&auml;",a["229"]="&aring;",a["230"]="&aelig;",a["231"]="&ccedil;",a["232"]="&egrave;",a["233"]="&eacute;",a["234"]="&ecirc;",a["235"]="&euml;",a["236"]="&igrave;",a["237"]="&iacute;",a["238"]="&icirc;",a["239"]="&iuml;",a["240"]="&eth;",a["241"]="&ntilde;",a["242"]="&ograve;",a["243"]="&oacute;",a["244"]="&ocirc;",a["245"]=
				"&otilde;",a["246"]="&ouml;",a["247"]="&divide;",a["248"]="&oslash;",a["249"]="&ugrave;",a["250"]="&uacute;",a["251"]="&ucirc;",a["252"]="&uuml;",a["253"]="&yacute;",a["254"]="&thorn;",a["255"]="&yuml;");"ENT_NOQUOTES"!==d&&(a["34"]="&quot;");"ENT_QUOTES"===d&&(a["39"]="&#39;");a["60"]="&lt;";a["62"]="&gt;";for(c in a)a.hasOwnProperty(c)&&(g[String.fromCharCode(c)]=a[c]);return g}

				function html_entity_decode(e,f){var a,g="",c,b;c=e.toString();if(!1===(a=get_html_translation_table("HTML_ENTITIES",f)))return!1;delete a["&"];a["&"]="&amp;";for(g in a)b=a[g],c=c.split(b).join(g);return c=c.split("&#039;").join("'")};

				// make an array contain unique values */
				function aa_array_unique(arr) {
					var a = [];
					for (var i = 0, l = arr.length; i < l; i++) {
						if (a.indexOf(arr[i]) === -1 && arr[i] !== "") {
							a.push(arr[i]);
						}
					}
					return a;
				};

				// strip_tags from phpjs
				function aa_strip_tags(input, allowed) {
					allowed = (((allowed || '') + '')
							.toLowerCase()
							.match(/<[a-z][a-z0-9]*>/g) || [])
							.join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)

					var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
					commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;


					return input.replace(commentsAndPhpTags, '')
						.replace(tags, function($0, $1) {
							return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
						});
				}



				/* array of words to not include in search query, if found remove them */
				var stopwords = ['able', 'aint', 'also', 'amid', 'away', 'back', 'been', 'best', 'bill', 'both', 'call', 'came', 'cant', 'care', 'cmon', 'come', 'dare',
					'does', 'done', 'dont', 'down', 'each', 'else', 'even', 'ever', 'fify', 'fill', 'find', 'fire', 'five', 'four', 'from', 'full', 'gets',
					'give', 'goes', 'gone', 'half', 'have', 'hell', 'help', 'here', 'hers', 'into', 'isnt', 'itll', 'july', 'june', 'just', 'keep', 'kept',
					'know', 'last', 'less', 'lest', 'lets', 'like', 'list', 'look', 'made', 'make', 'many', 'mean', 'mill', 'mine', 'miss', 'more', 'most',
					'move', 'much', 'must', 'name', 'near', 'need', 'next', 'nine', 'none', 'okay', 'once', 'ones', 'only', 'onto', 'ours', 'over', 'part',
					'past', 'plus', 'said', 'same', 'says', 'seem', 'seen', 'self', 'sent', 'shed', 'shes', 'show', 'side', 'some', 'soon', 'such', 'sure',
					'take', 'tell', 'than', 'that', 'the', 'them', 'then', 'they', 'thin', 'this', 'thru', 'thus', 'till', 'took', 'tool', 'unto', 'upon',
					'used', 'uses', 'very', 'want', 'well', 'went', 'were', 'weve', 'what', 'when', 'whod', 'whom', 'whos', 'will', 'wish', 'with', 'wont',
					'youd', 'your', 'zero', 'about', 'above', 'abuse', 'acute', 'after', 'again', 'ahead', 'allow', 'alone', 'along', 'among', 'andor',
					'apart', 'apply', 'april', 'arent', 'aside', 'await', 'begin', 'being', 'below', 'brief', 'cause', 'coder', 'comes', 'could', 'crohn',
					'didnt', 'doing', 'eight', 'empty', 'every', 'fewer', 'fifth', 'first', 'forth', 'forty', 'found', 'front', 'given', 'gives', 'going',
					'guide', 'hadnt', 'hasnt', 'hello', 'hence', 'heres', 'iiiii', 'iilci', 'inner', 'issue', 'keeps', 'known', 'knows', 'later', 'least',
					'liked', 'lilll', 'looks', 'lower', 'makes', 'march', 'match', 'maybe', 'maynt', 'media', 'might', 'minus', 'needs', 'never', 'noone',
					'novel', 'obstr', 'often', 'other', 'ought', 'quite', 'ready', 'right', 'round', 'seems', 'seven', 'shall', 'shant', 'shell', 'since',
					'sixty', 'sorry', 'still', 'suite', 'taken', 'tends', 'thank', 'thanx', 'thats', 'their', 'there', 'these', 'theyd', 'thing', 'think',
					'third', 'those', 'three', 'tried', 'tries', 'truly', 'twice', 'under', 'until', 'using', 'value', 'wants', 'wasnt', 'whats', 'where',
					'which', 'while', 'whole', 'wholl', 'whose', 'would', 'wound', 'youll', 'youre', 'yours', 'youve', 'abroad', 'across', 'allows',
					'almost', 'always', 'amidst', 'amount', 'and/or', 'answer', 'anyhow', 'anyone', 'anyway', 'appear', 'around', 'asking', 'august',
					'became', 'become', 'before', 'behind', 'beside', 'better', 'beyond', 'bottom', 'cannot', 'causes', 'center', 'circle', 'closed',
					'coding', 'coming', 'course', 'darent', 'detail', 'doesnt', 'during', 'eighty', 'either', 'eleven', 'ending', 'enough', 'except',
					'fairly', 'former', 'gotten', 'hardly', 'havent', 'having', 'hereby', 'herein', 'hither', 'iiiiii', 'indeed', 'insfcy', 'inside',
					'inward', 'itself', 'lately', 'latter', 'likely', 'little', 'mainly', 'merely', 'mostly', 'mustnt', 'myself', 'namely', 'nearly',
					'neednt', 'neverf', 'ninety', 'no-one', 'nobody', 'others', 'placed', 'please', 'rather', 'really', 'recent', 'saying', 'second',
					'seeing', 'seemed', 'selves', 'should', 'source', 'stated', 'system', 'taking', 'thanks', 'thatll', 'thatve', 'theirs', 'thence',
					'thered', 'theres', 'theyll', 'theyre', 'theyve', 'thickv', 'things', 'thirty', 'though', 'toward', 'trying', 'twelve', 'twenty',
					'unless', 'unlike', 'unspec', 'useful', 'versus', 'werent', 'whatll', 'whatve', 'whence', 'wheres', 'whilst', 'within', 'wonder',
					'address', 'advisor', 'against', 'already', 'amongst', 'another', 'answers', 'anybody', 'anyways', 'awfully', 'because', 'becomes',
					'believe', 'besides', 'between', 'caption', 'certain', 'changes', 'chronic', 'clearly', 'contact', 'contain', 'contexo', 'couldnt',
					'despite', 'exactly', 'example', 'failure', 'farther', 'fifteen', 'follows', 'forever', 'fortune', 'forward', 'further', 'general',
					'getting', 'happens', 'herself', 'himself', 'howbeit', 'however', 'hundred', 'ignored', 'iiiiiii', 'insofar', 'instead', 'january',
					'looking', 'mightnt', 'neither', 'nothing', 'nowhere', 'october', 'oughtnt', 'outside', 'overall', 'perhaps', 'regards', 'seeming',
					'serious', 'several', 'sincere', 'someday', 'somehow', 'someone', 'specify', 'thereby', 'therein', 'therell', 'therere', 'thereve',
					'through', 'towards', 'undoing', 'upwards', 'usually', 'various', 'version', 'welcome', 'whereas', 'whereby', 'wherein', 'whether',
					'whither', 'whoever', 'willing', 'without', 'wouldnt', 'actually', 'addition', 'although', 'amoungst', 'anything', 'anywhere',
					'appendix', 'backward', 'becoming', 'consider', 'contains', 'creation', 'december', 'describe', 'directly', 'director', 'entirely',
					'evermore', 'everyone', 'february', 'fletcher', 'followed', 'formerly', 'hereupon', 'iiiiiiii', 'inasmuch', 'indicate', 'interest',
					'latterly', 'learning', 'likewise', 'meantime', 'moreover', 'normally', 'november', 'opposite', 'possible', 'probably', 'provided',
					'provides', 'recently', 'secondly', 'sensible', 'shouldnt', 'somebody', 'sometime', 'somewhat', 'thorough', 'together', 'training',
					'unlikely', 'whatever', 'whenever', 'wherever', 'whomever', 'yourself', 'according', 'alongside', 'ascending', 'available',
					'backwards', 'certainly', 'copyright', 'currently', 'described', 'diagnosis', 'different', 'downwards', 'elsewhere', 'essential',
					'everybody', 'following', 'greetings', 'hereafter', 'hopefully', 'immediate', 'indicated', 'indicates', 'intrinsic', 'meanwhile',
					'necessary', 'neverless', 'obviously', 'otherwise', 'ourselves', 'recording', 'reference', 'regarding', 'seriously', 'something',
					'sometimes', 'somewhere', 'specified', 'therefore', 'thereupon', 'whereupon', 'whichever','gsc','tab'];

				var host = document.location.host;

				var v = document.location.href;
				v = v.replace(document.location.protocol + '//' + document.location.host, '');
				v = v.replace( /%7C/gi, ' ' );
				aa_js_debug && console.debug('v', v);


				var v1 = aa_strip_tags(v);
				aa_js_debug && console.debug('v1', v1);

				var v2 = html_entity_decode(v1);
				aa_js_debug && console.debug('v2', v2);

				var v3 = v2.toLowerCase();
				aa_js_debug && console.debug('v3', v3);

				// [\u0400-\u04FF]+
				/* match alphanum of len 3-25 and special utf chars as well that arent covered by \w and use /g flag with .match to output found results into v4 array or matches */
				var v4 = v3.match(/[\w]{3,25}|[^\u0000-\u007E]{3,25}/g);
				aa_js_debug && console.debug('v4', v4);

				var v5 = aa_array_unique(v4);
				aa_js_debug && console.debug('v5', v5);

				/* gw will contain array of only words found from location.href that aren't found in stopwords array */
				var gw = [];
				for (var i = 0; i < v5.length; i++) {
					if (stopwords.indexOf(v5[i]) == -1) {
						gw.push(v5[i]);
					}
				}
				aa_js_debug && console.debug('gw', gw);

				/* if foundwords (fl) > 6, then only use the first 6 words from gw */
				var f1 = gw;
				if (gw.length > 6) {
					f1 = gw.splice(0, 6);
				}
				aa_js_debug && console.debug('f1', f1);

				/* aa_XX used by plugin as the search query, this joins the max6 words from f1 (found list) with the google special '"' */
				var aa_XX = f1.join('|');
				aa_js_debug && console.debug('aa_XX', aa_XX);

				return aa_XX;
			}


			window.__gcse = {
				callback: askapache_load_search
			};

			function askapache_load_search() {
				google.setOnLoadCallback(function() {
					var element = google.search.cse.element.getElement('two-column');
					element.execute(aa_XX);
				});
			}

			(function() {
				var cx = '<?php echo $AA_G404->options['cse_id']; ?>';
				var gcse = document.createElement('script');
				gcse.type = 'text/javascript';
				gcse.async = true;
				gcse.src = 'https://cse.google.com/cse.js?cx=' + cx;
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(gcse, s);
			})();
			</script>
			<script src="//www.google.com/jsapi" type="text/javascript"></script>
			<!-- Google 404 Plugin by www.AskApache.com -->
			<?php

			AA_G404D_F && ISCLOG::ti();
		}


	}

endif; // AA_G404 CLASS EXISTS





if ( ! function_exists( 'aa_google_404' ) ) :


	/**
	 * Singleton return of AA_G404
	 *
	 * @return object    AA_G404 object
	 */
	function aa_g404_get_object() {
		AA_G404D_F && ISCLOG::ti();

		static $aa_google_404_object = null;
		if ( null === $aa_google_404_object ) {
			$aa_google_404_object = new AA_G404();
			$GLOBALS['AA_G404'] =& $aa_google_404_object;
		}

		AA_G404D_F && ISCLOG::ti();

		return $aa_google_404_object;
	}


	/**
	 * Singleton return of AA_G404_Handler
	 *
	 * @return object    AA_G404_Handler object
	 */
	function aa_g404_get_handler_object() {
		AA_G404D_F && ISCLOG::ti();

		static $aa_google_404_handler_object = null;
		if ( null === $aa_google_404_handler_object ) {
			$aa_google_404_handler_object = new AA_G404_Handler();
			$GLOBALS['AA_G404_Handler'] =& $aa_google_404_handler_object;
		}

		AA_G404D_F && ISCLOG::ti();

		return $aa_google_404_handler_object;
	}




	/**
	 * Displays generated 404 content
	 *
	 * @return void
	 */
	function aa_google_404() {
		AA_G404D_F && ISCLOG::ti();

		global $AA_G404_Handler;
		if ( ! is_object( $AA_G404_Handler ) ) {
			AA_G404D && ISCLOG::tw( 'AA_G404_Handler NOT AN OBJECT' );
			$AA_G404_Handler = aa_g404_get_handler_object();
		}

		$AA_G404_Handler->output();

		AA_G404D_F && ISCLOG::ti();
	}




	/**
	 * A super efficient way to add the AA_G404->init() function to wordpress actions on init.
	 *
	 * @return void
	 */
	function aa_g404_init() {
		AA_G404D_F && ISCLOG::ti();

		global $AA_G404;
		if ( ! is_object( $AA_G404 ) ) {
			AA_G404D && ISCLOG::tw( 'AA_G404 NOT AN OBJECT' );
			$AA_G404 = aa_g404_get_object();
		}

		$AA_G404->init();

		AA_G404D_F && ISCLOG::ti();
	}
	add_action( 'init', 'aa_g404_init', 0 );

endif; // ! function_exists( 'aa_google_404' )










if ( is_admin() ) :


	/**
	 * Run on Activation
	 *
	 * @return void
	 */
	function aa_g404_activate() {
		AA_G404D_F && ISCLOG::ti();

		global $AA_G404;
		if ( ! is_object( $AA_G404 ) ) {
			AA_G404D && ISCLOG::tw( 'AA_G404 NOT AN OBJECT' );
			$AA_G404 = aa_g404_get_object();
		}

		$AA_G404->upgrade_settings();

		AA_G404D_F && ISCLOG::ti();
	}
	register_activation_hook( __FILE__, 'aa_g404_activate' );



	/**
	 * Deactivate
	 *
	 * @return void
	 */
	function aa_g404_deactivate() {
		AA_G404D_F && ISCLOG::ti();

		// delete plugin option
		delete_option( 'askapache_google_404_plugin' );

		AA_G404D_F && ISCLOG::ti();
	}
	register_deactivation_hook( __FILE__, 'aa_g404_deactivate' );



	/**
	 * Uninstallation
	 *
	 * @return void
	 */
	function aa_g404_uninstall() {
		AA_G404D_F && ISCLOG::ti();

		// delete options
		delete_option( 'askapache_google_404_plugin' );
		delete_option( 'askapache_google_404_code' );
		delete_option( 'askapache_google_404_options' );
		delete_option( 'askapache_google_404_orig_code' );
		delete_option( 'askapache_google_404_iframe_one_time' );
		delete_option( 'aa_google_404_api_key' );
		delete_option( 'aa_google_404_cse_id' );
		delete_option( 'aa_google_404_analytics_key' );

		AA_G404D_F && ISCLOG::ti();
	}
	register_uninstall_hook( __FILE__, 'aa_g404_uninstall' );




	/**
	 * Add options link to plugin listing in backend
	 *
	 * @return void
	 */
	function aa_g404_plugin_action_links( $l ) {
		return array_merge( array( '<a href="options-general.php?page=askapache-google-404.php">Settings</a>' ), $l );
	}
	add_filter( 'plugin_action_links_askapache-google-404/askapache-google-404.php', 'aa_g404_plugin_action_links' );



	/**
	 * JS to add to plugin-specific footer
	 *
	 * @return void
	 */
	function aa_g404_admin_footer_settings_page() {
		AA_G404D_F && ISCLOG::ti();

		?>
		<script type="text/javascript">
		document.getElementById('ag4_tabs_ul').style.display = 'block';
		jQuery("#ag4_form").submit(function(){
			if ( jQuery("#ag4_html_cp").length ) {
				jQuery("#ag4_html_cp").val(ag4_html.getCode()).removeAttr("disabled");
			}

			if ( jQuery("#ag4_css_cp").length ) {
				jQuery("#ag4_css_cp").val(ag4_css.getCode()).removeAttr("disabled");
			}
		});
		</script>
		<?php

		AA_G404D_F && ISCLOG::ti();
	}
	add_action( 'admin_footer-settings_page_askapache-google-404', 'aa_g404_admin_footer_settings_page' );

endif;




// EOF
