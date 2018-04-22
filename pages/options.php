<?php
/**
 * Author: Saul Boyd (avikar.io)
 * License: GPL (http://www.gnu.org/copyleft/gpl.html)
 */
?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h1><?php _e( "Blockstack Settings:", "blockstack" ); ?></h1>
	<form method="post" action="options.php">
		<div>
			<h2><?php _e( "Blockstack SSO Library Location", "blockstack" ); ?></h2>
			<h4><?php _e( "You can find the Blockstack SSO Library Here:", "blockstack" ); ?> <a target="_blank" href="https://github.com/saul-avikar/Blockstack-SSO"><?php _e( "Blockstack SSO", "blockstack" ); ?></a></h4>

			<?php settings_fields( "blockstack_settings" ); ?>

			<table class="form-table">
				<tr>
					<th>
						<label for="blockstack_jsLibraryLocation"><?php _e( "Blockstack SSO JS Location:", "blockstack" ); ?></label>
					</th>
					<td>
						<?php echo site_url(); ?>/<input type="text" id="blockstack_jsLibraryLocation" name="blockstack_jsLibraryLocation" value="<?php echo get_option( 'blockstack_jsLibraryLocation' ); ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="blockstack_phpLibraryLocation"><?php _e( "Blockstack SSO PHP Location:", "blockstack" ); ?></label>
					</th>
					<td>
						<?php echo site_url(); ?>/<input type="text" id="blockstack_phpLibraryLocation" name="blockstack_phpLibraryLocation" value="<?php echo get_option( 'blockstack_phpLibraryLocation' ); ?>" />
					</td>
				</tr>
			</table>
			<br />
			<br />
			<h2>Blockstack User Options</h2>
			<table class="form-table">
				<tr id="blockstack_accountCreationP">
					<th>
						<label for="blockstack_accountCreation"><?php _e( "Allow Blockstack Account Creation:", "blockstack" ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_accountCreation" name="blockstack_accountCreation" <?php echo ( get_option( 'blockstack_accountCreation' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_customUsernamesP">
					<th>
						<label for="blockstack_customUsernames"><?php _e( "Allow Custom Usernames:", "blockstack" ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_customUsernames" name="blockstack_customUsernames" <?php echo ( get_option( 'blockstack_customUsernames' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_uniqueUsernamesP">
					<th>
						<label for="blockstack_uniqueUsernames"><?php _e( "Force Unique Usernames:", "blockstack" ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_uniqueUsernames" name="blockstack_uniqueUsernames" <?php echo ( get_option( 'blockstack_uniqueUsernames' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_onenameUsernamesP">
					<th>
						<label for="blockstack_onenameUsernames"><?php _e( "Use Onename Usernames:", "blockstack" ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_onenameUsernames" name="blockstack_onenameUsernames" <?php echo ( get_option( 'blockstack_onenameUsernames' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_accountLinkingP">
					<th>
						<label for="blockstack_accountLinking"><?php _e( "Allow Blockstack Account Linking:", "blockstack" ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_accountLinking" name="blockstack_accountLinking" <?php echo ( get_option( 'blockstack_accountLinking' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
			</table>
		</div>
		<?php  submit_button(); ?>
	</form>
</div>

<script>
	document.addEventListener( "DOMContentLoaded", function( event ) {
		var accountCreation = document.getElementById( "blockstack_accountCreation" );
		var customUsernames = document.getElementById( "blockstack_customUsernames" );
		var accountLinking = document.getElementById( "blockstack_accountLinking" );

		function setState( el, state ) {
			var parent = document.getElementById( el + "P" );
			el = document.getElementById( el );

			parent.className = state ? "" : "disabled";
			el.style.visibility = state ? "visible" : "hidden";
			state ? "" : el.checked = false;
		}

		function updateState() {
			setState( "blockstack_customUsernames", accountCreation.checked );
			setState( "blockstack_uniqueUsernames", ( accountCreation.checked && !customUsernames.checked ) );
			setState( "blockstack_onenameUsernames", ( accountCreation.checked && !customUsernames.checked ) );
			setState( "blockstack_accountLinking", !accountCreation.checked );
		}

		document.getElementById("blockstack_accountCreation").addEventListener( "change", function ( event ) {
			updateState();
		});

		document.getElementById("blockstack_customUsernames").addEventListener( "change", function ( event ) {
			updateState();
		});

		updateState();
	});
</script>

<style>
	.disabled, .disabled label {
		opacity: 0.6;
		cursor: not-allowed;
	}
</style>
