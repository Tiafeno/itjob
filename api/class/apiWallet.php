<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 01/03/2019
 * Time: 15:42
 */

class apiWallet {
  public function __construct() {
    add_action('rest_api_init', [&$this, 'initWallet']);

    /**
     * Add meta fields support in rest API for post type `Wallets`
     * > How to use?
     * http://mysite.com/wp-json/wp/v2/wallets?meta_key=<my_meta_key>&meta_value=<my_meta_value
     */
    add_action('rest_wallets_query', function ($args, $request) {
      $args += array(
        'meta_key'   => $request['meta_key'],
        'meta_value' => $request['meta_value'],
        'meta_query' => $request['meta_query'],
      );
      return $args;
    }, 99, 2);
  }

  public function initWallet() {
    $post_type = "wallets";
    $wallet_meta = ["wallet", "user", "date_create"];
    foreach ($wallet_meta as $meta):
      register_rest_field($post_type, $meta, array(
        'update_callback' => function ($value, $object, $field_name) {
          return update_field($field_name, $value, (int)$object->ID);
        },
        'get_callback'    => function ($object, $field_name) {
          $post_id = (int)$object['id'];
          return get_field($field_name, $post_id);
        },
      ));
    endforeach;
  }
}

new apiWallet();