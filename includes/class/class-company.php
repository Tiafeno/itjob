<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\model\itModel;
use includes\object as Obj;

final class Company implements \iCompany {
  // Added Trait Class
  use \Auth;

  private static $error = false;
  public $addDate;
  public $ID;
  public $greeting; // mr: Monsieur, mrs: Madame
  // Le nom de l'utilisateur ou le responsable
  public $name;
  // Contient les information sur le compte utilisateur WP
  public $author;
  // Adresse email de l'utilisateur ou le responsable
  public $email;
  public $title;
  public $address;
  public $region;
  public $country;
  public $nif;
  public $stat;
  public $phone;
  public $alerts;
  public $newsletter = false;
  public $notification = false;
  // Cette variable contient l'information sur le type du compte
  public $account = 0; // 0: Standart, 1: Premium
  public $sector = 0; // 1: Recruteur, 2: Formateur (user post meta)
  // Contient les identifiants des candidats ou utilisateur wordpress (User id)
  private $interests = [];

  /**
   * @param int $value
   * @param string $handler - user_id, post_id (company post type) & email
   * @return Company|\WP_Error
   */
  public static function get_company_by( $value, $handler = 'user_id', $private_access = false ) {
    switch ( $handler ):
      case 'user_id':
        $User = get_user_by( 'ID', (int) $value );
        if ( ! $User->ID || $User->ID === 0 ) {
          return new \WP_Error('broke', "Utilisateur introuvable");
        }

        if ( ! in_array('company', $User->roles) ) {
          return new \WP_Error('broke', "Compte invalide");
        }

        $args = [
          'post_status'  => [ 'pending', 'publish' ],
          'post_type'    => 'company',
          'meta_key'     => 'itjob_company_email',
          'meta_value'   => $User->user_email,
          'meta_compare' => '='
        ];
        $pts  = get_posts( $args );
        if (is_array($pts) && empty($pts)) {
          return new \WP_Error('broke', "Compte professionnel inrouvable");
        }
        $pt   = $pts[0];
        return new Company( $pt->ID, $private_access );
        break;
    endswitch;
  }

  /**
   * Company constructor.
   *
   * @param int $postId - ID du post de type 'company'
   */
  public function __construct( $post, $access = false ) {
    if ( is_int( $post ) ) {
      if ( ! is_null( get_post( $post ) ) ) {
        $output = get_post( $post );
      } else {
        return new \WP_Error('broke', "Identifiant incorrect");
      }
    }

    if ( $post instanceof \WP_Post ) {
      $output = $post;
    }

    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    if ( is_null( $output ) ) {
      return new \WP_Error('broke', "Compte professionnel inrouvable");
    }

    if ( ! $this->is_company() ) {
      return new \WP_Error('broke', "Désolé, Votre compte n'est pas une compte professionnel");
    }

    $this->ID      = $output->ID;
    $this->title   = $output->post_title;
    $this->post_status = $output->post_status;
    $this->postType = $output->post_type;
    $this->addDate = get_the_date( 'l, j F Y', $output );
    $this->add_create = $output->post_date;

    $this->email = get_field( 'itjob_company_email', $this->ID );
    $user        = get_user_by( 'email', trim( $this->email ) ); // WP_User
    $this->author = Obj\jobServices::getUserData( $user->ID );
    $sector = get_user_meta($user->ID, 'sector', true);
    $this->sector = $sector ? (int)$sector : 1;
    // Récuperer la region
    $regions      = wp_get_post_terms( $this->ID, 'region' );
    $this->region = is_array($regions) && !empty($regions)  ? $regions[0] : null;
    // Récuperer le nom et la code postal de la ville
    $country       = wp_get_post_terms( $this->ID, 'city' );
    $this->country = is_array($country) && !empty($country)  ? $country[0] : null;
    // Récuperer le secteur d'activité
    $abranch               = wp_get_post_terms( $this->ID, 'branch_activity', [ "fields" => "all" ] );
    $this->branch_activity = is_array($abranch) && !empty($abranch)  ? $abranch[0] : null;

    $this->init();
    if ($access) {
      $this->getInterests();
    }
  }

  public static function is_wp_error ()
  {
    if (is_wp_error(self::$error)) {
      return self::$error->get_error_message();
    } else {
      return false;
    }
  }

  /**
   * Recuperer les identifiants ou les CV que l'entreprise s'interest
   * @return array|mixed
   */
  public function getInterests() {
    $itModel = new itModel();
    $interests = $itModel->get_interests($this->ID);
    $interests = array_map(function ($interest) { return $interest->id_candidate; }, $interests);
    return $this->interests = empty($interests) || !$interests ? [] : $interests;
  }

  /**
   * Vérifier si l'entreprise est un compte premium
   */
  public function isPremium() {
    if (!is_user_logged_in()) return false;
    $account = get_post_meta($this->getId(), 'itjob_meta_account', true);
    return (int)$account === 1 ? true : false;
  }

  /**
   * Vérifier si l'netrepise est valide
   * @return mixed|null
   */
  public function isValid() {
    $activated = get_field("activated", $this->ID);
    return $activated;
  }
  /**
   * Récuperer l'ID de l'entreprise (Non pas l'id de l'utilisateur)
   * @return int
   */
  public function getId() {
    return $this->ID;
  }

  /**
   * Vérifier si le jeton (id) est pour un compte entreprise ou autres
   * @return bool
   */
  public function is_company() {
    return get_post_type( $this->ID ) === 'company';
  }

  /**
   * Initialiser les propriétés de cette class
   * @return bool
   */
  private function init() {
    global $wp_version;
    if ( ! function_exists( 'get_field' ) ) {
      _doing_it_wrong( 'get_field', 'Function get_field n\'existe pas', $wp_version );

      return false;
    }
    $this->greeting     = get_field( 'itjob_company_greeting', $this->ID );
    $this->name         = get_field( 'itjob_company_name', $this->ID );
    $this->address      = get_field( 'itjob_company_address', $this->ID );
    $this->nif          = get_field( 'itjob_company_nif', $this->ID );
    $this->stat         = get_field( 'itjob_company_stat', $this->ID );

    $newsletter   = get_field( 'itjob_company_newsletter', $this->ID );
    $this->newsletter = boolval($newsletter);

    $notification = get_field( 'itjob_company_notification', $this->ID );
    $this->notification = boolval($notification);
    $phone        = get_field( 'itjob_company_phone', $this->ID );
    $this->phone  = $phone ? $phone : null;

    $account      = get_post_meta( $this->ID, 'itjob_meta_account', true );
    $this->account      = empty( $account ) ? 0 : (int)$account;

    $cellphones = get_field( 'itjob_company_cellphone', $this->ID );
    $this->cellphones = [];
    if ( is_array( $cellphones ) ) {
      foreach ( $cellphones as $cellphone ) {
        array_push( $this->cellphones, $cellphone['number'] );
      }
    }

    // Recuperer les alerts de cette entreprise
    $alerts = get_field('itjob_company_alerts', $this->ID);
    $this->alerts = !$alerts || !empty($alerts) ? explode(',', $alerts) : [];

    return true;
  }

  /**
   * @param int $paged
   * @param array $order
   *
   * @return array - Un tableau qui contient des objets'Company'
   */
  public static function getAllCompany( $paged = - 1 ) {
    $allCompany = [];
    $args       = [
      'post_type'      => 'company',
      'posts_per_page' => $paged,
      'post_status'    => 'publish',
      'orderby'        => 'date',
      'order'          => 'DESC'
    ];
    $posts      = get_posts( $args );
    foreach ( $posts as $post ) : setup_postdata( $post );
      array_push( $allCompany, new self( $post->ID ) );
    endforeach;
    wp_reset_postdata();

    return $allCompany;
  }


  public function update() {
    // TODO: Implement updateCompany() method.
  }

  public function remove() {
    // TODO: Implement removeCompany() method.
  }
}