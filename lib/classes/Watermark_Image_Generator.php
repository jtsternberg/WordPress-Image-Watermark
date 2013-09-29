<?php
/**
 * Watermark Image Generator
 *
 * @author  Justin Sternberg <justin@dsgnwrks.pro>
 * @package WatermarkImage
 * @version 0.1.0
 */

class Watermark_Image_Generator {

	public static $imgobject;
	public static $imgpath;
	public static $post_id = 0;

	/**
	 * Generates a watermarked image and saves it to the WordPress media library
	 * @since  0.1.0
	 * @param  string $text     Text to add as a watermark
	 * @param  string $img_url  Url image source
	 * @param  int    $post_id  Post ID to save the attachment to
	 * @return array|WP_Error   Array with image information on success
	 */
	public static function generate_watermark( $text, $img_url, $post_id = 0 ) {

		// Create a copy of this image for manipulation
		$imgpath = self::copy_img( $img_url, $post_id )
		if ( ! $imgpath )
			return new WP_Error( 'watermark_gen_error', __( 'Could not create a copy of the image.', 'wds_water' ) );

		self::$post_id =& $post_id;
		self::$imgpath =& $imgpath;

		// WordPress Image Administration API
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/image-edit.php' );

		// Get Imagick instance
		$image = wp_get_image_editor( $imgpath, array( 'methods' => array( 'annotateImage' ) ) );
		if ( is_wp_error( $image ) )
			return $image;

		self::$imgobject =& $image;

		$image->load();
		$size = $image->get_size();

		// Default text placement and settings
		$filter_defaults = wp_parse_args( apply_filters( 'watermark_image_filter_defaults', array(), get_defined_vars(), $image ), array(
			'text_antialias' => true,
			'text_alignment' => 2, // centered
			'quality' => 85,
			'font_size' => 50,
			'left_offset' => ( $size['width'] / 2 ), // centered
			'top_offset' => ( $size['height'] / 2 ), // middle
		) );
		extract( $filter_defaults );

		$image->set_quality( $quality );
		// Create a drawing object and set the text size, alignment, and antialias settings
		$draw = new ImagickDraw();
		$draw->setFontSize( $font_size );
		$draw->setTextAntialias( $text_antialias );
		$draw->setTextAlignment( $text_alignment );

		// Combine our text/image
		$image->annotateImage( $draw, $left_offset, $top_offset, 0, sanitize_text_field( $text ) );

		// Clean up our ImagickDraw instance
		$draw->clear();
		$draw->destroy();

		// @debug If query var set, won't save but will display pending image in browser (if run before any headers are sent)
		if ( isset( $_REQUEST['debug_watermark'] ) ) {
			$image->stream();
			die();
		}

		// Ok, save it!
		return self::save_image();
	}

	/**
	 * Sideloads and saves an image to WordPress media library
	 * @since  0.1.0
	 * @return array|WP_Error  Array with image information on success
	 */
	public static function save_image() {

		// Temporarily save manipulated image adjacent to original image
		$saved = self::$imgobject->save( self::$imgpath );
		if ( is_wp_error( $saved ) )
			return $saved;

		// Cleanup our Imagick instance
		self::$imgobject->__destruct();

		// Save image as an attachment to the post ID (or just move image to media library)
		$img = self::image_to_attachment( $saved['path'] );

		// delete leftover duplicate
		unlink( self::$imgpath );

		return $img;
	}

	/**
	 * Moves image from within WordPress to the media library and attaches to a post.
	 * @since  0.1.0
	 * @param  string  $file         The local path to the image to move
	 * @param  string  $desc         Optional. Description of the image
	 * @param  boolean $markup       Whether to return image markup or image data
	 * @return string|array|WP_Error Populated HTML img tag or image data on success
	 */
	public static function image_to_attachment( $file, $desc = '', $markup = false ) {
		// if file is empty
		if ( empty( $file ) )
			return new WP_Error( 'watermark_gen_error', __( 'No image file found.', 'wds_water' ) );

		// Get our required WordPress files
		require_once( ABSPATH .'/wp-admin/includes/file.php' );
		require_once( ABSPATH .'/wp-admin/includes/media.php' );
		require_once( ABSPATH .'/wp-admin/includes/image.php' );

		$file = CJ_E_File_Lib::get_url_from_file( $file );
		// Download file to temp location
		$tmp = download_url( $file );

		// fix filename containing query strings
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
		// Set variables for storage
		$file_array['name'] = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff

		// If we have a post ID, we'll save as an attachment to the post
		if ( self::$post_id ) {

			// move file to attachments & save to post
			$img_id = media_handle_sideload( $file_array, self::$post_id, $desc );
			// If error storing permanently, delete
			if ( is_wp_error( $img_id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $img_id;
			}

			$src = wp_get_attachment_url( $img_id );

			if ( empty( $src ) )
				return new WP_Error( 'watermark_gen_error', __( 'No image source found.', 'wds_water' ) );

			// Return our requested data
			return $markup ? '<img src="'. $src .'" alt="'. esc_attr( $desc ) .'" />' : compact( 'img_id', 'src' );
		}
		// If no post ID, we'll just save the file to the media library
		else {

			// move file to attachments
			$file = wp_handle_sideload( $file_array, array( 'test_form' => false ), current_time( 'mysql' ) );

			if ( isset( $file['error'] ) )
				return new WP_Error( 'upload_error', $file['error'] );

			if ( !isset( $file['url'] ) || empty( $file['url'] ) )
				return new WP_Error( 'watermark_gen_error', __( 'No image source found.', 'wds_water' ) );

			// Return our requested data
			return $markup ? '<img src="'. $file['url'] .'" alt="'. esc_attr( $desc ) .'" />' : compact( 'src' );
		}

	}

	/**
	 * Create a copy of an image. Image must be local
	 * @since  0.1.0
	 * @param  string  $img_url URL to image. Must be local.
	 * @param  integer $post_id Post ID
	 * @return string           Image copy's path
	 */
	public static function copy_img( $img_url, $post_id = 0 ) {
		$imgpath = $copy = CJ_E_File_Lib::get_file_from_url( $img_url );

		// Check if this file IS our post ID copy of image
		if ( false === strpos( $imgpath, 'ID'. $post_id ) ) {
			// If not, create our copy's filename
			$copy = str_replace( basename( $copy ), 'ID'. $post_id .'-'. basename( $copy ), $copy );
			// If no copy exists, create one for manipulation
			if ( ! file_exists( $copy ) ) {
				// broken copy? bail here
				if ( ! @copy( $imgpath, $copy ) )
					return;
			}
			// reset path to copy
			$imgpath = $copy;
		}

		return $imgpath;
	}
}
