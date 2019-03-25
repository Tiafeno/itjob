<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 22/03/2019
 * Time: 13:09
 */

class apiProduct {
  public function __construct() {
    add_action('rest_api_init', [&$this, 'initProduct']);

    add_action('wp_loaded', function () {
      add_action('woocommerce_product_options_general_product_data', function () {
        woocommerce_wp_text_input(array(
          'id' => '__type',
          'label' => "Type d'article",
          'class' => 'itjob-type',
          'desc_tip' => true
        ));

        woocommerce_wp_text_input(array(
          'id' => '__id',
          'label' => "Identifiant de l'article",
          'class' => 'itjob-id',
          'desc_tip' => true
        ));
      }, 10);


      function itjob_save_custom_field($post_id) {
        $product = wc_get_product($post_id);
        $type = Http\Request::getValue('__type', 0);
        $id = Http\Request::getValue('__id', 0);
        $product->update_meta_data('__type', sanitize_text_field($type));
        $product->update_meta_data('__id', sanitize_text_field($id));
        $product->save();
      }

      add_action('woocommerce_process_product_meta', 'itjob_save_custom_field', 10, 1);
    });


    /**
     * Add meta fields support in rest API for post type `Wallets`
     * > How to use?
     * http://mysite.com/wp-json/wp/v2/wallets?meta_key=<my_meta_key>&meta_value=<my_meta_value
     */
  }

}

new apiProduct();