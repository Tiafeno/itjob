<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

final class Offers implements iOffer {
  // Added Trait Class
  use Auth;

  /** @var int $ID - Identification de l'offre */
  public $ID;

  /** @var string $title - Titre de l'offre équivalent avec la variable `Poste pourvu` */
  public $title;

  /** @var date $datePublication - Date de la publication de l'offre */
  public $datePublication;

  /** @var date $dateLimit - Date limite de publication */
  public $dateLimit;

  /** @var  int $companyId - ID of company */
  public $companyId;

  /** @var string $positionFilled - Poste pourvu */
  public $positionFilled;

  /** @var string $reference -  Référence de l'offre */
  public $reference;

  /** @var int $proposedSalary - Salaire proposé (facultatif) */
  public $proposedSalary;

  /** @var string $region - Region où se trouve l'offre */
  public $region;

  /** @var string $contractType -  Type de contrat */
  public $contractType;

  /** @var string $profile - Type de profil réchercher pour l'offre */
  public $profile;

  /** @var string $mission - Les mission qui seront effectuer par le ou les candidats */
  public $mission;

  /** @var string $otherInformation - Autres information nécessaire (facultatif) */
  public $otherInformation;

  /** @var bool $featured - L'offre est à la une ou pas */
  public $featured;
  public $postType;

  public function __construct( $postId = null ) {
    if ( is_null( $postId ) ) {
      return false;
    }
    /** @var Wp_User|0 authUser */
    $this->authUser = wp_get_current_user();

    $post           = get_post( $postId );
    $this->ID       = $post->ID;
    $this->title    = $post->post_title; // Position Filled
    $this->postType = $post->post_type;
    $this->user     = jobServices::getUserData( $post->post_author );
    if ( $this->exist() ) {
      $this->acfElements();
    }
    return $this;
  }

  private function acfElements() {
    global $wp_version;
    if ( ! function_exists( 'get_field' ) ) {
      _doing_it_wrong( 'get_field', 'Function get_field n\'existe pas', $wp_version );

      return false;
    }
    $this->companyId = get_field('itjob_offer_companyid', $this->ID);
    $this->dateLimit = get_field('itjob_offer_datelimit', $this->ID);
    $this->reference = get_field('itjob_offer_reference', $this->ID);
    $this->proposedSalary = get_field('itjob_offer_proposedsallary', $this->ID);
    $this->contractType = get_field('itjob_offer_contrattype', $this->ID);
    $this->profile = get_field('itjob_offer_profil', $this->ID);
    $this->mission = get_field('itjob_offer_mission', $this->ID);
    $this->otherInformation = get_field('itjob_offer_otherinformation', $this->ID);
    $this->featured = get_field('itjob_offer_featured', $this->ID);

    return true;
  }

  /** return all offers */
  public static function getOffers( $paged = 10 ) {
    $allOffers = [];
    $args      = [
      'post_type'      => 'offers',
      'posts_per_page' => $paged,
      'post_status'    => 'publish',
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


  public function updateOffer() {

  }

  public function removeOffer() {
    delete_field('itjob_offer_companyid', $this->ID);
    delete_field('itjob_offer_datelimit', $this->ID);
    delete_field('itjob_offer_reference', $this->ID);
    delete_field('itjob_offer_proposedsallary', $this->ID);
    delete_field('itjob_offer_contrattype', $this->ID);
    delete_field('itjob_offer_profil', $this->ID);
    delete_field('itjob_offer_mission', $this->ID);
    delete_field('itjob_offer_otherinformation', $this->ID);
    delete_field('itjob_offer_featured', $this->ID);
  }

  public function isFeatured() {

  }
}