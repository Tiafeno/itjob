<?php

trait ModelNotice {
  private $noticeTable;

  public function __construct() {
    global $wpdb;
    $this->noticeTable = $wpdb->prefix . 'notices';
  }

  public function added_notice( $id_user, $Notice ) {
    global $wpdb;
    if ( is_numeric( $id_user ) && is_object($Notice)) {
      $data   = [
        'id_user' => $id_user,
        'template' => $Notice->tpl_msg,
        'guid'    => $Notice->guid,
        'needle'  => serialize( $Notice->needle )
      ];
      $format = [ '%d', '%s', '%s', '%s' ];
      $result = $wpdb->insert( $this->noticeTable, $data, $format );

      return $result;
    } else {
      return false;
    }
  }

  public function collect_notice( $id_notice = 0 ) {
    if ( is_numeric( $id_notice ) && 0 !== $id_notice ) {
      global $wpdb;
      $sql                 = "SELECT * FROM $this->noticeTable WHERE id_notice = %d";
      $prepare             = $wpdb->prepare( $sql, $id_notice );
      $result              = $wpdb->get_row( $prepare );
      $Notice              = unserialize( $result->notice ); // Instance of Notification
      $Notice->ID          = $result->id_notice;
      $Notice->date_create = $result->date_create;
      $Notice->status      = $result->status;

      return $Notice;
    } else {
      return false;
    }
  }

  public function collect_notices( $id_user = null ) {
    if ( is_null( $id_user ) || empty( $id_user ) ) {
      $User = wp_get_current_user();
      if ( 0 === $User->ID ) {
        return null;
      }
      $id_user = $User->ID;
    }
    global $wpdb;
    $sql           = "SELECT * FROM {$this->noticeTable} WHERE id_user = %d ORDER BY date_create DESC LIMIT 15";
    $prepare       = $wpdb->prepare( $sql, $id_user );
    $rows          = $wpdb->get_results( $prepare );
    $Notifications = [];
    // Récuperer l'objet template de la notification
    $Template = new \includes\object\NotificationTpl();
    foreach ( $rows as $row ) {
      // Récuper les variables
      $needle              = unserialize($row->needle);

      $Notice              = new stdClass();
      $Notice->ID          = $row->id_notice;
      $Notice->date_create = $row->date_create;
      $Notice->status      = $row->status;
      $Notice->guid        = $row->guid;

      $tpl      = $Template->tpls[(int)$row->template];
      $Notice->title = vsprintf($tpl, $needle);

      $Notifications[]     = $Notice;
    }

    return $Notifications;
  }

  public function change_notice_status( $id_notice = 0, $status = 1 ) {
    if ( ! is_user_logged_in() || ! is_numeric( $id_notice ) ) {
      return false;
    }
    if ( 0 === $id_notice ) {
      return false;
    }
    global $wpdb;
    $result = $wpdb->update( $this->noticeTable, [ 'status' => $status ], [ 'id_notice' => $id_notice ], [ '%d' ], [ '%d' ] );
    return $result;
  }

}