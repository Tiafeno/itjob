<?php
namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Formation {
    private $address = null;
    private $email = null;
    public $establish_name = null;
    public $title = null;
    public $region = null;
    public $duration = null;
    public $description = null;
    public $date_limit = null;
    public $date_create = null;
    public $distance_learning = false;

    public function __construct( $formation_id = null, $private_access = false ) {
      if (is_null($formation_id)) return false;
      $post_status = get_post_status( $formation_id );
      if ($post_status !== 'formation') return false;

      $post_formation = get_post( (int) $formation_id );
      $this->title = $post_formation->post_title;
      $this->description = $post_formation->post_content;
      $this->date_create = $post_formation->post_date;

      $this->establish_name = get_field('establish_name', $formation_id);
      $this->address        = get_field('address', $formation_id);
      $this->email          = get_field('email', $formation_id);
      $this->duration       = get_field('duration', $formation_id);
      $this->date_limit     = get_field('date_limit', $formation_id);
      $distance_learning    = get_field('distance_learning', $formation_id);
      $this->distance_learning = boolval($distance_learning);

      if ($private_access) $this->get_private_informations();
    }

    private function get_private_informations() {
      $this->__ = [
        'address' => $this->get_address(),
        'author'  => $this->get_author()
      ];
    }

    private function get_address() {
      return $this->address;
    }

    private function get_author() {
      if (!filter_var($this->email, FILTER_SANITIZE_EMAIL)) return null;
      $User = get_user_by_email( $this->email );
      $user_data = get_userdata( $User->ID );

      return $user_data;
    }
}