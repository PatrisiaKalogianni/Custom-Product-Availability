<?php

/**
* Plugin Name: Custom Product Availability
* Description: Modifies the products' availability string, based on their stock status and their supplier attribute value.
* Author: Patrisia Kalogianni
* Version: 1.0
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
    }
	
 
	/**
     * Enqueue css file.
     */
    public static function set_up_css_file() {
        wp_register_style( 'backorder_color', plugins_url('product-availability-text-color.css',__FILE__ ) );
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
	
		// Get the product's supplier.
		
	    $supplier = $product->get_attribute('pa_supplier'); 
		
		// Get the product's supplier term ID.
		
		$supplier_id = wc_get_product_terms( $product->get_id(), 'pa_supplier', array('fields' => 'ids')) [0] ;
		
		// Get the product's ID.
		
		$product_id =  $product -> get_id() ;
		
		// Check if the supplier's availability is outofstock and the product's stock quantity is smaller than or equal to 0.
		
		if ( ( get_post_meta($product_id, '_custom_supplier_availability', true)  == "outofstock" ) && ( $product->get_stock_quantity() <= 0 )){
			
			$availability['class'] = 'orange-product-availability-text';
					
			$availability['availability'] = "Κατόπιν Παραγγελίας" ; 
			
		}
		
	    // Check if the product is in stock.
		
	    else if($product->get_stock_status() == 'instock'){
		
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

}

WC_Custom_Product_Availability_Tab::init();