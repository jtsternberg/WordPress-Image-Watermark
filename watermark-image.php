<?php
/**
 * Create a watermarked image via WordPress
 *
 * @author  Justin Sternberg <justin@dsgnwrks.pro>
 * @package WatermarkImage
 * @version 0.1.0
 */

// Autoload helper classes
spl_autoload_register('Watermark_Image_Setup::autoload_helpers');
// for PHP versions < 5.3
if ( !defined( '__DIR__' ) ) {
	define( '__DIR__', dirname( __FILE__ ) );
}

class Watermark_Image_Setup {

	// A single instance of this class.
	public static $instance    = null;
	public static $meets_rqmts = null;

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return Watermark_Image_Setup A single instance of this class.
	 */
	public static function get() {
		if ( self::$instance === null )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Setup our plugin
	 * @since 0.1.0
	 */
	public function __construct() {

		// Requirements check
		add_action( 'all_admin_notices', array( $this, 'rqmts_check' ) );

		// If we didn't pass our requirments, we're out of luck
		if ( self::requirements() !== true )
			return;

		// add our supplemented image editor class
		add_filter( 'wp_image_editors', array( $this, 'add_image_editor' )  );
	}

	/**
	 * Check for Imagick extension & other requirements and show message if not met
	 * @since  0.1.0
	 */
	public function rqmts_check(){
		if ( self::requirements() === true )
			return;

		if ( self::requirements() == '3.5' )
			$msg = __( '"WordPress Image Watermark" requires WordPress 3.5 - Please update in order to use.' );
		else
			$msg = sprintf( __( '%s not found - Please install in order to use "WordPress Image Watermark".' ), self::requirements() );

		error_log( $msg );
		echo '<div id="message" class="error"><p>'. $msg .'</p></div>';
	}

	/**
	 * Add our supplemented image editor class
	 * @since 0.1.0
	 * @param array  $editors Available image editors to WP
	 */
	public function add_image_editor( $editors ) {

		if ( ! in_array( 'WP_Image_Editor_Imagick_Watermark', $editors ) )
			array_unshift( $editors, 'WP_Image_Editor_Imagick_Watermark' );

		return $editors;
	}

	/**
	 * Autoloads files with classes when needed
	 * @since  0.1.0
	 * @param  string $class_name Name of the class being requested
	 */
	public static function autoload_helpers( $class_name ) {
		if ( class_exists( $class_name, false ) )
			return;

		$file = __DIR__ .'/lib/classes/'. $class_name .'.php';
		if ( file_exists( $file ) )
			require_once( $file );
	}

	/**
	 * Checks if minimum requirements are met
	 * @since  0.1.0
	 * @return boolean True/False
	 */
	public static function requirements() {
		if ( self::$meets_rqmts !== null )
			return self::$meets_rqmts;

		self::$meets_rqmts = ! extension_loaded( 'imagick' ) ? 'Imagick PHP extension' : true;
		self::$meets_rqmts = ! class_exists( 'Imagick' ) ? 'Imagick class' : true;
		self::$meets_rqmts = ! class_exists( 'ImagickDraw' ) ? 'ImagickDraw class' : true;
		self::$meets_rqmts = ! version_compare( get_bloginfo( 'version' ), '3.5' ) ? '3.5' : true;

		return self::$meets_rqmts;
	}
}
Watermark_Image_Setup::get();

return;
/**
 * To use this library:
 */

// usage
Watermark_Image_Generator::generate_watermark( $text, $img_url );

// Filter defaults
add_filter( 'watermark_image_filter_defaults', array( $this, 'watermark_defaults' ), 10, 2 );
/**
 * Update watermark setting defaults
 * @since  0.1.0
 * @param  array  $defaults Arguments to modify the defaults
 * @param  array  $vars     Array of variables including size, image path etc
 * @return array            Modified default arguments
 */
public function watermark_defaults( $defaults, $vars ) {
	$defaults['top_offset'] = absint( $vars['size']['height'] - ( $vars['size']['height'] * .13 ) );
	$defaults['text'] = strtoupper( sanitize_title( $vars['text'] ) );
	return $defaults;
}
