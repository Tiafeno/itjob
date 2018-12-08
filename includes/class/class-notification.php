<?php
namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}


final class Notification {
  private $title;
  private $message;
  private $type;
  private $status; // lu ou non lu
  public $url;

  public function __construct( $type = null) {
    if (is_null($type) || empty($type)) return false;
  }

  public function get_message() {
    return $this->message;
  }

  public function get_title() {
    return $this->title;
  }

  public static function getInstance($type = null) {
    if (is_null($type) || empty($type) ) return false;
    return new Notification($type);
  }


}

final class NotificationHelper {
  public function __construct() {
    add_action('init', function () {

    });
  }

  
  public function notification_publish_cv( $id_cv ) {

  }

  public function notification_candidate_postuled( $id_cv, $id_offer ) {

  }

  public function notification_company_interest( $id_cv, $id_company ) {

  }

  public function notification_change_interet_status( $id_interest, $status ) {

  }
}

new NotificationHelper();