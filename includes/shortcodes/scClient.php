<?php

namespace includes\shortcode;

use Http;
use includes\model\itModel;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'scClient' ) ) :
  class scClient {
    public $User;
    public $Company = null;
    public $Candidate = null;

    public function __construct() {
      if ( is_user_logged_in() ) {
        if ( class_exists( 'includes\post\Company' ) && class_exists( 'includes\post\Candidate' ) ) {
          $userTypes  = [ 'company', 'candidate' ];
          $this->User = wp_get_current_user();
          if ( $this->User->ID !== 0 ) {
            $userRole = $this->User->roles[0];
            if ( ! in_array( $userRole, $userTypes ) ) {
              return;
            }
            $class_name_ucfirst          = ucfirst( $userRole );
            $class_name                  = "includes\\post\\$class_name_ucfirst";
            $this->{$class_name_ucfirst} = call_user_func( [ $class_name, "get_{$userRole}_by" ], $this->User->ID );
          }
        }

        add_action( 'wp_ajax_trash_offer', [ &$this, 'client_trash_offer' ] );
        add_action( 'wp_ajax_client_area', [ &$this, 'client_area' ] );
        add_action( 'wp_ajax_update_offer', [ &$this, 'update_offer' ] );
        add_action( 'wp_ajax_update_profil', [ &$this, 'update_profil' ] );
        add_action( 'wp_ajax_update_alert_filter', [ &$this, 'update_alert_filter' ] );
        add_action( 'wp_ajax_get_postuled_candidate', [ &$this, 'get_postuled_candidate' ] );
        add_action( 'wp_ajax_update-user-password', [ &$this, 'change_user_password' ] );
        add_action( 'wp_ajax_update-candidate-profil', [ &$this, 'update_candidate_profil' ] );
        add_action( 'wp_ajax_update_experiences', [ &$this, 'update_experiences' ] );
        add_action( 'wp_ajax_update_trainings', [ &$this, 'update_trainings' ] );
        add_action( 'wp_ajax_send_request_premium_plan', [ &$this, 'send_request_premium_plan' ] );
        add_action( 'wp_ajax_get_history_cv_view', [ &$this, 'get_history_cv_view' ] );
        add_action( 'wp_ajax_get_company_lists', [ &$this, 'get_company_lists' ] );
        add_action( 'wp_ajax_add_cv_list', [ &$this, 'add_cv_list' ] );
        add_action( 'wp_ajax_get_candidat_interest_lists', [ &$this, 'get_candidat_interest_lists' ] );
      }
      add_action( 'wp_ajax_nopriv_forgot_password', [ &$this, 'forgot_password' ] );
      add_shortcode( 'itjob_client', [ &$this, 'sc_render_html' ] );
    }

    /**
     * Afficher l'espace client
     */
    public function sc_render_html( $attrs, $content = '' ) {
      global $Engine, $itJob;
      if ( ! is_user_logged_in() ) {
        $customer_area_url = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : get_permalink();

        return do_shortcode( '[itjob_login role="candidate" redir="' . $customer_area_url . '"]', true );
      }
      extract(
        shortcode_atts(
          array(),
          $attrs
        )
      );
      // styles
      wp_enqueue_style( 'themify-icons' );
      wp_enqueue_style( 'b-datepicker-3' );
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_style( 'ng-tags-bootstrap' );
      wp_enqueue_style( 'froala' );
      wp_enqueue_style( 'alertify' );
      wp_enqueue_style( 'froala-gray', VENDOR_URL . '/froala-editor/css/themes/gray.min.css', '', '2.8.4' );
      // scripts
      wp_enqueue_script( 'sweetalert' );
      wp_enqueue_script( 'moment-locales' );
      wp_enqueue_script( 'jquery-validate' );
      wp_enqueue_script( 'datatable', VENDOR_URL . '/dataTables/datatables.min.js', [ 'jquery' ], $itJob->version, true );
      wp_register_script( 'espace-client', get_template_directory_uri() . '/assets/js/app/client/clients.js', [
        'angular',
        'angular-aria',
        'angular-messages',
        'angular-sanitize',
        'angular-route',
        'ngFileUpload',
        'datatable',
        'alertify',
        'ng-tags',
        'b-datepicker',
        'fr-datepicker',
        'froala'
      ], $itJob->version, true );

      $client       = get_userdata( $this->User->ID );
      $client_roles = $client->roles;
      try {
        do_action( 'get_notice' );
        $wp_localize_script_args = [
          'Helper' => [
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'tpls_partials' => get_template_directory_uri() . '/assets/js/app/client/partials',
            'img_url'       => get_template_directory_uri() . '/img',
          ]
        ];
        define( 'OC_URL', get_template_directory_uri() . '/assets/js/app/client' );
        // Load company template
        if ( in_array( 'company', $client_roles, true ) ) {
          wp_enqueue_script( 'app-company', OC_URL . '/configs-company.js', [ 'espace-client' ], $itJob->version, true );
          $wp_localize_script_args['Helper']['add_offer_url'] = get_permalink( (int) ADD_OFFER_PAGE );
          $wp_localize_script_args['client_type']             = 'company';
          $wp_localize_script_args['token']                   = $client->user_pass;
          wp_localize_script( 'espace-client', 'itOptions', $wp_localize_script_args );

          return $Engine->render( '@SC/client-company.html.twig', [
            'Helper' => [
              'template_url' => get_template_directory_uri()
            ]
          ] );
        }

        if ( in_array( 'candidate', $client_roles, true ) ) {
          // Load candidate template
          wp_enqueue_script( 'app-candidate', OC_URL . '/configs-candidate.js', [ 'espace-client' ], $itJob->version, true );
          $add_cv_id                                   = \includes\object\jobServices::page_exists( 'Ajouter un CV' );
          $wp_localize_script_args['Helper']['add_cv'] = get_permalink( (int) $add_cv_id );
          $wp_localize_script_args['client_type']      = 'candidate';
          wp_localize_script( 'espace-client', 'itOptions', $wp_localize_script_args );
          $this->Candidate->isMyCV();

          return $Engine->render( '@SC/client-candidate.html.twig', [
            'display_name' => $this->Candidate->privateInformations->firstname . ' ' . $this->Candidate->privateInformations->lastname,
            'Helper'       => [
              'template_url' => get_template_directory_uri(),
            ]
          ] );
        }
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

    /**
     * Modifier une offre
     */
    public function update_offer() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $post_id = Http\Request::getValue( 'post_id', null );
      $post_id = (int) $post_id;
      if ( is_null( $post_id ) ) {
        wp_send_json( false );
      }
      $form = [
        // FEATURED: Modifier le titre de l'offre et son champ ACF
        'post'             => Http\Request::getValue( 'postPromote' ),
        'datelimit'        => Http\Request::getValue( 'dateLimit' ),
        'contrattype'      => Http\Request::getValue( 'contractType' ),
        'profil'           => Http\Request::getValue( 'profil' ),
        'mission'          => Http\Request::getValue( 'mission' ),
        'proposedsallary'  => Http\Request::getValue( 'proposedSalary' ),
        'otherinformation' => Http\Request::getValue( 'otherInformation' ),
        'abranch'          => Http\Request::getValue( 'branch_activity' ),
      ];
      wp_update_post( [
        'ID'         => $post_id,
        'post_title' => $form->post
      ] );
      foreach ( $form as $key => $value ) {
        update_field( "itjob_offer_{$key}", $value, $post_id );
      }
      wp_send_json( [ 'success' => true, 'offers' => $this->__get_company_offers() ] );
    }

    /**
     * Modifier le profil de l'utilisateur
     */
    public function update_profil() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $candidate_id = Http\Request::getValue( 'candidate_id', null );
      $company_id   = Http\Request::getValue( 'company_id', null );
      $terms        = [
        'branch_activity' => Http\Request::getValue( 'branch_activity' ),
        'region'          => Http\Request::getValue( 'region' ),
        'city'            => Http\Request::getValue( 'country' ),
      ];
      if ( ! empty( $company_id ) ) {
        $form = [
          //'address'  => Http\Request::getValue( 'address' ),
          'greeting' => Http\Request::getValue( 'greeting', null ),
          'name'     => Http\Request::getValue( 'name' ),
          'stat'     => Http\Request::getValue( 'stat', null ),
          'nif'      => Http\Request::getValue( 'nif', null )
        ];
        foreach ( $form as $key => $value ) {
          if ( ! is_null( $value ) ) {
            update_field( "itjob_company_{$key}", $value, $company_id );
          }
        }
      } else {
        $input = [
          //'address'  => Http\Request::getValue( 'address' ),
          'greeting' => Http\Request::getValue( 'greeting' ),
        ];
        foreach ( $input as $key => $value ) {
          if ( $value ) {
            update_field( "itjob_cv_{$key}", $value, $candidate_id );
          }
        }
      }
      $post_id = is_null( $candidate_id ) ? $company_id : $candidate_id;
      foreach ( $terms as $key => $value ) {
        $isError = wp_set_post_terms( $post_id, [ (int) $value ], $key );
        if ( is_wp_error( $isError ) ) {
          wp_send_json( [ 'success' => false, 'msg' => $isError->get_error_message() ] );
        }
      }

      // TODO: Modifier une adresse
      // Pour modifier une adresse, le nouveau adresse sera ajouter dans un meta post '__address_update'.
      // Champ: itjob_cv_address_update, Le champ est vide par défault. Si le champ n'est pas vide c'est que l'utilisateur
      // veux changer son adresse et que celui-ci est en attente de validation.

      wp_send_json( [ 'success' => true ] );
    }

    /**
     * Fonction ajax - nopriv only
     * Envoie un email pour recuperer le mot de passe
     *
     */
    public function forgot_password() {
      if ( is_user_logged_in() ) {
        wp_send_json_error( "Vous ne pouvez pas effectuer cette action" );
      }
      $email = Http\Request::getValue( 'email' );
      if ( ! $email || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        wp_send_json_error( "Paramétre non valide" );
      }
      $user = get_user_by('email', $email);
      if ( ! $user ) {
        wp_send_json_error( "Votre recherche ne donne aucun résultat. Veuillez réessayer avec d’autres adresse email." );
      }
      $reset_key = get_password_reset_key( $user );
      if ( is_wp_error( $reset_key ) ) {
        wp_send_json_error($reset_key->get_error_message());
      }
      // Envoyer un email à l'utilisateur
      do_action( 'forgot_my_password', $email, $reset_key );
    }

    /**
     * Modifier le mot de passe de l'utilisateur
     * @route admin-ajax.php?action=update-user-password
     */
    public function change_user_password() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $oldPwd = Http\Request::getValue( 'oldpwd' );
      $pwd    = Http\Request::getValue( "pwd" );
      if ( wp_check_password( $oldPwd, $this->User->data->user_pass, $this->User->ID ) ) :
        wp_set_password( $pwd, $this->User->ID );
        wp_send_json( [ 'success' => true ] );
      else:
        wp_send_json( [
          'success' => false,
          'msg'     => 'Une erreur s\est produit,
        Il est probable que l\'ancien mot de passe n\'est pas correct'
        ] );
      endif;

    }

    /**
     * Function ajax
     * Modifier l'introduction du candidate
     */
    public function update_candidate_profil() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $status     = Http\Request::getValue( 'status' );
      $newsletter = Http\Request::getValue( 'newsletter' );
      update_field( 'itjob_cv_status', $status, $this->Candidate->getId() );
      update_field( 'itjob_cv_newsletter', $newsletter, $this->Candidate->getId() );
      wp_send_json( [ 'success' => true ] );
    }

    /**
     * Ajouter une offre dans la corbeille
     * @route admin-ajax.php?action=trash_offer&pId=<int>
     */
    public function client_trash_offer() {
      global $wpdb;
      /**
       * @func wp_doing_ajax
       * (bool) True if it's a WordPress Ajax request, false otherwise.
       */
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        return;
      }

      $current_user = wp_get_current_user();
      $post_id      = (int) Http\Request::getValue( 'pId' );
      $query        = "SELECT COUNT(*) FROM $wpdb->posts WHERE ID=$post_id";
      $result       = (int) $wpdb->get_var( $wpdb->prepare( $query, [] ) );
      if ( $result > 0 ) {
        $wpdb->flush();
        $pt = new Offers( $post_id );
        if ( (int) $pt->company->ID === $this->Company->getId() ) {
          $isTrash = wp_trash_post( $post_id );
          if ( $isTrash ):
            wp_send_json( [
              'success' => true,
              'msg'     => "L'offre a bien êtes effacer dans la base de données."
            ] );
          else:
            wp_send_json( [
              'success' => false,
              'msg'     => "Une erreur s'est produit pendant la suppression de l'offre."
            ] );
          endif;
        } else {
          wp_send_json( [
            'success' => false,
            'msg'     => "Vous n'êtes pas autoriser de modifier cette offre."
          ] );
        }
      } else {
        wp_send_json( [ 'success' => false, 'msg' => "L'offre n'existe pas." ] );
      }
    }

    /**
     * Function ajax
     * @route admin-ajax.php?action=update_alert_filter&alerts=<json>
     */
    public function update_alert_filter() {
      global $itJob;
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json( [ 'success' => false ] );
      }
      $alerts = Http\Request::getValue( 'alerts' );
      $alerts = \json_decode( $alerts );
      if ( $itJob->services->isClient() === 'company' ) {
        // Company
        $alerts = array_map( function ( $std ) {
          return $std->text;
        }, $alerts );
        update_field( 'itjob_company_alerts', implode( ',', $alerts ), $this->Company->getId() );
        wp_send_json( [ 'success' => true ] );
      } else {
        $notification = get_field( 'itjob_cv_notifEmploi', $this->Candidate->getId() );
        // Candidate
        $alerts = array_map( function ( $std ) {
          return $std->text;
        }, $alerts );
        $values = [
          'notification'    => $notification['notification'],
          'branch_activity' => $notification['branch_activity'],
          'job_sought'      => implode( ',', $alerts )
        ];
        update_field( 'itjob_cv_notifEmploi', $values, $this->Candidate->getId() );
        wp_send_json( [ 'success' => true ] );
      }
    }

    /**
     * Function ajax
     * @route admin-ajax.php?action=update_experiences&experiences=<json>
     */
    public function update_experiences() {
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json( false );
      }
      $new_experiences = [];
      $experiences     = Http\Request::getValue( 'experiences', null );
      if ( is_null( $experiences ) || empty( $experiences ) ) {
        wp_send_json( [ 'success' => false ] );
      }
      $experiences = json_decode( $experiences );
      foreach ( $experiences as $experience ) {
        $new_experiences[] = [
          'exp_dateBegin'    => $experience->exp_dateBegin,
          'exp_dateEnd'      => $experience->exp_dateEnd,
          'exp_country'      => $experience->exp_country,
          'exp_city'         => $experience->exp_city,
          'exp_company'      => $experience->exp_company,
          'exp_positionHeld' => $experience->exp_positionHeld,
          'exp_mission'      => $experience->exp_mission
        ];
      }
      update_field( 'itjob_cv_experiences', $new_experiences, $this->Candidate->getId() );
      $experiences = get_field( 'itjob_cv_experiences', $this->Candidate->getId() );
      wp_send_json( [ 'success' => true, 'experiences' => $experiences ] );
    }

    /**
     * Fonction ajax - Mettre a jour les formations
     * @route admin-ajax.php?action=update_trainings&trainings=<json>
     */
    public function update_trainings() {
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json( false );
      }
      $new_trainings = [];
      $trainings     = Http\Request::getValue( 'trainings', null );
      if ( is_null( $trainings ) || empty( $trainings ) ) {
        wp_send_json( [ 'success' => false ] );
      }
      $trainings = json_decode( $trainings );
      foreach ( $trainings as $training ) {
        $new_trainings[] = [
          'training_dateBegin'     => $training->training_dateBegin,
          'training_dateEnd'       => $training->training_dateEnd,
          'training_diploma'       => $training->training_diploma,
          'training_city'          => $training->training_city,
          'training_country'       => $training->training_country,
          'training_establishment' => $training->training_establishment
        ];
      }
      update_field( 'itjob_cv_trainings', $new_trainings, $this->Candidate->getId() );
      $trainings = get_field( 'itjob_cv_trainings', $this->Candidate->getId() );
      wp_send_json( [ 'success' => true, 'trainings' => $trainings ] );
    }

    /**
     * Retourne les candidates qui ont postuler
     */
    public function get_postuled_candidate() {
      $offer_id           = Http\Request::getValue( 'oId' );
      $offer_id           = (int) $offer_id;
      $postuledCandidates = [];
      // FEATURED: Récuperer aussi les candidats qui ont postuler
      $user_ids = get_field('itjob_users_apply', $offer_id);
      if ($user_ids) {
        foreach ($user_ids as $user_id) {
          $Candidate = Candidate::get_candidate_by((int)$user_id);
          array_push($postuledCandidates,
            [
              'status' => 1,
              'postuled' => 1,
              'candidate' => $Candidate
            ]);
        }
      }
      // Récuperer les candidats qui interesse l'entreprise
      $itModel = new itModel();
      $interests = $itModel->get_offer_interests($offer_id);
      if ( $interests ) {
        foreach ( $interests as $interest ) {
          $Candidate = new Candidate( (int)$interest->id_candidate );
          array_push( $postuledCandidates,
            [
             'status' => (int)$interest->status,
             'postuled' => 0,
             'candidate' => $Candidate
            ]);
        }

      }
      wp_send_json( $postuledCandidates );
    }

    /**
     * Function ajax
     * Récuperer la liste des CV d'entreprise
     */
    public function get_company_lists() {
      if (!is_user_logged_in()) wp_send_json_error('Désolé, Votre session a expiré');
      $User = wp_get_current_user();
      if ($User->ID) {
        $itModel = new itModel();
        $Candidate = [];
        $lists = $itModel->get_lists();
        foreach ($lists as $list) {
          $privateCandidate = new Candidate((int)$list->id_candidate);
          $privateCandidate->__get_access();
          array_push($Candidate, $privateCandidate);
        }
        wp_send_json_success($Candidate);
      } else {
        wp_send_json_error("Une erreur s'est produite");
      }
    }

    /**
     * Function ajax
     * Ajouter un CV dans la liste de l'entreprise
     */
    public function add_cv_list() {
      if (!is_user_logged_in()) wp_send_json_error('Désolé, Votre session a expiré');
      $id_candidat = Http\Request::getValue('id_candidate');

      // FEATURED: Verifier si l'entreprise n'a pas atteint le nombre limite de CV
      $itModel = new itModel();
      if ($itModel->check_list_limit()) {
        // Nombre limite atteinte
        wp_send_json_error("Vous avez atteint le nombre de limite de CV dans votre liste");
      }
      if ($id_candidat) {
        $id_candidat = (int)$id_candidat;
        $response = $itModel->add_list($id_candidat);
        if ($response) {
          wp_send_json_success($response);
        }
      }
      wp_send_json_error("Paramètre invalide");
    }

    /**
     * Function ajax
     * Envoyer un mail à l'administrateur pour une demande de compte premium
     * La valeur du post meta 'itjob_meta_account' de l'entreprise sera 2 si la demande à bien étés envoyer
     * NB: 0: Standart, 1: Premium et 2: En attente
     */
    public function send_request_premium_plan() {
      global $Engine;
      $information_message = "Une erreur s'est produite. <br> Pour signialer cette erreur veillez contactez le service " .
                             "commercial au: 032 45 378 60 - 033 82 591 13 - 034 93 962 18.";
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json_error( false );
      }
      $account = get_post_meta( $this->Company->getId(), 'itjob_meta_account', true );
      $account = (int) $account;
      if ( $account === 0 || empty( $account ) ) {
        $to = get_field( 'admin_mail', 'option' );
        if ( empty( $to ) ) {
          wp_send_json_error( "Adresse e-mail de l'administrateur abscent" );
        }
        $subject   = "Demande de compte premium";
        $headers   = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: itjobmada <no-reply@itjobmada.com';
        // TODO: Liens vers l'espace administrateur
        try {
          $content = $Engine->render( '@MAIL/demande-offre-premium.html.twig', [
            'company'          => $this->Company,
            'template_dir_url' => get_template_directory(),
            'dashboard_url'    => '#dashboard'
          ] );
        } catch ( \Twig_Error_Loader $e ) {
        } catch ( \Twig_Error_Runtime $e ) {
        } catch ( \Twig_Error_Syntax $e ) {
          wp_send_json_error( $e->getRawMessage() );
        }
        $sender = wp_mail( $to, $subject, $content, $headers );
        if ( $sender ) {
          // Changer la valeur du post meta pour '2' qui signifie rester en attente de validation
          update_post_meta( $this->Company->getId(), 'itjob_meta_account', 2 );
          wp_send_json_success( "Votre demande à bien été envoyer." );
        } else {
          wp_send_json_error( $information_message );
        }
      } else {
        wp_send_json_error( $information_message );
      }
    }

    /**
     * Function ajax
     * Récuperer les identifiants que un compte entreprise à activer
     */
    public function get_history_cv_view() {
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json_error( "Accès refuser" );
      }
      if ( $this->Company instanceof Company ) {
        $itModel = new itModel();
        $Candidates = [];
        /** @var array $interest_ids - Array of int, user id */
        $interests = $itModel->get_interests($this->Company->getId());
        $candidat_ids = array_map(function ($interest) { return $interest->id_candidate; }, $interests);
        $candidat_ids = array_unique($candidat_ids);
        // featured: Return candidate object
        foreach ( $candidat_ids as $candidat_id ) {
          $candidateInterest = new Candidate($candidat_id);
          array_push( $Candidates, $candidateInterest );
        }
        wp_send_json_success( $Candidates );
      } else {
        wp_send_json_error( "La classe n'est pas definie pour l'object entreprise. Signialer cette erreur à l'administrateur" );
      }
    }

    /**
     * Function ajax
     * @return array|bool|null|object
     */
    public function get_candidat_interest_lists() {
      $itModel = new itModel();
      $listsCandidate = $itModel->get_lists();
      if (is_null($listsCandidate) || !$listsCandidate || empty($listsCandidate)) wp_send_json_success([]);
      $listsCandidate = array_map(function ($list) { return (int)$list->id_candidate; }, $listsCandidate);
      wp_send_json_success($listsCandidate);
    }

    /**
     * Function ajax
     * Récuperer les information nécessaire pour l'espace client
     * @route admin-ajax.php?action=client_area
     */
    public function client_area() {
      global $itJob;
      if ( ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $itModel = new itModel();
      $User = wp_get_current_user();
      if ( $itJob->services->isClient() === 'company' ) {
        $alert            = get_field( 'itjob_company_alerts', $this->Company->getId() );
        $interest_page_id = jobServices::page_exists( 'Interest candidate' );
        $listsCandidate = $itModel->get_lists();
        $listsCandidate = array_map(function ($list) { return (int)$list->id_candidate; }, $listsCandidate);
        wp_send_json( [
          'iClient'   => Company::get_company_by( $User->ID ),
          'Offers'    => $this->__get_company_offers(),
          'Alerts'    => explode( ',', $alert ),
          'ListsCandidate' => $listsCandidate,
          'post_type' => 'company',
          'Helper'    => [
            'interest_page_uri' => get_the_permalink( $interest_page_id )
          ]
        ] );
      } else {
        // candidate
        $notification = get_field( 'itjob_cv_notifEmploi', $this->Candidate->getId() );
        $Candidate    = Candidate::get_candidate_by( $User->ID );
        $Candidate->isMyCV();
        $alerts = explode( ',', $notification['job_sought'] );
        wp_send_json( [
          'iClient'   => $Candidate,
          'Alerts'    => $alerts,
          'post_type' => 'candidate'
        ] );
      }
    }

    private function __get_company_offers() {
      $resolve      = [];
      $User         = wp_get_current_user();
      $user_company = Company::get_company_by( $User->ID );
      $offers       = get_posts( [
        'posts_per_page' => - 1,
        'post_type'      => 'offers',
        'orderby'        => 'post_date',
        'order'          => 'ASC',
        'post_status'    => [ 'publish', 'pending' ],
        'meta_key'       => 'itjob_offer_company',
        'meta_value'     => $user_company->ID,
        'meta_compare'   => '='
      ] );
      foreach ( $offers as $offer ) {
        $_offer = new Offers( $offer->ID );
        $_offer->__get_access();
        array_push( $resolve, $_offer);
      }

      return $resolve;
    }
  }
endif;

return new scClient();
