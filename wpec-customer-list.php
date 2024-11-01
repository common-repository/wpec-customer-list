<?php
/*
Plugin Name: WPEC Customers List
Plugin URI: http://www.3dolab.net/en/wpec-customer-list
Description: List table of customers with details of total orders, items and amount in sortable columns.
Version: 1.0
Author: 3dolab
Author URI: http://www.3dolab.net/
License: GPL2
*/
/*  Copyright 2012  3dolab  (email : boss@3dolab.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//load_plugin_textdomain( 'wpec-customerlist', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
function customerlist_textdomain() {
	if ( function_exists('load_plugin_textdomain') ) {
		load_plugin_textdomain( 'wpec-customerlist', '/wp-content/plugins/wpec-customer-list/languages', dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

}
add_action( 'init', 'customerlist_textdomain' );
function wpec_customerlist_include_coupon_js() {

	// Variables
	$siteurl            = get_option( 'siteurl' );
	$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;

	// Coupon CSS
	wp_enqueue_style( 'wp-e-commerce-admin_2.7',        WPSC_URL         . '/wpsc-admin/css/settingspage.css', false, false,               'all' );
	wp_enqueue_style( 'wp-e-commerce-admin',            WPSC_URL         . '/wpsc-admin/css/admin.css',        false, $version_identifier, 'all' );

	// Coupon JS
	wp_enqueue_script( 'wp-e-commerce-admin-parameters', $siteurl        . '/wp-admin/admin.php?wpsc_admin_dynamic_js=true', false,                     $version_identifier );
	wp_enqueue_script( 'livequery',                     WPSC_URL         . '/wpsc-admin/js/jquery.livequery.js',             array( 'jquery' ),         '1.0.3' );
	wp_enqueue_script( 'datepicker-ui',                 WPSC_CORE_JS_URL . '/ui.datepicker.js',                              array( 'jquery-ui-core' ), $version_identifier );
	wp_enqueue_script( 'wp-e-commerce-admin_legacy',    WPSC_URL         . '/wpsc-admin/js/admin-legacy.js',                 array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'datepicker-ui' ), $version_identifier );
}

function customers_list_to_db_template($dbtemplate){
	if(is_array($dbtemplate)){
		$table_name = WPSC_CUSTOMERS_LIST; /* !wpsc_also_bought */
		$dbtemplate[$table_name]['columns']['ID'] = "bigint(20) unsigned NOT NULL";
		$dbtemplate[$table_name]['columns']['username'] = "varchar(255) NOT NULL DEFAULT '' ";
		$dbtemplate[$table_name]['columns']['name'] = "varchar(255) NOT NULL DEFAULT '' ";
		$dbtemplate[$table_name]['columns']['email'] = "varchar(255) NOT NULL DEFAULT '' ";
		$dbtemplate[$table_name]['columns']['orders'] = "int(10) unsigned NOT NULL DEFAULT '0' ";
		$dbtemplate[$table_name]['columns']['items'] = "int(10) unsigned NOT NULL DEFAULT '0' ";
		$dbtemplate[$table_name]['columns']['amount'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
		$dbtemplate[$table_name]['columns']['date'] = "varchar(255) NOT NULL DEFAULT '' ";
		$dbtemplate[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `ID` )";
		$dbtemplate[$table_name]['previous_names'] = "{$wpdb->prefix}dummy_customers_list";
	}
	return $dbtemplate;
}
add_filter( 'wpsc_alter_database_template', 'customers_list_to_db_template' );

function customers_list_first_fill(){
    global $wpdb;
    $purchases = $wpdb->get_results( "SELECT pur.id, pur.totalprice, pur.user_ID, pur.date FROM ".WPSC_TABLE_PURCHASE_LOGS." as pur WHERE pur.processed = '3'" );
    //$user_purchases = $wpdb->get_results("SELECT pur.id, pur.totalprice, cart.price, cart.quantity, user.ID, user.user_login, user.user_email, user.user_nicename FROM ".WPSC_TABLE_PURCHASE_LOGS." as pur LEFT JOIN ".WPSC_TABLE_CART_CONTENTS." as cart ON (".WPSC_TABLE_CART_CONTENTS.".purchaseid = ".WPSC_TABLE_PURCHASE_LOGS.".id) LEFT JOIN ".$wpdb->users." as users ON (".$wpdb->users.".ID = ".WPSC_TABLE_PURCHASE_LOGS.".user_ID) WHERE ".WPSC_TABLE_PURCHASE_LOGS.".processed='3'");
    $users = array();
    $couponusers = get_users(array('meta_key' => 'wpec-customer-coupons'));
    if($couponusers) {
		foreach($couponusers as $couponuser)
			$users[]=$couponuser->ID;
    }
  if ($purchases){
    foreach ($purchases as $purchase){
		$users[] = $purchase->user_ID;
    }
    array_unique($users);
    if(!empty($users)){
		foreach($users as $userid){
			$purchaseqty = array();
			$cartitemsqty = 0;
			$cartitemsprice = 0;
			$purchaseprice = 0;
			$lastdate = 0;
			$userobject = get_userdata($userid);
			foreach($purchases as $purchase){
				if($purchase->user_ID == $userid){
					  $purchaseqty[] = $purchase->id;
					  if($purchase->date > $lastdate)
						  $lastdate = $purchase->date;
					  //$cartitems = $wpdb->get_results( $wpdb->prepare( "SELECT cart.price, cart.quantity FROM ".WPSC_TABLE_CART_CONTENTS." as cart WHERE cart.purchaseid = ".$purchase->id."" ) );
					  $cartitems = $wpdb->get_results( "SELECT cart.price, cart.quantity FROM ".WPSC_TABLE_CART_CONTENTS." as cart WHERE cart.purchaseid = ".$purchase->id."" );
					  if ($cartitems){
					      foreach ($cartitems as $cartitem){
						  $cartitemsqty += $cartitem->quantity;
						  $cartitemsprice += $cartitem->price * $cartitem->quantity;
					      }
					  } else {
					      $cartitemerror = $purchase->id;
					  }
					  //$cartitemsqty += $wpdb->get_results( $wpdb->prepare( "SELECT SUM cart.quantity FROM ".WPSC_TABLE_CART_CONTENTS." as cart WHERE cart.purchaseid = ".$purchase->id."" ) );
					  $purchaseprice += $purchase->totalprice;
				}
			}
		    if ( $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPSC_CUSTOMERS_LIST . "` WHERE `ID`='".$userid."'" ) == 0 )
			$wpdb->query( "INSERT INTO `" . WPSC_CUSTOMERS_LIST . "` ( `ID` , `username` ,`name`, `email`, `orders` ,`items`, `amount`, `date` ) VALUES ( '".$userid."', '".$userobject->display_name."', '".$userobject->user_login."', '".$userobject->user_email."', '".count($purchaseqty)."', '".$cartitemsqty."', '".$purchaseprice."', '".$lastdate."')" );
		    else
			$wpdb->query("UPDATE `".WPSC_CUSTOMERS_LIST."` SET  `username` = '".$userobject->display_name."', `name` = '".$userobject->user_login."', `email` = '".$userobject->user_email."', `orders` = '".count($purchaseqty)."', `items` = '".$cartitemsqty."', `amount` = '".$purchaseprice."', `date` = '".$lastdate."' WHERE `ID` = '".$userid."' LIMIT 1");
		}
    }
  }
}
function customers_list_install(){
	global $wpdb, $user_level, $wp_rewrite, $wp_version, $wpsc_page_titles, $table_prefix;

	// Use the DB method if it's around
	if ( !empty( $wpdb->prefix ) )
		$wp_table_prefix = $wpdb->prefix;

	// Fallback on the wp_config.php global
	else if ( !empty( $table_prefix ) )
		$wp_table_prefix = $table_prefix;

	define( 'WPSC_CUSTOMERS_LIST', "{$wp_table_prefix}wpsc_customers_list" );

	$table_name    = $wpdb->prefix . "wpsc_customers_list";
	$first_install = false;

	if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name ) {
		// Table doesn't exist
		//$first_install = true;
		wpsc_create_or_update_tables();
		customers_list_first_fill();
	}
	//wpsc_create_or_update_tables();
	if('' == get_option('customer_reward_pur'))
		update_option('customer_reward_pur',0);
	if('' == get_option('customer_reward_itm'))
		update_option('customer_reward_itm',0);
	if('' == get_option('customer_reward_amt'))
		update_option('customer_reward_amt',0);
	if('' == get_option('customer_reward_show'))
		update_option('customer_reward_show',0);
	if('' == get_option('customer_reward_form_show'))
		update_option('customer_reward_form_show',0);
	if('' == get_option('customer_reward_auto'))
		update_option('customer_reward_auto',0);
	if ( isset( $_REQUEST['reward_pur'] ) )
		update_option('customer_reward_pur', $_REQUEST['reward_pur']);
	if ( isset( $_REQUEST['reward_itm'] ) )
		update_option('customer_reward_itm', $_REQUEST['reward_itm']);
	if ( isset( $_REQUEST['reward_amt'] ) )
		update_option('customer_reward_amt', $_REQUEST['reward_amt']);
	if ( isset( $_REQUEST['set-customers-reward'] ) && isset( $_REQUEST['reward_show'] ) )
		update_option('customer_reward_show', 1);
	elseif( isset( $_REQUEST['set-customers-reward'] ) && !isset( $_REQUEST['reward_show'] ))
		update_option('customer_reward_show', 0);
	if ( isset( $_REQUEST['set-customers-reward'] ) && isset( $_REQUEST['reward_auto'] ) )
		update_option('customer_reward_auto', 1);
	elseif( isset( $_REQUEST['set-customers-reward'] ) && !isset( $_REQUEST['reward_auto'] ))
		update_option('customer_reward_auto', 0);
	if ( isset( $_REQUEST['set-customers-reward'] ) && isset( $_REQUEST['reward_form_show'] ) )
		update_option('customer_reward_form_show', 1);
	elseif( isset( $_REQUEST['set-customers-reward'] ) && !isset( $_REQUEST['reward_form_show'] ))
		update_option('customer_reward_form_show', 0);
	if ( isset( $_REQUEST['set-customers-reward'] ) && isset( $_REQUEST['reward_user_notify'] ) )
		update_option('customer_reward_user_notify', 1);
	elseif( isset( $_REQUEST['set-customers-reward'] ) && !isset( $_REQUEST['reward_user_notify'] ))
		update_option('customer_reward_user_notify', 0);
	if ( isset( $_REQUEST['reward_regen'] ) )
		customers_list_first_fill();
}
add_action( 'wpsc_includes', 'customers_list_install' );
function wpec_customerlist_scripts(){

    //wpec_customerlist_include_coupon_js();
    wpsc_admin_include_coupon_js();
    $version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
    wp_enqueue_script( 'wp-e-commerce-admin', WPSC_URL . '/wpsc-admin/js/admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), $version_identifier, false );
    wp_enqueue_script( 'wp-e-commerce-legacy-ajax', WPSC_URL . '/wpsc-admin/js/ajax.js', false, $version_identifier ); // needs removing
    wp_enqueue_style( 'wp-e-commerce-admin', WPSC_URL . '/wpsc-admin/css/admin.css', false, $version_identifier, 'all' );
    wp_enqueue_style( 'wp-e-commerce-admin-dynamic', $siteurl . "/wp-admin/admin.php?wpsc_admin_dynamic_css=true", false, $version_identifier, 'all' );
    wp_localize_script( 'wp-e-commerce-admin', 'wpsc_adminL10n', array(
				'unsaved_changes_detected' => __( 'Unsaved changes have been detected. Click OK to lose these changes and continue.', 'wpsc' ),
				'dragndrop_set' => ( get_option( 'wpsc_sort_by' ) == 'dragndrop' ? 'true' : 'false' ),
				'l10n_print_after' => 'try{convertEntities(wpsc_adminL10n);}catch(e){};'
			) );
    //wpsc_admin_include_css_and_js_refac( 'index.php' );
}
//if ( isset( $_REQUEST['customer_details'] ) ){
	if ( isset( $_GET['wpsc_admin_dynamic_js'] ) && ( $_GET['wpsc_admin_dynamic_js'] == 'true' ) )
		add_action( "admin_init", 'wpsc_admin_dynamic_js' );
	if ( isset( $_GET['wpsc_admin_dynamic_css'] ) && ( $_GET['wpsc_admin_dynamic_css'] == 'true' ) )
		add_action( "admin_init", 'wpsc_admin_dynamic_css' );
	add_action( 'admin_enqueue_scripts', 'wpec_customerlist_scripts' );
//}
class Customers_List_Table extends WP_List_Table {
    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'customer',     //singular name of the listed records
            'plural'    => 'customers',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    function column_default($item, $column_name){
        switch($column_name){
	    case 'ID':
            case 'name':
            case 'email':
            case 'items':
                return $item[$column_name];
            case 'amount':
		return wpsc_currency_display( $item[$column_name], array( 'display_as_html' => false ) );
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_username($item){
	    $url = '?page=customers_list';
	    $userurl = add_query_arg('customer_details',$item['ID'],$url);
            return sprintf('<a href="%s">%s</a>',$userurl,$item['username']);
	    //return esc_url( add_query_arg( 'customer_details', $item['ID'], $url ) );
    }

    function column_orders($item){
            //return sprintf('<a href="?customer_details=%s">%s</a>',$item['ID'],$item['orders']); 
	    $url = '?page=customers_list';
	    $userurl = add_query_arg('customer_details',$item['ID'],$url);
            return sprintf('<a href="%s">%s</a>',$userurl,$item['orders']);
    }

    function column_coupons($item){
            //return sprintf('<a href="?customer_details=%s">%s</a>',$item['ID'],$item['orders']); 
	    //$url = '?page=customers_list';
	    $usercoupons = get_user_meta($item['ID'], 'wpec-customer-coupons', true);
	    $userurl = add_query_arg(array('customer_details'=>$item['ID'],'page'=>'customers_list'));
            return sprintf('<a href="%s">%s</a>',$userurl,count($usercoupons));
    }

    function column_date($item){
	    if(empty($item['date'])||$item['date']==0)
		return '/';
            return date_i18n( 'j M Y', $item['date'] );  
    }

    function get_columns(){
        $columns = array(
		'ID'		=> __( 'ID' ),
		'username'	=> __( 'Username' ),
		'name'		=> __( 'Name' ),
		'email'		=> __( 'E-mail' ),
		'orders'	=> __( 'Total Orders', 'wpec-customerlist' ),
		'items'		=> __( 'Total Items', 'wpec-customerlist' ),
		'amount'	=> __( 'Total Amount', 'wpec-customerlist' ),
		'date'		=> __( 'Last Date', 'wpec-customerlist' ),
		'coupons'	=> __( 'Customer Coupons', 'wpec-customerlist' )
        );
        return $columns;
    }
    
    function get_sortable_columns(){
        $sortable_columns = array(
            'ID'    => array('ID',false),
            'username'     => array('username',false),     //true means its already sorted
            'name'    => array('name',false),
            'email'  => array('email',false),
		'orders'     => array('orders',false),
		'items'    => array('items',false),
		'amount' => array('amount',true),
		'date'    => array('date',false),
        );
        return $sortable_columns;
    }

    function prepare_items(){
	global $wpdb, $usersearch;

		$usersearch = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
		$per_page = ( $this->is_site_users ) ? 'customers_per_page' : 'customers_per_page';
		$per_page = $this->get_items_per_page( $per_page );
		$paged = $this->get_pagenum();

	//$per_page = 20;

	// get the current user ID
	$user = get_current_user_id();
	// get the current admin screen
	$screen = get_current_screen();
	// retrieve the "per_page" option
	$screen_option = $screen->get_option('per_page', 'option');
	// retrieve the value of the option stored for the current user
	$per_page = get_user_meta($user, $screen_option, true);
	if ( empty ( $per_page) || $per_page < 1 ) {
		// get the default value if none is set
		$per_page = $screen->get_option( 'per_page', 'default' );
	}
	// now use $per_page to set the number of items displayed
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        //$this->process_bulk_action();
       
		if ( isset( $_REQUEST['orderby'] ) )
			$orderby = 'ORDER BY '.$_REQUEST['orderby'];

		if ( isset( $_REQUEST['order'] ) )
			$order = $_REQUEST['order'];

		if ( '' !== $usersearch )
			$search = " WHERE customer.name LIKE '".$usersearch."' OR customer.username LIKE '".$usersearch."' OR customer.email LIKE '".$usersearch."' OR customer.ID LIKE '".$usersearch."'";
		else
			$search = $usersearch;

		if ( isset( $_REQUEST['users_per_coupon'] ) ){
			$couponlist = array();
			$userlist = array();
			//$users = get_users(array('meta_key' => 'wpec-customer-coupons', 'meta_value' => $_REQUEST['users_per_coupon']));
			$users = get_users(array('meta_key' => 'wpec-customer-coupons'));
			if($users) {
				foreach($users as $user) {
					$couponlist[$user->ID] = get_user_meta($user->ID, 'wpec-customer-coupons', true);
					if(!empty($couponlist[$user->ID])) {
						foreach ($couponlist as $couponuser => $couponids) {
							if(is_array($couponids)){
								foreach ($couponids as $couponid){
									if(empty($userlist[$couponid]))
										$userlist[$couponid] = array();
									$userlist[$couponid][] = $couponuser;
								}
							}else{
								if(empty($userlist[$couponids]))
									$userlist[$couponids] = array();
								$userlist[$couponids][] = $couponuser;
							}
						}
					}
				}
				$coupon_users = $userlist[$_REQUEST['users_per_coupon']];
				if(is_array($coupon_users))
					$add_users = implode(',', array_unique($coupon_users));
				else
					$add_users = $coupon_users;
				if ( '' !== $usersearch )
					$search .= " AND customer.ID IN (".$add_users.")";
				else
					$search = " WHERE customer.ID IN (".$add_users.")";
			}
		}

	$purchases = $wpdb->get_results( "SELECT * FROM ".WPSC_CUSTOMERS_LIST." as customer ".$orderby." ".$order."".$search."", ARRAY_A );
	$data = $purchases;
        $current_page = $this->get_pagenum();

        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
}

class Customers_Orders_Table extends WP_List_Table {
    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'order',     //singular name of the listed records
            'plural'    => 'orders',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    function column_default($item, $column_name){
        switch($column_name){
            case 'totalprice':
		return wpsc_currency_display( $item[$column_name], array( 'display_as_html' => false ) );
	    case 'sessionid':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
      
    function column_id($item){
            return '<a href="index.php?page=wpsc-sales-logs&purchaselog_id='.$item['id'].'">'.$item['id'].'</a>';  
    }
  
    function column_items($item){
            return '<a href="index.php?page=wpsc-sales-logs&purchaselog_id='.$item['id'].'">'.$item['items'].'</a>';  
    }
    
    function column_date($item){
	    if(empty($item['date'])||$item['date']==0)
		  return '/';
            return date_i18n( 'j M Y', $item['date'] );  
    }

    function get_columns(){
        $columns = array(
		'id'		=> __( 'ID' ),
		'items'		=> __( 'Items', 'wpec-customerlist' ),
		'totalprice'	=> __( 'Amount', 'wpec-customerlist' ),
		'date'		=> __( 'Date' ),
		'sessionid'	=> __( 'Session', 'wpec-customerlist' ),
        );
        return $columns;
    }
    
    function get_sortable_columns(){
        $sortable_columns = array(
		'id'    => array('id',false),
		'items'    => array('items',false),
		'totalprice' => array('totalprice',true),
		'date'    => array('date',false)
        );
        return $sortable_columns;
    }

    function prepare_items(){
	global $wpdb;
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
		if ( isset( $_REQUEST['orderby'] ) )
			$orderby = ' ORDER BY '.$_REQUEST['orderby'].' ';

		if ( isset( $_REQUEST['order'] ) )
			$order = strtoupper($_REQUEST['order']);

      if ( isset( $_REQUEST['customer_incomplete'] ) )
	  $purchases = $wpdb->get_results( "SELECT pur.id as id, pur.totalprice as totalprice, pur.date as date, pur.sessionid as sessionid, SUM(cart.quantity) as items FROM ".WPSC_TABLE_PURCHASE_LOGS." as pur INNER JOIN ".WPSC_TABLE_CART_CONTENTS." as cart ON (cart.purchaseid = pur.id) WHERE pur.user_ID = ".$_REQUEST['customer_details'].' GROUP BY id'.$orderby.$order."", ARRAY_A );
      else
	  $purchases = $wpdb->get_results( "SELECT pur.id as id, pur.totalprice as totalprice, pur.date as date, pur.sessionid as sessionid, SUM(cart.quantity) as items FROM ".WPSC_TABLE_PURCHASE_LOGS." as pur INNER JOIN ".WPSC_TABLE_CART_CONTENTS." as cart ON (cart.purchaseid = pur.id) WHERE pur.user_ID = ".$_REQUEST['customer_details']." AND pur.processed = '3' GROUP BY id".$orderby.$order."", ARRAY_A );

	$data = $purchases;
        $current_page = $this->get_pagenum();

        $total_items = count($data);

        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
    
}
function customer_coupon_compare_itemid($logic, $c, $product_obj) {
		global $wpdb;
		if ($c['property'] == 'item_id') {
			$product_data = $wpdb->get_results("SELECT * FROM " . $wpdb->posts . " WHERE id='{$product_obj->product_id}'");
			$product_data = $product_data[0];
		// VARIATIONS! == ID or in_array($child_ids)	
			switch($c['logic']) {
				case 'equal': //Checks if the product name is exactly the same as the condition value
				if ($product_data->ID == $c['value']) {
					
					return true;
				}
				break;
				
				case 'greater'://Checks if the product name is not the same as the condition value
				if ($product_data->ID > $c['value'])
					return true;
				break;
				
				case 'less'://Checks if the product name is not the same as the condition value
				if ($product_data->ID < $c['value'])
					return true;
				break;

				case 'contains'://
				if (in_array($product_data->ID, explode(',',str_replace(', ',',',$c['value']))))
					return true;
				break;

				case 'not_contain'://
				if (!in_array($product_data->ID, explode(',',str_replace(', ',',',$c['value']))))
					return true;
				break;
				
				default:
				return false;
			}
		}
}
function customer_coupon_rule_add() {
	return '<option value="item_id" rel="order">' . __( 'Item ID', 'wpsc') . '</option>';
}
add_filter( 'wpsc_coupon_rule_property_options', 'customer_coupon_rule_add' );
add_filter( 'wpsc_coupon_compare_logic', 'customer_coupon_compare_itemid', 10, 3 );
function replace_default_coupons_page(){
    require_once( 'customer-coupons.php' );
    $_REQUEST['customer_details'] = 'all';
    wpec_customer_coupons_page('all');
}
function customers_add_menu_items(){
    global $customerlist_page, $customerlist_subpage;
    $customerlist_page = add_users_page(__('Customers List','wpec-customerlist'), __('Customers List','wpec-customerlist'), 'list_users', 'customers_list', 'customers_render_list_page');
    $customerlist_subpage = add_submenu_page( 'index.php', __( 'Customers List', 'wpec-customerlist' ), __( 'Customers List', 'wpec-customerlist' ), 'list_users', 'customers_list', 'customers_render_list_page' );
    $url = '?page=customers_list';
    $allurl = add_query_arg('customer_details','all',$url);
    $products_page = 'edit.php?post_type=wpsc-product';
    remove_submenu_page($products_page, 'wpsc-edit-coupons');
    add_submenu_page( $products_page, __( 'Customer Coupons', 'wpec-customerlist' ), __( 'Customer Coupons', 'wpec-customerlist' ), 'list_users', 'customers_list', 'replace_default_coupons_page' );
    //add_menu_page('Customers List Table plugin', 'Customers List Table', 'activate_plugins', 'customers_list', 'customers_render_list_page');
    add_action("load-$customerlist_page", "customerlist_screen_options");
    add_action("load-$customerlist_subpage", "customerlist_screen_options");
    //add_filter('set-screen-option', 'customerlist_set_screen_option', 10, 3);
}
//add_action('admin_menu', 'customers_add_menu_items');
add_action('wpsc_add_submenu', 'customers_add_menu_items');
function customerlist_screen_options(){
    //include('./admin-header.php');
	global $customerlist_page, $customerlist_subpage;
	$currentscreen = get_current_screen();
	if(!is_object($currentscreen) || ($currentscreen->id != $customerlist_subpage && $currentscreen->id != $customerlist_page))
		return;
	$args = array(
		'label' => __('Customers per page', 'wpec-customerlist'),
		'default' => 20,
		'option' => 'customers_per_page'
	);
	//add_screen_option( 'per_page', array('label' => _x( 'Users', 'users per page (screen options)' )) );
	add_screen_option( 'per_page', $args );
}

function customerlist_set_screen_option($status, $option, $value) {
	if ( 'customers_per_page' == $option ) {
//echo '-status'.$status;
		$value = (int) $value;
//echo '-value'.$value;
		$status = $value;
//if ( $value > 1 || $value < 999 )
//echo '-newstatus'.$status;
		if ( $value > 1 || $value < 999 )
			return $value;
		else
			return false;
	}
	return $status;
}
add_filter('set-screen-option', 'customerlist_set_screen_option', 99, 3);

function customers_render_list_page(){
    require_once( 'customer-coupons.php' );

    if ( isset( $_REQUEST['customer_details'] ) ){
	//Create an instance of our package class...
    $CustomersOrdersTable = new Customers_Orders_Table();
    //Fetch, prepare, sort, and filter our data...
    $CustomersOrdersTable->prepare_items();

	if ( $_REQUEST['customer_details'] == 'all' ){
	       wpec_customer_coupons_page('all');
	       return;
	} else {
	      $customer = get_userdata($_REQUEST['customer_details']);
	}
	if($customer)
	      wpec_customer_coupons_page($_REQUEST['customer_details']);
	      //wpec_customer_coupons_page($customer->ID); ?>
    <div class="wrap">
	<?php screen_icon(); ?>
	<h2>
	<?php
	if($customer)
		$title = __('Purchase History for', 'wpec-customerlist').' '.$customer->display_name;
	echo esc_html( $title );
	//$url = 'index.php?page=customers_list';
	$url = add_query_arg('page', 'customers_list', $_SERVER['HTTP_REFERER']);
	$userurl = add_query_arg('customer_details',$customer->ID,$url);?>
	</h2>
                <div style='background:#ECECEC;border:1px solid #CCC;padding: 10px;margin:10px 0;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;'>
		  <h3><?php _e('User Details', 'wpec-customerlist'); ?></h3>
		  <form id='set_customers_filter' method='get' action='<?php //echo $userurl; ?>'>
			<input id='page' name='page' type='hidden' value='customers_list' />
			<input id='customer_details' name='customer_details' type='hidden' value='<?php echo $customer->ID; ?>' />
			<input id='customer_incomplete' name='customer_incomplete' type='checkbox' <?php if(isset($_REQUEST['customer_incomplete'])) echo 'checked="checked"';?> />&nbsp;<?php _e('Show Incomplete','wpec-customerlist'); ?>&nbsp;
			<input type='submit' class='button-primary' value='<?php _e('Set', 'wpec-customerlist'); ?>' />&nbsp;
		  </form>
        </div>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="customers-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $CustomersOrdersTable->display(); ?>
        </form>
        <br class="clear" />
    </div>
    <?php
    }else{
	echo '<style type="text/css">';
	echo '.wp-list-table .column-ID { width: 5%!important; }';
	echo '.wp-list-table .column-orders, .wp-list-table .column-items, .wp-list-table .column-amount, .wp-list-table .column-coupons, .wp-list-table .column-amount, .wp-list-table .column-date { width: 10%!important; }';
	echo '</style>';
	//Create an instance of our package class...
	$CustomersListTable = new Customers_List_Table();
	//Fetch, prepare, sort, and filter our data...
	$CustomersListTable->prepare_items();
	if ( ! current_user_can( 'list_users' ) )
		wp_die( __( 'Cheatin&#8217; uh?' ) ); 
	//$wp_list_table = _get_list_table('WP_Users_List_Table');
	$pagenum = $CustomersListTable->get_pagenum();

	//$currentscreen = get_current_screen();	
	//add_screen_option( 'per_page', array('label' => _x( 'Users', 'users per page (screen options)' )) );

	$rwd_pur = get_option('customer_reward_pur');
	$rwd_itm = get_option('customer_reward_itm');
	$rwd_amt = get_option('customer_reward_amt');
	if(get_option('customer_reward_show') == 1)
		$showchecked = 'checked="checked"';
	if(get_option('customer_reward_form_show') == 1)
		$formchecked = 'checked="checked"';
	if(get_option('customer_rewardauto') == 1)
		$autochecked = 'checked="checked"';
	if(get_option('customer_reward__user_notify') == 1)
		$notifychecked = 'checked="checked"';
	$title = __('Customers List', 'wpec-customerlist');
    ?>
    <div class="wrap">
	<?php screen_icon(); ?>
	<h2>
	<?php echo esc_html( $title );?>
	</h2>
<?php	if ( $usersearch )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( $usersearch ) ); ?>

        <div style='background:#ECECEC;border:1px solid #CCC;padding: 10px;margin:10px 0;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;'>
		  <h3><?php _e('Reward Options', 'wpec-customerlist'); ?></h3>
		  <form id='set_customers_reward' method='post'>
		      <p>
			<input type='hidden' name='set-customers-reward' value='1' />
			<label for='reward_pur'><?php _e('Orders', 'wpec-customerlist'); ?></label>
			<input id='reward_pur' name='reward_pur' type='text' value='<?php echo $rwd_pur; ?>' size='4' />&nbsp;
			<label for='reward_itm'><?php _e('Items', 'wpec-customerlist'); ?></label>
			<input id='reward_itm' name='reward_itm' type='text' value='<?php echo $rwd_itm; ?>' size='4' />&nbsp;
			<label for='reward_amt'><?php _e('Amount', 'wpec-customerlist'); ?></label>
			<input id='reward_amt' name='reward_amt' type='text' value='<?php echo $rwd_amt; ?>' size='8' /> €&nbsp;
			<input type='submit' class='button-primary' value='<?php _e('Set', 'wpec-customerlist'); ?>' />&nbsp;
			<a class='wpsc_edit_coupon button-secondary' rel='default' href='#'><?php _e('Default Reward', 'wpec-customerlist'); ?></a>
		      </p>
		      <p>
			<input id='reward_regen' name='reward_regen' type='checkbox' value='1' />&nbsp;<?php _e('Regenerate Stats','wpec-customerlist'); ?>
			<input id='reward_show' name='reward_show' type='checkbox' <?php echo $showchecked; ?> />&nbsp;<?php _e('Show Score to Users','wpec-customerlist'); ?>&nbsp;
			<input id='reward_form_show' name='reward_form_show' type='checkbox' <?php echo $formchecked; ?> />&nbsp;<?php _e('Show Coupon in User Cart','wpec-customerlist'); ?>&nbsp;
			<input id='reward_auto' name='reward_auto' type='checkbox' <?php echo $autochecked; ?> />&nbsp;<?php _e('Auto-create Rewards','wpec-customerlist'); ?>&nbsp;
			<input id='reward_user_notify' name='reward_user_notify' type='checkbox' <?php echo $notifychecked; ?> />&nbsp;<?php _e('Notify Reward to User E-mail','wpec-customerlist'); ?>&nbsp;
		      </p>
		  </form>
		  <br />
		  <div id='coupon_box_default' class='displaynone modify_coupon'>
			<?php $default_coupon = customer_default_coupon();
			      customer_coupon_edit_form( $default_coupon ); ?>
		  </div>
        </div>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="customers-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
	    <?php $CustomersListTable->search_box( __( 'Search Users' ), 'customer' ); ?>
            <?php $CustomersListTable->display(); ?>
        </form>
        <br class="clear" />
    </div>
    <?php
    }
}
function mail_customer_reward($user_ID, $rwd_type, $inserted_coupon){

	$reward_text = date_i18n('j M Y', $inserted_coupon['start'])."\n\r";
        if(!empty($rwd_type['orders']))
		$reward_text .= '<h2>'.$rwd_type['orders'].' '.__('orders').'</h2>';
        if(!empty($rwd_type['items']))
		$reward_text .= '<h2>'.$rwd_type['items'].' '.__('items').'</h2>';
        if(!empty($rwd_type['amount']))
		$reward_text .= '<h2>'.$rwd_type['amount'].' '.__('amount').'</h2>';
	$reward_text .= __('Code', 'wpec-customerlist').": ".$inserted_coupon['coupon_code']."\n\r";
	$reward_text .= __('Discount', 'wpsc').": ".$inserted_coupon['value'];
	if($inserted_coupon['is_percentage']==0)
		$reward_text .= wpsc_get_currency_symbol()."\n\r";
	if($inserted_coupon['is_percentage']==1)
		$reward_text .= "%"."\n\r";
	if($inserted_coupon['is_percentage']==2)
		$reward_text .=  __( 'Free shipping', 'wpsc' )."\n\r";
	$reward_text .= __('Expiry', 'wpsc').": ".date_i18n('j M Y', $inserted_coupon['expiry'])."\n\r";
	
		$user_data = get_userdata($user_ID);

		$editor_subject = __('New Customer Reward', 'wpec-customerlist');
		$mail_subject = __('New Reward from', 'wpec-customerlist').' '.wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
		$mail_content = __('User').' '.$user_ID.': '.$user_data->display_name.'
				'.$reward_text;
		
		$editors = get_users('role=editor');
		//$admins = get_users('role=administrator');
		//$roles = array_merge($admins, $editors);
		$roles = $editors;
		$uniques = array();
		if ( get_option('customer_reward_user_notify') == 1 ) {
		    if (class_exists('MailPress')) {
			$args_array = array('toemail' => $user_data->user_email, 'toname' => $user_data->display_name, 'subject' => $mail_subject, 'plaintext' => strip_tags($reward_text), 'html' => $reward_text);
			$args = (object)$args_array;
			MailPress::mail($args);
		    } else {
			@wp_mail( $user_data->user_email, $mail_subject, strip_tags($reward_text) );
		    }
		}
		foreach ($roles as $editor) {
		  if (!in_array($editor->ID, $uniques)){
		    $uniques[] = $editor->ID;
		    $editor_info = get_userdata($editor->ID);
		    $editor_mail = $editor_info->user_email;
		    if (!empty($editor_info->first_name) || !empty($editor_info->last_name)) {
		      $editor_name = $editor_info->first_name;
		      $editor_name  .= " ";
		      $editor_name  .= $editor_info->last_name;
		    } else {
		      $editor_name  = $editor_info->display_name;
		    }
		    $editorargs_array = array(
				  'toemail' => $editor_mail,
				  'toname' =>  $editor_name,
				  'subject' => $editor_subject,
				  'plaintext' => strip_tags($mail_content),
				  'html' => $mail_content);
		    $editorargs = (object)$editorargs_array;
		    if (class_exists('MailPress')) {	
			MailPress::mail($editorargs);
		    } else {
			@wp_mail( $editor_mail, $editor_subject, strip_tags($mail_content));
		    }
		  }
		}
}
function customers_list_update_db(){
    global $wpdb, $wpsc_cart, $user_ID, $purchase_log, $order_id, $redemption_sum;
	if ( !isset( $user_ID ) )
            $user_ID = $purchase_log['user_ID'];
    $purchase_id = $purchase_log['id'];
    //$session_id = get_post_meta( $order_id, "_session_id", true );
    $session_id = $purchase_log['sessionid'];
    $userobject = get_userdata($user_ID);

    if($purchase_log['processed']!=3)
	return;
    if (!empty($wpsc_cart->cart_items)){
	$cartitems = $wpsc_cart->cart_items;
	foreach ($cartitems as $cartitem){
		 $cartitemsqty += $cartitem->quantity;
		 $cartitemsprice += $cartitem->price * $cartitem->quantity;
	}	
    }
	$rwd_pur = get_option('customer_reward_pur');
	$rwd_itm = get_option('customer_reward_itm');
	$rwd_amt = get_option('customer_reward_amt');

    $values = $wpdb->get_row($wpdb->prepare("SELECT * FROM `".WPSC_CUSTOMERS_LIST."` WHERE `ID` = '".$user_ID."'"), ARRAY_A);
    if(!empty($values)){
	if($values['date'] > $purchase_log['date'])
	    $upd_date = $values['date'];
	else
	    $upd_date = $purchase_log['date'];
	$wpdb->query($wpdb->prepare("UPDATE `".WPSC_CUSTOMERS_LIST."` SET `orders` = '".($values['orders'] + 1)."', `items` = '".($values['items'] + $cartitemsqty)."', `amount` = '".($values['amount'] + $purchase_log['totalprice'])."', `date` = '".$upd_date."' WHERE `ID` = '".$user_ID."'"));

	if ( get_option('customer_reward_auto') == 1 ){
	    if ( ( $rwd_pur != 0 && ($values['orders'] + 1) % $rwd_pur == 0 && ($values['orders'] + 1) / $rwd_pur >= 1) ){
			$inserted_coupon = wpec_customer_coupons_page($user_ID, $session_id);
			mail_customer_reward($user_ID, array('orders' => $values['orders'] + 1), $inserted_coupon);
	    }
	    if ( ( $rwd_itm != 0 && ($values['items'] + $cartitemsqty) % $rwd_itm == 0 && ($values['items'] + $cartitemsqty) / $rwd_itm >= 1) ){
			$inserted_coupon = wpec_customer_coupons_page($user_ID, $session_id);
			mail_customer_reward($user_ID, array('items' => $values['items'] + $cartitemsqty), $inserted_coupon);
	    }
	    if ( ( $rwd_amt != 0 && ($values['amount'] + $purchase_log['totalprice']) % $rwd_amt == 0 && ($values['amount'] + $purchase_log['totalprice']) / $rwd_amt >= 1) ){
			$inserted_coupon = wpec_customer_coupons_page($user_ID, $session_id);
			mail_customer_reward($user_ID, array('amount' => $values['amount'] + $purchase_log['totalprice']), $inserted_coupon);
	    }
	}
    }else{
	$purchases = $wpdb->get_results( "SELECT pur.id, pur.totalprice, pur.date FROM ".WPSC_TABLE_PURCHASE_LOGS." as pur WHERE pur.processed = '3' AND pur.user_ID = '".$user_ID."'" );
	if(empty($purchases)) {
		$wpdb->query( "INSERT INTO `" . WPSC_CUSTOMERS_LIST . "` ( `ID` , `username` ,`name`, `email`, `orders` ,`items`, `amount`, `date` ) VALUES ( '".$user_ID."', '".$userobject->display_name."', '".$userobject->user_login."', '".$userobject->user_email."', '1', '".$cartitemsqty."', '".$purchase_log['totalprice']."', '".$purchase_log['date']."')" );
	} else {
		$purchaseqty = array();
		$cartitemsqty = 0;
		$cartitemsprice = 0;
		$purchaseprice = 0;
		$lastdate = 0;
		foreach($purchases as $purchase){
			if($purchase->user_ID == $userid){
				$purchaseqty[] = $purchase->id;
				if($purchase->date > $lastdate)
					  $lastdate = $purchase->date;
				$cartitems = $wpdb->get_results( "SELECT cart.price, cart.quantity FROM ".WPSC_TABLE_CART_CONTENTS." as cart WHERE cart.purchaseid = ".$purchase->id."" );
				if ($cartitems){
					foreach ($cartitems as $cartitem){
						$cartitemsqty += $cartitem->quantity;
						$cartitemsprice += $cartitem->price * $cartitem->quantity;
					}
				} else {
					$cartitemerror = $purchase->id;
				}
				//$cartitemsqty += $wpdb->get_results( $wpdb->prepare( "SELECT SUM cart.quantity FROM ".WPSC_TABLE_CART_CONTENTS." as cart WHERE cart.purchaseid = ".$purchase->id."" ) );
				$purchaseprice += $purchase->totalprice;
			}
		}
		$wpdb->query( "INSERT INTO `" . WPSC_CUSTOMERS_LIST . "` ( `ID` , `username` ,`name`, `email`, `orders` ,`items`, `amount`, `date` ) VALUES ( '".$user_ID."', '".$userobject->display_name."', '".$userobject->user_login."', '".$userobject->user_email."', '".count($purchaseqty)."', '".$cartitemsqty."', '".$purchaseprice."', '".$lastdate."')" );
	}
    }
}
add_filter( 'wpsc_pre_transaction_results', 'customers_list_update_db' );