WordPress-Image-Watermark
=========================

Uses WordPress and Imagick to overlay text on an image and save as a separate image.

### To use this library:

```php
// Usage
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
```