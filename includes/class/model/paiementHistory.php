<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/03/2019
 * Time: 13:12
 */

namespace includes\model;


final class paiementHistory {
  public static $table = "paiement_history";
  public function __construct() {
  }

  public function add( $args = [] ) {
    global $wpdb;
    $obj = (object) $args;
    $data   = [
      'data' => serialize($obj->data)
    ];
    $format = ['%s'];
    $result = $wpdb->insert($wpdb->prefix . self::$table, $data, $format );

    return $result;
  }

  public function get_history( $id = 0 ) {
    global $wpdb;
    if (!is_numeric($id) || $id === 0) return false;

    $table = $wpdb->prefix . self::$table;
    $sql = "SELECT * FROM $table as tb WHERE tb.id_history = $id";
    $result = $wpdb->get_results($sql, OBJECT);
    return is_array($result) ? $result : [];
  }
}