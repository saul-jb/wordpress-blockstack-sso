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
* @package blockstack
* @category Core
* @author Saul Boyd
*/

if ( "https:\/\/" . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI] == "https:\/\/cryptographicapps.com/manifest.json" ) {
	header("Access-Control-Allow-Origin: *");
}

register_activation_hook( __FILE__, array( "blockstack", "activated" ) );

add_action( "plugins_loaded", array( "blockstack", "init" ) );

class blockstack {
	public static function init() {
		// hooks for directing the blockstack-login url
		add_filter( "generate_rewrite_rules", array( get_called_class(), "rewriteRules" ) );
		add_filter( "query_vars", array( get_called_class(), "queryVars" ) );
		add_action( "template_redirect", array( get_called_class(), "templateRedirect" ) );

		// hooks for login
		add_action( "init", array( get_called_class(), "preventPassowrdChange" ) );
		add_action( "login_footer", array( get_called_class(), "loginForm" ) );

		// admin options
		if ( is_admin() ) {
			add_action( "admin_menu", array( get_called_class(), "adminMenu" ) );
			add_action( "admin_init", array( get_called_class(), "registerSettings" ) );
		}

		// blockstack options
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( get_called_class(), 'plugin_settings_link' ) );
		add_action( 'user_new_form', array( get_called_class(), 'blockstackProfileSettings' ) );
	    add_action( 'show_user_profile', array( get_called_class(), 'blockstackProfileSettings' ) );
	    add_action( 'edit_user_profile', array( get_called_class(), 'blockstackProfileSettings' ) );
	}

	function plugin_settings_link( $links ) {
		$url = get_admin_url() . 'options-general.php?page=blockstack%2Fblockstack.php';
		$settings_link = '<a href="'.$url.'">' . __( 'Settings', 'textdomain' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	public static function blockstackProfileSettings($user){
		// Only display if current user is view his/her profile

		if ( wp_get_current_user()->ID == $user->ID ) {
			// We only need to display this if the admin has set the following options:

			if ( get_option( "blockstack_customUsernames" ) === "on" || get_option( "blockstack_accountLinking" ) === "on"  ) {
				?>
				<h3>Blockstack</h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th>Blockstack Login</th>
							<td>
								<div class="button button-primary" id="clearBSLogin">
									Disable Blockstack Login
								</div>
								<div id="clearedMessage" style="padding: 10px;" class="hidden">
									Login disabled.
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<style>
					.hidden {
						display: none;
					}
				</style>
				<script src="<?php echo "http" . ( ( is_ssl() ) ? 's' : '' ) . "://" . $_SERVER['SERVER_NAME'] . "/" . get_option( 'blockstack_jsLibraryLocation' ); ?>"></script>
				<script>
					document.addEventListener( "DOMContentLoaded", function( event ) {
						var clearedMessage = document.getElementById( "clearedMessage" );

						document.getElementById( "clearBSLogin" ).addEventListener( "click", function () {
							clearedMessage.className = "hidden";
							Blockstack_sso.setLoginDetails( "", "" ).then( function (res) {
								clearedMessage.className = "";
							});
						});
					});
				</script>
				<?php
			}
		}
	}


	public static function loginForm() {
		?>
		<script src="<?php echo get_option( 'blockstack_jsLibraryLocation' ); ?>"></script>
		<script>
			document.addEventListener( "DOMContentLoaded", function( event ) {
				if ( "<?php echo ( get_option( 'blockstack_accountCreation' ) ) ?>" === "on" || "<?php echo ( get_option( 'blockstack_accountLinking' ) ) ?>" === "on" ) {
					var form = document.getElementById( "loginform" );
					var btn = document.createElement( "INPUT" );
					btn.type = "button";
					btn.value = "Sign in with blockstack.";
					btn.className = "button button-primary button-large";
					btn.style = "position: relative; top: 20px; width: 100%";

					btn.addEventListener( "click", function( event ) {
						event.preventDefault();
						Blockstack_sso.login( false ).then( ( url ) => {
							window.location.replace( url );
						}).catch( ( err ) => {
							console.error( "Error: " + err );
						});
					});

					form.appendChild( btn );
				}
			});
		</script>
		<?php
	}

	public function registerSettings() {
		add_option( "blockstack_jsLibraryLocation", "wp-content/plugins/blockstack/js/blockstack_sso.js" );
		add_option( "blockstack_phpLibraryLocation", "wp-content/plugins/blockstack/libs/blockstack_sso.php" );
		add_option( "blockstack_accountCreation", true );
		add_option( "blockstack_customUsernames", true );
		add_option( "blockstack_uniqueUsernames", false );
		add_option( "blockstack_onenameUsernames", false );
		add_option( "blockstack_accountLinking", true );

		register_setting( "blockstack_settings", "blockstack_jsLibraryLocation" );
		register_setting( "blockstack_settings", "blockstack_phpLibraryLocation" );
		register_setting( "blockstack_settings", "blockstack_accountCreation" );
		register_setting( "blockstack_settings", "blockstack_customUsernames" );
		register_setting( "blockstack_settings", "blockstack_uniqueUsernames" );
		register_setting( "blockstack_settings", "blockstack_onenameUsernames" );
		register_setting( "blockstack_settings", "blockstack_accountLinking" );
	}

	private function registerSetting( $settingName ) {
		add_option( $settingName, true );
		register_setting( "blockstack_settings", $settingName );
	}

	public function adminMenu(){
		add_options_page( "Blockstack options", "Blockstack", 'manage_options', __FILE__, array( get_called_class(), "optionsForm" ) );
	}

	public function optionsForm() {
		// This function displays the plugin options

		include( plugin_dir_path( __FILE__ ) . "pages/options.php" );
	}

	public static function preventPassowrdChange() {
		$user = wp_get_current_user();

		if ( $user->exists() && get_user_meta( $user->ID, "blockstack_user", true ) ) {
			add_filter( 'show_password_fields', function() {
				return false;
			});
			add_filter( "allow_password_reset", function() {
				return false;
			});
		}
	}

	public function rewriteRules( $wp_rewrite ) {
		$feed_rules = array( "manifest.json/?$" => "index.php?manifest=1" );
		$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;

		return $wp_rewrite->rules;
	}


	public static function queryVars( $query_vars ) {
		$query_vars[] = "manifest";
		$query_vars[] = "authResponse";

		return $query_vars;
	}


	public static function templateRedirect() {
		$manifest = intval( get_query_var( "manifest" ) );
		if ( $manifest ) {
			include plugin_dir_path( __FILE__ ) . "pages/manifest.php";
			die;
		}

		$authResponse = get_query_var( "authResponse" );
		if ( $authResponse ) {
			include plugin_dir_path( __FILE__ ) . "pages/authPage.php";
			die;
		}
	}

	public function activated() {
		flush_rewrite_rules();
	}
}

//__________________________________________________________________________________________________________________________

if( !function_exists( "get_avatar" ) ) {
	function get_avatar( $id_or_email, $size = 96, $default = '', $alt = '', $args = null ) {
		$bsUrl = get_user_meta( $id_or_email, "avatar_url", true );


		$defaults = array(
			// get_avatar_data() args.
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
		);

		if ( empty( $args ) ) {
			$args = array();
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
			/** This filter is documented in wp-includes/pluggable.php */
			return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
		}

		if ( ! $args['force_display'] && ! get_option( 'show_avatars' ) ) {
			return false;
		}

		$url2x = get_avatar_url( $id_or_email, array_merge( $args, array( 'size' => $args['size'] * 2 ) ) );

		$args = get_avatar_data( $id_or_email, $args );

		$url = $args['url'];

		if ( ! $url || is_wp_error( $url ) ) {
			return false;
		}

		$class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );

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

		if($bsUrl){
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
