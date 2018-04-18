<?php
/*
	Author: Saul Boyd (avikar.io)
	License: GPL (http://www.gnu.org/copyleft/gpl.html)
*/
?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h1>Blockstack Settings:</h1>
	<form method="post" action="options.php">
		<div>
			<h2>Blockstack SSO Library Location</h2>
			<h4>You can find the Blockstack SSO Library Here: <a target="_blank" href="https://github.com/saul-avikar/Blockstack-SSO">Blockstack SSO</a></h4>

			<?php settings_fields( "blockstack_settings" ); ?>

			<table class="form-table">
				<tr>
					<th>
						<label for="blockstack_jsLibraryLocation">Blockstack SSO JS Location:</label>
					</th>
					<td>
						<?php echo $_SERVER['SERVER_NAME']; ?>/<input type="text" id="blockstack_jsLibraryLocation" name="blockstack_jsLibraryLocation" value="<?php echo get_option( 'blockstack_jsLibraryLocation' ); ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="blockstack_phpLibraryLocation">Blockstack SSO PHP Location:</label>
					</th>
					<td>
						<?php echo $_SERVER['SERVER_NAME']; ?>/<input type="text" id="blockstack_phpLibraryLocation" name="blockstack_phpLibraryLocation" value="<?php echo get_option( 'blockstack_phpLibraryLocation' ); ?>" />
					</td>
				</tr>
			</table>
			<br />
			<br />
			<h2>Blockstack User Options</h2>
			<table class="form-table">
				<tr id="blockstack_accountCreationP">
					<th>
						<label for="blockstack_accountCreation">Allow Blockstack Account Creation:</label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_accountCreation" name="blockstack_accountCreation" <?php echo ( get_option( 'blockstack_accountCreation' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_customUsernamesP">
					<th>
						<label for="blockstack_customUsernames">Allow Custom Usernames:</label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_customUsernames" name="blockstack_customUsernames" <?php echo ( get_option( 'blockstack_customUsernames' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_uniqueUsernamesP">
					<th>
						<label for="blockstack_uniqueUsernames">Force Unique Usernames:</label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_uniqueUsernames" name="blockstack_uniqueUsernames" <?php echo ( get_option( 'blockstack_uniqueUsernames' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_onenameUsernamesP">
					<th>
						<label for="blockstack_onenameUsernames">Use Onename Usernames:</label>
					</th>
					<td>
						<input type="checkbox" id="blockstack_onenameUsernames" name="blockstack_onenameUsernames" <?php echo ( get_option( 'blockstack_onenameUsernames' ) ) ? "checked" : ""; ?> />
					</td>
				</tr>
				<tr id="blockstack_accountLinkingP">
					<th>
						<label for="blockstack_accountLinking">Allow Blockstack Account Linking:</label>
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
	document.addEventListener( "DOMContentLoaded", function ( event ) {
		var accountCreation = document.getElementById( "blockstack_accountCreation" );
		var customUsernames = document.getElementById( "blockstack_customUsernames" );
		var accountLinking = document.getElementById( "blockstack_accountLinking" );

		function setState( el, state ){
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
	.disabled, .disabled label{
		opacity: 0.6;
		cursor: not-allowed;
	}
</style>