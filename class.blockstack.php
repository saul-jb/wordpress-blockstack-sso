<?php
/**
 * Author: Saul Boyd (avikar.io)
 * License: GPL (http://www.gnu.org/copyleft/gpl.html)
 */

class Blockstack {
	public static function init() {
		// Hooks for directing the blockstack-login url
		add_filter( "generate_rewrite_rules", [get_called_class(), "rewriteRules"] );
		add_filter( "query_vars", [get_called_class(), "queryVars"] );
		add_action( "template_redirect", [get_called_class(), "templateRedirect"] );

		// Hooks for login
		add_action( "init", [get_called_class(), "preventPassowrdChange"] );
		add_action( "login_footer", [get_called_class(), "loginForm"] );

		// Admin options
		if ( is_admin() ) {
			add_action( "admin_menu", [get_called_class(), "adminMenu"] );
			add_action( "admin_init", [get_called_class(), "registerSettings"] );
		}

		// Blockstack options
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [get_called_class(), 'plugin_settings_link'] );
		add_action( 'user_new_form', [get_called_class(), 'blockstackProfileSettings'] );
	    add_action( 'show_user_profile', [get_called_class(), 'blockstackProfileSettings'] );
	    add_action( 'edit_user_profile', [get_called_class(), 'blockstackProfileSettings'] );
	}

	// Add a settings link on the plugin page.
	function plugin_settings_link( $links ) {
		$url = get_admin_url() . 'options-general.php?page=blockstack%2Fblockstack.php';
		$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'blockstack' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	// Add a button to clear the local user's blockstack storage
	public static function blockstackProfileSettings( $user ) {
		// Only display if current user is view his/her profile

		if ( wp_get_current_user()->ID == $user->ID ) {
			// We only need to display this if the admin has set the following options:

			if ( get_option( "blockstack_customUsernames" ) === "on" || get_option( "blockstack_accountCreation" ) !== "on"  ) {
				?>
				<h3>Blockstack</h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th><?php _e( "Blockstack Login", "blockstack" ); ?></th>
							<td>
								<div class="button button-primary" id="clearBSLogin">
									<?php _e( "Disable Blockstack Login", "blockstack" ); ?>
								</div>
								<div id="clearedMessage" style="padding: 10px;" class="hidden">
									<?php _e( "Login disabled.", "blockstack" ); ?>
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
				<script src="<?php echo site_url() . "/" . get_option( 'blockstack_jsLibraryLocation' ); ?>"></script>
				<script>
					document.addEventListener( "DOMContentLoaded", function( event ) {
						var clearedMessage = document.getElementById( "clearedMessage" );

						document.getElementById( "clearBSLogin" ).addEventListener( "click", function () {
							clearedMessage.className = "hidden";
							BlockstackCommon.setLoginDetails( "", "" ).then( function (res) {
								clearedMessage.className = "";
							});
						});
					});
				</script>
				<?php
			}
		}
	}

	// Modify the login form to include the "Sign in with blockstack." button.
	public static function loginForm() {
		?>
		<?php if ( get_option( "blockstack_accountCreation" ) === "on" || get_option( "blockstack_accountCreation" ) !== "on" ): ?>
			<script src="<?php echo get_option( 'blockstack_jsLibraryLocation' ); ?>"></script>
			<script>
				document.addEventListener( "DOMContentLoaded", function( event ) {
					var form = document.getElementById( "loginform" );
					var btn = document.createElement( "INPUT" );
					btn.type = "button";
					btn.value = "<?php _e( 'Sign in with blockstack.', 'blockstack' ); ?>";
					btn.className = "button button-primary button-large";
					btn.style = "position: relative; top: 20px; width: 100%";

					btn.addEventListener( "click", function( event ) {
						event.preventDefault();
						BlockstackCommon.login( false ).then( ( url ) => {
							window.location.replace( url );
						}).catch( ( err ) => {
							console.error( "Error: " + err );
						});
					});

					form.appendChild( btn );
				});
			</script>
		<?php endif; ?>
		<?php
	}

	// Register the plugin settings
	public static function registerSettings() {
		add_option( "blockstack_jsLibraryLocation", "wp-content/plugins/blockstack/js/blockstack_sso.js" );
		add_option( "blockstack_phpLibraryLocation", "wp-content/plugins/blockstack/libs/blockstack_sso.php" );
		add_option( "blockstack_accountCreation", true );
		add_option( "blockstack_customUsernames", true );
		add_option( "blockstack_uniqueUsernames", false );
		add_option( "blockstack_onenameUsernames", false );

		register_setting( "blockstack_settings", "blockstack_jsLibraryLocation" );
		register_setting( "blockstack_settings", "blockstack_phpLibraryLocation" );
		register_setting( "blockstack_settings", "blockstack_accountCreation" );
		register_setting( "blockstack_settings", "blockstack_customUsernames" );
		register_setting( "blockstack_settings", "blockstack_uniqueUsernames" );
		register_setting( "blockstack_settings", "blockstack_onenameUsernames" );
	}

	// Add an option to the settings menu for the blockstack options page
	public static function adminMenu(){
		add_options_page( "Blockstack options", __("Blockstack", "blockstack"), "manage_options", __FILE__, [get_called_class(), "optionsForm"] );
	}

	// This function displays the plugin options
	public static function optionsForm() {
		include( plugin_dir_path( __FILE__ ) . "pages/options.php" );
	}

	// Prevent blockstack users from chaning their own passwords if "blockstack_uniqueUsernames" is enabled
	public static function preventPassowrdChange() {
		if ( get_option( 'blockstack_uniqueUsernames' ) === "on" ) {
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
	}

	// Add the authResponse query var ready for the blockstack service redirect
	public static function queryVars( $query_vars ) {
		$query_vars[] = "authResponse";

		return $query_vars;
	}

	// Redirect the authResponse to the authenticaiton page
	public static function templateRedirect() {
		$authResponse = get_query_var( "authResponse" );
		if ( $authResponse ) {
			include plugin_dir_path( __FILE__ ) . "pages/auth-page.php";
			die;
		}
	}
}
