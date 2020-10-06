<?php

/**
* Plugin Name: Custom Product Availability
* Description: Modifies the products' availability string, based on their stock status and their supplier attribute value.
* Author: Patrisia Kalogianni
* Version: 4.0.1.1
* Changelog : 1.0 Custom Product Availability - Orange "Κατόπιν Παραγγελίας".
*             1.0.1 Add out-of-stock status availability, default options and green general backorder text.
*             1.1 Add product level custom availability fields.
*             2.0 Add Custom Supplier Availability for XML Imports - If Supplier = instock and quantity >=0, set availability text = "Κατόπιν Παραγγελίας".
*             2.1 When supplier is out-of-stock disable backorders. 
*             2.2 Adapt for multiple suppliers.
*             2.3 When supplier is in-stock enable backorders. 
*             3.0 Hide products from Skroutz XML(WebExpert plugin adjustment).
*             3.1 Hide variations from Skroutz XML - Lamda XML, out-of-stock = "Αναμονές". 
*             4.0 Import Variations Custom Supplier Availability from Cat XML (via WP All Import).
*             4.0.1 Supplier custom product availability adaptation for Variations and Simple Products XML backorder update fix. 
*             4.0.1.1 Fix backorder import/update from XML bug.
**/



class WC_Custom_Product_Availability_Tab {

    //  Bootstraps the class and hooks required actions & filters.
     
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_custom_product_availability', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_custom_product_availability', __CLASS__ . '::update_settings' );
        add_filter( 'woocommerce_get_availability', __CLASS__ . '::custom_product_availability_text', 10, 2 );
		add_action('wp_enqueue_scripts', __CLASS__ . '::set_up_css_file');
        add_action( 'woocommerce_product_options_inventory_product_data', __CLASS__ . '::woocommerce_product_data_custom_fields' ); 
        add_action( 'woocommerce_process_product_meta', __CLASS__ . '::woocommerce_product_data_custom_fields_save' );
		add_action( 'save_post',  __CLASS__ . '::update_backorder_setting' );
		add_action( 'woocommerce_variation_options_pricing',  __CLASS__ . '::variations_custom_supplier_availability_field', 10, 3 );
		add_action( 'woocommerce_save_product_variation', __CLASS__ . '::save_variations_custom_supplier_availability_field', 10, 2 );
		add_action( 'pmxi_update_post_meta', __CLASS__ . '::update_backorder_setting_from_xml', 10, 3 ); // WP All Import hook.
    }
	
 
	/**
     * Enqueue css file.
     */
    public static function set_up_css_file() {
        wp_register_style( 'backorder_color', plugins_url('custom-product-availability-text-color.css',__FILE__ ) );
        wp_enqueue_style( 'backorder_color' );
    }
	
	
	/**
     * Display custom product availability fields in product data / inventory 
     */
	 
	public static function woocommerce_product_data_custom_fields(){
		
		global $woocommerce, $post;
		
        echo '<div class="product_custom_field">';
		
        // Custom Product Text Field for In Stock.
		
        woocommerce_wp_text_input(
            array(
			    'id' => '_custom_product_availability_in-stock',
				'label' => 'Λεκτικό διαθεσιμότητας "Σε απόθεμα":' ,
                'description' => 'Θέστε το λεκτικό διαθεσιμότητας του προϊόντος όταν υπάρχει απόθεμα.',
                'desc_tip' => true
            )
        );
		
		// Custom Product Text Field for Backorder.
		
		woocommerce_wp_text_input(
            array(
			    'id' => '_custom_product_availability_backorder',
				'label' => 'Λεκτικό διαθεσιμότητας "Σε προπαραγγελία":' ,
                'description' => 'Θέστε το λεκτικό διαθεσιμότητας του προϊόντος όταν είναι σε προπαραγγελία.',
                'desc_tip' => true
            )
        );
		
		// Custom Product Text Field for Out Of Stock.
		
		woocommerce_wp_text_input(
            array(
			    'id' => '_custom_product_availability_out-of-stock',
				'label' => 'Λεκτικό διαθεσιμότητας "Χωρίς απόθεμα":' ,
                'description' => 'Θέστε το λεκτικό διαθεσιμότητας του προϊόντος όταν δεν υπάρχει απόθεμα.',
                'desc_tip' => true
            )
        );
		
		// Custom Text Field for Supplier Availability.
		
		woocommerce_wp_text_input(
            array(
			    'id' => '_custom_supplier_availability',
				'label' => 'Διαθεσιμότητα Προμηθευτή' ,
                'description' => 'Διαθεσιμότητα του προϊόντος από το προμηθευτή.',
                'desc_tip' => true
            )
        );
		
        echo '</div>';
		
	}
	
	
    /**
     * Save the custom product availability text.
     */
	public static function woocommerce_product_data_custom_fields_save($post_id){
		
		// In stock.
		
		$woocommerce_product_in_stock_text_field = $_POST['_custom_product_availability_in-stock'];
        update_post_meta($post_id, '_custom_product_availability_in-stock', esc_attr($woocommerce_product_in_stock_text_field));
		
		// On backorder.
		
		$woocommerce_product_backorder_text_field  = $_POST['_custom_product_availability_backorder'];
        update_post_meta($post_id, '_custom_product_availability_backorder', esc_attr($woocommerce_product_backorder_text_field));
		
		// Out of stock.
		
		$woocommerce_product_out_of_stock_text_field = $_POST['_custom_product_availability_out-of-stock'];
        update_post_meta($post_id, '_custom_product_availability_out-of-stock', esc_attr($woocommerce_product_out_of_stock_text_field ));
		
		// Supplier.
		
		$woocommerce_product_supplier_text_field = $_POST['_custom_supplier_availability'];
        update_post_meta($post_id, '_custom_supplier_availability', esc_attr($woocommerce_product_supplier_text_field ));
		
		
	}
    
	
	/**
	 * Add custom field input in Product Data > Variations > Single Variation
     */ 

 
    public static function variations_custom_supplier_availability_field( $loop, $variation_data, $variation ) {
		
		// Supplier
		
		woocommerce_wp_text_input( array(
			'id' => '_custom_variation_supplier_availablity[' . $loop . ']',
            'class' => 'short',
            'label' => 'Διαθεσιμότητα Προμηθευτή',
            'value' => get_post_meta( $variation->ID, '_custom_variation_supplier_availablity', true )
        ));
		
    }
	
	/**
	 * Save data of product variation custom field. 
     */

    function save_variations_custom_supplier_availability_field( $variation_id, $i ) {
        
		// Supplier
		
		$custom_field = $_POST['_custom_variation_supplier_availablity'][$i];
        
		if ( isset( $custom_field ) ) {
			
			update_post_meta( $variation_id, '_custom_variation_supplier_availablity', esc_attr( $custom_field ) );

        }
		
	}
	
	/**
	 * Fires when WP All Import creates or updates post meta (custom fields).
	 */
	 
	public static function update_backorder_setting_from_xml( $post_id, $xml_node, $is_update ) {
		
		if ( get_post_type( $post_id ) == "product" ){
			
			$product = wc_get_product( $post_id );
			
			if ( $product->is_type( 'variable' )){
				
				// Product Inventory level backorders.
				
				$key = '_custom_supplier_availability';
			
			    self::find_backorder_setting( $post_id, $key,'no' );
				
				// All Variation quantities imported into Inventory Custom Supplier Availability from Cat XML.
				
				if ( stristr(get_post_meta($post_id, '_custom_supplier_availability', true), '|') ){ 
				
				    self::inventory_to_variation_supplier_availablity ( get_post_meta($post_id, '_custom_supplier_availability', true), $product );
				
				}
			
				// Product Variations.
			
			    $variations = $product->get_children(); // Returns an array of variation ids.
			
			    foreach ( $variations as $variation ) {
				
				    $key = '_custom_variation_supplier_availablity'; 
				
				    self::find_backorder_setting( $variation, $key ,'yes' ); 
				
			    }
		    }
			
		    else if ( $product->is_type( 'simple' )){ // Simple Products.
			    
				// If a product is a single Variation imported from Cat XML.
				
				if ( stristr(get_post_meta($post_id, '_custom_supplier_availability', true), '|') ){ 
				
					update_post_meta($post_id, '_custom_supplier_availability', explode("|", get_post_meta($post_id, '_custom_supplier_availability', true))[0]);
				
				}
			
			    $key = '_custom_supplier_availability';
			
			    self::find_backorder_setting( $post_id, $key,'no' );
		    }
		}

    }
	
    
	/**
     *  Change the product's backorder setting to "Do not allow" when the custom supplier availability is "out of stock".
     */
	 
	public static function update_backorder_setting( $post_id ){
		
		if ( get_post_type( $post_id ) == "product" ){
			
			$product = wc_get_product( $post_id );
			
			if ( $product->is_type( 'variable' )){
				
				// Product Inventory level backorders.
				
				$key = '_custom_supplier_availability';
			
			    self::find_backorder_setting( $post_id, $key,'no' );
				
				// All Variation quantities imported into Inventory Custom Supplier Availability from Cat XML.
				
				if ( stristr(get_post_meta($post_id, '_custom_supplier_availability', true), '|') ){ 
				
				    self::inventory_to_variation_supplier_availablity ( get_post_meta($post_id, '_custom_supplier_availability', true), $product );
				
				}
			
				// Product Variations.
			
			    $variations = $product->get_children(); // Returns an array of variation ids.
			
			    foreach ( $variations as $variation ) {
				
				    $key = '_custom_variation_supplier_availablity'; 
				
				    self::find_backorder_setting( $variation, $key ,'yes' ); 
				
			    }
		    }
			
		    else if ( $product->is_type( 'simple' )){ // Simple Products.
			    
				// If a product is a single Variation imported from Cat XML.
				
				if ( stristr(get_post_meta($post_id, '_custom_supplier_availability', true), '|') ){ 
				
					update_post_meta($post_id, '_custom_supplier_availability', explode("|", get_post_meta($post_id, '_custom_supplier_availability', true))[0]);
				
				}
			
			    $key = '_custom_supplier_availability';
			
			    self::find_backorder_setting( $post_id, $key,'no' );
		    }
		}
	
	}
	
	/**
	 *  Pass the Custom Supplier Availability from the Inventory to the Variations tab.
	 */
	 
	public static function inventory_to_variation_supplier_availablity( $inv_sup_avail, $product ){
		
		$trimmed = str_replace(' ', '', $inv_sup_avail); // Trim spaces.
		
		$variation_qnts = explode("|", $trimmed, -1); // Split on '|'. Example : 9|0|.
		
		$variations = $product->get_children(); // Returns an array of variation ids.
		
		if ( sizeof($variations) == sizeof($variation_qnts) ){
			
		    for ($x = 0; $x < sizeof($variations); $x++) {
			
			    update_post_meta( $variations[$x], '_custom_variation_supplier_availablity', $variation_qnts[$x] );
			
			}	
	    }
		
		else if ( sizeof($variations) == sizeof(explode("|", $trimmed)) ){
			
			$variation_qnts = explode("|", $trimmed); // Split on '|'. Example : 9|0.
			
			for ($x = 0; $x < sizeof($variations); $x++) {
			
			    update_post_meta( $variations[$x], '_custom_variation_supplier_availablity', $variation_qnts[$x] );
			
			}
		}
		
	}
	
	
	/**
	 *  Set the correct Backorder settings
	 */
	 
	public static function find_backorder_setting( $product_id, $key , $is_variation ){

		// Checks if the post is a product and if the custom supplier availability is out of stock.

		if ( (get_post_meta($product_id, $key, true) == "outofstock") || ( is_numeric(get_post_meta($product_id, $key, true)) && (get_post_meta($product_id, $key, true) <= "0")) || (get_post_meta($product_id, $key, true)  ==  "ΟΧΙ") || (get_post_meta($product_id, $key, true)  ==  "Μη Διαθέσιμο") || (get_post_meta($product_id, $key, true)  ==  "Αναμονές") ){
			
			if ( $is_variation == 'yes' ){
				
				update_post_meta( $product_id, '_backorders', 'no' );
				
			} 
			else {
			    
				// Gets product object.
			    $product = wc_get_product( $product_id );
			
	    	    // Sets backorder setting to "Do not allow".
  	            $product->set_backorders( 'no' );
			
		        // Saves the data and refreshes caches.
     	        $product->save();
			}
			
	        
		}
		
		// Checks if the post is a product and if the custom supplier availability is in stock.
		
		else if ( (get_post_meta($product_id, $key, true) == "instock") || ( is_numeric(get_post_meta($product_id, $key, true)) && (get_post_meta($product_id, $key, true) > "0") ) || (get_post_meta($product_id, $key, true)  ==  "ΝΑΙ") || (get_post_meta($product_id, $key, true)  ==  "Διαθέσιμο") || (get_post_meta($product_id, $key, true)  ==  "Άμεσα Διαθέσιμο") ){
			
			
			
			if ( $is_variation == 'yes' ){
				
				update_post_meta( $product_id, '_backorders', 'yes' );
				
			} 
			else {
			    
				// Gets product object.
			    $product = wc_get_product( $product_id );
	    	    
				// Sets backorder setting to "Allow".
  	            $product->set_backorders( 'yes' );
			
		        // Saves the data and refreshes caches.
     	        $product->save();
			}
	        
		}
	
	}
	
	
    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['custom_product_availability'] = 'Προσαρμοζόμενη Διαθεσιμότητα Προϊόντων';
        return $settings_tabs;
    }


    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }


    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() ); //save input data
    }


    /**
     * Get all the settings for the plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
	public static function get_settings() {
		
        // Create the $settings array and insert the section beggining element.
		
		$settings['section_title'] = array(
            'name'     => 'Προσαρμοζόμενες Ρυθμίσεις Διαθεσιμότητας Προϊόντων',
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'wc_settings_custom_product_availability_tab_title'
            
        );
		
		// Add default in-stock, available-on-backorder and out-of-stock input fields.
		
		$settings[] = array(
            'name' => 'Προεπιλεγμένο και "Σε απόθεμα":' ,
            'type' => 'text',
            'desc' => 'Θέστε την προκαθορισμένη διαθεσιμότητα των προϊόντων όταν υπάρχει απόθεμα',
            'desc_tip' => true,
            'id'   => 'wc_settings_custom_availability_default-in-stock'
		);
				
		$settings[] = array(
            'name' => 'Προεπιλεγμένο και "Σε προπαραγγελία":' ,
            'type' => 'text',
            'desc' => 'Θέστε την προκαθορισμένη διαθεσιμότητα των προϊόντων όταν τα προϊόντα είναι σε προπαραγγελία' , 
            'desc_tip' => true,
			'id'   => 'wc_settings_custom_availability_default-backorder'
		);
			
		$settings[] = array(
            'name' => 'Προεπιλεγμένο και "Χωρίς απόθεμα":' ,
            'type' => 'text',
			'desc' => 'Θέστε την προκαθορισμένη διαθεσιμότητα των προϊόντων όταν δεν υπέρχει απόθεμα' , 
			'desc_tip' => true,
	        'id'   => 'wc_settings_custom_availability_default-out-of-stock'
		);
		
		// Get all terms of the pa_suppliers taxonomy (aka the values of the supplier attribute).
		
		$suppliers = get_terms("pa_supplier"); 
				
        foreach ( $suppliers as $supplier ) {
				
		    // Add in-stock, available-on-backorder and out-of-stock input fields for each supplier.
					
	 	    $settings[] = array(
                'name' => $supplier->name.' και "Σε απόθεμα":' ,
                'type' => 'text',
                'desc' => 'Θέστε την διαθεσιμότητα των προϊόντων από αυτόν τον προμηθευτή όταν υπάρχει απόθεμα',
                'desc_tip' => true,
                'id'   => 'wc_settings_custom_availability_'.$supplier->term_id.'-in-stock'
			);
				
			$settings[] = array(
                'name' => $supplier->name.' και "Σε προπαραγγελία":' ,
                'type' => 'text',
                'desc' => 'Θέστε την διαθεσιμότητα των προϊόντων από αυτόν τον προμηθευτή όταν τα προϊόντα είναι σε προπαραγγελία' , 
                'desc_tip' => true,
			    'id'   => 'wc_settings_custom_availability_'.$supplier->term_id.'-backorder'
			);
			
			$settings[] = array(
                'name' => $supplier->name.' και "Χωρίς απόθεμα":' ,
                'type' => 'text',
                'desc' => 'Θέστε την διαθεσιμότητα των προϊόντων από αυτόν τον προμηθευτή όταν δεν υπέρχει απόθεμα' , 
                'desc_tip' => true,
			    'id'   => 'wc_settings_custom_availability_'.$supplier->term_id.'-out-of-stock'
			);
		}
		
		
		// Signify the end of this settings section.
		
		$settings['section_end'] = array(
            'type' => 'sectionend',
            'id' => 'wc_settings_custom_product_availability_tab_section_end'
        );
		
        return apply_filters( 'wc_custom_product_availability_settings', $settings );
    }
	
	
	// Modify the product availability, if necessary.
	
    public static function custom_product_availability_text( $availability, $product ) {
	
		// Get the product's ID. If product is variable, get parent id.  
		
		$product_id = $product->is_type( 'variation' ) ?  $product->get_parent_id() : $product->get_id(); 
	
		// Get the product's supplier.
		
		$supplier = $product->is_type( 'variation' ) ? wc_get_product($product_id)->get_attribute('pa_supplier') : $product->get_attribute('pa_supplier');
		
		// Get the product's supplier term ID.
		
		$supplier_id = wc_get_product_terms( $product_id, 'pa_supplier', array('fields' => 'ids')) [0] ;

		// Check if the product is in stock.
		
	    if($product->get_stock_status() == 'instock'){
		
		    // Check if the product's in-stock value has been set.
			
			if(  get_post_meta($product_id, '_custom_product_availability_in-stock', true)  != "" ){
				
				
				if ( get_post_meta($product_id, '_custom_product_availability_in-stock', true) == "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text orange when the text value is "Κατόπιν Παραγγελίας".
					
					$availability['class'] = 'orange-product-availability-text';
					
					$availability['availability'] = get_post_meta($product_id, '_custom_product_availability_in-stock', true); 
					
				}
				
				else{
					
					// Add the product's in-stock value.
			    
			        $availability['availability'] = get_post_meta($product_id, '_custom_product_availability_in-stock', true); 
				
				}
			}

			// Check if the supplier's in-stock value of this product's supplier has been set in the Custom Product Availability tab and that a supplier has been set for the product.
		
		    else if( get_option( 'wc_settings_custom_availability_'.$supplier_id.'-in-stock' ) != "" && $supplier != "" ){
				
				if ( get_option( 'wc_settings_custom_availability_'.$supplier_id.'-in-stock' ) == "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text orange when the text value is "Κατόπιν Παραγγελίας".
					
					$availability['class'] = 'orange-product-availability-text';
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_'.$supplier_id.'-in-stock'); 
					
				}
				
				else{
					
					// Add the supplier's in-stock value.
			    
			        $availability['availability'] = get_option( 'wc_settings_custom_availability_'.$supplier_id.'-in-stock' ); 
				
				}
			
		    }
			
			else if ( get_option( 'wc_settings_custom_availability_default-in-stock' ) != "" ){
				
				if ( get_option( 'wc_settings_custom_availability_default-in-stock' ) == "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text orange when the text value is "Κατόπιν Παραγγελίας".
					
					$availability['class'] = 'orange-product-availability-text';
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_default-in-stock' ) ; 
					
				}
				
				else{
					
					// Add the default in-stock value.
			    
			        $availability['availability'] = get_option( 'wc_settings_custom_availability_default-in-stock' ) ;
				
				}
				
			}
		
	    }
		
	    else if($product->get_stock_status() == 'onbackorder'){ 
		
		    // Check if the product's available-on-backorder value has been set.
			
			if( get_post_meta($product_id, '_custom_product_availability_backorder', true) != "" ){
				
				
				if ( get_post_meta($product_id, '_custom_product_availability_backorder', true) != "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text green.
					
					$availability['class'] = 'green-backorder-availability-text';
					
					$availability['availability'] = get_post_meta($product_id, '_custom_product_availability_backorder', true); 
					
				}
				
				else{
					
					// Add the product's available-on-backorder value.
			    
			        $availability['availability'] = get_post_meta($product_id, '_custom_product_availability_backorder', true); 
				
				}
			}
		
		    // Check if the available-on-backorder value of this product's supplier has been set in the Custom Product Availability tab and that a supplier has been set for the product.
		
		    else if(get_option( 'wc_settings_custom_availability_'.$supplier_id.'-backorder') != "" && $supplier != "" ){
			
				if ( get_option( 'wc_settings_custom_availability_'.$supplier_id.'-backorder' ) != "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text green.
					
					$availability['class'] = 'green-backorder-availability-text';
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_'.$supplier_id.'-backorder'); 
					
				}
				
				else{
					
					// Add the supplier's available-on-backorder value.
			     
					$availability['availability'] = get_option( 'wc_settings_custom_availability_'.$supplier_id.'-backorder'); 
			
				}
			   
		    }
			
			else if ( get_option( 'wc_settings_custom_availability_default-backorder' ) != "" ){
				
				if ( get_option( 'wc_settings_custom_availability_default-backorder' ) != "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text green.
					
					$availability['class'] = 'green-backorder-availability-text';
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_default-backorder' );
					
				}
				
				else{
					
					// Add the default available-on-backorder value.
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_default-backorder' ); 
					
				}
			    
			}
			
			else{
				
				//Make availability status text green.
				
				$availability['class'] = 'green-backorder-availability-text';
			
			}
		
	    }
		
		else if($product->get_stock_status() == 'outofstock'){ 
			
		    // Check if the product's out-of-stock value has been set.
			
		    if( get_post_meta($product_id, '_custom_product_availability_out-of-stock', true) != "" ){
				
				
				if ( get_post_meta($product_id, '_custom_product_availability_out-of-stock', true) == "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text orange when the text value is "Κατόπιν Παραγγελίας".
					
					$availability['class'] = 'orange-product-availability-text';
					
					$availability['availability'] = get_post_meta($product_id, '_custom_product_availability_out-of-stock', true); 
					
				}
				
				else{
					
					// Add the product's out-of-stock value.
			    
			        $availability['availability'] = get_post_meta($product_id, '_custom_product_availability_out-of-stock', true); 
				
				}
			}
		
		    // Check if the out-of-stock value of this product's supplier has been set in the Custom Product Availability tab and that a supplier has been set for the product.
		
		    else if ( get_option( 'wc_settings_custom_availability_'.$supplier_id.'-out-of-stock') != "" && $supplier != "" ){
				
				if ( get_option( 'wc_settings_custom_availability_'.$supplier_id.'-out-of-stock' ) == "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text orange when the text value is "Κατόπιν Παραγγελίας".
					
					$availability['class'] = 'orange-product-availability-text';
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_'.$supplier_id.'-out-of-stock');  
					
				}
				
				else{
					
					// Add the supplier' out-of-stock value.
			
				    $availability['availability'] = get_option( 'wc_settings_custom_availability_'.$supplier_id.'-out-of-stock'); 
				
				}
			    
		    }
			
			else if ( get_option( 'wc_settings_custom_availability_default-out-of-stock' ) != "" ){
				
				if ( get_option( 'wc_settings_custom_availability_default-out-of-stock' ) == "Κατόπιν Παραγγελίας" ){ 
					
					// Make availability status text orange when the text value is "Κατόπιν Παραγγελίας".
					
					$availability['class'] = 'orange-product-availability-text';
					
					$availability['availability'] = get_option( 'wc_settings_custom_availability_default-out-of-stock' );  
					
				}
				
				else{
					
					// Add the default out-of-stock value.
			    
			        $availability['availability'] = get_option( 'wc_settings_custom_availability_default-out-of-stock' ); 
				
				}
				
			}
		
	    }
		
	    return $availability;
    }
	
    // Functions for the WebExpert WooCommerce Skroutz & BestPrice XML Feed plugin to hide a product from the xml. 
	
	public static function custom_hide_xml_availability_text( $product ) {
		
		// Get product id.
		
		$product_id = $product->is_type( 'variation' ) ?  $product->get_parent_id()  : $product->get_id();
		
		// Check WebExpert Availability Field. 
		
		$xml_hide = get_post_meta($product_id, 'we_skroutzxml_custom_availability', true);
		
		if ( $xml_hide == "Απόκρυψη από το XML" ){
			
			$xml_availability = $xml_hide; // Hide Product.
		
		}
		
		else {
			
			// Get product availability.
		
		    $availability = $product->get_availability();
			
			if ( $product->is_type( 'variation' ) ){
				
				$variation_id = $product->get_id();
				
				// Check Variable WebExpert Availabilty.
				
				$xml_hide = get_post_meta($variation_id, 'we_skroutzxml_custom_availability', true); 
				
				$xml_availability = $xml_hide == 'Απόκρυψη από το XML' ? $xml_hide : $availability['availability'];
    
			}
			
			else {
		
			    $xml_availability = $availability['availability']; // Return Product Availability. 
			
			} 
		
		}
		
		return $xml_availability;
		
	}
	
	public static function custom_hide_xml_noavailability_text( $product ) {
		
		// Get product id.
		
        $product_id = $product->is_type( 'variation' ) ?  $product->get_parent_id()  : $product->get_id();		
		
		// Check WebExpert Availability Field. 
		
		$xml_hide = get_post_meta($product_id, 'we_skroutzxml_custom_noavailability', true);
		
		if ( $xml_hide == "Απόκρυψη από το XML" ){
			
			$xml_availability = $xml_hide; // Hide Product.
		
		}
		
		else {
			
			// Get product availability.
			
			$availability = $product->get_availability();
			
			if ( $product->is_type( 'variation' ) ){
				
				$variation_id = $product->get_id();
				
				// Check Variable WebExpert Availability Field.
			
			    $xml_hide = get_post_meta($variation_id, 'we_skroutzxml_custom_noavailability', true);
				
				$xml_availability = ($xml_hide == 'Απόκρυψη από το XML') ? $xml_hide : $availability['availability'];
    
			}
			
			else {
		
			    $xml_availability = $availability['availability']; // Return Product Availability. 
			
			}
		
		}
		
		return $xml_availability;
		
	}
	
}

WC_Custom_Product_Availability_Tab::init();