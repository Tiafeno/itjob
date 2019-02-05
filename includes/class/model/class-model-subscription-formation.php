<?php
namespace includes\model;

final class Model_Subscription_Formation {
    public static $table = "registration_training";
    public function __construct() {}
    public static function add_resources( $args = [] ) {
        global $wpdb;
        $obj = (object) $args;
        $data   = [
            'formation_id' => $obj->formation_id,
            'user_id' => $wpdb->esc_like( $obj->user_id )
          ];
        $format = [ '%d', '%d' ];
        $result = $wpdb->insert($wpdb->prefix . self::$table, $data, $format );

        return $result;
    }

    public static function get_subscription( $formation_id = 0 ) {
      global $wpdb;
      if (!is_numeric($formation_id) || $formation_id === 0) return false;
      $table = $wpdb->prefix . self::$table;
      $sql = "SELECT * FROM $table as tb WHERE tb.formation_id = $formation_id";
      $result = $wpdb->get_results($sql, OBJECT);
      return is_array($result) ? $result : [];
    }

    public static function update_paid( $registration_id, $paid = 0) {
        global $wpdb;
        if (!is_numeric($registration_id)) return false;
        $result = $wpdb->update( $wpdb->prefix . self::$table, [ 'paid' => $paid ], [ 'ID' => (int)$registration_id ], [ '%d' ], [ '%d' ] );
        return $result;
    }

    public function get_paid( $formation_id, $user_id) {
      global $wpdb;
      if (!is_numeric($formation_id) || !is_numeric($user_id)) return false;
      $table = $wpdb->prefix . self::$table;
      $result = $wpdb->get_row( $wpdb->prepare("SELECT ID as registration_id, paid FROM $table WHERE formation_id = %d AND user_id = %d", $formation_id, $user_id));
      return $result;
    }
}