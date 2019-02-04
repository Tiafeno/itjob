<?php

namespace includes\model;

final class Model_Request_Formation {
    public static $table = "request_training";

    public static function get_resources( $id_request = 0 ) {
        global $wpdb;
        if (self::request_exists($id_request)) {
            $table = self::$table;
            $sql = "SELECT * FROM {$wpdb->prefix}{$table} WHERE ID = {$id_request}";
            return $wpdb->get_row($sql);
        } else {
            return false;
        }
    }

    public static function add_resources($args = []) {
        global $wpdb;
        $obj = (object) $args;
        if ( ! self::hasRequest($obj->subject)) {
            $data   = [
                'user_id' => $obj->user_id,
                'subject' => $wpdb->esc_like( $obj->subject ),
                'topic'   => $obj->topic,
                'description' => $obj->description,
                'concerned'   => serialize([]),
              ];
            $format = [ '%d', '%s', '%s', '%s', '%s' ];
            $result = $wpdb->insert($wpdb->prefix . self::$table, $data, $format );

            return $result;
        } else return null;
    }

    public static function request_exists( $id_request ) {
        global $wpdb;
        $table = self::$table;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE ID = {$id_request}";
        $query = $wpdb->get_var($sql);

        return $query ? true : false;
    }

    public static function hasRequest($request_subject) {
        global $wpdb;
        $table = self::$table;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE subject = %s";
        $query = $wpdb->prepare($sql, $request_subject);
        $result = $wpdb->get_var($query);

        return $result ? true : false;
    }

    public static function set_concerned( $request_training_id = 0, $User = null ) {
        global $wpdb;
        if ($request_training_id === 0 || is_null($User) || !$User instanceof \WP_User) return false;
        $table = $wpdb->prefix . self::$table;
        $request_sql = "SELECT concerned FROM $table WHERE ID = $request_training_id";
        $request_result = $wpdb->get_row($request_sql, OBJECT);
        if ($request_result && is_object($request_result)) {
            $concerned = unserialize($request_result->concerned);
            // Ajouter le candidat dans la liste des candidat interesser
            $concerned = is_array($concerned) ? $concerned : [];
            $concerned[] = $User->ID;

            $update_result  = $wpdb->update($table, ['concerned' => serialize($concerned)], ['ID' => $request_training_id], ['%d'], ['%d']);
            if (!$update_result) return false;

            return true;
        } else return false;
    }

    public static function get_concerned( $request_training_id = 0 ) {
        global $wpdb;
        if (!is_numeric($request_training_id) || $request_training_id === 0) return false;
        $table = $wpdb->prefix . self::$table;
        $sql = "SELECT concerned FROM $table WHERE ID = $request_training_id";
        $result = $wpdb->get_row($sql, OBJECT);
        return is_object($result) ? unserialize($result->concerned) : [];
    }

    public static function update_validation( $request_training_id = 0, $validation = 0 ) {
        global $wpdb;
        if (!is_numeric($request_training_id) || $request_training_id === 0) return false;
        $result = $wpdb->update( $wpdb->prefix . self::$table, [ 'validated' => $validation ],
          [ 'ID' => (int)$request_training_id ], [ '%d' ], [ '%d' ] );

        return $result;
    }

    public static function update_activation( $request_training_id = 0, $activation = 0 ) {
        global $wpdb;
        if (!is_numeric($request_training_id)) return false;
        $result = $wpdb->update( $wpdb->prefix . self::$table, [ 'disabled' => $activation ],
          [ 'ID' => (int)$request_training_id ], [ '%d' ], [ '%d' ] );
        
        return $result;
    }

    public static function collect_resources( $offset = 1, $number = 10) {
        global $wpdb;
        $sql = "SELECT * FROM %s LIMIT %d, %d";
        $prepare = $wpdb->prepare($sql, $wpdb->prefix . self::$table, $offset, $number);
        $results = $wpdb->get_results($prepare);

        return $results;
    }

}