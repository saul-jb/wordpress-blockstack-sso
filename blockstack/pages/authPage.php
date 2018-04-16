<?php
/*
	Template Name: Authentication page
	Author: Saul Boyd (avikar.io)
	License: GPL (http://www.gnu.org/copyleft/gpl.html)
*/
?>

<?php
include( plugin_dir_path( __FILE__ ) . "../libs/blockstack sso.php");
$blkstk = new Blockstack_sso();
?>
<div class="loginText">
	Logging in!
</div>

<style>
	.loginText{
		text-align: center;
		position: relative;
		top: 200px;
	}
</style>
<!-- include the blockstack file -->
<script src="<?php echo plugin_dir_url( __FILE__ ) . '../js/blockstack sso.js'; ?>"></script>
<script>
	Blockstack_sso.isSignedIn().then((userData) => {
		// successful sign in
		var url = "<?php echo plugin_dir_url( __FILE__ ) . 'auth.php' ?>";

		Blockstack_sso.phpSignIn(userData, url).then((res) => {
			// seccessful sign-in
			window.location.replace("http:\/\/" + window.location.hostname + "/wp-admin/");
		}).catch((err) => {
			// failed for some reason or another
			window.location.replace("http:\/\/" + window.location.hostname + "/wp-login.php");
		});
	}).catch((err) => {
		// sign in failed.
		window.location.replace("http:\/\/" + window.location.hostname + "/wp-login.php");
	});
</script>
