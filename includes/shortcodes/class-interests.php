<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use Underscore\Types\Arrays;

class scInterests {
  public function __construct() {
    // Page title: Interest candidate
    add_shortcode( 'ask_candidate', [ &$this, 'ask_candidate_render_html' ] );

    // ajax
    add_action( 'wp_ajax_get_ask_cv', [ &$this, 'get_ask_cv' ] );
    add_action( 'wp_ajax_nopriv_get_ask_cv', [ &$this, 'get_ask_cv' ] );

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
    $token        = Http\Request::getValue( 'token' );
    $mode         = Http\Request::getValue( 'mode', 'added' );
    if ( ! $token || ! $candidate_id || $User->ID === 0 ) {
      return "Une erreur s'est produite";
    }
    if ( trim( $token ) === $User->data->user_pass && $candidate_id ) {
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
          return "<p class='text-center mt-4'>Vous avez epuiser le nombre limite pour voir les CV des candidates.</p>";
        }
        // Added access token
        if ( ! $Candidate->hasTokenAccess( $token ) ) {
          $Candidate->updateAccessToken();
        }
      endif;

      if ( $mode === 'view' ):
        // Ici pour voir le CV en mode premium
        if ( ! $Entreprise->isPremium() ) {
          return "<p class='text-center mt-4'>Vous n'avez pas d'accès sur la partie de cette page. Membre premium seulement</p>";
        }
      endif;
      // Vérifier si le candidat est déja dans la liste
      $candidate_id = $Candidate->getAuthor()->data->ID;
      if ( ! in_array( $candidate_id, $candidate_ids ) ) {
        // Mettre à jours la liste des candidats ajouter par l'entreprise
        // Cette liste sera mise à jours pour les entreprise premium ou standart
        array_push( $candidate_ids, $candidate_id );
        update_field( 'itjob_company_interests', $candidate_ids, $Entreprise->getId() );
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


  public function remove_token_access() {
    if ( ! is_user_logged_in() ) {
      wp_send_json_error( "Accès refuser" );
    }
    if ( is_admin() && current_user_can( 'delete_user' ) ) {
      $token        = Http\Request::getValue( 'token' );
      $candidate_id = (int) Http\Request::getValue( 'candidate_id' );
      $company_id   = (int) Http\Request::getValue( 'company_id' );
      if ( $token && $candidate_id ) {
        $Company = new Company( $company_id );
        // Récuperer les tokens qui ont access au informations privée du candidat.
        // La valeur retourner est un tableau d'objet Token (class-token.php)
        $company_access_token = get_post_meta( $candidate_id, 'access_company_token', true );

        // Mettre à jours la liste des token qui ont access a ce candidat
        if ( ! is_array( $company_access_token ) || empty( $company_access_token ) ) {
          wp_send_json_success( "Il existe aucun token dans le CV" );
        }
        $company_access_token = Arrays::filter( $company_access_token, function ( $Token ) use ( $token ) {
          /** @var Token $Token */
          return $Token->getToken() !== $token;
        } );
        update_post_meta( $candidate_id, 'access_company_token', $company_access_token );

        // Supprimer l'id du candidat dans la liste des candidats interesé de l'entreprise
        $candidate_ids = $Company->getInterests();
        $candidate_ids = Arrays::filter( $candidate_ids, function ( $id ) use ( $candidate_id ) {
          return $id !== $candidate_id;
        } );
        update_field( 'itjob_company_interests', $candidate_ids, $Company->getId() );
        wp_send_json_success( [ "Le token à bien etes supprimer avec succès", $candidate_ids ] );
      }
    }
  }

  /**
   * Cette function permet de recuperer les informations sur l'utilisateur s'il peut s'interesser sur le candidate.
   * Si l'utilisateur connecter est une entreprise, un lien vers la page de visualisation du CV sera disponible.
   */
  public function get_ask_cv() {
    if ( ! \wp_doing_ajax() ) {
      wp_send_json( [ 'success' => false, 'msg' => false, 'status' => 'ajax' ] );
    }
    if ( ! \is_user_logged_in() ) {
      $cvId            = Http\Request::getValue( 'cvId' );
      $redir           = get_the_permalink( (int) $cvId );
      $singup_page_url = get_the_permalink( (int) REGISTER_COMPANY_PAGE_ID );
      wp_send_json(
        [
          'success' => false,
          'msg'     => 'Mme/Mr pour pouvoir sélectionner ce candidat vous devez vous inscrire, cela est gratuit, ' .
                       'en cliquant sur le bouton «s’inscrire » sinon si vous êtes déjà inscrit ' .
                       'cliquez sur le bouton « connexion »',
          'status'  => 'logged',
          'data'    => [
            'loginUrl'  => home_url( "/connexion/company?redir={$redir}" ),
            'singupUrl' => $singup_page_url
          ]
        ] );
    }
    $User = wp_get_current_user();
    if ( in_array( 'company', $User->roles ) ) {
      $cv_url = jobServices::page_exists( 'Interest candidate' );
      wp_send_json( [
        'success' => true,
        'client'  => [ 'token' => $User->data->user_pass, 'cv_url' => get_the_permalink( $cv_url ) ]
      ] );
    } else {
      wp_send_json( [
        'success' => false,
        'msg'     => 'Vous ne pouvez sélectionner de candidat avec votre compte',
        'status'  => 'user'
      ] );
    }
  }
}

return new scInterests();