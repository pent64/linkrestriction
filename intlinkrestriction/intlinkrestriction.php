<?php
/**
 * Plugin Name: Internal Link Restriction
 * Plugin URI: http://www.mywebsite.com/my-first-plugin
 * Description: Plugin restricts saving pages and posts if count of internal links are lower than established
 * Version: 0.1
 * Author: Vladyslav Serhiienko
 * Author URI:
 */

if ( !function_exists( 'add_action' ) ) {
	exit;
}

define( 'LINKRESTRICTION_VERSION', '0.1.0' );
define( 'LINKRESTRICTION__MINIMUM_WP_VERSION', '4.0' );
define( 'LINKRESTRICTION__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LINKRESTRICTION_DELETE_LIMIT', 100000 );

register_activation_hook( __FILE__, array( 'Linkrestriction', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Linkrestriction', 'plugin_deactivation' ) );

require_once( LINKRESTRICTION__PLUGIN_DIR . 'class.linkrestriction.php' );
add_action( 'init', array( 'Linkrestriction', 'init' ) );