<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

abstract class UserParticular {
  public $ID = 0; // User id
  private $__Candidate; // Object candidate
  private $__firstName;
  private $__lastName;
  private $__phones = [];

  protected $email;

  public $greeting;
  public $birthdayDate;
  public $address;
  public $country; // City + Postal code, eg: Antananarivo (101)
  public $region;
  public $dateAdd;

  /**
   * @return bool
   */
  public function hasCV() {
    $activated = get_field( 'activated', $this->getId() );
    return (bool) $activated;
  }

  public function getId() {
    return $this->ID;
  }

  public function setId( $id ) {
    $this->ID = $id;
  }

  public function get_display_name() {
    return "{$this->__firstName} {$this->__lastName}";
  }

  public function __construct( $candidate_id = null ) {
    if ( ! function_exists( 'the_field' ) ) {
      return new \WP_Error( 'ACF', 'Plugin ACF non installer ou non activer' );
    }

    if ( ! is_null( $candidate_id ) ) {
      $this->setId( $candidate_id );
      // Object candidate
      $this->__Candidate = new Candidate( $candidate_id );
    }

    $this->greeting  = get_field( 'itjob_cv_greeting', $this->getId() );
    $this->__firstName = get_field( 'itjob_cv_firstname', $this->getId() );
    $this->__lastName  = get_field( 'itjob_cv_lastname', $this->getId() );

    $birthdayDate       = get_field( 'itjob_cv_birthdayDate', $this->getId() );
    $this->birthdayDate = date( 'd/m/Y', strtotime( $birthdayDate ) );
    $this->address      = get_field( 'itjob_cv_address', $this->getId() );
    $this->dateAdd      = get_the_date( 'j F, Y', $this->getId() );
    // repeater field
    $phones = get_field( 'itjob_cv_phone', $this->getId() );
    if ( $phones ) {
      foreach ( $phones as $phone ):
        array_push( $this->__phones, $phone['number'] );
      endforeach;
    }
  }

  protected function getCellphone() {
    return $this->__phones;
  }

}