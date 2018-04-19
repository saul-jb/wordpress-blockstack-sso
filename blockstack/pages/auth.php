<?php
/*
	Author: Saul Boyd (avikar.io)
	License: GPL (http://www.gnu.org/copyleft/gpl.html)
*/
if ( !function_exists( "plugin_dir_path" ) ) {
	$parse_uri = explode( "wp-content", $_SERVER["SCRIPT_FILENAME"] );
	require_once( $parse_uri[0] . "wp-load.php" );
}

include( ABSPATH . get_option( 'blockstack_phpLibraryLocation' ) );

$blkstk = new Blockstack_sso();
$response = json_decode( $blkstk->auth(), true );

if ( $response["error"] ) {
	//error handle
	die('{"error": true, "data": ' . $response["data"] . '}');
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


	if ( get_option( "blockstack_customUsernames" ) === "on" || get_option( "blockstack_accountLinking" ) === "on" ) {
		if ( blockstack_validateCustomInfo( $response["data"]["login"] ) ) {
			//attempt login with these details

			$userName = $response["data"]["login"]["username"];
		} else {
			// We are using custom usernames and the username are invalid? No thanks...

			die( '{"error": false, "data": "Username and/or password is invalid", "request": true, "message": "Please enter a valid username or password."}' );
		}
	}

	$userId = username_exists( $userName );

	if ( !$userId ) {
		// User doesn't exist - request username, create user, account linking only or reject

		if ( get_option( "blockstack_customUsernames" ) === "on" ) {
			// User doesn't exist - create one:

			$responseMessage = '{"error": false, "data": "New user using custom username"}';

			$response["data"]["password"] = $response["data"]["login"]["password"];
			$userId = wp_create_user( $userName,  $response["data"]["password"] );
			add_user_meta( $userId, "avatar_url", $response["data"]["avatarUrl"] );
			add_user_meta( $userId, "blockstack_user", true );
		}
		elseif ( get_option( "blockstack_accountCreation" ) === "on" ) {
			// Create the account

			$responseMessage = '{"error": false, "data": "Creating user"}';

			$userId = wp_create_user( $userName, $response["data"]["password"] );
			add_user_meta( $userId, "avatar_url", $response["data"]["avatarUrl"] );
			add_user_meta( $userId, "blockstack_user", true );
		}
		elseif ( get_option( "blockstack_accountLinking" ) === "on" ) {
			// Account linking only

			die( '{"error": false, "data": "Account creation is disabled", "request": true, "message": "Please enter a existing account."}' );
		} else {
			// Account creation is not allowed

			die( '{"error": true, "data": "Account creation is disabled", "message": "Account creation is disabled."}' );
		}
	} else {
		// User exists - check whether we are linking account, or loggin in like normal

		if ( get_option( "blockstack_accountLinking" ) === "on" ||  get_option( "blockstack_customUsernames" ) === "on" ) {
			// User exists - attempt saved blockstack login details and request details if they don't work

			$creds = array(
				'user_login' => $userName,
				'user_password' => $response["data"]["login"]["password"],
				'remember' => true
			);

			$user = wp_signon( $creds, is_ssl() );

			if ( !is_wp_error( $user ) ) {
				// Login details work

				blockstack_updateUserMeta( $userId, $response["data"]["profile"]["name"],  $response["data"]["profile"]["description"] );
				die( '{"error": false, "data": "Custom username login successful"}' );
			} else {
				// Login details failed - request new ones

				die( '{"error": false, "data": "Login details failed, requesting new ones", "request": true, "message": "Password is incorrect."}' );
			}
		} else {
			// Attempt login like normal

			$responseMessage = '{"error": false, "data": "Logging in"}';
			update_user_meta( $userId, "avatar_url", $response["data"]["avatarUrl"] );
		}
	}

	blockstack_updateUserMeta( $userId, $response["data"]["profile"]["name"],  $response["data"]["profile"]["description"] );

	$creds = array(
		'user_login' => $userName,
		'user_password' => $response["data"]["password"],
		'remember' => true
	);

	$user = wp_signon( $creds, is_ssl() );

	if ( !is_wp_error( $user ) ) {
		die( $responseMessage );
	} else {
		die( '{"error": true, "data": "' . $user . '", "message": "Something went wrong with the signin, please try again."}' );
	}
}


function blockstack_updateUserMeta ( $id, $name, $description ) {
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

function blockstack_validateCustomInfo ( $loginObj ) {
	return (
		isset( $loginObj ) &&
		$loginObj !== false &&
		isset( $loginObj["username"] ) &&
		isset( $loginObj["password"] ) &&
		$loginObj["username"] != null &&
		$loginObj["password"] != null &&
		$loginObj["username"] != "" &&
		$loginObj["password"] != ""
	);
}
