<?php
/**
 * Plugin Name: WC Vendors Pro Simple Auctions
 * Plugin URI: http://www.wcvendors.com/wc-vendors-simple-auctions/ 
 * Description: Add WooCommerce simple auctions support to WC Vendors Pro 
 * Version: 1.0.0
 * Author:            WC Vendors
 * Author URI:        http://www.wcvendors.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wcvendors-pro-simple-auctions
 * Domain Path:       /languages
 *
 * @link              http://www.wcvendors.com
 * @since             1.0.0
 * @package           WCVendors_Pro_Simple_Auctions 
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Vendors_Simple_Auctions' ) ) :

class WC_Vendors_Simple_Auctions { 

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin public actions.
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		

		// Checks if WC Vendors Pro is installed 
		if ( class_exists( 'WCVendors_Pro' ) ) {

			// Checks that Simple Auctions is active 
			if ( class_exists( 'WooCommerce_simple_auction' ) ){ 

				add_action( 'wcv_save_product_meta', array( $this, 'simple_auctions_meta_save' ) ); 
				add_filter( 'wcv_product_meta_tabs', array( $this, 'simple_auction_meta_tab' ) );
				add_action( 'wcv_after_shipping_tab', array( $this, 'simple_auctions_form' ) ); 

			} else { 
				add_action( 'admin_notices', array( $this, 'simple_auctions_missing_notice' ) );
			}

			
		} else {

			add_action( 'admin_notices', array( $this, 'wcvendors_pro_missing_notice' ) );
		}

	} // __construct()

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0 
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	} // get_instance()


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0 
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wcvendors-pro-simple-auctions' );

		load_textdomain( 'wcvendors-pro-simple-auctions', trailingslashit( WP_LANG_DIR ) . 'wc-vendors/wcvendors-pro-simple-auctions-' . $locale . '.mo' );
		load_plugin_textdomain( 'wcvendors-pro-simple-auctions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	} // load_plugin_textdomain() 

	/**
	 * Display the WC Vendors missing admin notice
	 *
	 * @since 1.0.0 
	 */
	public function wcvendors_pro_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WC Vendors Pro Simple Auctions requires WC Vendors Pro to function.', 'wcvendors-pro-simple-auctions' ) ) . '</p></div>';
	} // wcvendors_pro_missing_notice()

	/**
	 * Display the WooCommerce Simple Auctions missing admin notice
	 *
	 * @since 1.0.0 
	 */
	public function simple_auctions_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WC Vendors Pro Simple Auctions requires WooCommerce Simple Auctions to function.', 'wcvendors-pro-simple-auctions' ) ) . '</p></div>';
	}  // simple_auctions_missing_notice()


	/**
	 * Hook into the product meta save for the auction 
	 *
	 * @since 1.0.0 
	*/
	public function simple_auctions_meta_save( $post_id ) { 

		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST[ 'product-type' ] ) );
		
		if ( $product_type == 'auction' ) {

		 	update_post_meta( $post_id, '_manage_stock', 'yes'  );
		 	update_post_meta( $post_id, '_stock', '1'  );
		 	update_post_meta( $post_id, '_backorders', 'no'  );
			update_post_meta( $post_id, '_sold_individually', 'yes'  );

			if ( isset($_POST['_auction_item_condition'])) 
				update_post_meta( $post_id, '_auction_item_condition', stripslashes( $_POST['_auction_item_condition'] ) );
			if ( isset($_POST['_auction_type'])) 
				update_post_meta( $post_id, '_auction_type', stripslashes( $_POST['_auction_type'] ) );
			if ( isset($_POST['_auction_proxy'])){
				update_post_meta( $post_id, '_auction_proxy', stripslashes( $_POST['_auction_proxy'] ) );
			} else {
				delete_post_meta( $post_id, '_auction_proxy' );	
			}
			if (isset($_POST['_auction_start_price']))
			update_post_meta( $post_id, '_auction_start_price', stripslashes( $_POST['_auction_start_price'] ) );
			if (isset($_POST['_auction_bid_increment']))
				update_post_meta( $post_id, '_auction_bid_increment', stripslashes( $_POST['_auction_bid_increment'] ) );
			if (isset($_POST['_auction_reserved_price']))
				update_post_meta( $post_id, '_auction_reserved_price', stripslashes( $_POST['_auction_reserved_price'] ) );
			if (isset($_POST['_regular_price']))
				update_post_meta( $post_id, '_regular_price', stripslashes( $_POST['_regular_price'] ) );
			if (isset($_POST['_auction_dates_from']))
				update_post_meta( $post_id, '_auction_dates_from', stripslashes( $_POST['_auction_dates_from'] ) );
			if (isset($_POST['_auction_dates_to']))
				update_post_meta( $post_id, '_auction_dates_to', stripslashes( $_POST['_auction_dates_to'] ) );
	        
	        if(isset($_POST['_relist_auction_dates_from']) && isset($_POST['_relist_auction_dates_to']) && !empty($_POST['_relist_auction_dates_from']) && !empty($_POST['_relist_auction_dates_to']) ){
	           $this->do_relist($post_id, $_POST['_relist_auction_dates_from'], $_POST['_relist_auction_dates_to']);
	            
	        }
	        if (isset($_POST['_auction_automatic_relist']))
	        	update_post_meta( $post_id, '_auction_automatic_relist', stripslashes( $_POST['_auction_automatic_relist'] ) );
	        if (isset($_POST['_auction_relist_fail_time']))
				update_post_meta( $post_id, '_auction_relist_fail_time', stripslashes( $_POST['_auction_relist_fail_time'] ) );
			if (isset($_POST['_auction_relist_not_paid_time']))
				update_post_meta( $post_id, '_auction_relist_not_paid_time', stripslashes( $_POST['_auction_relist_not_paid_time'] ) );
			if (isset($_POST['_auction_relist_duration']))
				update_post_meta( $post_id, '_auction_relist_duration', stripslashes( $_POST['_auction_relist_duration'] ) );
	    	}

	} // simple_auctions_meta_save() 


	/**
	 * Hook into the product meta save for the auction 
	 *
	 * @since 1.0.0 
	*/
	public function simple_auction_meta_tab( $tabs ) { 

		$tabs[ 'simple_auction' ]  = array( 
					'label'  => __( 'Auction', 'wcvendors-pro-simple-auctions' ), 
					'target' => 'auction',
					'class'  => array( 'auction_tab', 'show_if_auction', 'hide_if_grouped', 'hide_if_external', 'hide_if_variable', 'hide_if_simple' ),
				);

		return $tabs; 
	
	} // simple_auction_meta_tab() 

	/**
	 * Output the auction tab i=on the product-edit template. 
	 *
	 * @since 1.0.0 
	*/
	public function simple_auctions_form( $post_id ){ 

		echo '<div class="wcv-product-auction auction_product_data tabs-content" id="auction">'; 

		// Item Condition
		WCVendors_Pro_Form_Helper::select( apply_filters( 'wcv_simple_auctions_item_condition', array( 
				'post_id'			=> $post_id, 
				'id' 				=> '_auction_item_condition', 
				'class'				=> 'select2',
				'label'	 			=> __( 'Item Condition', 'wc_simple_auctions' ), 
				'desc_tip' 			=> 'true', 
				'description' 			=> sprintf( __( 'The condition of the item you are selling', 'wcvendors-pro-simple-auctions' ) ), 
				'wrapper_start' 		=> '<div class="all-100">',
				'wrapper_end' 			=> '</div>', 
				'options' 			=> array( 'new' => __('New', 'wc_simple_auctions'), 'used'=> __('Used', 'wc_simple_auctions') )
				) )
		);

		// Type of Auction
		WCVendors_Pro_Form_Helper::select( apply_filters( 'wcv_simple_auctions_auction_type', array(
                'post_id'                       => $post_id,
                'id'                            => '_auction_type',
                'class'                         => 'select2',
                'label'                         => __( 'Auction Type', 'wc_simple_auctions' ),
                'desc_tip'                      => 'true',
                'description'                   => sprintf( __( 'Type of Auction - Normal prefers high bidders, reverse prefers low bids to win.', 'wcvendors-pro-simple-auctions' ) ),                                             
                'wrapper_start'                 => '<div class="all-100">',
                'wrapper_end'                   => '</div>',
                'options'                       => array( 'normal' => __('Normal', 'wc_simple_auctions'), 'reverse'=> __('Reverse', 'wc_simple_auctions') )
                ) )
        );

		// Proxy Options
		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_proxy_bidding', array( 
			'post_id'			=> $post_id, 
			'id' 				=> '_auction_proxy', 
			'label' 			=> __( 'Enable proxy bidding', 'wc_simple_auctions' ), 
			'type' 				=> 'checkbox' 
			) )
		);

		// Auction Start Price
		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_start_price', array( 
			'post_id'		=> $post_id, 
			'id' 			=> '_auction_start_price', 
			'label' 		=> __( 'Start Price', 'wc_simple_auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
			'data_type' 		=> 'price', 
			'wrapper_start' 	=> '<div class="wcv-cols-group wcv-horizontal-gutters"><div class="all-50 small-100">', 
			'wrapper_end' 		=>  '</div></div>'
			) )
		);

		// Auction Bid Increment
		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_bid_increment', array(
                'post_id'               => $post_id,
                'id'                    => '_auction_bid_increment',
                'label'                 => __( 'Bid increment', 'wc_simple_auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
                'data_type'             => 'price',
                'wrapper_start'         => '<div class="wcv-cols-group wcv-horizontal-gutters"><div class="all-50 small-100">',
                'wrapper_end'           =>  '</div></div>'
                ) )
        );

		// Reserve Price (note the keys are reserved not reserve, as is the auction developers code)
		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_reserved_price', array(
	            'post_id'               => $post_id,
	            'id'                    => '_auction_reserved_price',
	            'label'                 => __( 'Reserve price', 'wc_simple_auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
	            'data_type'             => 'price',
	            'wrapper_start'         => '<div class="wcv-cols-group wcv-horizontal-gutters"><div class="all-50 small-100">',
	            'wrapper_end'           =>  '</div></div>'
	            ) )
	    );

		// Buy it Now Price
		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_buy_it_now_price', array(
	            'post_id'               => $post_id,
	            'id'                    => '_regular_price',
	            'label'                 => __( 'Buy it now price', 'wc_simple_auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
	            'data_type'             => 'price',
	            'wrapper_start'         => '<div class="wcv-cols-group wcv-horizontal-gutters"><div class="all-50 small-100">',
	            'wrapper_end'           =>  '</div></div>'
	            ) )
	    );

	 
		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_start_date', array( 
			'post_id'		=> $post_id, 
			'id' 			=> '_auction_dates_from', 
			'label' 		=> __( 'From', 'wcvendors-pro-simple-auctions' ), 
			'class'			=> 'wcv-datepicker', 
			'placeholder'	=> __( 'From&hellip;', 'placeholder', 'wcvendors-pro-simple-auctions' ). ' YYYY-MM-DD',  
			'wrapper_start' => '<div class="wcv-cols-group wcv-horizontal-gutters"><div class="all-50 small-100 sale_price_dates_fields">',
			'wrapper_end' 	=> '</div>', 
			'custom_attributes' => array(
				'maxlenth' 	=> '10', 
				'pattern' 	=> '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])'
				),
			) )
		);

		WCVendors_Pro_Form_Helper::input( apply_filters( 'wcv_simple_auctions_end_date', array( 
			'post_id'			=> $post_id, 
			'id' 				=> '_auction_dates_to', 
			'label' 			=> __( 'To', 'wcvendors-pro-simple-auctions' ), 
			'class'				=> 'wcv-datepicker', 
			'placeholder'		=> __( 'To&hellip;', 'placeholder', 'wcvendors-pro-simple-auctions' ). ' YYYY-MM-DD', 
			'wrapper_start' 	=> '<div class="all-50 small-100 sale_price_dates_fields">',
			'wrapper_end' 		=> '</div></div>', 
			'desc_tip'			=> true, 
			'custom_attributes' => array(
				'maxlenth' 		=> '10', 
				'pattern' 		=> '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])'
				),
			) )
		);


		/* OLD SHIT -- The dates below work but they are not using the pro form helper.
		 * Next, notice the code below has functionality for a "relist" product.  A relist button would be very useful and for sure requested.
		 * Third, the vendor store does not show any auctions
		 * And last but not least, once an auction is added, they dont show up on the dashboard > products page to edit them.
		 */
						
		// echo '<p class="form-field auction_dates_fields">
		// 	<label for="_auction_dates_from">' . __( 'Auction Dates', 'wc_simple_auctions' ) . '</label>
		// 	<input type="text" class="short datetimepicker" name="_auction_dates_from" id="_auction_dates_from" value="' . $auction_dates_from . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'wc_simple_auctions' ) . ' YYYY-MM-DD HH:MM" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
		// 	<input type="text" class="short datetimepicker" name="_auction_dates_to" id="_auction_dates_to" value="' . $auction_dates_to . '" placeholder="' . _x( 'To&hellip;', 'placeholder', 'wc_simple_auctions' ) . '  YYYY-MM-DD HH:MM" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
		// 	</p>';
		                
		// if ($product->auction_closed  && !$product->auction_payed ){
		//         echo '<p class="form-field relist_dates_fields"><a class="button relist" href="#" id="relistauction">'.__('Relist','wc_simple_auctions').'</a></p>
		//                            <p class="form-field relist_auction_dates_fields"> <label for="_relist_auction_dates_from">' . __( 'Relist Auction Dates', 'wc_simple_auctions' ) . '</label>
		// 							<input type="text" class="short datetimepicker" name="_relist_auction_dates_from" id="_relist_auction_dates_from" value="" placeholder="' . _x( 'From&hellip;', 'placeholder', 'wc_simple_auctions' ) . ' YYYY-MM-DD HH:MM" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
		// 							<input type="text" class="short datetimepicker" name="_relist_auction_dates_to" id="_relist_auction_dates_to" value="" placeholder="' . _x( 'To&hellip;', 'placeholder', 'wc_simple_auctions' ) . '  YYYY-MM-DD HH:MM" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
		//                         </p>';
		// }		

	} // simple_auctions_form() 


}

//  Load the plugin instance 
add_action( 'plugins_loaded', array( 'WC_Vendors_Simple_Auctions', 'get_instance' ) );

endif; 