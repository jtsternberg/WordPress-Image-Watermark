<?php
/**
 * Extends WordPress Imagick Image Editor
 *
 * @package WordPress
 * @subpackage Image_Editor
 * @subpackage Image_Editor_Imagick
 */

/**
 * WordPress Image Editor Class that extends the Image Manipulation capabilites provided by WP_Image_Editor_Imagick
 *
 * @since 1.0.0
 * @package WordPress
 * @subpackage Image_Editor_Imagick
 * @uses WP_Image_Editor_Imagick Extends class
 */

class WP_Image_Editor_Imagick_Watermark extends WP_Image_Editor_Imagick {

	public function compositeImage( $composite_object, $composite, $x, $y, $channel = '' ) {
		return $this->image->compositeImage( $composite_object, $composite, $x, $y, $channel );
	}

	public function annotateImage( $draw_settings, $x, $y, $angle, $text ) {
		return $this->image->annotateImage( $draw_settings, $x, $y, $angle, $text );
	}

	public function scaleImage( $cols, $rows, $bestfit = false ) {
		return $this->image->scaleImage( $cols, $rows, $bestfit );
	}

}
