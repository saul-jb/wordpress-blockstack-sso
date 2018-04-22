<?php
/**
 * Plugin Name: Blockstack - Authentication Via Blockstack
 * Plugin URI:
 * Description: Blockstack modifies the login page to allow signing in by blockstack.
 * Version: 1.0
 * Author: Saul Boyd
 * Author URI: http://avikar.io
 * Text Domain: blockstack
 * License: GPL (http://www.gnu.org/copyleft/gpl.html)
 *
 * @package Blockstack
 * @category Core
 * @author Saul Boyd
 */

// If this is a request for the blockstack manifest set a CORS header, return the JSON manifest and exit
if ( preg_match( '|/manifest.json$|', $_SERVER['REQUEST_URI'] ) ) {
	header("Access-Control-Allow-Origin: *");
	?>{
		"name": "Wordpress Blockstack Log-in",
		"start_url": "<?php echo site_url(); ?>",
		"description": "The blockstack plugin to log into Wordpress with blockstack",
		"icons": [
			{
				"src": "https://blockstack.org/images/logos/blockstack-bug.svg",
				"sizes": "192x192",
				"type": "image/svg"
			}
		]
	}<?php
	exit;
}

// Initialise our plugin
add_action( "plugins_loaded", ["Blockstack", "init"] );

// Modify the default "get_avitar" function to use our blockstack image if it exists
if( !function_exists( "get_avatar" ) ) {
	function get_avatar( $id_or_email, $size = 96, $default = '', $alt = '', $args = null ) {
		$bsUrl = get_user_meta( $id_or_email, "avatar_url", true );

		// get_avatar_data() args.
		$defaults = [
			'size'          => 96,
			'height'        => null,
			'width'         => null,
			'default'       => get_option( 'avatar_default', 'mystery' ),
			'force_default' => false,
			'rating'        => get_option( 'avatar_rating' ),
			'scheme'        => null,
			'alt'           => '',
			'class'         => null,
			'force_display' => false,
			'extra_attr'    => '',
		];

		if ( empty( $args ) ) {
			$args = [];
		}

		$args['size']    = (int) $size;
		$args['default'] = $default;
		$args['alt']     = $alt;

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['height'] ) ) {
			$args['height'] = $args['size'];
		}
		if ( empty( $args['width'] ) ) {
			$args['width'] = $args['size'];
		}

		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$id_or_email = get_comment( $id_or_email );
		}

		$avatar = apply_filters( 'pre_get_avatar', null, $id_or_email, $args );

		if ( ! is_null( $avatar ) ) {
			// This filter is documented in wp-includes/pluggable.php
			return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
		}

		if ( ! $args['force_display'] && ! get_option( 'show_avatars' ) ) {
			return false;
		}

		$url2x = get_avatar_url( $id_or_email, array_merge( $args, ['size' => $args['size'] * 2] ) );

		$args = get_avatar_data( $id_or_email, $args );

		$url = $args['url'];

		if ( ! $url || is_wp_error( $url ) ) {
			return false;
		}

		$class = ['avatar', 'avatar-' . (int) $args['size'], 'photo'];

		if ( ! $args['found_avatar'] || $args['force_default'] ) {
			$class[] = 'avatar-default';
		}

		if ( $args['class'] ) {
			if ( is_array( $args['class'] ) ) {
				$class = array_merge( $class, $args['class'] );
			} else {
				$class[] = $args['class'];
			}
		}

		if ( $bsUrl ) {
			$url = $bsUrl;
			$url2x = $bsUrl;
		}

		$avatar = sprintf(
			"<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d' %s/>",
			esc_attr( $args['alt'] ),
			esc_url( $url ),
			esc_url( $url2x ) . ' 2x',
			esc_attr( join( ' ', $class ) ),
			(int) $args['height'],
			(int) $args['width'],
			$args['extra_attr']
		);

		return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
	}
}
