<?php
/*
Plugin Name: Order Locators for Jigoshop
Plugin URI: http://wordpress.org/extend/plugins/jigoshop-coupon-products
Description: Extends JigoShop adding an unique locator per order+product
Version: 0.1
Author: Carlos Sanz García
Author URI: http://codingsomething.wordpress.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/



//  Check if Jigoshop is active
if ( in_array( 'jigoshop/jigoshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ):
	


/**
 * Gets Jigoshop version
 *
 * @package		Jigoshop
 * @subpackage 	Order Locators for Jigoshop
 * @since 		0.1
 *
 **/
function jigoshop_locator_version() {
	require_once(ABSPATH.'wp-admin/includes/plugin.php');
	$plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/jigoshop/jigoshop.php');
	return $plugin_data['Version'];
}



/**
 * Adds news settings to Jigoshop
 *
 * @package		Jigoshop
 * @subpackage 	Order Locators for Jigoshop
 * @since 		0.1
 *
 **/
function jigoshop_locator_access() {
	
	if ( jigoshop_locator_version() < '1.3') return;
	
	load_plugin_textdomain( 'jigoshop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
	Jigoshop_Base::get_options()->install_external_options_after_id( 'jigoshop_disable_fancybox', jigoshop_locator_settings() );
}
add_action('plugins_loaded', 'jigoshop_locator_access');



function jigoshop_locator_settings() {
	
	$settings = array();
	
	$settings[]  = array(
		'name' => __('Order locator', 'jigoshop'),
		'type' => 'title', 
		'desc' 		=> '' 
	);
	$settings[] =  array(
		'name' => __('Locator length', 'jigoshop'),
		'desc' 		=> __('Default = 6', 'jigoshop'),
		'tip' 		=> __('Setting up the locator digits length; min: 6 digits, max: 32 digits.', 'jigoshop'),
		'id' 		=> 'jigoshop_locator_length',
		'std' 		=> '6',
		'type' 		=> 'range',
		'extra'		=> array(
			'min'			=> 6,
			'max'			=> 32,
			'step'			=> 1
		)
	);
	return $settings;
}



function jigoshop_locator_after_email_order_info($order_id) {
	
    echo '=====================================================================' . PHP_EOL;
    echo __('LOCATORS', 'jigoshop') . PHP_EOL;
    echo '=====================================================================' . PHP_EOL;

	$order = new jigoshop_order($order_id);
	foreach ($order->items as $row => $item) {
		$row++;
		
		if ( isset($item['qty']) && !empty($item['qty']) && $item['qty'] > 1 ):
			for ($n=1; $n < ($item['qty']+1); $n++) { 
				$locator = jigoshop_generate_locator($order_id, $item['id'], $row, $n);
				echo "$locator - {$item['name']}" . PHP_EOL;
			}
		else:
			$locator = jigoshop_generate_locator($order_id, $item['id'], $row);
			echo "$locator - {$item['name']}" . PHP_EOL;
		endif;
	}
	echo PHP_EOL;
	
}
add_action('jigoshop_after_email_order_info', 'jigoshop_locator_after_email_order_info');



/**
 * prints locator column in header of table of product lists on order page
 *
 * @package		Jigoshop
 * @subpackage 	Order Locators for Jigoshop
 * @since 		0.1
 *
**/
function jigoshop_locator_order_item_headers() {
	global $post;
	
	// dirty trick
	jigoshop_session::instance()->current_admin_order = $post;
	jigoshop_session::instance()->current_admin_order_item_count = 0;

	?><th class="variation"><?php _e('Locators', 'jigoshop'); ?></th><?php
}
add_action('jigoshop_admin_order_item_headers', 'jigoshop_locator_order_item_headers');



/**
 * prints locator column in body of table of product lists on order page
 *
 * @package		Jigoshop
 * @subpackage 	Order Locators for Jigoshop
 * @since 		0.1
 *
**/
function jigoshop_locator_order_item_values($_product, $item = false) {
	global $post;
	
	if (!isset($post)) $post = jigoshop_session::instance()->current_admin_order;
	
	// dirty trick
	$row = jigoshop_session::instance()->current_admin_order_item_count += 1;
	
	echo '<td>' . PHP_EOL;
	
	if ( $item && !empty($item) ):
		
	for ( $n=1; $n < ($item['qty']+1); $n++ ):
		$locator = jigoshop_generate_locator($post->ID, $item['id'], $row, $n);
		if (!empty($locator)) echo "<code style=\"font-size: 13px; line-height:2em\">$locator</code><br>";
	endfor;
	
	else:
		
		$locator = jigoshop_generate_locator($post->ID, $_product->ID, $row);
		if (!empty($locator)) echo "<code style=\"font-size: 13px; line-height:2em\">$locator</code><br>";
	
	endif;
	
	echo '</td>' . PHP_EOL;
}
add_action('jigoshop_admin_order_item_values', 'jigoshop_locator_order_item_values', 10, 2 );



function jigoshop_generate_locator($order_ID = false, $product_ID = false, $row = false, $qty = 1) {
	
	if (!$order_ID || !$product_ID || !$row ) return '';
	
	$locator_length = Jigoshop_Base::get_options()->get_option('jigoshop_locator_length');
	
	$locator = "#$order_ID#$product_ID#$row";
	if (!empty($qty)) $locator .= "#$qty";
	
	return jigoshop_encode_locator($locator , $locator_length);
	
}


/**
 * encodes a string with length parameter (resource-intensive)
 *
 * @param $input string to be encripted
 * @param $length length of result
 * @param $charset avaiable source for encript
 *
 * @return ecripted string
 *
 * @package		Jigoshop
 * @subpackage 	Order Locators for Jigoshop
 * @since 		0.1
 *
**/
function jigoshop_encode_locator($input, $length, $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUFWXIZ0123456789') {
    $output = '';
    $input = md5($input); //this gives us a nice random hex string regardless of input 

    do{
        foreach (str_split($input,8) as $chunk){
            srand(hexdec($chunk));
            $output .= substr($charset, rand(0,strlen($charset)), 1);
        }
        $input = md5($input);

    } while(strlen($output) < $length);

    return substr($output,0,$length);
}


/**
 * Admin scripts
 **/
 /*
function jigoshop_locator_admin_scripts() {

	if (!jigoshop_is_admin_page()) return false;
	wp_enqueue_script('jigoshop_coupon_backend', plugins_url( 'assets/js/write-panels.js' , __FILE__ ), array( 'jigoshop_backend' ), '0.1' );
}
add_action( 'admin_print_scripts' , 'jigoshop_coupon_admin_scripts');
*/


/**
 * Enqueue admin styles
 *
 * @package		Jigoshop
 * @subpackage 	Order Locators for Jigoshop
 * @since 0.1
 *
 **/
 /*
function jigoshop_locator_admin_styles() {

	if ( ! jigoshop_is_admin_page() ) return false;
	wp_enqueue_style( 'jigoshop_coupon_admin_styles', plugins_url( 'assets/css/admin.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'jigoshop_coupon_admin_styles', 640 );
*/





endif;
// END Check if Jigoshop is active