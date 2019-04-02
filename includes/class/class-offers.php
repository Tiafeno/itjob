<?php

namespace includes\post;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\object as Obj;
use includes\model\itModel;

final class Offers implements \iOffer {
  // Added Trait Class
  use \Auth;
  use \OfferHelper;

  /** @var int $ID - Identification de l'offre */
  public $ID = 0;

  /** @var url $offer_url - Contient le liens de l'offre */
  public $offer_url;

  /** @var bool $activated - 1: Activer, 0: Non disponible */
  public $activated;

  /** @var string $postPromote - Titre ou le champ 'itjob_offer_post' ACF */
  public $postPromote;

  /** @var array $branch_activity - Secteur d'activité */
  public $branch_activity;

  /** @var array $offer_status - Status de l'offre ['status' => 0 , 'name' => 'En attente'] */
  public $offer_status;

  /** @var array|null $tags - Tag pour le réferencement et la recherche interne */
  public $tags = [];

  /** @var string $title - Titre de l'offre équivalent avec la variable `$postPromote` */
  public $title;

  /** @var date $datePublication - Date de la publication de l'offre */
  public $datePublication;

  /** @var date $dateLimit - Date limite de publication */
  public $dateLimit;

  /** @var string $reference -  Référence de l'offre */
  public $reference;

  /** @var int $proposedSalary - Salaire proposé (facultatif) */
  public $proposedSalary;

  /** @var string $region - Region */
  public $region;

  /** @var $town - Ville */
  public $town;

  /** @var array $contractType -  Type de contrat */
  public $contractType;

  /** @var string $rateplan - Mode de diffusion (sereine, standard et premium) */
  public $rateplan;
  public $paid = 0;

  /** @var string $profil - Type de profil réchercher pour l'offre */
  public $profil;

  /** @var string $mission - Les mission qui seront effectuer par le ou les candidats */
  public $mission;

  /** @var string $otherInformation - Autres information nécessaire (facultatif) */
  public $otherInformation;

  /** @var bool $featured - L'offre est à la une ou pas */
  public $featured;
  public $featuredDateLimit = null;


  public function __construct( $postId = null, $private_access = false ) {
    if ( is_null( $postId ) ) {
      return new \WP_Error('broke', "Désolé, l'identification est introuvable");
    }

    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    $output             = get_post( $postId );
    $this->ID           = $output->ID;

    if ( ! $this->is_offer()) {
      return new \WP_Error('broke', "Désolé, Votre post n'est pas une offre valide");
    }

    $this->post_type    = $output->post_type;
    $this->title        = $output->post_title; // Position Filled
    $this->offer_url    = get_the_permalink( $output->ID );
    $this->offer_status = $output->post_status;
    $this->datePublication = get_the_date( 'j F, Y', $output );
    $this->date_create  = $output->post_date;

    $this->id_offer = &$this->ID;
    $this->post_url = get_the_permalink( $this->ID );
    $this->acfElements()->getOfferTaxonomy();

    if ($private_access) {
      $this->__get_access();
    }

    // La variable `author` contient l'information de l'utilisateur qui a publier l'offre.
    // Retourne post entreprise...
    $post_company   = get_field( "itjob_offer_company", $this->ID );
    if (empty($post_company) || !isset($post_company->ID)) return $this;
    $company_email  = get_field( 'itjob_company_email', $post_company->ID );
    $post_user      = get_user_by( 'email', trim($company_email) );
    $this->author   = Obj\jobServices::getUserData( $post_user->ID );
    return $this;
  }

  public function getId() {
    return $this->ID;
  }

  public function is_offer() {
    return get_post_type( $this->ID ) === 'offers';
  }

  public function is_activated() {
    return $this->activated ? 1 : 0;
  }

  public function is_publish() {
    return $this->offer_status === 'publish' ? 1 : 0;
  }

  public function __get_access() {
    if ( ! is_user_logged_in() ) {
      return;
    }
    $this->getPrivateInformations();
  }

  /**
   * Récuperer les tags et la region pour l'annonce
   */
  private function getOfferTaxonomy() {
    // get region
    $regions      = wp_get_post_terms( $this->ID, 'region', ["fields" => "all"] );
    $towns        = wp_get_post_terms( $this->ID, 'city', ["fields" => "all"]);
    $this->region = is_array($regions) && !empty($regions) ? $regions[0] : '';
    $this->town   = is_array($towns) && !empty($towns) ? $towns[0] : null;
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
    $this->dateLimitFormat  = date_i18n( 'j F Y', strtotime($this->dateLimit)); // \DateTime::createFromFormat( 'm/d/Y', $this->dateLimit )->format( 'F j, Y' );
    $this->activated        = get_field( 'activated', $this->ID ); // Bool
    $this->postPromote      = get_field( 'itjob_offer_post', $this->ID ); // Date
    $this->reference        = get_field( 'itjob_offer_reference', $this->ID );
    $this->proposedSalary   = get_field( 'itjob_offer_proposedsallary', $this->ID );
    $this->contractType     = get_field( 'itjob_offer_contrattype', $this->ID );
    $profil           = get_field( 'itjob_offer_profil', $this->ID ); // WYSIWYG
    $this->profil     = apply_filters( 'the_content', $profil );
    $mission          = get_field( 'itjob_offer_mission', $this->ID ); // WYSIWYG
    $this->mission    = apply_filters( 'the_content', $mission );
    $this->otherInformation = get_field( 'itjob_offer_otherinformation', $this->ID ); // WYSIWYG
    $featured         = get_field( 'itjob_offer_featured', $this->ID ); // Bool
    $this->featured = boolval($featured);
    if (boolval($this->featured)){
      $featuredDateLimit = get_field('itjob_offer_featured_datelimit', $this->ID);
      $this->featuredDateLimit = strtotime($featuredDateLimit);
    }
    $this->branch_activity  = get_field( 'itjob_offer_abranch', $this->ID ); // Objet Term
    $rateplan       = get_field( 'itjob_offer_rateplan', $this->ID ); // String
    $this->rateplan = $rateplan ? $rateplan : 'standard';
    if ($this->rateplan !== 'standard') {
      $paid = get_field('itjob_offer_paid', $this->ID); // bool
      $this->paid = $paid ? intval($paid) : 0;
    }

    return $this;
  }

  /** return all offers */
  public static function getOffers( $paged = 10 ) {
    $allOffers = [];
    $args      = [
      'post_type'      => 'offers',
      'posts_per_page' => $paged,
      'post_status'    => [ 'publish' ],
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

    $Model = new itModel();
    $Model->remove_interest($this->ID);

  }

  public function isFeatured() {
    return $this->featured == true;
  }
}