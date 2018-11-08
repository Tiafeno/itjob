<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\model\itModel;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;

class scInterests {
  public function __construct() {
    // Page title: Interest candidate
    add_shortcode( 'ask_candidate', [ &$this, 'ask_candidate_render_html' ] );

    // ajax
    add_action( 'wp_ajax_get_ask_cv', [ &$this, 'get_ask_cv' ] );
    add_action( 'wp_ajax_nopriv_get_ask_cv', [ &$this, 'get_ask_cv' ] );

    add_action( 'wp_ajax_get_current_user_offers', [ &$this, 'get_current_user_offers' ] );
    add_action( 'wp_ajax_nopriv_get_current_user_offers', [ &$this, 'get_current_user_offers' ] );

    add_action( 'wp_ajax_remove_token_access', [ &$this, 'remove_token_access' ] );
  }

  /**
   * Cette function affiche le CV d'un candidate, c'est aussi un shortcode
   *
   * @param $attrs
   *
   * @return string
   */
  public function ask_candidate_render_html( $attrs ) {
    global $Engine;
    $oc_url = jobServices::page_exists( 'Espace client' );
    extract(
      shortcode_atts(
        array(
          'redir' => $oc_url
        ),
        $attrs
      )
    );
    $User         = wp_get_current_user();
    $Entreprise   = Company::get_company_by( $User->ID );
    $candidate_id = (int) Http\Request::getValue( 'cvId' );
    $mode         = Http\Request::getValue( 'mode', 'added' );
    if ( ! $candidate_id || $User->ID === 0 ) {
      return "Une erreur s'est produite";
    }
    if ( $candidate_id ) {
      $Candidate = new Candidate( $candidate_id );
      // Une systéme pour limiter la visualisation des CV
      // Verifier si le compte de l'entreprise est sereine ou standart
      if ( ! $Candidate->is_candidate() || ! $Entreprise->is_company() ) {
        return "<p class='text-center mt-4'>Un erreur s'est produite</p>";
      }

      $candidate_ids = $Entreprise->getInterests();
      $candidate_ids = $candidate_ids ? $candidate_ids : [];

      // Le mode ajouter est le mode par default
      if ( $mode === 'added' ) :
        // featured: Un probléme se produits si le client est un membre premium
        if ( count( $candidate_ids ) > 5 && ! $Entreprise->isPremium() ) {
          return "<div class=\"alert uk-width-1-3 alert-info alert-dismissable fade show mt-4\">
                    <p>Vous avez epuiser le nombre limite pour voir les CV des candidates.</p>
                     <hr>
                    <div class=\"d-flex align-items-center justify-content-between\">
                      <button onclick='window.history.go(-1);' class=\"btn btn-sm btn-outline-pink btn-outline\" data-dismiss=\"alert\">Retour</button>
                    </div>
                  </div>";

        }
        // Added access token
        if ( ! $Candidate->hasTokenAccess( $token ) ) {
          $Candidate->updateAccessToken();
        }
      endif;

      if ( $mode === 'view' ):
        // Ici pour voir le CV en mode premium
        if ( ! $Entreprise->isPremium() ) {
          return "<div class=\"alert uk-width-1-3 alert-info alert-dismissable fade show mt-4\">
                    <p>Vous n'avez pas d'accès sur la partie de cette page. Membre premium seulement.</p>
                     <hr>
                    <div class=\"d-flex align-items-center justify-content-between\">
                      <button onclick='window.history.go(-1);' class=\"btn btn-sm btn-outline-pink btn-outline\" data-dismiss=\"alert\">Retour</button>
                    </div>
                  </div>";
        }
        $Candidate->__client_premium_access();
      endif;
      // Vérifier si le candidat est déja dans la liste
      // Si oui, envoyer un email de notification a l'administrateur
      $candidate_id = $Candidate->getAuthor()->data->ID;
      if ( ! in_array( $candidate_id, $candidate_ids ) ) {
        // Mettre à jours la liste des candidats ajouter par l'entreprise
        // Cette liste sera mise à jours pour les entreprise premium ou standart
        array_push( $candidate_ids, $candidate_id );
        update_field( 'interest_valid_cv', $candidate_ids, $Entreprise->getId() );
        // Envoyer un mail a l'administrateur
        do_action( 'alert_when_company_interest', $candidate_id );
      }
    } else {
      return "<p class='text-center mt-4'>La clé est non valide</p>";
    }
    try {
      return $Engine->render( '@SC/cv-candidate.html.twig', [
        'candidate' => $Candidate,
      ] );
    } catch ( Twig_Error_Loader $e ) {
    } catch ( Twig_Error_Runtime $e ) {
    } catch ( Twig_Error_Syntax $e ) {
      echo $e->getRawMessage();
    }
  }

  /**
   * Function ajax
   * Cette function permet de recuperer les informations sur l'utilisateur s'il peut s'interesser sur le candidate.
   * Si l'utilisateur connecter est une entreprise, un lien vers la page de visualisation du CV sera disponible.
   */
  public function get_ask_cv() {
    if ( ! \wp_doing_ajax() ) {
      wp_send_json_error( "Une erreur s'est produite" );
    }
    $cv_id    = (int) Http\Request::getValue( 'cv_id' );
    $offer_id = (int) Http\Request::getValue( 'offer_id', 0 );
    if ( ! $offer_id ) {
      wp_send_json_error( 'Une erreur s\'est produite' );
    }
    $redir           = get_the_permalink( $cv_id );
    $singup_page_url = get_the_permalink( (int) REGISTER_COMPANY_PAGE_ID );
    if ( ! \is_user_logged_in() ) {
      wp_send_json_error(
        [
          'msg'    => 'Mme/Mr pour pouvoir sélectionner ce candidat vous devez vous inscrire, cela est gratuit, ' .
                      'en cliquant sur le bouton «s’inscrire » sinon si vous êtes déjà inscrit ' .
                      'cliquez sur le bouton « connexion »',
          'status' => 'logged',
          'helper'   => [
            'login'  => home_url( "/connexion/company?redir={$redir}" ),
            'singup' => $singup_page_url
          ]
        ] );
    }
    $User = wp_get_current_user();
    if ( in_array( 'company', $User->roles ) ) {
      $itModel = new itModel();
      if ( ! $itModel->exist_interest( $cv_id, $offer_id ) ) {
        $response = $itModel->added_interest( $cv_id, $offer_id );
        do_action( 'alert_when_company_interest', $cv_id );
        wp_send_json_success( $response );
      } else {
        wp_send_json_error( [
          'msg'    => "Vous avez déja ajouter ce candidat dans votre liste",
          'status' => 'exist'
        ] );
      }

    } else {
      wp_send_json_error( [
        'msg'    => 'Vous ne pouvez sélectionner de candidat avec votre compte',
        'status' => 'access',
        'data'   => [
          'login'  => home_url( "/connexion/company?redir={$redir}" ),
          'singup' => $singup_page_url
        ]
      ] );
    }
  }

  /**
   * Function ajax
   * Récuperer les offres d'une entreprise
   *
   * @param null|int $user_id
   *
   * @return array|bool
   */
  public function get_current_user_offers( $user_id = null ) {
    if ( ! \wp_doing_ajax() || ! is_user_logged_in() ) {
      $singup_page_url = get_the_permalink( (int) REGISTER_COMPANY_PAGE_ID );
      wp_send_json_error( [
        'msg'    => 'Mme/Mr pour pouvoir sélectionner ce candidat vous devez vous inscrire, cela est gratuit, ' .
                    'en cliquant sur le bouton «s’inscrire » sinon si vous êtes déjà inscrit ' .
                    'cliquez sur le bouton « connexion »',
        'status' => 'logged',
        'helper' => [
          'login'  => home_url( "/connexion/company" ),
          'singup' => $singup_page_url
        ]
      ] );
    }
    if ( is_null( $user_id ) ) {
      $User = wp_get_current_user();
      if ( ! in_array( 'company', $User->roles ) ) {
        wp_send_json_error( [
          'msg'    => 'Vous ne pouvez pas sélectionner de candidat avec votre compte',
          'status' => 'access'
        ] );
      }
      $Company = Company::get_company_by( $User->ID );
    } else {
      $Company = new Company( (int) $user_id );
    }

    $args   = [
      'post_type'   => 'offers',
      'post_status' => 'publish',
      'meta_query'  => [
        'key'     => 'itjob_offer_company',
        'value'   => $Company->getId(),
        'compare' => '=',
        'type'    => 'NUMERIC'
      ]
    ];
    $offers = get_posts( $args );
    if (empty($offers)) {
      wp_send_json_error([
        'msg' => 'Vous n\'avez pas encore publier d\'offre. Veillez publier une offre avant de continuer',
        'status' => 'access'
      ]);
    }
    $offers = array_map( function ( $offer ) {
      return new Offers( $offer->ID );
    }, $offers );

    wp_send_json_success( $offers );
  }
}

return new scInterests();