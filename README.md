WordPress Image Watermark
=========================

Uses WordPress and Imagick to overlay text on an image and save as a separate image. Needs testing. Feel free to use at your own risk.

### Sample Library Usage (example.php)

```php
<?php
/**
 * Sample Library Usage
 *
 * @author  Justin Sternberg <justin@dsgnwrks.pro>
 * @package WatermarkImage
 * @version 0.1.0
 */

add_action( 'admin_init', 'include_watermark_image' );
/**
 * Include the library
 */
function include_watermark_image() {
	if ( ! class_exists( 'Watermark_Image_Generator' ) )
		require_once( 'watermark-image.php' );
}


add_action( 'save_post', 'save_watermark_image' );
/**
 * Save watermark image on post save
 */
function save_watermark_image( $post_id ) {

	// Make sure we should be doing this action
	if (
		defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE
		|| ( 'page' == $_POST['post_type'] && ! current_user_can( 'edit_page', $post_id ) )
		|| ! current_user_can( 'edit_post', $post_id )
		|| ! function_exists( 'valueinsured_get_pbn' )
	)
		return;

	// Filter the default overlay settings
	add_filter( 'watermark_image_filter_defaults', 'update_watermark_image_defaults', 10, 2 );

	// Text to add as a watermark
	$text = 'Text to overlay on image';
	// URL image source
	$img_url = 'http://example.com/image.jpg';

	Watermark_Image_Generator::generate_watermark( $text, $img_url, $post_id );
}

/**
 * Update watermark setting defaults
 * @since  0.1.0
 * @param  array  $defaults Arguments to modify the defaults
 * @param  array  $vars     Array of variables including size, image path etc
 * @return array            Modified default arguments
 */
function update_watermark_image_defaults( $defaults, $vars ) {
	// set 13% up from the bottom
	$defaults['top_offset'] = absint( $vars['size']['height'] - ( $vars['size']['height'] * .13 ) );
	// Capitalize and concatenate text
	$defaults['text'] = strtoupper( sanitize_title( $vars['text'] ) );
	return $defaults;
}
```