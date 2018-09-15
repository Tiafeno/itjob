<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

final class Candidate extends UserParticular implements \iCandidate {
  // Added Trait Class
  use \Auth;

  public $title;
  public $reference;
  public $district; // Region
  public $status; // Je cherche...
  public $driveLicences; // A, B, C & A`
  public $jobSought;
  public $languages = [];
  public $masterSoftware = [];
  public $trainings = [];
  public $experiences = [];
  public $centerInterest;
  public $jobNotif; // False|Array
  public $trainingNotif;
  public $newsletter;
  public $branch_activity;
  public $tags;

  /**
   * Candidate constructor.
   *
   * @param int $postId - ID post 'candidate' type
   */
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
    $this->setId( $output->ID );
    // Initialiser l'utilisateur particulier
    parent::__construct();

    $this->title    = $this->reference = $output->post_title;
    $this->postType = $output->post_type;

    if ( $this->acfElements() ) {
      $this->email      = get_field( 'itjob_cv_email', $this->getId() );
      $User             = get_user_by( 'email', $this->email );
      $this->userAuthor = $User->data;
      // Remove login information (security)
      unset( $this->userAuthor->user_login, $this->userAuthor->user_pass );

      // get Terms
      $this->fieldTax();
    }
  }

  public static function get_candidate_by( $value, $handler = 'user_id' ) {
    if ( $handler === 'user_id' ) {
      $usr        = get_user_by( 'id', (int) $value );
      $args       = [
        'post_type'      => 'candidate',
        'post_status'    => [ 'publish', 'pending' ],
        'posts_per_page' => - 1,
        'meta_key'       => 'itjob_cv_email',
        'meta_value'     => $usr->user_email,
        'meta_compare'   => '='
      ];
      $candidates = get_posts( $args );
      if ( empty( $candidates ) ) return null;
      $candidate = reset( $candidates );

      return new Candidate( $candidate->ID );
    } else {
      return false;
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

    $this->status      = get_field( 'itjob_cv_status', $this->getId() );
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
    $this->newsletter     = get_field( 'itjob_cv_newsletter', $this->getId() );
    $this->driveLicences   = get_field('itjob_cv_driveLicence', $this->getId());
    return true;
  }

  /**
   * Vérifier si le candidate est notifier pour les formations
   * @return bool
   */
  public function isTrainingNotif() {
    $notif = get_field( 'itjob_cv_notifFormation', $this->getId() );
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
    $notif = get_field( 'itjbob_cv_notifEmploi', $this->getId() );
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
    $this->languages      = wp_get_post_terms( $this->getId(), 'language', [ "fields" => "all" ] );
    $this->masterSoftware = wp_get_post_terms( $this->getId(), 'master_software', [ "fields" => "all" ] );
    $this->district       = wp_get_post_terms( $this->getId(), 'region', [ "fields" => "all" ] );
    $this->jobSought      = wp_get_post_terms( $this->getId(), 'job_sought', [ "fields" => "all" ] );
    $this->tags           = wp_get_post_terms( $this->getId(), 'itjob_tag', [ "fields" => "names" ] );
    $this->branch_activity  = wp_get_post_terms( $this->getId(), 'branch_activity', [ "fields" => "names" ] );
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
    $rows    = get_field( $repeaterField, $this->getId() );
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

    $groupe = get_field( $group, $this->getId() );
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

  public static function getAllCandidate( $paged = - 1 ) {
    $allCandidate = [];
    $args         = [
      'post_type'      => 'company',
      'posts_per_page' => $paged,
      'post_status'    => 'publish',
      'orderby'        => 'date',
      'order'          => 'DESC'
    ];

    $posts = get_posts( $args );
    foreach ( $posts as $post ) : setup_postdata( $post );
      array_push( $allCandidate, new self( $post->ID ) );
    endforeach;
    wp_reset_postdata();

    return $allCandidate;
  }
}

