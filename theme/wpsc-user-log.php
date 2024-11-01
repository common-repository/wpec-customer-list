<?php
/**
 * The User Account Theme.
 *
 * Displays everything within the user account.  Hopefully much more useable than the previous implementation.
 *
 * @todo This basically shows 'screens' for each of the following: Purchase History, Your Details, Downloads.  Could argue that these should be separate templates.
 *
 * @package WPSC
 * @since WPSC 3.8
 */
global $files, $separator, $purchase_log, $col_count, $products, $links; ?>

<div class="wrap login">
	<div class="user-profile-links">
		<ul>
	<?php if (isset($_GET['userlogid'])) : ?>
		<li><a href="<?php echo get_option( 'user_account_url' ) ?>"><?php _e('Purchase History','wpsc'); ?></a></li>
	<?php else: ?>
		<li><a href="<?php echo admin_url( 'profile.php' ); ?>"><?php _e( 'Profile', 'theme-my-login' ); ?></a></li>		
	<?php endif; ?>
		<?php do_action('wpsc_additional_user_profile_links', '|'); ?>
		</ul>
	</div>

	<br class="clear" />
	<!-- 	START OF PROFILE PAGE -->
	<?php if ( is_wpsc_profile_page() ) : ?>

		<form method="post">

			<?php echo validate_form_data(); ?>

			<table>

				<?php wpsc_display_form_fields(); ?>

				<tr>
					<td></td>
					<td>
						<input type="hidden" value="true" name="submitwpcheckout_profile" />
						<input type="submit" value="<?php _e( 'Save Profile', 'wpsc' ); ?>" name="submit" />
					</td>
				</tr>
			</table>
		</form>
	
	<!-- 	START OF DOWNLOADS PAGE -->
	<?php elseif ( is_wpsc_downloads_page() ) : ?>

		<?php if ( wpsc_has_downloads() ) : ?>

				<table class="logdisplay">
					<tr>
						<th><?php _e( 'File Names', 'wpsc' ); ?> </th>
						<th><?php _e( 'Downloads Left', 'wpsc' ); ?> </th>
						<th><?php _e( 'Date', 'wpsc' ); ?> </th>
					</tr>

					<?php
						$i = 0;
						foreach ( (array)$files as $file ) :

							$alternate = "";

							if ( ( $i % 2 ) != 1 )
								$alternate = "class='alt'";
					?>

							<tr <?php echo $alternate; ?>>
								<td>
					<?php
						if ( $products[$i]['downloads'] > 0 )
						
							echo "<a href = " . get_option('siteurl')."?downloadid=".$products[$i]['uniqueid'] . ">" . $file['post_title'] . "</a>";
						else
							echo $file['post_title'] . "";

					?>

								</td>
								<td><?php echo $products[$i]['downloads']; ?></td>
								<td><?php echo date( get_option( "date_format" ), strtotime( $products[$i]['datetime'] ) ); ?></td>
							</tr>
					<?php
							$i++;
						endforeach;
					?>

				</table>
		<?php else : ?>

			<?php _e( 'You have not purchased any downloadable products yet.', 'wpsc' ); ?>

		<?php endif; ?>
	<!-- 	START OF PURCHASE HISTORY PAGE -->
	<?php else : ?>
		
		<?php if ( is_user_logged_in() ) : ?>

			<?php if ( wpsc_has_purchases() ) : ?>

			<?php if (!isset($_GET['userlogid'])) : ?>

<!-- 	START OF REWARD SECTION -->
	<?php
	if (class_exists('Customers_List_Table') && get_option('customer_reward_show') == 1){
	global $wpdb, $current_user;
	get_currentuserinfo();
	if ( $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPSC_CUSTOMERS_LIST . "` WHERE `ID`='".$current_user->ID."'" ) != 0 )
		$customer_details = $wpdb->get_results( "SELECT * FROM ".WPSC_CUSTOMERS_LIST." as customer WHERE `ID`='".$current_user->ID."'", ARRAY_A );
	$purchases = $customer_details[0];
	$rwd_pur = get_option('customer_reward_pur');
	$rwd_itm = get_option('customer_reward_itm');
	$rwd_amt = get_option('customer_reward_amt');
	$multiplied = array();
	if($rwd_pur != 0 && $purchases['orders'] % $rwd_pur == 0 && $purchases['orders'] / $rwd_pur >= 1)
		$purclass = 'class="reward"';
	if($purchases['orders'] - $rwd_pur <= 0 || $rwd_pur == 0)
		$multiplied['orders'] = 1 * $rwd_pur;
	else
		$multiplied['orders'] = ceil($purchases['orders'] / $rwd_pur) * $rwd_pur;
	if($rwd_itm != 0 && $purchases['items'] % $rwd_itm == 0 &&  $purchases['items'] / $rwd_itm >= 1)
		$itmclass = 'class="reward"';
	if($purchases['items'] - $rwd_itm <= 0 || $rwd_itm == 0)
		$multiplied['items'] = 1 * $rwd_itm;
	else
		$multiplied['items'] = ceil($purchases['items'] / $rwd_itm) * $rwd_itm;
	if($rwd_amt != 0 && $purchases['amount'] % $rwd_amt == 0 &&  $purchases['amount'] / $rwd_amt >= 1)
		$amtclass = 'class="reward"';
	if($purchases['amount'] - $rwd_amt <= 0 || $rwd_amt == 0)
		$multiplied['amount'] = 1 * $rwd_amt;
	else
		$multiplied['amount'] = ceil($purchases['amount'] / $rwd_amt) * $rwd_amt;
	//print_r($purchases);
	?>
	<h4><?php _e('Summary', 'wpec-customerlist'); ?></h4>
		<table width="33%" style="text-align:center;margin:10px 0;">
			<tbody>
				<tr class="toprow">
					<td width="33%"><strong><?php _e('Orders', 'wpec-customerlist'); ?></strong></td>
					<td width="33%"><strong><?php _e('Items', 'wpec-customerlist'); ?></strong></td>
					<td width="33%"><strong><?php _e('Amount', 'wpec-customerlist'); ?></strong></td>
				</tr>
				<tr class="alt">
					<td width="33%" <?php echo $purclass; ?>><?php echo $purchases['orders']; if($rwd_pur != 0) echo ' / '.$multiplied['orders']; ?></td>
					<td width="33%" <?php echo $itmclass; ?>><?php echo $purchases['items']; if($rwd_itm != 0) echo ' / '.$multiplied['items']; ?></td>
					<td width="33%" <?php echo $amtclass; ?>><?php echo $purchases['amount']; if($rwd_amt != 0) echo ' / '.$multiplied['amount']; echo wpsc_get_currency_symbol() ?></td>
				</tr>
			</tbody>
		</table>
				<?php if($rwd_pur != 0 || $rwd_itm != 0 || $rwd_amt != 0): ?>
						<?php if( ($rwd_pur != 0 && $multiplied['orders']==$purchases['orders']) || ($rwd_itm != 0 && $multiplied['items']==$purchases['items']) || ($rwd_amt != 0 && $multiplied['amount']==$purchases['amount']) ){
								echo '<p class="reward">';
								echo '<strong>'.__('Congratulations! You got a reward coupon','wpec-customerlist').'</strong>';
								echo '</p>';
						      }
						      if($rwd_pur != 0 && $multiplied['orders']!=$purchases['orders']){
								echo '<p class="advice">';
								echo __('You need only','wpec-customerlist');
								$difference = ($multiplied['orders']-$purchases['orders']);
								echo ' <strong>'.$difference.' ';
								echo _nx( 'Order more', 'Orders more', $difference, 'userlog detail', 'wpec-customerlist' ).'</strong> ';
								echo __('to get a reward coupon','wpec-customerlist');
								echo '</p>';
						      }
						      if($rwd_itm != 0 && $multiplied['items']!=$purchases['items']){
								echo '<p class="advice">';
								echo __('You need only','wpec-customerlist');
								$difference = ($multiplied['items']-$purchases['items']);
								echo ' <strong>'.$difference.' ';
								echo _nx( 'Item more', 'Items more', $difference, 'userlog detail', 'wpec-customerlist' ).'</strong> ';
								echo __('to get a reward coupon','wpec-customerlist');
								echo '</p>';
						      }
						      if($rwd_amt != 0 && $multiplied['amount']!=$purchases['amount']){
								echo '<p class="advice">';
								echo __('You need only','wpec-customerlist');
								$difference = ($multiplied['amount']-$purchases['amount']);
								echo ' <strong>'.$difference.' ';
								echo __( 'more amount spent', 'wpec-customerlist' ).'</strong> ';
								echo __('to get a reward coupon','wpec-customerlist');
								echo '</p>';
						      }
							?>
				<?php endif; ?>
	<?php 
	//IF AUTOGENERATED COUPON REWARDS ACTIVE SHOW LIST BY USER META
	$coupons = get_user_meta($current_user->ID, 'wpec-customer-coupons', true);
	if(!empty($coupons)){
		if(count($coupons)>1)
			$coupons_string = 'IN ('.implode($coupons,', ').')';
		else
			$coupons_string = '= '.$coupons[0];
			//$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` ".$coupons_string." AND ( `active` = '1' OR `is-used` = '1' )", ARRAY_A );
			$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` ".$coupons_string." AND ( `active` = '1' OR `is-used` = '1' )", ARRAY_A );
			}
//print_r($coupon_data);
	if(!empty($coupon_data)) {
		$columns = array(
				'coupon_code' => __( 'Coupon Code', 'wpsc' ),
				'discount' => __( 'Discount', 'wpsc' ),
				'start' => __( 'Start', 'wpsc' ),
				'expiry' => __( 'Expiry', 'wpsc' ),
				'active' => __( 'Available', 'wpec-customerlist'),
				//'apply_on_prods' => __( 'Apply On All Products', 'wpsc' ),
			);
		//register_column_headers( 'display-coupon-details', $columns );
		?>
		<h4><?php _e('Customer Coupons', 'wpec-customerlist'); ?></h4>
		<table class="coupon-list widefat" cellspacing="0" width="100%">
			<thead>
				<tr class="toprow">
				    <th><strong><?php echo $columns['coupon_code']; ?></strong></th>
				    <th><strong><?php echo $columns['discount']; ?></strong></th>
				    <th><strong><?php echo $columns['start']; ?></strong></th>
				    <th><strong><?php echo $columns['expiry']; ?></strong></th>
				    <th><strong><?php echo $columns['active']; ?></strong></th>
				    <th><strong><?php //echo $columns['apply_on_prods']; ?></strong></th>
					<?php //print_column_headers( 'display-coupon-details' );
					      ?>
				</tr>
			</thead>
			<tbody>
			<?php
					$now =  strtotime( date_i18n( 'Y-m-j H:i:s' ) );
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
						echo date( "d/m/Y", strtotime( esc_attr( $coupon['start'] ) ) );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						$end_timestamp = strtotime( esc_attr( $coupon['expiry'] ) );
						if($now < $end_timestamp)
							echo date( "d/m/Y", strtotime( esc_attr( $coupon['expiry'] ) ) );
						else
							echo '<span class="expired">'.date( "d/m/Y", strtotime( esc_attr( $coupon['expiry'] ) ) ).'</span>';
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['active'] ) {
							case 1:
								if($now < $end_timestamp)
									echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								else
									echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						/*
						switch ( $coupon['every_product'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						*/
						echo "    <td>\n\r";
						echo "  </tr>\n\r";
					}
				?>
			</tbody>
		</table>
		<br />
	<?php }
}
?>
<?php endif; ?>
				<h4><?php _e('Orders', 'wpec-customerlist'); ?></h4>
				<table class="logdisplay">

				<?php if ( wpsc_has_purchases_this_month() || isset($_GET['userlogid']) ) : ?>
					
						<tr class="toprow">
							<td><strong><?php _e( 'Status', 'wpsc' ); ?></strong></td>
							<td><strong><?php _e( 'Date', 'wpsc' ); ?></strong></td>
							<td><strong><?php _e( 'Price', 'wpsc' ); ?></strong></td>

							<?php if ( get_option( 'payment_method' ) == 2 ) : ?>

								<td><strong><?php _e( 'Payment Method', 'wpsc' ); ?></strong></td>

							<?php endif; ?>

						</tr>

						<?php wpsc_user_details(); ?>

				<?php else : ?>

						<tr>
							<td colspan="<?php echo $col_count; ?>">

								<?php _e( 'No transactions for this month.', 'wpsc' ); ?>

							</td>
						</tr>

				<?php endif; ?>

				</table>

			<?php else : ?>

				<table>
					<tr>
						<td><?php _e( 'There have not been any purchases yet.', 'wpsc' ); ?></td>
					</tr>
				</table>

			<?php endif; ?>

		<?php else : ?>

			<?php _e( 'You must be logged in to use this page. Please use the form below to login to your account.', 'wpsc' ); ?>

			<form name="loginform" id="loginform" action="<?php echo wp_login_url(); ?>" method="post">
				<p>
					<label><?php _e( 'Username:', 'wpsc' ); ?><br /><input type="text" name="log" id="log" value="" size="20" tabindex="1" /></label>
				</p>

				<p>
					<label><?php _e( 'Password:', 'wpsc' ); ?><br /><input type="password" name="pwd" id="pwd" value="" size="20" tabindex="2" /></label>
				</p>

				<p>
					<label>
						<input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" />
						<?php _e( 'Remember me', 'wpsc' ); ?>
					</label>
				</p>

				<p class="submit">
					<input type="submit" name="submit" id="submit" value="<?php _e( 'Login &raquo;', 'wpsc' ); ?>" tabindex="4" />
					<input type="hidden" name="redirect_to" value="<?php echo get_option( 'user_account_url' ); ?>" />
				</p>
			</form>

		<?php endif; ?>

	<?php endif; ?>

</div>
