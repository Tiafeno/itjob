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
  public $greeting; // Mr, Mrs
  // Le nom de l'utilisateur ou le responsable
  public $name;
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
  public $newsletter = false;
  public $notification = false;

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
        $pt   = reset( $pts );

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
    $this->ID    = $output->ID;
    $this->title = $output->post_title;
    $this->addDate = get_the_date( 'l, j F Y', $output);

    if ( $this->is_company() ) {
      // FIX: Corriger une erreur sur l'utilisateur si l'admin ajoute une company
      $this->email = get_field( 'itjob_company_email', $this->ID );
      $user        = get_user_by( 'email', trim( $this->email ) ); // WP_User

      // FIX: Ajouter ou crée un utilisateur quand un entreprise est publier ou ajouter
      $this->author = Obj\jobServices::getUserData( $user->ID );

      // Récuperer la region
      $regions = wp_get_post_terms($this->ID, 'region');
      $this->region = reset($regions);

      // Récuperer le nom et la code postal de la ville
      $country = wp_get_post_terms($this->ID, 'city');
      $this->country = reset($country);

      // Récuperer le secteur d'activité
      $abranch = wp_get_post_terms($this->ID, 'branch_activity');
      $this->branch_activity = reset($abranch);

      $this->init();
    }
  }

  public function is_company() {
    return get_post_type( $this->ID ) === 'company';
  }

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

    $cellphones = get_field( 'itjob_company_cellphone', $this->ID );

    $this->cellphones = [];
    if (is_array($cellphones))
      foreach ($cellphones as $cellphone) {
        array_push($this->cellphones, $cellphone['number']);
      }

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