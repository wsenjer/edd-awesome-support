<?php
/**
 * Plugin Name:     Easy Digital Downloads - Awesome Support
 * Plugin URI:      https://wpruby.com
 * Description:     EDD Support for Awesome Support.
 * Version:         1.0.1
 * Author:          Waseem Senjer
 * Author URI:      https://waseem-senjer.com
 * Text Domain:     edd-awesome-support
 *
 * @author          Waseem Senjer <waseem.senjer@gmail.com>
 * @copyright       Copyright (c) 2014, Waseem Senjer
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EDD_Awesome_Support{

	public function __construct(){
		add_action('edd_purchase_history_header_after', array($this, 'add_support_column'),99);
		add_action('edd_purchase_history_row_end', array($this, 'add_support_cell'), 99, 2);
		add_action('add_meta_boxes', array($this, 'register_meta_boxes') );
		if(is_admin() && isset($_GET['post'])){
			$user_id = intval(get_post_field('post_author', intval($_GET['post'])));
		}else{
			$user_id = get_current_user_id();
		}

		if($user_id != 0){
			$purchases = edd_get_users_purchases( $user_id );
			$options = array();
			$options['-1'] = __('General Enquiry','edd-awesome-support');
			if(is_array($purchases)){
				foreach($purchases as $post){
					$purchase_data = edd_get_payment_meta( $post->ID );
					$payment_number = edd_get_payment_number( $post->ID );
					$options[  $payment_number  ] = '#'. $payment_number. ' '.  date_i18n( get_option('date_format'), strtotime( get_post_field( 'post_date', $post->ID ) ) ).' - '.edd_currency_filter( edd_format_amount( edd_get_payment_amount( $post->ID ) ) ); ;
				}
			}
			if(function_exists('wpas_add_custom_field')){
				wpas_add_custom_field( 'order_number',
			 		array( 
			 			'title' => 'Order Number',
				 		'label' => 'Order Number', 
				 		'label_plural' => 'Order Numbers',
				 		'field_type' => 'select',
				 		'required'	 => true,
				 		'options' => $options,
			 		));
			}
		}

	}


	public function add_support_column(){
		echo '<th class="edd_purchase_support">'.__('Support','edd-awesome-support' ).'</th>';
	}

	public function add_support_cell($postID, $purchase_data){
		echo '<td class="edd_support"><a href="/submit-ticket/">'.__('Get Help','edd-awesome-support').'</a></td>';
	}

	public function register_meta_boxes() {
    	add_meta_box( 'order_details', __( 'Order Details', 'edd-awesome-support' ), array($this, 'display_metabox'), 'ticket', 'side', 'high' );
	}
 

	public function display_metabox( $post ) {
	    // Display code/markup goes here. Don't forget to include nonces!
	    $order_number = get_post_meta($post->ID, '_wpas_order_number', true);
		if($order_number != ""){
			$purchase_data = edd_get_payment_meta( $order_number );
			$licence_id  = EDD_Software_Licensing::instance()->get_license_by_key($purchase_data['key']);
			$status = EDD_Software_Licensing::instance()->get_license_status( $licence_id );
			$payment_id   = absint( $order_number );
			$payment      = new EDD_Payment( $payment_id );		
			$number         = $payment->number;
			$payment_meta   = $payment->get_meta();
			$cart_items     = $payment->cart_details;
			$user_id        = $payment->user_id;
			$user_info      = edd_get_payment_meta_user_info( $payment_id );
			$customer       = new EDD_Customer( $payment->customer_id );

			$licenses = EDD_Software_Licensing::instance()->get_licenses_of_purchase( $payment_id );

	?>
			<table class="widefat posts">
				<tr>
					<th>Order Details</th>
					<td><a target="_blank" href="<?php echo admin_url('edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $order_number); ?>">View Order</a></td>
				</tr>
				<tr>
					<th>Purchase Date</th>
					<td><?php echo $payment->date; ?></td>
				</tr>
				<tr>
					<th>Status</th>
					<td><span style="color:#fff;padding:3px;background:#609E60;"><?php echo $status; ?></span></td>
				</tr>
				<tr>
					<th>Payment Email</th>
					<td><?php echo $customer->email; ?></td>
				</tr>
				<?php foreach($cart_items as $item): ?>
					<tr>
						<th>Item</th>
						<td><?php echo $item['name']; ?></td>
					</tr>			
				<?php endforeach; ?>
			</table>
				<div id="edd-payment-licenses" class="postbox">
		<h3 class="hndle"><?php _e( 'License Keys', 'edd_sl' ); ?></h3>
		<div class="inside">
			<?php if( $licenses ) : ?>
				<table class="wp-list-table widefat fixed" cellspacing="0">
					<tbody id="the-list">
						<?php
						$i = 0;
						foreach ( $licenses as $key => $license ) :
							$key            = get_post_meta( $license->ID, '_edd_sl_key', true );
							$status         = EDD_Software_Licensing::instance()->get_license_status(  $license->ID );
							$status_display = '<span class="edd-sl-' . esc_attr( $status ) . '">' . esc_html( $status ) . '</span>';
							?>
							<tr class="<?php if ( $i % 2 == 0 ) { echo 'alternate'; } ?>">
								<td class="name column-name">
									<?php echo $license->post_title; ?>
								</td>
								<td class="price column-key">
									<a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-licenses&s=' . $key ); ?>" title="<?php _e( 'View License Key', 'edd_sl' ); ?>">
										<?php echo $key; ?>
									</a> - <?php echo $status_display; ?>
								</td>
							</tr>
							<?php
							$i++;
						endforeach;
						?>
					</tbody>
				</table>
			<?php endif; ?>
		</div><!-- /.inside -->
	</div><!-- /#edd-payment-licenses -->
							<table class="wp-list-table widefat fixed">
							<h3 class="hndle"><?php _e( 'Domains', 'edd_sl' ); ?></h3>

							<?php
		 				if( $licenses ) :

						foreach ( $licenses as $key => $license ) :

							$sites = EDD_Software_Licensing::instance()->get_sites( $license->ID );
							if( ! empty( $sites ) ) :
								$i = 0;
								foreach( $sites as $site ) : ?>
								<?php $site_url = strpos( $site, 'http' ) !== false ? $site : 'http://' . $site; ?>
								<tr class="row">
									<td><a href="<?php echo $site_url; ?>" target="_blank"><?php echo $site; ?></a></td>
								</tr>
								<?php
								$i++;
								endforeach;
							else : ?>
							<tr class="row"><td colspan="2"><?php _e( 'This license has not been activated on any sites', 'edd_sl' ); ?></td></tr>
							<?php endif; ?>
							<?php endforeach; ?>
							<?php endif; ?>

							</table>
			<?php
		} else{ 
			echo 'General Enquiry'; 
		}
	}

}


/**
 * The main function responsible for returning the one true EDD_Awesome_Support
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Awesome_Support The one true EDD_Awesome_Support
 */
function edd_awesome_support() {

	return new EDD_Awesome_Support();
}
add_action( 'plugins_loaded', 'edd_awesome_support' );


add_action('admin_bar_menu', 'wpruby_add_kelly_switch_link', 100);
function wpruby_add_kelly_switch_link($admin_bar){
	if(class_exists('user_switching')){
		if(current_user_can('manage_options')){
			$kelly = new WP_User(1328);
			$switch_link =  user_switching::maybe_switch_url( $kelly );

		    $admin_bar->add_menu( array(
		        'id'    => 'switch-to-kelly',
		        'title' => 'Switch to Kelly',
		        'href'  => $switch_link,
		        'meta'  => array(
		            'title' => __('Switch to Kelly'),            
		        ),
		    ));	
		}

	}
}