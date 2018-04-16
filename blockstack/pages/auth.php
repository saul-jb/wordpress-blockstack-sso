<?php
/*
	Author: Saul Boyd (avikar.io)
	License: GPL (http://www.gnu.org/copyleft/gpl.html)
*/

if(!function_exists("plugin_dir_path")){
	$parse_uri = explode("wp-content", $_SERVER["SCRIPT_FILENAME"]);
	require_once($parse_uri[0] . "wp-load.php");
}

include( plugin_dir_path( __FILE__ ) . "../libs/blockstack sso.php");

$blkstk = new Blockstack_sso();
$response = json_decode($blkstk->auth(), true);

if($response["error"]){
	//error handle
	echo '{"error": true, "data": ' . $response["data"] . '}';
}
else{
	//login!
	$userName = $response["data"]["did"];

	if(isset($response["data"]["username"]) && $response["data"]["username"] != null){
		$userName = $response["data"]["username"];
	}

	$userId = username_exists($userName);

	if(!$userId){
		echo '{"error": false, "data": "Creating user"}';

		$userId = wp_create_user($userName, $response["data"]["password"]);
		add_user_meta($userId, "avatar_url", $response["data"]["avatarUrl"]);
		add_user_meta($userId, "blockstack_user", true);
	}
	else{
		echo '{"error": false, "data": "Logging in"}';
		update_user_meta($userId, "avatar_url", $response["data"]["avatarUrl"]);
	}

	$nameParts = explode(" ", $response["data"]["profile"]["name"]);
	$lastName = array_values(array_slice($nameParts, -1))[0];

	if($lastName === $nameParts[0]){
		$lastName = "";
	}

	update_user_meta($userId, "first_name", $nameParts[0]);
	update_user_meta($userId, "last_name", $lastName);
	update_user_meta($userId, "nickname", $response["data"]["profile"]["name"]);
	update_user_meta($userId, "display_name", $response["data"]["profile"]["name"]);
	update_user_meta($userId, "description", $response["data"]["profile"]["description"]);

	$creds = array(
		'user_login' => $userName,
		'user_password' => $response["data"]["password"],
		'remember' => true
	);

	$user = wp_signon( $creds, is_ssl() );
}
?>
