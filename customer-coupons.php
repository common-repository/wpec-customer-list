<?php
function customer_default_coupon() {
	$default_coupon = get_option('wpec-customerlist-default-reward');
	if(empty($default_coupon)) {
	    $default_coupon = array(
						   'id' => 'default',
						   'coupon_code' => '000aaa',
						   'value' => 50,
						   'is-percentage' => 1,
						   'use-once' => 1,
						   'is-used' => 0,
						   'active' => 0,
						   'every_product' => 0,
						   'duration' => 30,
						   'condition' => serialize( $new_rule ));
	  update_option('wpec-customerlist-default-reward',$default_coupon);
	}
	return $default_coupon;
}
function customer_coupon_edit_form($coupon) {

	$users = get_users(array('meta_key' => 'wpec-customer-coupons'));
	$couponlist = array();
	$userlist = array();
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
	}

$conditions = maybe_unserialize($coupon['condition']);

  $start_timestamp = strtotime($coupon['start']);
  $end_timestamp = strtotime($coupon['expiry']);
  $id = $coupon['id'];
//array_unique($userlist[$id]);
if(is_array($userlist[$id]))
  $add_users = implode(',', array_unique($userlist[$id]));
else
  $add_users = $userlist[$id];
  $output = '';
  $output .= "<form name='edit_coupon' id='".$coupon['coupon_code']."' method='post' action=''>\n\r";
  $output .= "<table class='add-coupon'>\n\r";
  $output .= " <tr>\n\r";
  $output .= "   <th>".__('Coupon Code', 'wpsc');
if($id == 'default')
  $output .= " (".__('prefix', 'wpec-customerlist').")";
  $output .= "</th>\n\r";
  $output .= "   <th>".__('Discount', 'wpsc')."</th>\n\r";
if($id != 'default'){
  $output .= "   <th>".__('Start', 'wpsc')."</th>\n\r";
  $output .= "   <th>".__('Expiry', 'wpsc')."</th>\n\r";
}else{
  $output .= "   <th>".__('Expiry', 'wpsc')."</th>\n\r";
}
  $output .= "   <th>".__('Use Once', 'wpsc')."</th>\n\r";
  $output .= "   <th>".__('Used', 'wpec-customerlist')."</th>\n\r";
  $output .= "   <th>".__('Active', 'wpsc')."</th>\n\r";
  $output .= "   <th>".__('Apply On All Products', 'wpsc')."</th>\n\r";
if($id != 'default')
  $output .= "   <th>".__('Add Users', 'wpec-customerlist')."</th>\n\r";
  $output .= "   <th></th>\n\r";
  $output .= " </tr>\n\r";
  $output .= " <tr>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='true' name='is_edit_coupon' />\n\r";
  $output .= "   <input type='text' size='11' value='".$coupon['coupon_code']."' name='edit_coupon[".$id."][coupon_code]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";

  $output .= "   <input type='text' size='3' value='".$coupon['value']."'  name=edit_coupon[".$id."][value]' />";
  $output .= "   <select name='edit_coupon[".$id."][is-percentage]'>";
  $output .= "     <option value='0' ".(($coupon['is-percentage'] == 0) ? "selected='true'" : '')." >".wpsc_get_currency_symbol()."</option>\n\r";//
  $output .= "     <option value='1' ".(($coupon['is-percentage'] == 1) ? "selected='true'" : '')." >%</option>\n\r";
  $output .= "     <option value='2' ".(($coupon['is-percentage'] == 2) ? "selected='true'" : '')." >" . __( 'Free shipping', 'wpsc' ) . "'</option>\n\r";
  $output .= "   </select>\n\r";
  $output .= "  </td>\n\r";
if($id != 'default'){
  $output .= "  <td>\n\r";
  $coupon_start = explode(" ",$coupon['start']);
  $output .= "<input type='text' class='pickdate' size='11' name='edit_coupon[".$id."][start]' value='{$coupon_start[0]}'>";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $coupon_expiry = explode(" ",$coupon['expiry']);
  $output .= "<input type='text' class='pickdate' size='11' name='edit_coupon[".$id."][expiry]' value='{$coupon_expiry[0]}'>";
  $output .= "  </td>\n\r";
}else{
  $output .= "   <td>\n\r";
  $output .= "+ <input type='text' size='3' name='edit_coupon[".$id."][duration]' value='".$coupon['duration']."'>".__('days', 'wpec-customerlist');
  $output .= "  </td>\n\r";
}
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][use-once]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['use-once'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][use-once]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][is-used]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['is-used'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][is-used]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][active]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['active'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][active]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][every_product]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['every_product'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][every_product]' />\n\r";
  $output .= "  </td>\n\r";
if($id != 'default'){
  $output .= "  <td>\n\r";
  $output .= "   <input type='text' size='11' value='".$add_users."'  name='edit_coupon[".$id."][add_users]' />";
  $output .= "  </td>\n\r";
}
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='".$id."' name='edit_coupon[".$id."][id]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= " </tr>\n\r";

  if($conditions != null){
	  $output .= "<tr>";
	  $output .= "<th>";
	  $output .= __("Conditions", 'wpec-customerlist');
	  $output .= "</th>";
	  $output .= "</tr>";
	  $output .= "<th>";
	  $output .= __("Delete", 'wpsc');
	  $output .= "</th>";
	  $output .= "<th>";
	  $output .= __("Property", 'wpsc');
	  $output .= "</th>";
	  $output .= "<th>";
	  $output .= __("Logic", 'wpsc');
	  $output .= "</th>";
	  $output .= "<th>";
	  $output .= __("Value", 'wpsc');
	  $output .= "</th>";
	  $output .= " </tr>\n\r";
	  $i=0;
	  foreach ($conditions as $condition){
		  $output .= "<tr>";
		  $output .= "<td>";
		  $output .= "<input type='hidden' name='coupon_id' value='".$id."' />";
		  $output .= "<input type='submit' id='delete_condition".$i."' style='display:none;' value='".$i."' name='delete_condition' />";
		  $output .= "<span style='cursor:pointer;' class='delete_button' onclick='jQuery(\"#delete_condition".$i."\").click()'>" . __('Delete', 'wpsc') . "</span>";
		  $output .= "</td>";
		  $output .= "<td>";
		  $output .= $condition['property'];
		  $output .= "</td>";
		  $output .= "<td>";
		  $output .= $condition['logic'];
		  $output .= "</td>";
		  $output .= "<td>";
		  $output .= $condition['value'];
		  $output .= "</td>";
		  $output .= "</tr>";
		  $i++;
	  }
	  $output .=	wpsc_coupons_conditions( $id);
  }elseif($conditions == null){
  	$output .=	wpsc_coupons_conditions( $id);

  }
  $output .= "</table>\n\r";
  $output .= "</form>\n\r";
  echo $output;
  return $output;
}
function wpec_customer_coupons_page($user_ID, $autogen_code = false) {
	global $wpdb;
	if ( $autogen_code != false) {
		 $default_coupon = get_option('wpec-customerlist-default-reward');
		 $inserted_coupon = array( 'coupon_code' => $default_coupon['value'].$autogen_code,
						   'value' => $default_coupon['value'],
						   'is-percentage' => $default_coupon['is-percentage'],
						   'use-once' => $default_coupon['use-once'],
						   'is-used' => $default_coupon['is-used'],
						   'active' => $default_coupon['active'],
						   'every_product' => $default_coupon['every_product'],
						   'start' => date('Y-m-d'),
						   'expiry' => date('Y-m-d', strtotime('+'.$default_coupon['duration'].' days')),
						   'condition' => serialize( $new_rule ));
		 $wpdb->insert(WPSC_TABLE_COUPON_CODES,
					    $inserted_coupon,
					    array( '%s', '%d','%d','%d','%d','%d','%s','%s','%s','%s'));
		$coupon_id = $wpdb->insert_id;
		$coupons = get_user_meta($user_ID, 'wpec-customer-coupons', true);
		if(!$coupons)
			$coupons = array();
		$coupons[] = $coupon_id;
		update_user_meta($user_ID, 'wpec-customer-coupons', array_unique($coupons));
		return $inserted_coupon;
	}	
	if ( isset( $_POST ) && is_array( $_POST ) && !empty( $_POST ) ) {

		if ( isset( $_POST['add_coupon'] ) && ($_POST['add_coupon'] == 'true') && (!isset( $_POST['is_edit_coupon'] ) || !($_POST['is_edit_coupon'] == 'true')) ) {

			$coupon_code   = $_POST['add_coupon_code'];
			$discount      = (double)$_POST['add_discount'];
			$discount_type = (int)$_POST['add_discount_type'];
			$use_once      = (int)(bool)$_POST['add_use-once'];
			$is_used      = (int)(bool)$_POST['add_is_used'];
			$every_product = (int)(bool)$_POST['add_every_product'];
			$is_active     = (int)(bool)$_POST['add_active'];
			$start_date    = date( 'Y-m-d', strtotime( $_POST['add_start'] ) ) . " 00:00:00";
			$end_date      = date( 'Y-m-d', strtotime( $_POST['add_end'] ) ) . " 00:00:00";
			$rules         = $_POST['rules'];
			//$add_users	= explode(',', $_POST['add_users']);

			foreach ( $rules as $key => $rule ) {
				foreach ( $rule as $k => $r ) {
					$new_rule[$k][$key] = $r;
				}
			}

			foreach ( $new_rule as $key => $rule ) {
				if ( '' == $rule['value'] ) {
					unset( $new_rule[$key] );
				}
			}

			//if ( $wpdb->query( "INSERT INTO `" . WPSC_TABLE_COUPON_CODES . "` ( `coupon_code` , `value` , `is-percentage` , `use-once` , `is-used` , `active` , `every_product` , `start` , `expiry`, `condition` ) VALUES ( '$coupon_code', '$discount', '$discount_type', '$use_once', '0', '$is_active', '$every_product', '$start_date' , '$end_date' , '" . serialize( $new_rule ) . "' );" ) )
			if ( $wpdb->insert(WPSC_TABLE_COUPON_CODES,
					    array( 'coupon_code' => $coupon_code,
						   'value' => $discount,
						   'is-percentage' => $discount_type,
						   'use-once' => $use_once,
						   'is-used' => $is_used,
						   'active' => $is_active,
						   'every_product' => $every_product,
						   'start' => $start_date,
						   'expiry' => $end_date,
						   'condition' => serialize( $new_rule )),
					    array( '%s', '%d','%d','%d','%d','%d','%s','%s','%s','%s'))) {
				echo "<div class='updated'><p align='center'>" . __( 'Thanks, the coupon has been added.', 'wpsc' ) . "</p></div>";
				$coupon_id = $wpdb->insert_id;
				if (isset($_POST['add_users']) && strpos($_POST['add_users'], ',') !== false)
					$userarray = array_unique(explode(',', $_POST['add_users']));
				else
					$userarray = array($_POST['add_users']);
				foreach($userarray as $user){
					$coupons = get_user_meta($user, 'wpec-customer-coupons', true);
					if(!$coupons)
						$coupons = array();
					$coupons[] = $coupon_id;
					update_user_meta($user, 'wpec-customer-coupons', array_unique($coupons));
				}
			}
		}

		if ( isset( $_POST['is_edit_coupon'] ) && ($_POST['is_edit_coupon'] == 'true') && !(isset( $_POST['delete_condition'] )) && !(isset( $_POST['submit_condition'] )) ) {

			foreach ( (array)$_POST['edit_coupon'] as $coupon_id => $coupon_data ) {

				if ($coupon_id == 'default') {
					update_option('wpec-customerlist-default-reward',$coupon_data);
					return;
				} 

				$coupon_id = (int)$coupon_id;

				if (isset($coupon_data['add_users']) && strpos($coupon_data['add_users'], ',') !== false)
					$userarray = array_unique(explode(',', $coupon_data['add_users']));
				else
					$userarray = array($coupon_data['add_users']);
				foreach($userarray as $user){
					$coupons = get_user_meta($user, 'wpec-customer-coupons',true);
					if(!$coupons)
						$coupons = array();
					$coupons[] = $coupon_id;
					update_user_meta($user, 'wpec-customer-coupons', array_unique($coupons));
				}

				$coupon_data['start']  = $coupon_data['start'] . " 00:00:00";
				$coupon_data['expiry'] = $coupon_data['expiry'] . " 00:00:00";
				$check_values          = $wpdb->get_row( "SELECT `id`, `coupon_code`, `value`, `is-percentage`, `use-once`, `active`, `is-used`, `start`, `expiry`,`every_product` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '$coupon_id'", ARRAY_A );

				// Sort both arrays to make sure that if they contain the same stuff,
				// that they will compare to be the same, may not need to do this, but what the heck
				if ( $check_values != null )
					ksort( $check_values );

				ksort( $coupon_data );

				if ( $check_values != $coupon_data ) {

					$insert_array = array();

					foreach ( $coupon_data as $coupon_key => $coupon_value ) {
						if ( ($coupon_key == "submit_coupon") || ($coupon_key == "delete_coupon") )
							continue;

						if ( isset( $check_values[$coupon_key] ) && $coupon_value != $check_values[$coupon_key] )
							$insert_array[] = "`$coupon_key` = '$coupon_value'";

					}

					if ( isset( $check_values['every_product'] ) && $coupon_data['add_every_product'] != $check_values['every_product'] )
						$insert_array[] = "`every_product` = '$coupon_data[add_every_product]'";

					if ( count( $insert_array ) > 0 )
						$wpdb->query( "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET " . implode( ", ", $insert_array ) . " WHERE `id` = '$coupon_id' LIMIT 1;" );

					unset( $insert_array );
					$rules = $_POST['rules'];

					foreach ( (array)$rules as $key => $rule ) {
						foreach ( $rule as $k => $r ) {
							$new_rule[$k][$key] = $r;
						}
					}

					foreach ( (array)$new_rule as $key => $rule ) {
						if ( $rule['value'] == '' ) {
							unset( $new_rule[$key] );
						}
					}

					$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
					$conditions = unserialize( $conditions );
					$new_cond = array();

					if ( $_POST['rules']['value'][0] != '' ) {
						$new_cond['property'] = $_POST['rules']['property'][0];
						$new_cond['logic'] = $_POST['rules']['logic'][0];
						$new_cond['value'] = $_POST['rules']['value'][0];
						$conditions [] = $new_cond;
					}

					$sql = "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET `condition`='" . serialize( $conditions ) . "' WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1";
					$wpdb->query( $sql );
				}
			}
		}

		if ( isset( $_POST['delete_condition'] ) ) {

			$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
			$conditions = unserialize( $conditions );

			unset( $conditions[(int)$_POST['delete_condition']] );

			$sql = "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET `condition`='" . serialize( $conditions ) . "' WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1";
			$wpdb->query( $sql );
		}

		if ( isset( $_POST['submit_condition'] ) ) {
			$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
			$conditions = unserialize( $conditions );

			$new_cond             = array();
			$new_cond['property'] = $_POST['rules']['property'][0];
			$new_cond['logic']    = $_POST['rules']['logic'][0];
			$new_cond['value']    = $_POST['rules']['value'][0];
			$conditions[]         = $new_cond;

			$sql = "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET `condition`='" . serialize( $conditions ) . "' WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1";
			$wpdb->query( $sql );
		}
	}
	if($user_ID != 'all') {
		//$url = '?page=customers_list';
		//$allurl = add_query_arg('customer_details','all',$url);
		$allurl = add_query_arg(array('customer_lists'=>'all','page'=>'customers_list'));
		$all_coupons_link = sprintf('<a class="button" href="%s">%s</a>',$allurl,__('All'));
	}
?>

	<script type='text/javascript'>
		jQuery(".pickdate").datepicker();
		/* jQuery datepicker selector */
		if (typeof jQuery('.pickdate').datepicker != "undefined") {
			jQuery('.pickdate').datepicker({ dateFormat: 'yy-mm-dd' });
		}
	</script>

	<div class="wrap">
		<h2>
			<?php _e( 'Customer Coupons', 'wpec-customerlist' ); ?>
			<a href="#" id="add_coupon_box_link" class="add_item_link button add-new-h2" onClick="return show_status_box( 'add_coupon_box', 'add_coupon_box_link' );">
				<?php _e( 'Add New', 'wpsc' ); ?>
			</a>&nbsp;<?php echo $all_coupons_link; ?>
		</h2>
		
		<table style="width: 100%;">
			<tr>
				<td id="coupon_data">
					<div id='add_coupon_box' class='modify_coupon' >
						<form name='add_coupon' method='post' action=''>
							<table class='add-coupon' >
								<tr>
									<th><?php _e( 'Coupon Code', 'wpsc' ); ?></th>
									<th><?php _e( 'Discount', 'wpsc' ); ?></th>
									<th><?php _e( 'Start', 'wpsc' ); ?></th>
									<th><?php _e( 'Expiry', 'wpsc' ); ?></th>
								<!--
									<th><?php _e( 'Use Once', 'wpsc' ); ?></th>
									<th><?php _e( 'Used', 'wpec-customerlist' ); ?></th>
									<th><?php _e( 'Active', 'wpsc' ); ?></th>
									<th><?php _e( 'Apply On All Products', 'wpsc' ); ?></th>
									<th><?php _e( 'Users', 'wpec-customerlist' ); ?></th>
								-->

								</tr>
								<tr>
									<td>
										<input type='text' value='' name='add_coupon_code' />
									</td>
									<td>
										<input type='text' value='' size='3' name='add_discount' />
										<select name='add_discount_type'>
											<option value='0' ><?php echo wpsc_get_currency_symbol(); ?></option>
											<option value='1' >%</option>
											<option value='2' ><?php _e( 'Free shipping', 'wpsc' ); ?></option>
										</select>
									</td>
									<td>
										<input type='text' class='pickdate' size='11' value="<?php echo date('Y-m-d'); ?>" name='add_start' />
									</td>
									<td>
										<input type='text' class='pickdate' size='11' name='add_end' value="<?php echo (date('Y-')) . (date('m')+1) . date('-d') ; ?>">
									</td>
									<td>
										<input type='hidden' value='true' name='add_coupon' />
										<input type='submit' value='<?php echo _e('Add Coupon', 'wpec-customerlist'); ?>' name='submit_coupon' class='button-primary' />
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Active', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_active' />
											<input type='checkbox' value='1' checked='checked' name='add_active' />
											<span class='description'><?php _e( 'Activate coupon on creation.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Use Once', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_use-once' />
											<input type='checkbox' value='1' name='add_use-once' />
											<span class='description'><?php _e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Used', 'wpec-customerlist' ); ?></span><input type='hidden' value='0' name='add_is_used' />
											<input type='checkbox' value='1' name='add_is_used' />
											<span class='description'><?php _e( 'Already used', 'wpec-customerlist' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Apply On All Products', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_every_product' />
											<input type="checkbox" value="1" name='add_every_product'/>
											<span class='description'><?php _e('This coupon affects each product of the cart at checkout','wpec-customerlist'); //_e( 'This coupon affects each product at checkout.', 'wpsc' ); ?></span>
										</p>
									</td>
								</tr>
								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Add Users', 'wpec-customerlist' ); ?></span>
											<input type='text' name='add_users' size='11' value='<?php echo $user_ID; ?>' />
											<span class='description'><?php _e( 'Add per-User Coupons.', 'wpec-customerlist' ); ?> <small><?php _e( '(comma separated list of User ID)', 'wpec-customerlist' ); ?></small></span>
										</p>
									</td>
								</tr>
								<tr><td colspan='3'><span id='table_header'><?php _e('Conditions','wpsc'); ?></span></td></tr>
								<tr>
									<td colspan="8">
									<div class='coupon_condition' >
										<div class='first_condition'>
											<select class="ruleprops" name="rules[property][]">
												<option value="item_id" rel="order"><?php _e( 'Item ID', 'wpec-customerlist' ); ?></option>
												<option value="item_name" rel="order"><?php _e( 'Item name', 'wpsc' ); ?></option>
												<option value="item_quantity" rel="order"><?php _e( 'Item quantity', 'wpsc' ); ?></option>
												<option value="total_quantity" rel="order"><?php _e( 'Total quantity', 'wpsc' ); ?></option>
												<option value="subtotal_amount" rel="order"><?php _e( 'Subtotal amount', 'wpsc' ); ?></option>
												<?php //echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>
											</select>

											<select name="rules[logic][]">
												<option value="equal"><?php _e( 'Is equal to', 'wpsc' ); ?></option>
												<option value="greater"><?php _e( 'Is greater than', 'wpsc' ); ?></option>
												<option value="less"><?php _e( 'Is less than', 'wpsc' ); ?></option>
												<option value="contains"><?php _e( 'Contains', 'wpsc' ); ?></option>
												<option value="not_contain"><?php _e( 'Does not contain', 'wpsc' ); ?></option>
												<option value="begins"><?php _e( 'Begins with', 'wpsc' ); ?></option>
												<option value="ends"><?php _e( 'Ends with', 'wpsc' ); ?></option>
												<option value="category"><?php _e( 'In Category', 'wpsc' ); ?></option>
											</select>

											<span><input type="text" name="rules[value][]"/></span>

											<span>
												<script>
													var coupon_number=1;
													function add_another_property(this_button){
														var item_name="<?php esc_js(_e('Item name', 'wpsc')); ?>";
														var item_quantity="<?php esc_js(_e('Item quantity', 'wpsc')); ?>";
														var total_quantity="<?php esc_js(_e('Total quantity', 'wpsc')); ?>";
														var subtotal_amount="<?php esc_js(_e('Subtotal amount', 'wpsc')); ?>";
														var new_property='<div class="coupon_condition">\n'+
															'<div><img height="16" width="16" class="delete" alt="Delete" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" onclick="jQuery(this).parent().remove();"/> \n'+
															'<select class="ruleprops" name="rules[property][]"> \n'+
															'<option value="item_name" rel="order">&#39; '+item_name+'</option> \n'+
															'<option value="item_quantity" rel="order">'+item_quantity+'</option>\n'+
															'<option value="total_quantity" rel="order">'+total_quantity+'</option>\n'+
															'<option value="subtotal_amount" rel="order">'+subtotal_amount+'</option>\n'+
															'<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>'+
															'</select> \n'+
															'<select name="rules[logic][]"> \n'+
															'<option value="equal"><?php esc_js(_e('Is equal to', 'wpsc')); ?></option> \n'+
															'<option value="greater"><?php _e('Is greater than', 'wpsc'); ?></option> \n'+
															'<option value="less"><?php _e('Is less than', 'wpsc'); ?></option> \n'+
															'<option value="contains"><?php _e('Contains', 'wpsc'); ?></option> \n'+
															'<option value="not_contain"><?php _e('Does not contain', 'wpsc'); ?></option> \n'+
															'<option value="begins"><?php _e('Begins with', 'wpsc'); ?></option> \n'+
															'<option value="ends"><?php _e('Ends with', 'wpsc'); ?></option> \n'+
															'</select> \n'+
															'<span> \n'+
															'<input type="text" name="rules[value][]"/> \n'+
															'</span>  \n'+
															'</div> \n'+
															'</div> ';
		
														jQuery('.coupon_condition :first').after(new_property);
														coupon_number++;
													}
												</script>
											</span>
										</div>
									</div>
								</tr>

								<tr>
									<td>
										<a class="wpsc_coupons_condition_add" onclick="add_another_property(jQuery(this));">
											<?php _e( 'Add New Condition', 'wpsc' ); ?>
										</a>
									</td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
		</table>

		<?php
			$columns = array(
				'coupon_code' => __( 'Coupon Code', 'wpsc' ),
				'discount' => __( 'Discount', 'wpsc' ),
				'start' => __( 'Start', 'wpsc' ),
				'expiry' => __( 'Expiry', 'wpsc' ),
				'active' => __( 'Active', 'wpsc' ),
				'is_used' => __( 'Used', 'wpec-customerlist'),
				'apply_on_prods' => __( 'Apply On All Products', 'wpsc' ),
				'add_users' => __( 'Users', 'wpec-customerlist' ),
				'edit' => __( 'Edit', 'wpsc' )
			);
			register_column_headers( 'display-coupon-details', $columns );

				$i = 0;
			if($user_ID == 'all') {
				$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "`", ARRAY_A );
			} else {
				$coupons_array = get_user_meta( $user_ID, 'wpec-customer-coupons', true );
				if(!empty($coupons_array)){
					$coupons_string = implode($coupons_array,',');
					$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE id IN (".$coupons_string.")", ARRAY_A );
				}
			}
	if(!empty($coupon_data)) {

	$users = get_users(array('meta_key' => 'wpec-customer-coupons'));
	$couponlist = array();
	$userlist = array();
	if($users) {
		  foreach($users as $user) {
		      $couponlist[$user->ID] = get_user_meta($user->ID, 'wpec-customer-coupons',true);
//print_r($couponlist[$user->ID]);
		      //if(!empty($couponlist[$user->ID])) {
			  foreach ($couponlist as $couponuser => $couponids) {
				  if(is_array($couponids)){
					   foreach ($couponids as $couponid){
//var_dump($couponid);
						if(empty($userlist))
							$userlist[$couponid] = array();
						elseif(empty($userlist[$couponid]))
							$userlist[$couponid] = array();
						$userlist[$couponid][] = $couponuser;
					   }
				  }else{
					if(empty($userlist[$couponids]))
							$userlist[$couponids] = array();
						$userlist[$couponids][] = $couponuser;
				  }
			  }
		      //}
		  }   
	}
				  ?>
		<table class="coupon-list widefat" cellspacing="0">
			<thead>
				<tr>
					<?php print_column_headers( 'display-coupon-details' ); ?>
				</tr>
			</thead>
			<tbody>
			<?php
					foreach ( (array)$coupon_data as $coupon ) {
						$alternate = "";
						$i++;
						if ( ($i % 2) != 0 ) {
							$alternate = "class='alt'";
						}
						echo "<tr $alternate>\n\r";

						echo "    <td>\n\r";
						esc_attr_e( $coupon['coupon_code'] );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						if ( $coupon['is-percentage'] == 1 )
							echo esc_attr( $coupon['value'] ) . "%";

						else if ( $coupon['is-percentage'] == 2 )
							echo __("Free Shipping", 'wpsc');

						else
							echo wpsc_currency_display( esc_attr( $coupon['value'] ) );

						echo "    </td>\n\r";

						echo "    <td>\n\r";
						
						$coupon_start_time = strtotime( esc_attr( $coupon['start'] ) );
						$coupon_expiry_time = strtotime( esc_attr( $coupon['expiry'] ) );

						if(empty($coupon_start_time)||$coupon_start_time==0)
							echo '/';
						else
							echo date( "d/m/Y", strtotime( esc_attr( $coupon['start'] ) ) );
						echo "    </td>\n\r";

						echo "    <td>\n\r";

						if(empty($coupon_expiry_time)||$coupon_expiry_time==0)
							echo '/';
						else
							echo date( "d/m/Y", strtotime( esc_attr( $coupon['expiry'] ) ) );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['active'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['is-used'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['every_product'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						echo "    <td>\n\r";
						//esc_attr_e( implode($userlist[$coupon['id']],',') );
						if(!empty($userlist[$coupon['id']])){
							$userurl = add_query_arg(array('users_per_coupon'=>$coupon['id'], 'page'=>'customers_list'),'users.php');
							echo sprintf('<a href="%s">%s</a>',$userurl,count(array_unique($userlist[$coupon['id']])));
							//echo '<a href="">'.count(array_unique($userlist[$coupon['id']])).'</a>';
						} else
							echo '0';
						echo "    </td>\n\r";

						echo "    </td>\n\r";
						echo "    <td>\n\r";
						echo "<a title='" . esc_attr( $coupon['coupon_code'] ). "' href='#' rel='" . $coupon['id'] . "' class='wpsc_edit_coupon'  >" . __( 'Edit', 'wpsc' ) . "</a>";
						echo "    </td>\n\r";
						echo "  </tr>\n\r";
						echo "  <tr class='coupon_edit'>\n\r";
						echo "    <td colspan='8' style='padding-left:0px;'>\n\r";
						echo "      <div id='coupon_box_" . $coupon['id'] . "' class='displaynone modify_coupon' >\n\r";
						customer_coupon_edit_form( $coupon );
						echo "      </div>\n\r";
						echo "    </td>\n\r";
						echo "  </tr>\n\r";
					}
				?>
			</tbody>
		</table>
	<?php } ?>
	</div>

<?php

}
?>