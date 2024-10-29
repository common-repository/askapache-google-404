<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 *
 * created by AskApache.com for https://www.askapache.com/seo/404-google-wordpress-plugin/
 */

ob_start();

get_header();

if ( function_exists( 'aa_google_404' ) ) {
	aa_google_404();
}

get_sidebar();


get_footer();


die();


// EOF
