<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

abstract class UserParticular {
  public $ID = 0; // User id
  private $Candidate; // Object candidate
  private $firstName;
  private $lastName;
  private $phones = [];
  private $address; // string
  private $birthdayDate; // string

  protected $country; // term
  protected $email;

  public $has_cv = false;
  public $greeting;
  public $region;
  public $dateAdd;

  /**
   * @return bool
   */
  public function hasCV() {
    $hasCV = get_field( 'itjob_cv_hasCV', $this->getId() );
    return $this->has_cv = (bool) $hasCV;
  }

  public function getId() {
    return $this->ID;
  }

  public function setId( $id ) {
    $this->ID = $id;
  }

  public function getAddress() {
    return [
      'address' => $this->address,
      'country' => $this->country,
      'region'  => $this->region
    ];
  }

  public function getBirthday(){
    return $this->birthdayDate;
  }

  public function getFirstName() {
    return $this->firstName;
  }

  public function getLastName() {
    return $this->lastName;
  }

  public function getPhones() {
    return $this->phones;
  }

  public function __construct( $candidate_id = null ) {
    if ( ! function_exists( 'the_field' ) ) {
      return new \WP_Error( 'ACF', 'Plugin ACF non installer ou non activer' );
    }

    if ( ! is_null( $candidate_id ) ) {
      $this->setId( $candidate_id );
      // Object candidate
      $this->Candidate = new Candidate( $candidate_id );
    }

    $this->firstName = get_field( 'itjob_cv_firstname', $this->getId() );
    $this->lastName  = get_field( 'itjob_cv_lastname', $this->getId() );
    $this->address   = get_field( 'itjob_cv_address', $this->getId() );

    $birthdayDate       = get_field( 'itjob_cv_birthdayDate', $this->getId() );
    $this->birthdayDate = date( 'd/m/Y', strtotime( $birthdayDate ) );
    $this->greeting     = get_field( 'itjob_cv_greeting', $this->getId() );
    $this->dateAdd      = get_the_date( 'j F, Y', $this->getId() );
    $this->date_create  = get_the_date('m/d/Y H:i:s', $this->getId());
    // repeater field
    $phones = get_field( 'itjob_cv_phone', $this->getId() );
    if ( $phones && is_array($phones) ) {
      foreach ( $phones as $phone ):
        array_push( $this->phones, $phone['number'] );
      endforeach;
    }
  }

  protected function getCellphone() {
    return $this->phones;
  }

}