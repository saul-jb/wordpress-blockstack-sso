<?php
/*
	Template Name: Manifest Page
	Author: Saul Boyd (avikar.io)
	License: GPL (http://www.gnu.org/copyleft/gpl.html)
*/
?>
<?php
header( "Content-Type: application/json" );
header( "Access-Control-Allow-Origin: *" );
header( 'Access-Control-Allow-Methods: "GET, POST, PUT, DELETE"' );
header( 'Access-Control-Allow-Headers: "Content-Type"' );
?>
{
	"name": "Wordpress Blockstack Log-in",
	"start_url": "<?php echo $_SERVER['SERVER_NAME']; ?>",
	"description": "The blockstack plugin to log into wordpress with blockstack",
	"icons": [
		{
			"src": "https://blockstack.org/images/logos/blockstack-bug.svg",
			"sizes": "192x192",
			"type": "image/svg"
		}
	]
}
