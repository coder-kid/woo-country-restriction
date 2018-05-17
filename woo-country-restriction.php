<?php
/**
 * Product country restriction
 *
 * @package     WooCommerce_Product_Country_Restriction
 * @author      Mahfuz Rahman
 * @copyright   2018 Mahfuz Rahman
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Woo Product Country Restriction
 * Plugin URI:  https://github.com/woocommerce-country-restriction
 * Description: WooCommerce product country restrictions for purchase.
 * Version:     1.0.0
 * Author:      Mahfuz Rahman <asrmahfuz8@gmail.com>
 * Author URI:  https://github.com/coder-kid
 * Text Domain: woo-product-restriction
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

class WooCommerce_Product_Country_Restriction {

    /**
     * @var null
     */
    protected static $_instance = null;

    public function instance() {
        if( is_null(self::$_instance) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', array($this, 'woo_add_general_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'woo_save_general_fields_data'));

        add_filter('woocommerce_geolocation_update_database_periodically', array($this, 'update_geo_database'));
        add_filter('woocommerce_is_purchasable', array($this, 'is_purchasable'), 10, 2);
        add_filter('woocommerce_product_settings', array($this, 'add_new_woo_settings'));
    }

    public function on_activation() {
        WC_Geolocation::update_database();
    }

    public function woo_add_general_fields() {
        global $woocommerce, $post;
        echo '<div class="options_group">';
        ?>
            <p class="form-field forminp">
                <label for="product_field_type"><?php echo _e('Product Countries', 'woocommerce'); ?></label>
                <select
                    name="product_field_type[]"
                    id="product_field_type"
                    class="wc-enhanced-select"
                    multiple="multiple"
                    style="width:300px"
                    data-placeholder="<?php _e('Choose a country&hellip;', 'woocommerce'); ?>"
                    >
                        <?php
                            $countries = WC()->countries->get_shipping_countries();
                            asort($countries);

                            $selections = get_post_meta($post->ID, 'product_field_type', true);
                            if(empty($selections) || ! is_array($selections)) {
                                $selections = [];
                            }

                            foreach($countries as $key => $val) {
                                echo '<option value="'.$key.'" '.selected(in_array($key, $selections), true, false).'>'.$val.'</option>';
                            }
                    
                        ?>
                    </select>
            </p>
        <?php
        echo '</div>';
    }

    public function woo_save_general_fields_data( $post_id ) {
        
        $countries = [];

        if(isset($_POST['product_field_type'])) {
            $countries = $_POST['product_field_type'];
            update_post_meta($post_id, 'product_field_type', $countries);
        }

    }

    public function get_user_country() {
        $geoloc = WC_Geolocation::geolocate_ip();
        return $geoloc['country'];
    }


    public function update_geo_database() {
        return true;
    }

    public function is_restricted_by( $id ) {

        $countries = get_post_meta($id, 'product_field_type', true);
        if(empty($countries) || ! is_array($countries))
            $countries = array();

        $customer_country = $this->get_user_country();

        if( ! in_array($customer_country, $countries) )
            return true;
    }

    public function is_restricted( $product ) {
        $id = $product->get_id();
        return $this->is_restricted_by($id);
    }

    public function is_purchasable($purchasable, $product) {
        if($this->is_restricted($product))
            return false;
        return true;
    }

    public function add_new_woo_settings( $settings ) {
        $new_settings = $settings;

        $new_settings[] = [
            'type'  => 'title',
            'title' => __( 'Product Country Restriction', 'woocommerce' )
        ];

        $new_settings[] = [
            'name'      => __('Restriction Message', 'woocommerce'),
            'desc_tip'  => __( 'Eroor message to display when a product is restricted by country', 'woocomemrce' ),
            'id'        => 'product-country-restriction-message',
            'type'      => 'text',
            'css'       => 'min_width: 300px;',
            'std'       => '',
            'default'   => 'Country restriction default message'
        ];

        $new_settings[] = [
            'type'  => 'sectionend'
        ];

        return $new_settings;
    }
    
}

WooCommerce_Product_Country_Restriction::instance();
register_activation_hook(__FILE__, array('WooCommerce_Product_Country_Restriction', 'on_activation'));