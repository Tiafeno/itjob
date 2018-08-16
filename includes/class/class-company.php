<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

final class Company implements iCompany {
  // Added Trait Class
  use Auth;

  public $ID;
  public $user;
  public $title;
  public $address;
  public $nif;
  public $stat;
  public $phones = array();
  public $newsletter = false;
  public $notification = false;

  /**
   * Company constructor.
   *
   * @param int $postId - ID du post de type 'company'
   */
  public function __construct( $postId ) {
    if ( is_null( get_post( $postId ) ) ) {
      return null;
    }
    $post        = get_post( $postId );
    $this->ID    = $post->ID;
    $this->title = $post->post_title;
    $this->user  = $this->getUserData( $post->post_author );
    $this->acfElements();
  }

  private function acfElements() {
    global $wp_version;
    if ( ! function_exists( 'get_field' ) ) {
      _doing_it_wrong( 'get_field', 'Function get_field n\'existe pas', $wp_version );

      return false;
    }
    $this->address      = get_field( 'itjob_company_address', $this->ID );
    $this->nif          = get_field( 'itjob_company_nif', $this->ID );
    $this->stat         = get_field( 'itjob_company_stat', $this->ID );
    $this->phones       = get_field( 'itjob_company_phones', $this->ID );
    $this->newsletter   = get_field( 'itjob_company_newsletter', $this->ID );
    $this->notification = get_field( 'itjob_company_notification', $this->ID );

    return true;
  }

  /**
   * Récuperer les informations nécessaire d'un utilisateur
   *
   * @param int $userId - ID d'un utilisateur
   *
   * @return stdClass
   */
  public function getUserData( $userId ) {
    $user                    = new WP_User( $userId );
    $userClass               = new stdClass();
    $userClass->user_login   = $user->user_login;
    $userClass->token        = $user->user_pass;
    $userClass->user_email   = $user->user_email;
    $userClass->display_name = $user->display_name;

    return $userClass;
  }

  /**
   * @param int $paged
   * @param array $order
   *
   * @return array - Un tableau qui contient des objets'Company'
   */
  public static function getAllCompany( $paged = 10, $order = [ 'orderby' => 'date', 'order' => "DESC" ] ) {
    $allCompany = [];
    $args       = [
      'post_type'      => 'company',
      'posts_per_page' => $paged,
      'post_status'    => 'publish',
      'orderby'        => $order['orderby'],
      'order'          => $order['order']
    ];
    $posts      = get_posts( $args );
    foreach ( $posts as $post ) : setup_postdata( $post );
      array_push( $allCompany, new self( $post->ID ) );
    endforeach;
    wp_reset_postdata();

    return $allCompany;
  }

  public function getCompany() {
    // TODO: Implement getCompany() method.
  }

  public function updateCompany() {
    // TODO: Implement updateCompany() method.
  }

  public function removeCompany() {
    // TODO: Implement removeCompany() method.
  }
}