<?php

/*
Plugin Name: get_url_from_file Example
Description: This plugin shows the code Chris Jean uses to convert URLs to file paths and file paths to URLs in WordPress.
Version: 1.0.0
Author: Chris Jean
Author URI: http://chrisjean.com/
Date: 2013-05-31
*/

class CJ_E_File_Lib {
	public static function get_url_from_file( $file, $auto_ssl = true, $prevent_recursion = false ) {
		$file = str_replace( '\\', '/', $file );

		$url = '';

		$upload_dir = CJ_E_File_Lib::get_cached_value( 'wp_upload_dir' );

		if ( is_array( $upload_dir ) && ( false === $upload_dir['error'] ) ) {
			if ( 0 === strpos( $file, $upload_dir['basedir'] ) )
				$url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file );
			else if ( false !== strpos( $file, 'wp-content/uploads' ) )
				$url = $upload_dir['baseurl'] . substr( $file, strpos( $file, 'wp-content/uploads' ) + 18 );
		}

		if ( empty( $url ) ) {
			if ( ! isset( $GLOBALS['it_classes_cache_wp_content_dir'] ) )
				$GLOBALS['it_classes_cache_wp_content_dir'] = rtrim( str_replace( '\\', '/', WP_CONTENT_DIR ), '/' );
			if ( ! isset( $GLOBALS['it_classes_cache_abspath'] ) )
				$GLOBALS['it_classes_cache_abspath'] = rtrim( str_replace( '\\', '/', ABSPATH ), '/' );

			if ( 0 === strpos( $file, $GLOBALS['it_classes_cache_wp_content_dir'] ) )
				$url = WP_CONTENT_URL . str_replace( '\\', '/', preg_replace( '/^' . preg_quote( $GLOBALS['it_classes_cache_wp_content_dir'], '/' ) . '/', '', $file ) );
			else if ( 0 === strpos( $file, $GLOBALS['it_classes_cache_abspath'] ) )
				$url = get_option( 'siteurl' ) . str_replace( '\\', '/', preg_replace( '/^' . preg_quote( $GLOBALS['it_classes_cache_abspath'], '/' ) . '/', '', $file ) );
		}

		if ( empty( $url ) && ! $prevent_recursion )
			$url = CJ_E_File_Lib::get_url_from_file( realpath( $file ), $auto_ssl, true );

		if ( empty( $url ) )
			return '';


		if ( $auto_ssl )
			$url = CJ_E_File_Lib::fix_url( $url );

		return $url;
	}

	public static function get_file_from_url( $url ) {
		$url = preg_replace( '/^https/', 'http', $url );
		$url = preg_replace( '/\?.*$/', '', $url );

		$file = '';

		$upload_dir = CJ_E_File_Lib::get_cached_value( 'wp_upload_dir' );

		if ( is_array( $upload_dir ) && ( false === $upload_dir['error'] ) ) {
			if ( 0 === strpos( $url, $upload_dir['baseurl'] ) )
				$file = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
			else if ( false !== strpos( $url, 'wp-content/uploads' ) )
				$file = $upload_dir['basedir'] . substr( $url, strpos( $url, 'wp-content/uploads' ) + 18 );
		}

		if ( empty( $file ) ) {
			if ( ! isset( $GLOBALS['it_classes_cache_wp_content_url'] ) )
				$GLOBALS['it_classes_cache_wp_content_url'] = preg_replace( '/^https/', 'http', WP_CONTENT_URL );
			if ( ! isset( $GLOBALS['it_classes_cache_siteurl'] ) )
				$GLOBALS['it_classes_cache_siteurl'] = preg_replace( '/^https/', 'http', get_option( 'siteurl' ) );

			if ( 0 === strpos( $url, $GLOBALS['it_classes_cache_wp_content_url'] ) )
				$file = rtrim( WP_CONTENT_DIR, '\\\/' ) . preg_replace( '/^' . preg_quote( $GLOBALS['it_classes_cache_wp_content_url'], '/' ) . '/', '', $url );
			else if ( 0 === strpos( $url, $GLOBALS['it_classes_cache_siteurl'] ) )
				$file = rtrim( ABSPATH, '\\\/' ) . preg_replace( '/^' . preg_quote( $GLOBALS['it_classes_cache_siteurl'], '/' ) . '/', '', $url );
		}

		return $file;
	}

	public static function get_cached_value( $function, $args = array() ) {
		if ( ! isset( $GLOBALS['it_classes_cached_values'] ) )
			$GLOBALS['it_classes_cached_values'] = array();

		$key = $function;

		if ( ! empty( $args ) )
			$key .= '-' . md5( serialize( $args ) );

		if ( ! isset( $GLOBALS['it_classes_cached_values'][$key] ) )
			$GLOBALS['it_classes_cached_values'][$key] = call_user_func_array( $function, $args );

		return $GLOBALS['it_classes_cached_values'][$key];
	}

	/* Automatically changes http protocols to https when is_ssl is true */
	public static function fix_url( $url ) {
		if ( ! is_ssl() )
			return $url;

		return preg_replace( '|^http://|', 'https://', $url );
	}
}

function cj_e_show_file_details( $description, $path, $echo = true ) {
	$url = CJ_E_File_Lib::get_url_from_file( $path );
	$file = CJ_E_File_Lib::get_file_from_url( $url );

	$output = "<p><strong>$description</strong></p>";
	$output .= "<pre>";
	$output .= "Original path: $path\n";
	$output .= "URL:           $url\n";
	$output .= "File:          $file";
	$output .= "</pre>\n";

	if ( $echo )
		echo $output;

	return $output;
}
