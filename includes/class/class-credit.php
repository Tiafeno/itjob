<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 22/02/2019
 * Time: 15:15
 */

namespace includes\object;

use includes\model\Model_Wallet;
use includes\post\Wallet;

if (!defined('ABSPATH')) {
  exit;
}

class credit {
  public function __construct() {
    add_action('wp_loaded', function () {
      if (isset($_POST['wp_credit_nonce'])) {
        if (wp_verify_nonce($_POST['wp_credit_nonce'], 'order-credit')) {
          $quantity = isset($_POST['credit']) ? intval($_POST['credit']) : 1;
          $User = wp_get_current_user();
          $result = wp_insert_post([
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_title' => "#CREDIT",
            'post_author' => $User->ID
          ], true);

          if (is_wp_error($result)) { return false; }
          $product_id = $result;
          $_product = new \WC_Product($product_id);
          $_product->set_price(__CREDIT_PRICE__);
          $_product->set_regular_price(__CREDIT_PRICE__);
          $_product->set_sku("CRD{$product_id}");

          $_product->add_meta_data('__type', 'credit');
          $_product->add_meta_data('__id', $User->ID);

          $_product->save();

          WC()->cart->empty_cart(); // Clear cart
          WC()->cart->add_to_cart($product_id, $quantity); // Add new product in cart
          // https://docs.woocommerce.com/wc-apidocs/function-wc_get_page_id.html
          $checkout = get_permalink(wc_get_page_id('cart'));

          wp_redirect($checkout);
		      exit;
        }
      }

      add_action('payment_complete_credit', function ($user_id, $qty) {
        $from = "no-reply@itjobmada.ccom";
        $admin_email = get_field( 'admin_mail', 'option' ); // return string (mail)
        $admin_email = !$admin_email || empty($admin_email) ? "david@itjobmada.com" : $admin_email;
        $to = $admin_email;
        $headers   = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: ITJOB <{$from}>";

        $User = get_user_by('ID', intval($user_id));

        $content = "Bonjour, <br><br>";
        $content .= "{$User->first_name} {$User->last_name} ({$User->user_email}) vient d'acheter <b>{$qty}</b> credit(s). <br> Bonne journée";
        $subject = "Paiement effectuer avec succès sur le site ITJob";

        wp_mail($to, $subject, $content, $headers);
      }, 10, 2);

    }, 10);
    add_shortcode( 'wallets', [ &$this, 'sc_render_html' ] );
    vc_map( [
      'name'     => 'Wallets',
      'base'     => 'wallets',
      'category' => 'itJob',
      'params'   => [
        array(
          'type'        => 'textfield',
          'holder'      => 'h3',
          'class'       => 'vc-ij-title',
          'heading'     => 'Titre',
          'param_name'  => 'title',
          'value'       => '',
          'description' => "Ajouter un titre",
          'admin_label' => true,
          'weight'      => 0
        ),
      ]
    ] );
  }

  public function sc_render_html( $attrs ) {
    global $Engine, $itJob;

    extract(
      shortcode_atts(
        array(
          'title'  => '',
        ),
        $attrs
      )
    );
    wp_enqueue_script('sweetalert');
    wp_enqueue_style('sweetalert');

    if ( ! is_user_logged_in() ) {
      $redirection = get_the_permalink();
      return do_shortcode('[itjob_login role="candidate" redir="' . $redirection . '"]', true);
    }

    $User = $itJob->services->getUser();
    try {
      do_action('get_notice');
      $wModel = new Model_Wallet();
      $wallet = Wallet::getInstance($User->ID, 'user_id', true);
      $credit = $wallet->credit;
      /** @var STRING $title */
      return $Engine->render('@VC/wallet.html', [
        'title' => $title,
        'credit' => $credit,
        'price' => __CREDIT_PRICE__,
        'histories' => $wModel->collect_history( $wallet->getId() ),
        'nonce' => wp_create_nonce('order-credit')
      ]);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }

  }

  public function get_current_wallet() {

  }

  public static function create_wallet($user_id = 0) {
    global $itJob;
    $User = $user_id ? get_user_by('ID', (int)$user_id) : $itJob->services->getUser();
    $args = [
      'post_type' => 'wallets',
      'post_status' => 'any',
      'meta_value' => $User->ID,
      'meta_key' => 'user',
    ];
    $wallets = get_posts($args);
    if (empty($wallets)) {
      $insert = [
        'post_type' => "wallets",
        'post_status' => 'publish',
        'post_title' => "#{$User->ID}"
      ];
      $result = wp_insert_post($insert, true);
      if ( ! is_wp_error($result)) {
        $wallet_id = intval($result);

        update_field('user', $User->ID, $wallet_id);
        update_field('wallet', __CREDITS__, $wallet_id);
        update_field('date_create', date_i18n("Y-m-d H:i:s"), $wallet_id);

        $args = [
          'id_wallet' => $wallet_id,
          'template' => 0,
          'args'   => [],
        ];
        $wModel = new Model_Wallet();
        $wModel->add_resource($args);

        return $wallet_id;
      } else {
        return $result;
      }
    } else {
      $wallet = $wallets[0];
      return $wallet->ID;
    }
  }

}

return new credit();