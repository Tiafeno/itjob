<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\object as Object;

final class Candidate implements \iCandidate {
  // Added Trait Class
  use \Auth;

  private $ID;

  public $title;
  public $reference;
  public $greeting;
  public $firstName;
  public $lastName;
  public $address;
  public $city;
  public $district;
  public $phones;
  public $birthdayDate;
  public $status; // Je cherche...
  public $allowed; // A, B, C & A`
  public $jobSought;
  public $languages = [];
  public $masterSoftware = [];
  public $training = [];
  public $experiences = [];
  public $notification;
  public $newsletter;

  public function __construct( $postId = null ) {
    if ( is_null( $postId ) ) {
      return false;
    }
    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    $output = get_post( (int) $postId );
    if ( is_null( $output ) ) {
      return false;
    }
    $this->ID         = $output->ID;
    $this->title      = $this->reference = $output->post_title;
    $this->postType   = $output->post_type;
    $this->userAuthor = Object\jobServices::getUserData( (int) $output->post_author );
  }

  public function is_candidate() {
    return $this->postType === 'candidate';
  }

  public function acfElements() {

  }

  public function getId() {
    return $this->ID;
  }
}

