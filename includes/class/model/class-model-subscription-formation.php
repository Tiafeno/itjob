<?php
namespace includes\model;

use includes\post\Candidate;

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

    public static function is_register($formation_id, $user_id) {
      global $wpdb;
      if (!is_numeric($formation_id) || !is_numeric($user_id)) {
        return false;
      }
      $sql = "SELECT * FROM {$wpdb->prefix}registration_training WHERE formation_id = %d AND user_id = %d";
      $results = $wpdb->prepare($sql, $formation_id, $user_id);

      return $wpdb->get_results($results);
    }


    public static function get_subscription( $formation_id = 0, $paid = null ) {
      global $wpdb;
      if (!is_numeric($formation_id) || $formation_id === 0) return false;
      $table = $wpdb->prefix . self::$table;
      $condition = '';
      if (!is_null($paid)) {
        $condition = " AND tb.paid = $paid";
      }
      $sql = "SELECT * FROM $table as tb WHERE tb.formation_id = $formation_id $condition";
      $result = $wpdb->get_results($sql, OBJECT);
      return is_array($result) ? $result : [];
    }

    public static function get_candidates( $formation_id ) {
      $subscribes = self::get_subscription($formation_id);
      $Candidates = [];
      foreach ($subscribes as $subscribe) {
        $user = get_userdata(intval($subscribe->user_id));
        if (!in_array('candidate', $user->roles)) continue;
        $Candidate = Candidate::get_candidate_by(intval($subscribe->user_id), 'user_id', true);
        $Candidates[] = [
          'paid' => intval($subscribe->paid),
          'date' => $subscribe->date_create,
          "candidate" => $Candidate
        ];
      }

      return $Candidates;
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