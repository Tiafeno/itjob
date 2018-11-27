<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\object as Obj;

final class Candidate extends UserParticular implements \iCandidate {
  // Added Trait Class
  use \Auth;

  private $activated;
  private $author;
  private $avatar;

  public $title;
  public $state;
  public $candidate_url;
  public $reference;
  public $region; // Region
  public $status; // Je cherche...
  public $driveLicences; // A, B, C & A`
  public $jobSought;
  public $languages = [];
  public $softwares = [];
  public $trainings = [];
  public $experiences = [];
  public $centerInterest;
  public $jobNotif = false;
  public $trainingNotif = false;
  public $newsletter;
  public $branch_activity;
  public $tags;
  public $privateInformations;

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

    $this->title         = $this->reference = $output->post_title;
    $this->state         = $output->post_status;
    $this->candidate_url = get_the_permalink( $this->getId() );
    $this->postType      = $output->post_type;

    if ( $this->is_candidate() ) {
      $this->acfElements();
      $this->email = get_field( 'itjob_cv_email', $this->getId() );
      $User        = get_user_by( 'email', $this->email );
      // Remove login information (security)
      if ($User->ID === 0) return false;
      $this->author = Obj\jobServices::getUserData( $User->ID );
      $this->avatar = wp_get_attachment_image_src( get_post_thumbnail_id( $this->getId() ), [300, 300] );

      // TODO: Verifier si le client est une entreprise avec un compte premium

      // get Terms
      $this->fieldTax();

      // Cette methode appel une fonction qui ajoute une propriété (has_cv) de type boolean
      // qui constitue à verifier si le candidate posséde un CV ou autrement.
      $this->hasCV();
    }
  }

  // Getter
  public function getAuthor() {
    return $this->author;
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
      if ( empty( $candidates ) ) {
        return null;
      }
      $candidate = reset( $candidates );

      return new Candidate( $candidate->ID );
    } else {
      return false;
    }
  }

  /**
   * Verifier si le post est un candida (CV) valide ou pas
   * @return {} bool
   */
  public function is_candidate() {
    return $this->postType === 'candidate';
  }

  /**
   * Verifier si le CV est visible dans le site ou pas
   */
  public function is_activated() {
    $activation = get_field( 'activated', $this->getId() );

    return (bool) $activation;
  }

  /**
   * Verifier si le CV est publier (valider) ou autres
   */
  public function is_publish() {
    $post_status = [ 'pending', 'draft', 'private', 'trash' ];

    return ! in_array( $this->postType, $post_status ) ? 1 : 0;
  }

  public function acfElements() {
    if ( ! function_exists( 'the_field' ) ) {
      return false;
    }
    $this->activated   = get_field( 'activated', $this->getId() );
    $this->status      = get_field( 'itjob_cv_status', $this->getId() );
    $this->trainings   = $this->acfRepeaterElements( 'itjob_cv_trainings', [
      'training_dateBegin',
      'training_dateEnd',
      'training_country',
      'training_city',
      'training_diploma',
      'training_establishment',
      'validated'
    ] );
    $this->experiences = $this->acfRepeaterElements( 'itjob_cv_experiences', [
      'exp_dateBegin',
      'exp_dateEnd',
      'exp_city',
      'exp_country',
      'exp_company',
      'exp_mission',
      'validated'
    ] );

    $this->centerInterest = $this->acfGroupField( 'itjob_cv_centerInterest', [ 'various', 'projet' ] );
    $this->newsletter     = get_field( 'itjob_cv_newsletter', $this->getId() );
    $this->driveLicences  = get_field( 'itjob_cv_driveLicence', $this->getId() );

    return true;

  }

  /**
   * Vérifier si le candidate est notifier pour les formations
   * @return bool
   */
  protected function getTrainingNotif() {
    $notif = get_field( 'itjob_cv_notifFormation', $this->getId() );
    if ( $notif ) {
      if ( $notif['notification'] ) {
        $this->trainingNotif = (object) [ 'branch_activity' => $notif['branch_activity'] ];
      }
    }
  }

  /**
   * Vérifier si le candidate est notifier pour les emplois publier
   * @return bool
   */
  protected function getJobNotif() {
    $notif = get_field( 'itjob_cv_notifEmploi', $this->getId() );
    if ( $notif ) {
      if ( $notif['notification'] ) {
        $this->jobNotif = [
          'branch_activity' => $notif['branch_activity'],
          'job_sought'      => $notif['job_sought']
        ];
      }
    }
  }

  /**
   * Vérifier si le CV est le mien
   * Si oui, Ajouter mes notifications sur les emplois et formations
   */
  public function isMyCV() {
    $User = wp_get_current_user();
    if ( $User->user_email === $this->email || is_user_admin() ) {
      // Récuperer les notifications du client
      // Notification pour les offres
      $this->getJobNotif();
      // Notification pour les formations
      $this->getTrainingNotif();

      // Les informations du candidate
      $this->getInformations();
    }
  }

  /**
   * Récuperer les information de contact et son nom
   */
  protected function getInformations() {
    $this->privateInformations                = new \stdClass();
    $this->privateInformations->cellphone     = $this->getCellphone();
    $this->privateInformations->phone         = $this->getPhones();
    $this->privateInformations->firstname     = $this->getFirstName();
    $this->privateInformations->lastname      = $this->getLastName();
    $this->privateInformations->address       = $this->getAddress();
    $this->privateInformations->birthday_date = $this->getBirthday();

    $this->privateInformations->author = $this->author;
    $this->privateInformations->avatar = $this->avatar;

  }

  /**
   * Cette fonction récupere les informations privé d'un candidate.
   * Il est appelé dans le filtre "the_post' pour les utilisateurs de compte entreprise premium.
   * NB: L'utilisation de cette fonction n'est pas conseiller pour une raison de sécurité
   */
  public function __client_premium_access() {
    if ( ! is_user_logged_in() ) {
      return false;
    }
    $this->getInformations();

    return true;
  }

  public function __get_access() {
    $this->getJobNotif();
    $this->getTrainingNotif();

    return $this->__client_premium_access();
  }

  /**
   * Récuperer les terms
   */
  private function fieldTax() {
    // Get postuled offer
    $this->postuled  = get_field( 'itjob_cv_offer_apply', $this->getId() );
    $this->languages = wp_get_post_terms( $this->getId(), 'language', [ "fields" => "all" ] );
    // Get softwares
    $softwares       = wp_get_post_terms( $this->getId(), 'software', [ "fields" => "all" ] );
    $this->softwares = $this->getActivateField( $softwares );
    // Get region
    $this->region = wp_get_post_terms( $this->getId(), 'region', [ "fields" => "all" ] );
    $this->region = isset( $this->region[0] ) ? $this->region[0] : '';
    // Get region
    $this->country = wp_get_post_terms( $this->getId(), 'city', [ "fields" => "all" ] );
    $this->country = isset( $this->country[0] ) ? $this->country[0] : '';

    // Récuperer l'ancien emploi rechercher
    $old_job_sought = get_post_meta( $this->getId(), '_old_job_sought', true );
    // Récuperer les emplois recherché
    $jobSoughts     = wp_get_post_terms( $this->getId(), 'job_sought', [ "fields" => "all" ] );
    $jobSoughts     = $this->getActivateField( $jobSoughts );
    // Si l'ancien emploi est definie ou existe
    if ( $old_job_sought ) {
      $objJob         = new \stdClass();
      $objJob->name      = $old_job_sought;
      $objJob->activated = true;
    }
    $this->jobSought = empty( $jobSoughts ) ? ( $old_job_sought ? $objJob : [] ) : $jobSoughts;

    // Les tags sont ajouter par 'administrateur
    $this->tags = wp_get_post_terms( $this->getId(), 'itjob_tag', [ "fields" => "names" ] );
    // Le secteur d'activite du candidate
    $this->branch_activity = wp_get_post_terms( $this->getId(), 'branch_activity', [ "fields" => "all" ] );
    $this->branch_activity = ! is_array( $this->branch_activity ) || ! empty( $this->branch_activity ) ? $this->branch_activity[0] : null;
  }

  /**
   * Cette fonction permet de recuperer les terms qui sont activer
   *
   * @param array $terms - Array of term
   *
   * @return array
   */
  private function getActivateField( $terms ) {
    $validTerms = [];
    if ( ! is_wp_error( $terms ) ) :
      foreach ( $terms as $term ) {
        if ( ! is_wp_error( $term ) ) {
          $valid           = get_term_meta( $term->term_id, 'activated', true );
          $term->activated = (int) $valid;
          array_push( $validTerms, $term );
        }
      }
    endif;

    return $validTerms;
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
    if ( $rows && is_array($rows) ) {
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

  public function remove() {
  }

  public function update() {
  }
}
