<?php
/**
 * Author: Saul Boyd (avikar.io)
 * License: GPL (http://www.gnu.org/copyleft/gpl.html)
 */
if ( !function_exists( "plugin_dir_path" ) ) {
	$parse_uri = explode( "wp-content", $_SERVER["SCRIPT_FILENAME"] );
	require_once( $parse_uri[0] . "wp-load.php" );
}

include( ABSPATH . get_option( 'blockstack_phpLibraryLocation' ) );

$blkstk = new BlockstackCommon();
$response = json_decode( $blkstk->auth(), true );

if ( $response["error"] ) {
	// error handle
	die('{"error": true, "data": "' . $response["data"] . '"}');
} else {
	// login!

	$responseMessage = "";

	// modify usernames in order of priority
	if ( get_option( "blockstack_uniqueUsernames" ) === "on" ) {
		$userName = $response["data"]["id"];
	} else {
		$userName = $response["data"]["did"];
	}

	if ( get_option( "blockstack_onenameUsernames" ) === "on" ) {
		if ( isset( $response["data"]["username"] ) && $response["data"]["username"] != null ) {
			$userName = str_replace( ".id", "", $response["data"]["username"] );
		}
	}


	if ( get_option( "blockstack_didUsernames" ) !== "on" && get_option( "blockstack_uniqueUsernames" ) !== "on" ) {
		if ( blockstack_validateCustomInfo( $response["data"]["login"] ) ) {
			//attempt login with these details

			$userName = $response["data"]["login"]["username"];
		} else {
			// We are using custom usernames and the username are invalid? No thanks...

			die( '{"error": false, "data": "' . __( "Username and/or password is invalid", "blockstack" ) . '", "request": true, "message": "' . __( "Please enter a valid username or password.", "blockstack" ) . '"}' );
		}
	}

	$userId = username_exists( $userName );

	if ( !$userId ) {
		// User doesn't exist - request username, create user, account linking only or reject

		if ( get_option( "blockstack_didUsernames" ) !== "on" && get_option( "blockstack_uniqueUsernames" ) !== "on" && get_option( "blockstack_accountCreation" ) === "on" ) {
			// User doesn't exist - create one:

			$responseMessage = '{"error": false, "data": "' . __( "New user using custom username", "blockstack" ) . '"}';

			$response["data"]["password"] = $response["data"]["login"]["password"];
			$userId = wp_create_user( $userName,  $response["data"]["password"] );
			add_user_meta( $userId, "avatar_url", $response["data"]["avatarUrl"] );
			add_user_meta( $userId, "blockstack_user", true );
		} elseif ( get_option( "blockstack_accountCreation" ) === "on" ) {
			// Create the account

			$responseMessage = '{"error": false, "data": "' . __( "Creating user", "blockstack" ) . '"}';

			$userId = wp_create_user( $userName, $response["data"]["password"] );
			add_user_meta( $userId, "avatar_url", $response["data"]["avatarUrl"] );
			add_user_meta( $userId, "blockstack_user", true );
		} else {
			// Account linking only

			die( '{"error": false, "data": "' . __( "Account creation is disabled", "blockstack" ) . '", "request": true, "message": "' . __( "Please enter a existing account", "blockstack" ) . '"}' );
		}
	} else {
		// User exists - check whether we are linking account, or loggin in like normal

		if ( get_option( "blockstack_didUsernames" ) !== "on" && get_option( "blockstack_uniqueUsernames" ) !== "on" ) {
			// User exists - attempt saved blockstack login details and request details if they don't work

			$creds = [
				'user_login' => $userName,
				'user_password' => $response["data"]["login"]["password"],
				'remember' => true
			];

			$user = wp_signon( $creds, is_ssl() );

			if ( !is_wp_error( $user ) ) {
				// Login details work

				blockstack_updateUserMeta( $userId, $response["data"]["profile"]["name"],  $response["data"]["profile"]["description"] );
				die( '{"error": false, "data": "' . __( "Custom username login successful", "blockstack" ) . '"}' );
			} else {
				// Login details failed - request new ones

				die( '{"error": false, "data": "' . __( "Login details failed, requesting new ones", "blockstack" ) . '", "request": true, "message": "' . __( "Password is incorrect.", "blockstack" ) . '"}' );
			}
		} else {
			// Attempt login like normal

			$responseMessage = '{"error": false, "data": "' . __( "Logging in", "blockstack" ) . '"}';
			update_user_meta( $userId, "avatar_url", $response["data"]["avatarUrl"] );
		}
	}

	blockstack_updateUserMeta( $userId, $response["data"]["profile"]["name"],  $response["data"]["profile"]["description"] );

	$creds = [
		'user_login' => $userName,
		'user_password' => $response["data"]["password"],
		'remember' => true
	];

	$user = wp_signon( $creds, is_ssl() );

	if ( !is_wp_error( $user ) ) {
		die( $responseMessage );
	} else {
		die( '{"error": true, "data": "' . $user . '", "message": "' . __( "Something went wrong with the signin, please try again.", "blockstack" ) . '"}' );
	}
}


function blockstack_updateUserMeta( $id, $name, $description ) {
	$nameParts = explode( " ", $name );
	$lastName = array_values( array_slice( $nameParts, -1 ) )[0];

	if( $lastName === $nameParts[0] ){
		$lastName = "";
	}

	update_user_meta( $id, "first_name", $nameParts[0] );
	update_user_meta( $id, "last_name", $lastName );
	update_user_meta( $id, "nickname", $name );
	update_user_meta( $id, "display_name", $name );
	update_user_meta( $id, "description", $description );
}

function blockstack_validateCustomInfo( $loginObj ) {
	return (
		isset( $loginObj )
		&& $loginObj !== false
		&& isset( $loginObj["username"] )
		&& isset( $loginObj["password"] )
		&& $loginObj["username"] != null
		&& $loginObj["password"] != null
		&& $loginObj["username"] != ""
		&& $loginObj["password"] != ""
	);
}
