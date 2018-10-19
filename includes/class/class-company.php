<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\object as Obj;

final class Company implements \iCompany {
  // Added Trait Class
  use \Auth;

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
  // Contient les identifiants des candidats ou utilisateur wordpress (User id)
  private $interests = [];

  /**
   * @param string $handler - user_id, post_id (company post type) & email
   * @param int|string $value
   */
  public static function get_company_by( $value, $handler = 'user_id' ) {
    switch ( $handler ):
      case 'user_id':
        $User = get_user_by( 'ID', (int) $value );
        $args = [
          'post_status'  => [ 'pending', 'publish' ],
          'post_type'    => 'company',
          'meta_key'     => 'itjob_company_email',
          'meta_value'   => $User->user_email,
          'meta_compare' => '='
        ];
        $pts  = get_posts( $args );
        $pt   = $pts[0];

        return new Company( $pt->ID );
        break;
    endswitch;
  }

  /**
   * Company constructor.
   *
   * @param int $postId - ID du post de type 'company'
   */
  public function __construct( $post ) {
    if ( is_int( $post ) ) {
      if ( ! is_null( get_post( $post ) ) ) {
        $output = get_post( $post );
      } else {
        return null;
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
      return false;
    }
    $this->ID      = $output->ID;
    $this->title   = $output->post_title;
    $this->addDate = get_the_date( 'l, j F Y', $output );

    if ( $this->is_company() ) {
      // FIX: Corriger une erreur sur l'utilisateur si l'admin ajoute une company
      $this->email = get_field( 'itjob_company_email', $this->ID );
      $user        = get_user_by( 'email', trim( $this->email ) ); // WP_User

      // FIX: Ajouter ou crée un utilisateur quand un entreprise est publier ou ajouter
      $this->author = Obj\jobServices::getUserData( $user->ID );

      // Récuperer la region
      $regions      = wp_get_post_terms( $this->ID, 'region' );
      $this->region = reset( $regions );

      // Récuperer le nom et la code postal de la ville
      $country       = wp_get_post_terms( $this->ID, 'city' );
      $this->country = reset( $country );

      // Récuperer le secteur d'activité
      $abranch               = wp_get_post_terms( $this->ID, 'branch_activity' );
      $this->branch_activity = !is_array ($abranch) || !empty($abranch) ? $abranch[0] : null;

      $this->init();
    }
  }

  /**
   * Recuperer les identifiants ou les CV que l'entreprise s'interest
   * @return array|mixed
   */
  public function getInterests() {
    $ids = get_field('itjob_company_interests', $this->ID);
    return $this->interests = empty($ids) || !$ids ? [] : $ids;
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
   * Initialiser les prorpriétés de cette class
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
    $this->newsletter   = get_field( 'itjob_company_newsletter', $this->ID );
    $this->notification = get_field( 'itjob_company_notification', $this->ID );
    $this->phone        = get_field( 'itjob_company_phone', $this->ID );
    $this->account      = get_post_meta( $this->ID, 'itjob_meta_account', true );
    $this->account      = empty( $this->account ) ? 0 : (int)$this->account;

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