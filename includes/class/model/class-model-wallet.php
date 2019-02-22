<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 22/02/2019
 * Time: 22:24
 */

namespace includes\model;

if (!defined('ABSPATH')) {
  exit;
}

final class Model_Wallet {
  public $tpls = [];
  public static $wallet_history = "wallet_history";

  public function __construct() {
    $credit_nbr = __CREDITS__;
    $this->tpls[0] = "{$credit_nbr} credits en cadeau de bienvenue";
    $this->tpls[1] = "Vous avez acheter <b>%1\$s</b> credit(s)";
  }

  public function add_resource( $args = [] ) {
    global $wpdb;
    $obj = (object)$args;
    $data = [
      'id_wallet' => $obj->id_wallet,
      'template' => $obj->template,
      'args'   => serialize($obj->args),
      'date_create' => date_i18n("Y-m-d H:i:s")
    ];
    $format = ['%d', '%s', '%s', '%s'];
    $result = $wpdb->insert($wpdb->prefix . self::$wallet_history, $data, $format);

    return $result;
  }

  public function collect_history( $id_wallet = 0, $offset = 0, $number = 10 ) {
    global $wpdb;
    if (!$id_wallet) return [];
    $table = $wpdb->prefix . self::$wallet_history;
    $sql = "SELECT * FROM `{$table}` ORDER BY date_create ASC LIMIT %d, %d";
    $prepare = $wpdb->prepare($sql, intval($offset), intval($number));
    $results = $wpdb->get_results($prepare);
    $histories = [];
    foreach ($results as $key => $result) {
      $histories[$key] = new \stdClass();
      $histories[$key]->id_wallet = intval($result->id_wallet);
      $tpls = $this->tpls[(int) $result->template];
      $histories[$key]->msg = vsprintf($tpls, unserialize($result->args));
      $histories[$key]->date_create = date_i18n('j F Y', strtotime($result->date_create));
    }

    return $histories;
  }
}