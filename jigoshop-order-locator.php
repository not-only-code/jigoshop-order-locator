<?php
/*
Plugin Name: Jigoshop Order Locator
Plugin URI: http://wordpress.org/extend/plugins/jigoshop-coupon-products
Description: Extends JigoShop adding an unique locator per order+product
Version: 0.1
Author: Carlos Sanz García
Author URI: http://codingsomething.wordpress.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/



/**
 * Adds news settings to Jigoshop
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
 **/
function jigoshop_locator_access() {
    
    // gettext
    load_plugin_textdomain( 'jigoshop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
	// dependeces
	$active_plugins_ = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	if ( in_array( 'jigoshop/jigoshop.php', $active_plugins_ ) && JIGOSHOP_VERSION >= 1207160  ):
        
        Jigoshop_Base::get_options()->install_external_options_after_id( 'jigoshop_disable_fancybox', jigoshop_locator_settings() );
        add_action('jigoshop_after_email_order_info', 'jigoshop_locator_after_email_order_info', 10 );
        
        // admin hooks
        if (is_admin()) {
            
            add_action( 'jigoshop_admin_order_item_headers', 'jigoshop_locator_order_item_headers' );
            add_action( 'jigoshop_admin_order_item_values', 'jigoshop_locator_order_item_values', 10, 2 );
            //add_action( 'jigoshop_process_shop_order_meta', 'jigoshop_store_locator', 10 );
        };
        
    else:

		if (is_admin())
            add_action( 'admin_notices', 'jigoshop_locator_dependences');
        
        
    endif;
}
add_action('plugins_loaded', 'jigoshop_locator_access');



/**
 * Adds locator length in settings
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
**/
/*
function jigoshop_store_locator($post_id = false) {
	if ( !$post_id ) return;
	
	$order_items = get_post_meta($post_id, 'order_items', true);
	
	foreach ($order_items as $row => $item):
		$row++;
        $locators = array();
			
		if ( $item['qty'] > 1) {
            
			for ( $n=1; $n < ($item['qty']+1); $n++)
				$locators[] = jigoshop_generate_locator($post_id, $item['id'], $row, $n);
            
		} else {
				
			$locators[] = jigoshop_generate_locator($post_id, $item['id'], $row );
		};
		
        $order_items[$row-1]['locator_id'] = $locators;
	endforeach;
    
    update_post_meta($post_id, 'order_items', $order_items);
}
*/


/**
 * Adds locator length in settings
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
**/
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



/**
 * Adds locators in order email
 *
 * @param int $order_id: id of order reference
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
**/
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
                $print = $locator .' - ' .$item['name'];                
                
                echo apply_filters('jigoshop_email_item_locator', $print, $locator, $order_id, $item, $row );
			}
		else:
            
			$locator = jigoshop_generate_locator($order_id, $item['id'], $row);
			$print =  $locator .' - ' .$item['name'];
            
            echo apply_filters('jigoshop_email_item_locator', $print, $locator, $order_id, $item, $row );
            
		endif;
	}
	echo PHP_EOL;
	
}



/**
 * Adds locators in order email
 *
 * @param int $order_id: id of order reference
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
**/
function jigoshop_get_item_id_by_locator( $order_id = false, $locator = false) {
	
    if ( !$order_id || !$locator ) return;
        
	$order = new jigoshop_order($order_id);
    
    $row = 0;
	foreach ($order->items as $item) {
		$row++;
		
		if ( isset($item['qty']) && !empty($item['qty']) && $item['qty'] > 1 ):
            
			for ($n=1; $n < ($item['qty']+1); $n++) {
                if ( $locator == jigoshop_generate_locator($order_id, $item['id'], $row, $n) ) return $item['id'];
            }
            
		else:
            
            if ( $locator == jigoshop_generate_locator($order_id, $item['id'], $row) ) return $item['id'];
            
		endif;
	}
	
    return false;
}



/**
 * prints locator column in header of table of product lists on order page
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
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



/**
 * prints locator column in body of table of product lists on order page
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
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



/**
 * generates locator hash
 *
 * @param int $order_ID id reference of order
 * @param int $product_ID id reference of product
 * @param int $row position of current item inside order
 * @param int $qty quantity number inside position
 *
 * @return ecripted string
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
**/
function jigoshop_generate_locator($order_ID = false, $product_ID = false, $row = 1, $qty = 1) {
	
	if (!$order_ID || !$product_ID ) return '';
	
    $locator = "#$order_ID#$product_ID#$row#$qty";
	$length = Jigoshop_Base::get_options()->get_option('jigoshop_locator_length');
    $output = md5($locator, false);
    
    return substr($output, 0, $length);
}



/**
 * admin notice: depencendes
 *
 * @package		Jigoshop
 * @subpackage 	Jigoshop Order Locator
 * @since 		0.1
 *
**/
function jigoshop_locator_dependences() {
	global $current_screen;
		
    echo "<div class=\"error\">" . PHP_EOL;
	echo "<p><strong>Jigoshop Order Locator:</strong></p>" . PHP_EOL;
	echo "<p>" . __('This plugin requires at least <strong>Jigoshop 1.3</strong> active.', 'jigoshop') . "</p>" . PHP_EOL;
    echo "</div>" . PHP_EOL;
}