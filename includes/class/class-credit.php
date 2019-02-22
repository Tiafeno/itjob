<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 22/02/2019
 * Time: 15:15
 */

namespace includes\object;

use includes\model\Model_Wallet;

if (!defined('ABSPATH')) {
  exit;
}

class credit {
  public function __construct() {
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
          'admin_label' => false,
          'weight'      => 0
        ),
      ]
    ] );
  }

  public function sc_render_html( $attrs ) {
    global $Engine;

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

    if ( ! is_user_logged_in()) {
      return '<div class="d-flex align-items-center">' .
        '<div class="uk-margin-large-top uk-margin-auto-left badge badge-danger uk-margin-auto-right">' .
          "Vous n'avez pas l'autorisation nécessaire pour acceder cette page".
        '</div></div>';
    }

    $wallet_id = self::create_wallet();
    if (is_wp_error($wallet_id)) {
      $msg = $wallet_id->get_error_message();
      return $msg;
    }
    try {
      do_action('get_notice');
      $wModel = new Model_Wallet();
      /** @var STRING $title */
      return $Engine->render('@VC/wallet.html', [
        'title' => $title,
        'histories' => $wModel->collect_history( $wallet_id )
      ]);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      echo $e->getRawMessage();
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