<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\object as Object;

final class Offers implements \iOffer {
  // Added Trait Class
  use \Auth;

  /** @var int $ID - Identification de l'offre */
  public $ID;

  private $activated;

  public $postPromote;

  public $branch_activity;

  /** @var array|null $tags - Tag pour le réferencement et la recherche interne */
  public $tags = [];

  /** @var string $title - Titre de l'offre équivalent avec la variable `Poste pourvu` */
  public $title;

  /** @var date $datePublication - Date de la publication de l'offre */
  public $datePublication;

  /** @var date $dateLimit - Date limite de publication */
  public $dateLimit;

  /** @var string $reference -  Référence de l'offre */
  public $reference;

  /** @var int $proposedSalary - Salaire proposé (facultatif) */
  public $proposedSalary;

  /** @var string $region - Region où se trouve l'offre */
  public $region;

  /** @var array $contractType -  Type de contrat */
  public $contractType;

  /** @var string $profil - Type de profil réchercher pour l'offre */
  public $profil;

  /** @var string $mission - Les mission qui seront effectuer par le ou les candidats */
  public $mission;

  /** @var string $otherInformation - Autres information nécessaire (facultatif) */
  public $otherInformation;

  /** @var bool $featured - L'offre est à la une ou pas */
  private $featured;


  public function __construct( $postId = null ) {
    if ( is_null( $postId ) ) {
      return false;
    }

    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    $output                = get_post( $postId );
    $this->ID              = $output->ID;
    $this->title           = $output->post_title; // Position Filled
    $this->userAuthor      = Object\jobServices::getUserData( $output->post_author );
    $this->datePublication = get_the_date( 'j F, Y', $output );
    if ( $this->is_offer() ) {
      $this->acfElements()->getOfferTaxonomy();
    }

    return $this;
  }

  public function is_offer() {
    return get_post_type( $this->ID ) === 'offers';
  }

  public function is_activated() {
    return $this->activated ? 1 : 0;
  }

  /**
   * Récuperer les tags et la region pour l'annonce
   */
  private function getOfferTaxonomy() {
    // get region
    $regions      = wp_get_post_terms( $this->ID, 'region', [
      "fields" => "all"
    ] );
    $this->region = reset( $regions );
    $this->tags   = wp_get_post_terms( $this->ID, 'itjob_tag', [ "fields" => "names" ] );
    if ( is_wp_error( $this->tags ) ) {
      $this->tags = null;
    }
  }

  private function acfElements() {
    global $wp_version;
    if ( ! function_exists( 'get_field' ) ) {
      _doing_it_wrong( 'get_field', 'Function get_field n\'existe pas', $wp_version );

      return false;
    }
    // company
    $this->company = get_field( 'itjob_offer_company', $this->ID ); // Object article

    $this->dateLimit        = get_field( 'itjob_offer_datelimit', $this->ID ); // Date
    $this->dateLimitFormat  = \DateTime::createFromFormat( 'm/d/Y', $this->dateLimit )->format( 'F j, Y' );
    $this->activated        = get_field( 'activated', $this->ID ); // Bool
    $this->postPromote      = get_field( 'itjob_offer_post', $this->ID ); // Date
    $this->reference        = get_field( 'itjob_offer_reference', $this->ID );
    $this->proposedSalary   = get_field( 'itjob_offer_proposedsallary', $this->ID );
    $this->contractType     = get_field( 'itjob_offer_contrattype', $this->ID );
    $this->profil           = get_field( 'itjob_offer_profil', $this->ID ); // WYSIWYG
    $this->mission          = get_field( 'itjob_offer_mission', $this->ID ); // WYSIWYG
    $this->otherInformation = get_field( 'itjob_offer_otherinformation', $this->ID ); // WYSIWYG
    $this->featured         = get_field( 'itjob_offer_featured', $this->ID ); // Bool
    $this->branch_activity  = get_field( 'itjob_offer_abranch', $this->ID );

    return $this;
  }

  /** return all offers */
  public static function getOffers( $paged = 10 ) {
    $allOffers = [];
    $args      = [
      'post_type'      => 'offers',
      'posts_per_page' => $paged,
      'post_status'    => [ 'publish', 'pending' ],
      'orderby'        => 'date',
      'order'          => 'DESC'
    ];
    $posts     = get_posts( $args );
    foreach ( $posts as $post ) : setup_postdata( $post );
      array_push( $allOffers, new self( $post->ID ) );
    endforeach;
    wp_reset_postdata();

    return $allOffers;
  }


  public function update() {

  }

  public function remove() {
    delete_field( 'itjob_offer_company', $this->ID );
    delete_field( 'itjob_offer_datelimit', $this->ID );
    delete_field( 'itjob_offer_reference', $this->ID );
    delete_field( 'itjob_offer_proposedsallary', $this->ID );
    delete_field( 'itjob_offer_contrattype', $this->ID );
    delete_field( 'itjob_offer_profil', $this->ID );
    delete_field( 'itjob_offer_mission', $this->ID );
    delete_field( 'itjob_offer_otherinformation', $this->ID );
    delete_field( 'itjob_offer_featured', $this->ID );
  }

  public function isFeatured() {
    return $this->featured == true;
  }
}