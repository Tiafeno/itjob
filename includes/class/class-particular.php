<?php
namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class UserParticular {
  private $ID = 0; // User id
  public $greeting;
  public $firstName;
  public $lastName;
  public $birthdayDate;
  public $phones = [];
  public $email;
  public $address;
  public $country; // City + Postal code, eg: Antananarivo (101)
  public $region;

  public static function my_cv() {

  }

  public function getId() {
    return $this->ID;
  }

  public function setId($id) {
    $this->ID = $id;
  }

  public function __construct($user_id) {
    if ( ! function_exists( 'the_field' ) ) {
      return false;
    }

    if ($this->getId() === 0) {
      $user        = get_user_by( 'id', (int) $user_id );
      $args       = [
        'post_type'      => 'candidate',
        'post_status'    => [ 'publish', 'pending' ],
        'posts_per_page' => - 1,
        'meta_key'       => 'itjob_cv_email',
        'meta_value'     => $user->user_email,
        'meta_compare'   => '='
      ];
      $candidates = get_posts( $args );
      if ( empty( $candidates ) ) return null;
      $candidate = reset( $candidates );
      $this->setId($candidate->ID);
    }

    $this->greeting  = get_field( 'itjob_cv_greeting', $this->getId() );
    $this->firstName = get_field( 'itjob_cv_firstname', $this->getId() );
    $this->lastName  = get_field( 'itjob_cv_lastname', $this->getId() );

    $birthdayDate       = get_field( 'itjob_cv_birthdayDate', $this->getId() );
    $this->birthdayDate = date( 'd/m/Y', strtotime( $birthdayDate ) );
    $this->address      = get_field( 'itjob_cv_address', $this->getId() );

    // repeater field
    $phones = get_field( 'itjob_cv_phone', $this->getId() );
    if ( $phones ) {
      foreach ( $phones as $phone ):
        array_push( $this->phones, $phone['number'] );
      endforeach;
    }
  }

}