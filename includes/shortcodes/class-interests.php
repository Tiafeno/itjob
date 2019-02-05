<?php

namespace includes\shortcode;

if (!defined('ABSPATH')) {
  exit;
}

use Http;
use includes\model\itModel;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;

class scInterests
{
  public function __construct()
  {
    add_action('init', function () {
     // add_rewrite_tag('%id%', '([^&]+)');
     // add_rewrite_rule('^download/([0-9]+)/?', admin_url('admin-ajax.php') . '?action=download_pdf&id=$matches[1]', 'top');

    });
    // Page title: Interest candidate
    add_shortcode('ask_candidate', [&$this, 'ask_candidate_render_html']);

    // ajax
    add_action('wp_ajax_get_ask_cv', [&$this, 'get_ask_cv']);
    add_action('wp_ajax_inspect_cv', [&$this, 'inspect_cv']);
    add_action('wp_ajax_nopriv_get_ask_cv', [&$this, 'get_ask_cv']);

    add_action('wp_ajax_get_current_user_offers', [&$this, 'get_current_user_offers']);
    add_action('wp_ajax_nopriv_get_current_user_offers', [&$this, 'get_current_user_offers']);
  }


  public static function get_cv_html($Candidate) {
    global $Engine;
    $html = '';
    try {
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $html .= $Engine->render('@SC/pdf.html', [
        'logo' => $logo[0],
        'candidate' => $Candidate,
      ]);
    } catch (Twig_Error_Loader $e) {
    } catch (Twig_Error_Runtime $e) {
    } catch (Twig_Error_Syntax $e) {
      echo $e->getRawMessage();
      return false;
    }

    return $html;
  }

  /**
   * Télécharger le CV par le bais d'une classe object
   */
  public static function get_cv_proformat($Candidate = null) {
    // create new PDF document
    require get_template_directory() . '/libs/pdfcrowd/pdfcrowd.php';
    $client = new \Pdfcrowd\HtmlToPdfClient("ddpixel", "d6f0bc2d93bd50ca240406e51e3a8279");

    //$mpdf = new \Mpdf\Mpdf();
    $html = self::get_cv_html($Candidate);
   
    // run the conversion and write the result to a file
    $pathFile = "/contents/pdf/itjobmada_{$Candidate->reference}.pdf";
    $absFile = get_template_directory() . $pathFile;
    if (file_exists($absFile)){
      chmod($absFile, 0777);
      @unlink($absFile);
    }
    // $mpdf->WriteHTML($html);
    // $mpdf->Output();
    $client->setPageMargins('5mm', '0mm', '0mm', '0mm');
    $client->setPageSize('A4');
    $client->setOrientation('portrait');
    $client->convertStringToFile($html, $absFile);

    return get_template_directory_uri(  ) . $pathFile;
  }

  /**
   * Cette function affiche le CV d'un candidate au complete, c'est aussi un shortcode
   *
   * @param $attrs
   *
   * @return string
   */
  public function ask_candidate_render_html($attrs)
  {
    global $Engine;
    $oc_url = jobServices::page_exists('Espace client');
    extract(
      shortcode_atts(
        array(
          'redir' => $oc_url
        ),
        $attrs
      )
    );

    $candidate_id = (int)Http\Request::getValue('cvId');
    $key = Http\Request::getValue('key', null);
    $hasKey = false;
    $ErrorMessage = "<p class='text-center mt-4'>Une erreur s'est produite</p>";

    $User = wp_get_current_user();
    if ($User->ID === 0 && !is_null($key)) {
      /**
       * Il es plus sécurisé de mettre à jours la clé tous les jours avec le tache "cron" de wordpress
       * Crée une tache pour mettre à jour cette clé
       * TODO: //
       */
      $backOfficeKey = get_field('bo_key', 'option');
      if ($backOfficeKey !== $key) return "<p class='text-center mt-4 badge badge-pink'>Votre clé a expiré ou vous n'avez pas l'accès a cette page veuillez réessayer</p>";
      $hasKey = true;
    }
    if ( ! $candidate_id) return $ErrorMessage;

    $Candidate = new Candidate($candidate_id);
    if ( is_user_logged_in() && !$hasKey ) {
      // Autoriser l'administratuer et les commercials
      if ( in_array('administrator', $User->roles) || in_array('editor', $User->roles) )
      {
        // Autoriser l'administratuer et les commercials
      } else {
        $Entreprise = Company::get_company_by($User->ID);
        if ( ! in_array('company', $User->roles) || ! $Candidate->is_candidate() || ! $Entreprise->is_company()) {
          return "<p class='text-center mt-4 badge badge-pink'>Vous n'avez pas l'autorisation nécessaire pour voir le contenue de cette page</p>";
        }
    
        // Verifier si l'entreprise a l'access au informations du candidat
        // FEATURED: Verifier si le CV est dans la liste de l'entreprise
        $Model = new itModel();
        if ( ! $Model->interest_access($Candidate->getId(), $Entreprise->getId()) &&
          ! $Model->list_exist($Entreprise->getId(), $Candidate->getId())) {
          do_action('add_notice', "<p class='text-center font-14 badge badge-warning'>Vous n'avez pas l'autorisation nécessaire pour voir le contenue de cette page</p>", 'default', false);
          do_action('get_notice');
          return;
        }
      }
    }
    
    wp_enqueue_style( 'poppins', "https://fonts.googleapis.com/css?family=Poppins:300,400,700,800" );
    wp_enqueue_script( 'jspdf', get_template_directory_uri() . '/assets/js/jspdf.debug.js', ["jquery"], "1.5.3" );
    wp_enqueue_script( 'html2canvas', get_template_directory_uri() . '/assets/js/html2canvas/html2canvas.min.js', null, "1.5.3" );
    wp_enqueue_script( 'convertor', get_template_directory_uri() . '/assets/js/convertor.js', null, "0.0.1" );

    $Candidate->__();
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
    try {
      do_action('get_notice');
      return $Engine->render('@SC/pdf.html', [
        'logo' => $logo[0],
        'candidate' => $Candidate
      ]);
    } catch (Twig_Error_Loader $e) {
    } catch (Twig_Error_Runtime $e) {
    } catch (Twig_Error_Syntax $e) {
      echo $e->getRawMessage();
    }
  }

  /**
   * Function ajax
   * Cette function permet de recuperer les informations sur l'utilisateur s'il peut s'interesser sur le candidate.
   * Si l'utilisateur connecter est une entreprise, un lien vers la page de visualisation du CV sera disponible.
   */
  public function get_ask_cv()
  {
    if (!\wp_doing_ajax()) {
      wp_send_json_error("Une erreur s'est produite");
    }
    $cv_id = (int)Http\Request::getValue('cv_id');
    $offer_id = (int)Http\Request::getValue('offer_id', 0);
    if (!$offer_id) {
      wp_send_json_error('Une erreur s\'est produite');
    }

    $Model = new itModel();

    $User = wp_get_current_user();
    if (in_array('company', $User->roles)) {
      $Company = Company::get_company_by($User->ID);
      if (!$Model->exist_interest($cv_id, $offer_id)) {
        $Model->added_interest($cv_id, $offer_id);
        $Interest = $Model->collect_interest_candidate($cv_id, $offer_id);
        do_action('notice-interest', (int)$Interest->id_cv_request);
        // Envoyer un mail a l'administrateur
        do_action('alert_when_company_interest', $cv_id, $offer_id);

        wp_send_json_success(true);
      } else {
        // Si le candidat a déja étes valider sur une autre offre de même entreprise
        // On ajoute et on active automatiquement l'affichage du CV
        if ($Model->interest_access($cv_id, $Company->getId())) {
          // Ajouter une requete qu est déja valider
          $Model->added_interest($cv_id, $offer_id, $Company->getId(), 'validated');
          // Récuperer la requete
          $Interest = $Model->collect_interest_candidate($cv_id, $offer_id);
          // Crée une notification
          do_action('notice-interest', (int)$Interest->id_cv_request);
          // Envoyer un mail a l'administrateur
          do_action('alert_when_company_interest', $cv_id, $offer_id);

          wp_send_json_success(true);
        }

        wp_send_json_error([
          'msg' => "Vous avez déjà sélectionner ce candidat pour cette offre",
          'status' => 'exist'
        ]);
      }

    } else {
      $redir = get_the_permalink($cv_id);
      $singup_page_url = get_the_permalink((int)REGISTER_COMPANY_PAGE_ID);
      wp_send_json_error([
        'msg' => 'Votre compte ne vous permet pas de postuler une offre, veuillez vous inscrire en tant que demandeur d\'emploi',
        'status' => 'access',
        'data' => [
          'login' => home_url("/connexion/company?redir={$redir}"),
          'singup' => $singup_page_url
        ]
      ]);
    }
  }

  public function inspect_cv()
  {
    if (!wp_doing_ajax()) {
      wp_send_json_error("Une erreur s'est produite");
    }
    $cv_id = (int)Http\Request::getValue('cv_id');
    $offer_id = (int)Http\Request::getValue('offer_id', 0);
    if (!$offer_id) {
      wp_send_json_error('Une erreur s\'est produite');
    }
    $redir = get_the_permalink($cv_id);
    $singup_page_url = get_the_permalink((int)REGISTER_COMPANY_PAGE_ID);
    if (!is_user_logged_in()) {
      wp_send_json_error(
        [
          'msg' => 'Mme/Mr pour pouvoir sélectionner ce candidat vous devez vous inscrire, cela est gratuit, ' .
            'en cliquant sur le bouton «s’inscrire » sinon si vous êtes déjà inscrit ' .
            'cliquez sur le bouton « connexion »',
          'status' => 'logged',
          'helper' => [
            'login' => home_url("/connexion/company?redir={$redir}"),
            'singup' => $singup_page_url
          ]
        ]
      );
    }
    $Model = new itModel();

    // FEATURED: Vérifier si le candidat a déja postuler pour cette offre
    $interests = $Model->get_offer_interests($offer_id);
    // Content array of user id
    $apply = array_map(function ($interest) {
      return (int)$interest->id_candidate;
    }, $interests);

    if (is_array($apply) && !empty($apply)) {
      $Candidate = new Candidate($cv_id);
      $author = $Candidate->getAuthor();
      if (in_array($author->ID, $apply)) {
        wp_send_json_error([
          'msg' => "Le candidat a déja postuler pour cette offre",
          'status' => 'exist'
        ]);
      }
    }

    $User = wp_get_current_user();
    if (in_array('company', $User->roles)) {
      $Company = Company::get_company_by($User->ID);
      $response = [
        'interests' =>  $interests
      ];
      wp_send_json_success($response);
    } else {
      wp_send_json_error([
        'msg' => 'Votre compte ne vous permet pas de postuler une offre, veuillez vous inscrire en tant que demandeur d\'emploi',
        'status' => 'access',
        'data' => [
          'login' => home_url("/connexion/company?redir={$redir}"),
          'singup' => $singup_page_url
        ]
      ]);
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
  public function get_current_user_offers($user_id = null)
  {
    if (!\wp_doing_ajax() || !is_user_logged_in()) {
      $singup_page_url = get_the_permalink((int)REGISTER_COMPANY_PAGE_ID);
      wp_send_json_error([
        'msg' => 'Mme/Mr pour pouvoir sélectionner ce candidat vous devez vous inscrire, cela est gratuit, ' .
          'en cliquant sur le bouton «s’inscrire » sinon si vous êtes déjà inscrit ' .
          'cliquez sur le bouton « connexion »',
        'status' => 'logged',
        'helper' => [
          'login' => home_url("/connexion/company"),
          'singup' => $singup_page_url
        ]
      ]);
    }
    if (is_null($user_id) || empty($user_id)) {
      $User = wp_get_current_user();
      if (!in_array('company', $User->roles)) {
        wp_send_json_error([
          'msg' => 'Votre compte ne vous permet pas de postuler une offre, veuillez vous inscrire en tant que demandeur d\'emploi',
          'status' => 'access'
        ]);
      }
      $Company = Company::get_company_by($User->ID);
    } else {
      $Company = new Company((int)$user_id);
    }

    $args = [
      'post_type' => 'offers',
      'post_status' => 'publish',
      'meta_key' => 'itjob_offer_company',
      'meta_value' => $Company->getId(),
      'meta_compare' => '='
    ];
    $offers = get_posts($args);
    if (empty($offers)) {
      wp_send_json_error([
        'msg' => 'Vous devez poster une offre et qu’elle soit validée avant de pouvoir sélectionner des candidats. Merci',
        'status' => 'access'
      ]);
    }
    $offers = array_map(function ($offer) {
      return new Offers($offer->ID);
    }, $offers);
    wp_send_json_success($offers);
  }
}

return new scInterests();