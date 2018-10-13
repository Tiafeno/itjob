<?php
namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
use Http;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;

class scInterests {
  public function __construct() {
    // Page title: Interest candidate
    add_shortcode( 'ask_candidate', [ &$this, 'ask_candidate_render_html' ] );

    // ajax
    add_action( 'wp_ajax_get_ask_cv', [ &$this, 'get_ask_cv' ] );
    add_action( 'wp_ajax_nopriv_get_ask_cv', [ &$this, 'get_ask_cv' ] );
  }

  /**
   * Cette function affiche le CV d'un candidate, c'est aussi un shortcode
   * @param $attrs
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
    $User = wp_get_current_user();
    $Entreprise = Company::get_company_by($User->ID);
    $candidate_id = (int)Http\Request::getValue('cvId');
    $token = Http\Request::getValue('token');
    if (!$token || !$candidate_id || $User->ID === 0) return "Une erreur s'est produite";
    if (trim($token) === $User->data->user_pass && $candidate_id) {
      $Candidate = new Candidate($candidate_id);
      // Une systéme pour limiter la visualisation des CV
      // Verifier si le compte de l'entreprise est sereine ou standart
      if (!$Candidate->is_candidate()) return "Un erreur dans l'url detecter";
      $user_ids = get_field('itjob_company_interests', $Entreprise->getId());
      $user_ids = $user_ids ? $user_ids : [];
      if (count($user_ids) > 5) return "Vous avez atteint le nombre limite de votre bonus.";
      // Added access token
      if ( ! $Candidate->hasTokenAccess($token)) {
        $Candidate->updateAccessToken();

        // Mettre à jours la liste des candidats ajouter par l'entreprise
        array_push($user_ids, $Candidate->author->data->ID);
        update_field('itjob_company_interests', $user_ids, $Entreprise->getId());
      }
    } else {
      return "<p class='text-center mt-4'>La clé est non valide</p>";
    }
    try {
      return $Engine->render( '@SC/cv-candidate.html.twig', [
        'candidate' => $Candidate,
      ]);
    } catch ( Twig_Error_Loader $e ) {
    } catch ( Twig_Error_Runtime $e ) {
    } catch ( Twig_Error_Syntax $e ) {
      echo $e->getRawMessage();
    }
  }

  /**
   * Cette function permet de recuperer les informations sur l'utilisateur s'il peut s'interesser sur le candidate.
   * Si l'utilisateur connecter est une entreprise, un lien vers la page de visualisation du CV sera disponible.
   */
  public function get_ask_cv() {
    if ( ! \wp_doing_ajax()) {
      wp_send_json(['success' => false, 'msg' => false, 'status' => 'ajax']);
    }
    if ( ! \is_user_logged_in()) {
      $cvId = Http\Request::getValue('cvId');
      $redir = get_the_permalink((int)$cvId);
      $singup_page_url = get_the_permalink((int)REGISTER_COMPANY_PAGE_ID);
      wp_send_json(
        [
        'success' => false,
        'msg' => 'Mme/Mr pour pouvoir sélectionner ce candidat vous devez vous inscrire, cela est gratuit, ' .
                 'en cliquant sur le bouton «s’inscrire » sinon si vous êtes déjà inscrit ' .
                 'cliquez sur le bouton « connexion »',
        'status' => 'logged',
        'data' => [
          'loginUrl' => home_url("/connexion/company?redir={$redir}"),
          'singupUrl' => $singup_page_url
        ]
        ]);
    }
    $User  = wp_get_current_user();
    if (in_array('company', $User->roles)) {
      $cv_url = jobServices::page_exists( 'Interest candidate' );
      wp_send_json(['success' => true, 'client' => ['token' => $User->data->user_pass, 'cv_url' => get_the_permalink($cv_url)]]);
    } else {
      wp_send_json(['success' => false, 'msg' => 'Vous ne pouvez sélectionner de candidat avec votre compte', 'status' => 'user']);
    }
  }
}

return new scInterests();