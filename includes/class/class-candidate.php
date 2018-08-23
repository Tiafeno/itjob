<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

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
  public $country; // City + Postal code, eg: Antananarivo (101)
  public $district; // Region
  public $phones = [];
  public $birthdayDate;
  public $status; // Je cherche...
  public $allowed; // A, B, C & A`
  public $jobSought;
  public $languages = [];
  public $masterSoftware = [];
  public $trainings = [];
  public $experiences = [];
  public $centerInterest;
  public $jobNotif; // False|Array
  public $trainingNotif;
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
    $this->ID       = $output->ID;
    $this->title    = $this->reference = $output->post_title;
    $this->postType = $output->post_type;

    if ( $this->acfElements() ) {
      $this->email      = get_field( 'itjob_cv_email', $this->ID );
      $User             = get_user_by( 'email', $this->email );
      $this->userAuthor = $User->data;
      // Remove login information (security)
      unset( $this->userAuthor->user_login, $this->userAuthor->user_pass );

      // get Terms
      $this->fieldTax();
    }
  }

  /**
   * @return bool
   */
  public function is_candidate() {
    return $this->postType === 'candidate';
  }

  public function acfElements() {
    if ( ! function_exists( 'the_field' ) ) {
      return false;
    }
    $this->greeting  = get_field( 'itjob_cv_greeting', $this->ID );
    $this->firstName = get_field( 'itjob_cv_firstname', $this->ID );
    $this->lastName  = get_field( 'itjob_cv_lastname', $this->ID );

    $birthdayDate       = get_field( 'itjob_cv_birthdayDate', $this->ID );
    $this->birthdayDate = date( 'd/m/Y', strtotime( $birthdayDate ) );
    $this->address      = get_field( 'itjob_cv_address', $this->ID );

    // repeater field
    $phones = get_field( 'itjob_cv_phone', $this->ID );
    if ( $phones ) {
      foreach ( $phones as $phone ):
        array_push( $this->phones, $phone['number'] );
      endforeach;
    }

    $this->status      = get_field( 'itjob_cv_status', $this->ID );
    $this->trainings   = $this->acfRepeaterElements( 'itjob_cv_trainings', [
      'training_dateBegin',
      'training_dateEnd',
      'training_country',
      'training_city',
      'training_diploma',
      'training_establishment',
    ] );
    $this->experiences = $this->acfRepeaterElements( 'itjob_cv_experiences', [
      'exp_dateBegin',
      'exp_dateEnd',
      'exp_city',
      'exp_country',
      'exp_company',
      'exp_mission'
    ] );

    $this->centerInterest = $this->acfGroupField( 'itjob_cv_centerInterest', [ 'various', 'projet' ] );
    $this->newsletter     = get_field( 'itjob_cv_newsletter', $this->ID );

    return true;
  }

  /**
   * Vérifier si le candidate est notifier pour les formations
   * @return bool
   */
  public function isTrainingNotif() {
    $notif = get_field( 'itjob_cv_notifFormation', $this->ID );
    if ( $notif ) {
      if ( $notif['notification'] ) {
        $this->trainingNotif = (object) [ 'branch_activity' => $notif['branch_activity'] ];

        return true;
      }
    } else {
      return false;
    }
  }

  /**
   * Vérifier si le candidate est notifier pour les emplois publier
   * @return bool
   */
  public function isJobNotif() {
    $notif = get_field( 'itjbob_cv_notifEmploi', $this->ID );
    if ( $notif ) {
      if ( $notif['notification'] ) {
        $branchActivity = [ 'branch_activity' => $notif['branch_activity'] ]; // Object Term
        $jobSought      = [ 'job_sought' => $notif['job_sought'] ];
        $this->jobNotif = (object) array_merge( $branchActivity, $jobSought );

        return true;
      } else {
        return $this->jobNotif = false;
      }
    } else {
      return false;
    }
  }

  /**
   * Récuperer les terms
   */
  private function fieldTax() {
    $this->languages      = wp_get_post_terms( $this->ID, 'language', [ "fields" => "all" ] );
    $this->masterSoftware = wp_get_post_terms( $this->ID, 'master_software', [ "fields" => "all" ] );
    $this->district       = wp_get_post_terms( $this->ID, 'region', [ "fields" => "all" ] );
    $this->jobSought      = wp_get_post_terms( $this->ID, 'job_sought', [ "fields" => "all" ] );
  }

  /**
   * @param null|string $rp - Groupe field name
   * @param array $subField
   *
   * @return array
   */
  private function acfRepeaterElements( $repeaterField = null, $subField = [] ) {
    if ( is_null( $repeaterField ) ) {
      return [];
    }
    $resolve = [];
    $rows    = get_field( $repeaterField, $this->ID );
    if ( $rows ) {
      foreach ( $rows as $row ) {
        array_push( $resolve, (object) $row );
      }
    }

    return $resolve;
  }

  /**
   * @param null|string $group
   * @param array $fields
   *
   * @return array|stdClass
   */
  private function acfGroupField( $group = null, $fields = [] ) {
    if ( is_null( $group ) ) {
      return [];
    }

    $groupe = get_field( $group, $this->ID );
    if ( $groupe ) {
      $resolve = new \stdClass();
      foreach ( $fields as $field ) {
        $resolve->{$field} = $groupe[ $field ];
      }

      return $resolve;
    } else {
      return [];
    }
  }

  public function getId() {
    return $this->ID;
  }
}

