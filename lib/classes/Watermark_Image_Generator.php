<?php
/**
 * Watermark Image Generator
 *
 * @author  Justin Sternberg <justin@dsgnwrks.pro>
 * @package WatermarkImage
 * @version 0.1.0
 */

/**
 * Still needs work
 */
class Watermark_Image_Generator {

	public static $imgobject;
	public static $imgpath;
	public static $post_id;

	/**
	 * Generates a watermarked image and saves it to the WordPress media library
	 * @since  0.1.0
	 * @param  string  $text    Text to add as a watermark
	 * @param  string  $img_url Url image source
	 * @return int              Attachment ID
	 */
	public static function generate_watermark( $text, $img_url ) {

		$imgpath = CJ_E_File_Lib::get_file_from_url( $img_url );
		$size = getimagesize( $imgpath );

		// WordPress Image Administration API
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/image-edit.php' );

		$image = wp_get_image_editor( $imgpath, array( 'methods' => array( 'annotateImage' ) ) );
		if ( is_wp_error( $image ) )
			return $image;


		self::$imgobject =& $image;
		self::$imgpath =& $imgpath;

		$image->load();
		$size = $image->get_size();

		$filter_defaults = wp_parse_args( apply_filters( 'watermark_image_filter_defaults', array(), get_defined_vars(), $image ), array(
			'text_antialias' => true,
			'text_alignment' => 2,
			'quality' => 85,
			'font_size' => 50,
			'left_offset' => ( $size['width'] / 2 ),
			'top_offset' => ( $size['height'] / 2 ),
		) );
		extract( $filter_defaults );

		$image->set_quality( $quality );
		// Create a drawing object and set the font size
		$draw = new ImagickDraw();
		$draw->setFontSize( $font_size );
		$draw->setTextAntialias( $text_antialias );
		$draw->setTextAlignment( $text_alignment );

		$image->annotateImage( $draw, $left_offset, $top_offset, 0, $text );

		// Clean up
		$draw->clear();
		$draw->destroy();

		$image->stream();
		die();

		return self::save_image();

	}

	public static function save_image() {

		// rename copy
		$newpath = str_replace( 'copy-', 'meme-', self::$imgpath );
		// save image
		$saved = self::$imgobject->save( $newpath );

		if ( is_wp_error( $saved ) )
			return $saved;

		// cleanup
		self::$imgobject->__destruct();

		$img = self::sideload_image( CJ_E_File_Lib::get_url_from_file( $saved['path'] ) );
		if ( is_wp_error( $img ) )
			return $img;

		// delete leftover duplicates
		unlink( self::$imgpath  );
		unlink( $newpath );

		// if ( !isset( $_POST['remote_request'] ) ) {
		// 	update_post_meta( self::$post_id, self::$prefix.'gen_image', $img[1] );
		// 	update_post_meta( self::$post_id, self::$prefix.'gen_image_id', $img[0] );
		// } else {
		// 	return $img[1];
		// }

		return array(
			'img_id' => $img[0],
			'src' => $img[1],
			'admin_markup' => '<img src="'. $img[1] .'" alt="" />',
		);
	}

	/**
	 * Download an image from the specified URL and attach it to a post.
	 *
	 * @since 2.6.0
	 *
	 * @param string $file The URL of the image to download
	 * @param string $desc Optional. Description of the image
	 * @return string|WP_Error Populated HTML img tag on success
	 */
	public static function sideload_image( $file, $desc = null, $markup = false ) {
		if ( ! empty($file) ) {

			require_once( ABSPATH .'/wp-admin/includes/file.php' );
			require_once( ABSPATH .'/wp-admin/includes/media.php' );
			require_once( ABSPATH .'/wp-admin/includes/image.php' );

			// Download file to temp location
			$tmp = download_url( $file );

			// Set variables for storage
			// fix file filename for query strings
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array['name'] = basename($matches[0]);
			$file_array['tmp_name'] = $tmp;

			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}

			// do the validation and storage stuff
			$img_id = media_handle_sideload( $file_array, self::$post_id, $desc );
			// If error storing permanently, unlink
			if ( is_wp_error($img_id) ) {
				@unlink($file_array['tmp_name']);
				return $img_id;
			}

			$src = wp_get_attachment_url( $img_id );
		}

		if ( !$markup )
			return array( $img_id, $src );
		// Finally check to make sure the file has been saved, then return the html
		if ( ! empty($src) ) {
			$alt = isset($desc) ? esc_attr($desc) : '';
			$html = "<img src='$src' alt='$alt' />";
			return $html;
		}
	}
}
