<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 22/02/2019
 * Time: 15:52
 */

namespace includes\post;

use includes\object\credit;

if (!defined('ABSPATH')) {
  exit;
}

class Wallet {
  private $ID;
  public $reference = '';
  public $credit = 0;
  public $date_create = '';
  public $author = null;
  public static $error = false;

  public function __construct($id_wallet = 0) {
    $id_wallet = intval($id_wallet);
    if ($id_wallet === 0) {
      self::$error = new \WP_Error('param', "L'identification de la portefeuille n'est pas definie");
      return false;
    }

    $wallet = get_post($id_wallet);
    if (is_null($wallet)) {
      self::$error = new \WP_Error('broken', "Portefeuille introuvable");
      return false;
    }

    $this->ID = $wallet->ID;
    $this->reference = $wallet->post_title;
    $this->author = get_field('user', $this->ID); // return WP_User (object)
    $credits = get_field('wallet', $this->ID);
    $this->credit = intval($credits);
  }

  public static function getInstance($value = null, $handler = "user_id", $create_if_not_exist = false) {
    if (is_null($value)) return false;
    switch ($handler) {
      CASE 'user_id':
        $User = get_user_by('ID', intval($value));
        if (!$User) {
          return new \WP_Error('broken', "Utilisateur introuvable ($value)");
        }
        $args = [
          'post_type' => 'wallets',
          'post_status' => 'any',
          'meta_value' => $value,
          'meta_key' => 'user',
        ];
        $wallets = get_posts($args);
        if (is_array($wallets) && !empty($wallets)) {
          $instance = new self($wallets[0]->ID);
        } else {
          if ($create_if_not_exist) {
            // Crée une portefeuille
            $wallet_id = credit::create_wallet($User->ID);
            if (is_wp_error($wallet_id)) $instance = $wallet_id;
            $instance = new self($wallet_id);
          } else {
            $instance = new \WP_Error('broken', "Portefeuille introuvable");
          }
        }
        return $instance;
        BREAK;
    }
  }

  public function getId() {
    return $this->ID;
  }

  public function update_wallet( $credit ) {
    if (!is_numeric($credit)) return false;
    update_field('wallet', $credit, $this->getId());
    return true;
  }

  public function is_wp_error() {
    if (is_wp_error(self::$error)) {
      return self::$error->get_error_message();
    } else {
      return false;
    }
  }

}