<?php

namespace includes\shortcode;

use Http;
use includes\model\itModel;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;
use includes\post\Formation;
use includes\post\Works;

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
        add_action( 'wp_ajax_update_request_status', [ &$this, 'update_request_status' ] );
        add_action( 'wp_ajax_get_all_request', [ &$this, 'get_all_request' ] );

        // Tout les action en bas sont des action pour les utilisateurs particulier et entreprise
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
        add_action( 'wp_ajax_settings_company', [ &$this, 'settings_company' ] );
        add_action( 'wp_ajax_update_offer', [ &$this, 'update_offer' ] );
        add_action( 'wp_ajax_update_profil', [ &$this, 'update_profil' ] );
        add_action( 'wp_ajax_update_company_information', [ &$this, 'update_company_information' ] );
        add_action( 'wp_ajax_update_candidate_information', [ &$this, 'update_candidate_information' ] );
        add_action( 'wp_ajax_update_alert_filter', [ &$this, 'update_alert_filter' ] );
        add_action( 'wp_ajax_update_job_search', [ &$this, 'update_job_search' ] );
        add_action( 'wp_ajax_update_settings', [ &$this, 'update_settings' ] );
        add_action( 'wp_ajax_get_postuled_candidate', [ &$this, 'get_postuled_candidate' ] );
        add_action( 'wp_ajax_update-user-password', [ &$this, 'change_user_password' ] );
        add_action( 'wp_ajax_update-candidate-profil', [ &$this, 'update_candidate_profil' ] );
        add_action( 'wp_ajax_update_experiences', [ &$this, 'update_experiences' ] );
        add_action( 'wp_ajax_update_candidate_softwares', [ &$this, 'update_candidate_softwares' ] );
        add_action( 'wp_ajax_update_trainings', [ &$this, 'update_trainings' ] );
        add_action( 'wp_ajax_send_request_premium_plan', [ &$this, 'send_request_premium_plan' ] );
        add_action( 'wp_ajax_get_history_cv_view', [ &$this, 'get_history_cv_view' ] );
        add_action( 'wp_ajax_get_company_lists', [ &$this, 'get_company_lists' ] );
        add_action( 'wp_ajax_add_cv_list', [ &$this, 'add_cv_list' ] ); // Ajouter un candidat dans la liste de l'entreprise
        add_action( 'wp_ajax_get_candidat_interest_lists', [ &$this, 'get_candidat_interest_lists' ] );
        add_action( 'wp_ajax_collect_favorite_candidates', [ &$this, 'collect_favorite_candidates' ] );
        add_action( 'wp_ajax_collect_formations', [ &$this, 'collect_formations' ] );
        add_action( 'wp_ajax_collect_works', [ &$this, 'collect_works' ] );
        add_action( 'wp_ajax_collect_annonces', [ &$this, 'collect_annonces' ] );
        add_action( 'wp_ajax_reject_cv', [ &$this, 'reject_cv' ] );
        add_action( 'wp_ajax_get_candidacy', [ &$this, 'get_candidacy' ] );
        add_action( 'wp_ajax_collect_current_user_notices', [ &$this, 'collect_current_user_notices' ] );
      }
      add_action( 'wp_ajax_nopriv_forgot_password', [ &$this, 'forgot_password' ] );
      add_shortcode( 'itjob_client', [ &$this, 'sc_render_html' ] );
    }

    /**
     * Afficher l'espace client
     */
    public function sc_render_html( $attrs, $content = '' ) {
      global $Engine, $itJob, $wp_version;
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
      $suffix = minify ? '.min' : '';
      // styles
      wp_enqueue_style( 'themify-icons' );
      wp_enqueue_style( 'b-datepicker-3' );
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_style( 'ng-tags-bootstrap' );
      wp_enqueue_style( 'alertify' );
      // scripts
      wp_enqueue_script( 'sweetalert' );
      wp_enqueue_script( 'moment-locales' );
      wp_enqueue_script( 'jquery-validate' );
      wp_enqueue_script( 'datatable', VENDOR_URL . '/dataTables/datatables.min.js', [ 'jquery' ], $itJob->version, true );
      wp_register_script( 'espace-client', get_template_directory_uri() . "/assets/js/app/client/clients{$suffix}.js", [
        'tinymce',
        'angular',
        'angular-ui-tinymce',
        'angular-ui-select2',
        'angular-aria',
        'angular-messages',
        'angular-sanitize',
        'angular-ui-route',
        'ngFileUpload',
        'datatable',
        'alertify',
        'ng-tags',
        'b-datepicker',
        'fr-datepicker',
        
      ], $itJob->version, true );

      $client       = get_userdata( $this->User->ID );
      $client_roles = $client->roles;
      $theme = wp_get_theme();

      try {
        do_action( 'get_notice' );
        $wp_localize_script_args = [
          'version' => $theme->get('Version'),
          'Helper'  => [
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'tpls_partials' => get_template_directory_uri() . '/assets/js/app/client/partials',
            'img_url'       => get_template_directory_uri() . '/img',
          ]
        ];
        define( 'OC_URL', get_template_directory_uri() . '/assets/js/app/client' );

        // Load company template
        if ( in_array( 'company', $client_roles, true ) ) {
          wp_enqueue_script( 'app-company', OC_URL."/configs-company{$suffix}.js", [ 'espace-client' ], $itJob->version, true );
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
          wp_enqueue_script( 'app-candidate', OC_URL . "/configs-candidate{$suffix}.js", [ 'espace-client' ], $itJob->version, true );
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

      $post_id      = (int) Http\Request::getValue( 'pId' );
      $query        = "SELECT COUNT(*) FROM $wpdb->posts WHERE ID = %d";
      $result       = (int) $wpdb->get_var( $wpdb->prepare( $query, $post_id ) );
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
      $form = (object)[
        // FEATURED: Modifier le titre de l'offre et son champ ACF
        'post'             => Http\Request::getValue( 'postPromote' ),
        'contrattype'      => Http\Request::getValue( 'contractType' ),
        'profil'           => Http\Request::getValue( 'profil' ),
        'mission'          => Http\Request::getValue( 'mission' ),
        'proposedsallary'  => Http\Request::getValue( 'proposedSalary' ),
        'otherinformation' => Http\Request::getValue( 'otherInformation' ),
        'abranch'          => Http\Request::getValue( 'branch_activity' ),
      ];
      $offer = get_post($post_id);
      wp_update_post( [
        'ID'         => $post_id,
        'post_title' => $form->post,
        'post_date'  => $offer->post_date
      ] );
      foreach ( $form as $key => $value ) {
        update_field( "itjob_offer_{$key}", $value, $post_id );
      }
      // Mettre à jour la date limite
      $datelimit =  Http\Request::getValue( 'dateLimit');
      $dt = date('Ymd', strtotime($datelimit));
      update_field('itjob_offer_datelimit', $dt, $post_id);

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

      wp_send_json( [ 'success' => true ] );
    }

    /**
     * Function ajax
     * Mettre à jour les informations de base (company) avant de continuer dans le site
     */
    public function update_company_information() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $User = wp_get_current_user();
      if ($User->ID !==0) {
        $terms        = [
          'branch_activity' => Http\Request::getValue( 'abranch' ),
          'region'          => Http\Request::getValue( 'region' ),
          'city'            => Http\Request::getValue( 'country' ),
        ];
        foreach ( $terms as $key => $value ) {
          if (!$value) continue;
          $isError = wp_set_post_terms( $this->Company->getId(), [ (int) $value ], $key );
          if ($key === 'branch_activity')
          {
            $term = get_term((int)$value, 'branch_activity');
            if (!is_wp_error($term) || null !== $term) {
              // Ajouter la secteur d'activiter pour tout ces offres
              $this->add_offers_branch_activity($this->Company->getId(), $term);
            }
          }
          if ( is_wp_error( $isError ) ) {
            wp_send_json_error( $isError->get_error_message() );
          }
        }
        // Mettre à jour l'adresse
        $address = Http\Request::getValue( 'address' );
        update_field('itjob_company_address', $address, $this->Company->getId());
        // Mettre à jour la salutation si necessaire
        $greeting = Http\Request::getValue('greet');
        if ($greeting)
          update_field("itjob_company_greeting", $greeting, $this->Company->getId());
        wp_send_json_success("Information mis à jour avec succès");
      }
    }

    /**
     * Function ajax
     * Mettre à jours le status d'un requete (postulant ou interesser) dans la base de donnée
     * @request wp-admin/admin-ajax.php?action=update_request_status&candidate_id=<int>&offer_id=<int>&status=validated|[reject, pending]
     */
    public function update_request_status() {
      if ( ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $candidate_id = (int)Http\Request::getValue('candidate_id');
      $offer_id = (int)Http\Request::getValue('offer_id');
      $status = Http\Request::getValue('status');
      $status = $status ? $status : 'validated';

      $Model = new itModel();
      if ($request = $Model->exist_interest($candidate_id, $offer_id)) {
        if (empty($request)) wp_send_json_error("Aucun resultat trouver pendant la verification");
        $update = $Model->update_interest_status((int)$request->id_cv_request, $status);
        if ($update):
          do_action('notice-change-request-status', (int)$request->id_cv_request, $status); // Ajouter les notifications
          wp_send_json_success("Requete mis à jours avec succès");
        endif;
        wp_send_json_error("Il est possible que la requete à déja activé la requete ou bien une erreur s'est produite");
      } else {
        wp_send_json_error("Aucun candidat n'a postulé ou ajouter à cette offre");
      }
    }

    /**
     * Function ajax
     * Cette fonction retourne tous les requetes dans la base de donnée
     */
    public function get_all_request() {
      if ( ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      wp_send_json_success(itModel::get_all());
    }

    // Mettre à jour la secteur d'activité pour les offres d'une entreprise definie
    private function add_offers_branch_activity($company_id, $term) {
      $args = [
        'post_type'   => 'offers',
        'post_status' => ['publish', 'pending'],
        'meta_key'    => 'itjob_offer_company',
        'meta_value'  => $company_id
      ];
      $offers = get_posts($args);
      foreach ($offers as $offer) {
        update_field('itjob_offer_abranch', $term->term_id, $offer->ID);
      }
      return true;
    }

    /**
     * Function ajax.
     * Mettre à jour les informations de base (candidate) avant de continuer dans le site
     */
    public function update_candidate_information() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $User = wp_get_current_user();
      if ($User->ID !==0) {
        $terms        = [
          'branch_activity' => Http\Request::getValue( 'abranch' ),
          'region'          => Http\Request::getValue( 'region' ),
          'city'            => Http\Request::getValue( 'country' )
        ];
        foreach ( $terms as $key => $value ) {
          if (!$value) continue;
          $isError = wp_set_post_terms( $this->Candidate->getId(), [ (int) $value ], $key );
          if ( is_wp_error( $isError ) ) {
            wp_send_json_error( $isError->get_error_message() );
          }
        }
        $address = Http\Request::getValue( 'address' );
        update_field('itjob_cv_address', $address, $this->Candidate->getId());
        // Mettre à jour la salutation si necessaire
        $greeting = Http\Request::getValue('greet');
        if ($greeting)
          update_field("itjob_cv_greeting", $greeting, $this->Candidate->getId());
        wp_send_json_success("Information mis à jour avec succès");
      }
    }

    /**
     * Mettre à jour la liste des logiciels
     */
    public function update_candidate_softwares() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      if (!isset($_POST['softwares'])) wp_send_json_error("Les conditions ne sont pas remplie");
      $softwares = Http\Request::getValue("softwares");
      $softwares = json_decode($softwares);
      $taxonomy = "software";
      $notValidTerms = []; // Cette variable contient les terms à valider
      $softContainer = [];
      foreach ( $softwares as $software ) {
        if ( isset( $job->term_id ) ) {
          array_push( $softContainer, $software->term_id );
        } else {
          $eT = term_exists($software->name, $taxonomy);
          if ( 0 === $eT || null === $eT || !$eT) {
            $term = wp_insert_term(
              wp_unslash(trim($software->name)),   // the term
              $taxonomy // the taxonomy
            );
            // Désactiver le term qu'on viens d'ajouter
            if ( ! is_wp_error( $term ) ) {
              update_term_meta( $term['term_id'], 'activated', 0 );
              do_action('notice-admin-new-software', $term, $this->Candidate);
              $notValidTerms[] = $term;
              array_push( $softContainer, (int) $term['term_id'] );
            } else {
              wp_send_json_error("Une erreur s'est produite. Veillez reéssayer plus tard");
            }
          } else {
            array_push( $softContainer, $eT['term_id']);
          }
        }
      }

      wp_set_post_terms($this->Candidate->getId(), $softContainer, $taxonomy);
      wp_send_json_success("Logiciel mis à jour avec succès");
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
          'exp_branch_activity' => (int)$experience->exp_branch_activity,
          'exp_mission'      => $experience->exp_mission,
          'old_value'        => isset($experience->old_value) ? $experience->old_value : ['exp_dateBegin' => '', 'exp_dateEnd' => '', 'exp_branch_activity' => ''],
          'validated'        => isset($experience->validated) ? intval($experience->validated) : 0
        ];
      }
      update_field( 'itjob_cv_experiences', $new_experiences, $this->Candidate->getId() );
      $experiences = get_field( 'itjob_cv_experiences', $this->Candidate->getId() );

      do_action('notice-admin-update-cv', $this->Candidate->getId());
      do_action('update_cv',  $this->Candidate->getId());

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
          'training_establishment' => $training->training_establishment,
          'validated'              => isset($training->validated) ? intval($training->validated) : 0
        ];
      }
      update_field( 'itjob_cv_trainings', $new_trainings, $this->Candidate->getId() );
      $trainings = get_field( 'itjob_cv_trainings', $this->Candidate->getId() );

      do_action('notice-admin-update-cv', $this->Candidate->getId());
      do_action('update_cv',  $this->Candidate->getId());

      wp_send_json( [ 'success' => true, 'trainings' => $trainings ] );
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
     * Mettre à jours la liste des emplois recherché
     */
    public function update_job_search() {
      global $itJob;
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json( [ 'success' => false ] );
      }
      $taxonomy = 'job_sought';
      $jobs = Http\Request::getValue( 'jobs' );
      $jobs = \json_decode( $jobs );
      if ($itJob->services->isClient() === 'candidate') {
        //$idJobs = array_map(function ($job) { return $job->term_id; }, $jobs);
        $jobContainer = [];
        $notValidTerms = [];
        foreach ( $jobs as $job ) {
          if ( isset( $job->term_id ) ) {
            array_push( $jobContainer, $job->term_id );
          } else {
            $eT = term_exists($job->name, $taxonomy);
            if ( 0 === $eT || null === $eT || !$eT) {
              $term = wp_insert_term(
                wp_unslash(trim($job->name)),   // the term
                $taxonomy // the taxonomy
              );
              // Désactiver le term qu'on viens d'ajouter
              if ( ! is_wp_error( $term ) ) {
                update_term_meta( $term['term_id'], 'activated', 0 );
                $notValidTerms[] = $term;
                array_push( $jobContainer, (int) $term['term_id'] );
              } else {
                wp_send_json_error("Une erreur s'est produite. Veillez reéssayer plus tard");
              }
            } else {
              array_push( $jobContainer, $eT['term_id']);
            }
          }
        }

        wp_set_post_terms($this->Candidate->getId(), $jobContainer, $taxonomy);
        wp_send_json_success("Emploi ajouter avec succès");
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
     * Mettre à jours les parametres
     */
    public function update_settings() {
      global $itJob;
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json_error("Vous n'êtes pas connecter ou vous n'avez pas l'autorisation necessaire");
      }

      $newsletter = Http\Request::getValue('newsletter');
      $notification = Http\Request::getValue('notification');

      if ( $itJob->services->isClient() === 'company' ) {
        update_field('itjob_company_newsletter', (int)$newsletter, $this->Company->getId());
        update_field('itjob_company_notification', (int)$notification, $this->Company->getId());

        wp_send_json_success("Parametre mise à jour avec succès");
      }

      if ( $itJob->services->isClient() === 'candidate' ) {
        // Notification pour les offre d'emploi
        $notification = Http\Request::getValue('notification_job'); // int
        $current_notification = get_field('itjob_cv_notifEmploi', $this->Candidate->getId());
        $notif_job = is_array($current_notification) ? $current_notification : [];
        $notif_job['notification'] = $notification;
        update_field('itjob_cv_notifEmploi', $notif_job, $this->Candidate->getId());

        // Notification pour les formations
        $notification = Http\Request::getValue('notification_formation'); // int
        $current_notification = get_field('itjob_cv_notifFormation', $this->Candidate->getId());
        $notif_formation = is_array($current_notification) ? $current_notification : [];
        $notif_formation['notification'] = (int)$notification;
        update_field('itjob_cv_notifFormation', $notif_formation, $this->Candidate->getId());

        // Le newsletter
        update_field('itjob_cv_newsletter', (int)$newsletter, $this->Company->getId());

        wp_send_json_success("Parametre mise à jour avec succès");
      }

      wp_send_json_error("Impossible de modifier le parametre");
    }

    /**
     * Fonction ajax - nopriv only.
     * Envoie un email pour recuperer le mot de passe
     *
     */
    public function forgot_password() {
      if ( is_user_logged_in() ) {
        wp_send_json_error( ["msg" => "Vous ne pouvez pas effectuer cette action"] );
      }
      $email = Http\Request::getValue( 'email' );
      if ( ! $email || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        wp_send_json_error( ["msg" => "Paramétre non valide"] );
      }
      $user = get_user_by( 'email', $email );
      if ( ! $user ) {
        wp_send_json_error( ['msg' => "Votre recherche ne donne aucun résultat. Veuillez réessayer avec d’autres adresse email."] );
      }
      $reset_key = get_password_reset_key( $user );
      if ( is_wp_error( $reset_key ) ) {
        wp_send_json_error( ['msg' => $reset_key->get_error_message() ]);
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
     * Retourne les candidates qui ont postuler
     */
    public function get_postuled_candidate() {
      $offer_id           = Http\Request::getValue( 'oId' );
      $offer_id           = (int) $offer_id;
      $postuledCandidates = [];
      // Récuperer les candidats qui ont postuler et interesser par l'entreprise
      $itModel   = new itModel();
      $interests = $itModel->get_offer_interests( $offer_id );
      if ( $interests ) {
        foreach ( $interests as $interest ) {
          $view = intval($interest->view);
          //if (!$view && $interest->type === "apply") continue;
          $Candidate = new Candidate( (int) $interest->id_candidate );
          if (is_wp_error($Candidate->error)) continue;
          array_push( $postuledCandidates,
            [
              'status'     => $interest->status,
              'view'       => intval($interest->view),
              'type'       => $interest->type,
              'id_request' => (int) $interest->id_cv_request,
              'candidate'  => $Candidate
            ] );
        }
      }
      wp_send_json( $postuledCandidates );
    }

    /**
     * Function ajax
     * Récuperer la liste des CV d'entreprise
     */
    public function get_company_lists() {
      global $itJob;
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $User = $itJob->services->getUser();
      if ( $User->ID ) {
        $Model   = new itModel();
        $Candidate = [];
        $lists     = $Model->get_lists();
        foreach ( $lists as $list ) {
          $privateCandidate = new Candidate( (int) $list->id_candidate );
          $privateCandidate->__get_access();
          array_push( $Candidate, $privateCandidate );
        }
        wp_send_json_success( $Candidate );
      } else {
        wp_send_json_error( "Une erreur s'est produite" );
      }
    }


    /**
     * Function ajax
     * Récuperer les candidatures d'un candidat
     */
    public function get_candidacy() {
      global $itJob;
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $User = $itJob->services->getUser();
      if ( in_array( 'candidate', $User->roles ) ) {
        $Candidate         = Candidate::get_candidate_by( $User->ID );
        $Model             = new itModel();
        $candidate_request = $Model->collect_candidate_request($Candidate->getId());
        $requests          = &$candidate_request;
        $requests          = array_map( function ( $request ) {
          // Ajouter une object offre
          $request->offer = new Offers((int)$request->id_offer);
          unset($request->id_offer);
          return $request;
        }, $requests );
        wp_send_json_success($requests);
      } else {
        wp_send_json_error("Vous n'étes pas un candidat");
      }
    }

    /**
     * Function ajax
     * Récupérer les formations d'un utilisateur
     */
    public function collect_formations() {
      global $itJob;
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $User = $itJob->services->getUser();
      $email = $User->user_email;
      $args = [
        'post_type' => 'formation',
        'post_status' => 'any',
        'meta_query' => [
          [
            'key' => 'email',
            'value' => $email
          ]
        ]
      ];
      $formations = get_posts( $args );
      $results = [];
      foreach ($formations as $formation) {
        $results[] = new Formation( $formation->ID );
      }
      
      wp_send_json_success( $results );
    }

    public function collect_works() {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $args = [
        'post_type' => 'works',
        'post_status' => 'any',
        'meta_query' => [
          [
            'key' => 'annonce_author',
            'value' => $this->User->ID,
            'compare' => '='
          ]
        ]
      ];
      $works = get_posts( $args );
      $results = [];
      foreach ($works as $work) {
        $results[] = new Works( $work->ID );
      }

      wp_send_json_success( $results );
    }

    public function collect_annonces() {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $args = [
        'post_type' => 'annonce',
        'post_status' => 'any',
        'meta_query' => [
          [
            'key' => 'annonce_author',
            'value' => $this->User->ID,
            'compare' => '='
          ]
        ]
      ];
      $works = get_posts( $args );
      $results = [];
      foreach ($works as $work) {
        $results[] = new Works( $work->ID );
      }

      wp_send_json_success( $results );
    }

    /**
     * Function ajax
     */
    public function collect_favorite_candidates() {
      global $itJob;
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $id_candidate = (int) Http\Request::getValue( 'id' );
      $id_offer     = (int) Http\Request::getValue( 'id_offer' );
      if ( $id_offer && $id_candidate ) {
        $User = $itJob->services->getUser();
        if ( ! $User->ID || ! in_array( 'company', $User->roles ) ) {
          wp_send_json_error( "Une erreur s'est produite" );
        }
        $Company = Company::get_company_by( $User->ID );
        $Model   = new itModel();
        if ( $Model->list_exist( $Company->getId(), $id_candidate ) ) {
          $request_interest = $Model->exist_interest( $id_candidate, $id_offer );
          if ( $request_interest ):
            $interest = $Model->collect_interest_candidate( $id_candidate, $id_offer );
            $interest->attachment = get_post( (int) $interest->id_attachment );
            $interest->candidate  = new Candidate( (int) $interest->id_candidate );
            $interest->candidate->__get_access();
            unset( $interest->id_candidate, $interest->id_attachment );
            wp_send_json_success( $interest );
          endif;
        }
        wp_send_json_error( "Accès non autoriser" );
      }
      wp_send_json_error( "Bad request" );
    }

    /**
     * Function ajax
     * Récuperer les notifications de l'utilisateur
     */
    public function collect_current_user_notices() {
      global $itJob;
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $User = $itJob->services->getUser();
      if ($User->ID === 0) wp_send_json_error("Vous n'êtes pas connecter à notre service");
      $Model = new itModel();
      wp_send_json_success($Model->collect_notices($User->ID));
    }

    /**
     * Function ajax
     * Ajouter un CV dans la liste de l'entreprise
     * Si l'offre est une entreprise premium ont ajoute le CV même s'il atteint le nombre limit ede CV
     */
    public function add_cv_list() {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $id_candidate = (int) Http\Request::getValue( 'id_candidate' );
      $id_request   = (int) Http\Request::getValue( 'id_request' );
     
      // FEATURED: Verifier si l'entreprise n'a pas atteint le nombre limite de CV
      if ($id_request === 0) wp_send_json_error('Aucune requete ne correspont à votre demande');
      $Model = new itModel();
      $request = $Model->get_request($id_request);
      $Candidate = new Candidate($id_candidate);
      $Offer = new Offers((int) $request->id_offer);

      if ( $Model->check_list_limit() && $Offer->rateplan === 'standard') { // Compte standard
        // Nombre limite atteinte
        wp_send_json_error( "Vous venez de sélectionner 5 candidats et vous vous apprêter à en sélectionner un sixième savez 
        vous qu’à partir de là les CV sont payants au prix de 25.000 HT / CV " );
      }
      if ( $Candidate->getId() !== 0 ) {
        $response = $Model->add_list( $Candidate->getId() );
        if ( $response ) {
          $Model->update_interest_status( $id_request, 'validated' );
          if ($request->type === 'apply') {
            do_action('notice-candidate-selected-cv', $Candidate->getId(), $request->id_offer);
          }
          wp_send_json_success( "Le candidat a bien étés sélectionner avec succès." );
        } else {
          wp_send_json_error( "Une erreur s'est produite" );
        }
      } else {
        wp_send_json_error( "Paramètre invalide" );
      }

    }

    /**
     * Function ajax
     * Ajouter un CV dans la liste de l'entreprise
     */
    public function reject_cv() {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Désolé, Votre session a expiré' );
      }
      $id_candidate = (int) Http\Request::getValue( 'id_candidate' );
      $id_request   = (int) Http\Request::getValue( 'id_request' );
      $Model      = new itModel();
      if ( $id_candidate ) {
        $change_status = $Model->update_interest_status( $id_request, 'reject' );
        if ( $change_status ) {
          wp_send_json_success( "Status mise à jour avec succès." );
        }
      }
      wp_send_json_error( "Paramètre invalide" );
    }

    /**
     * Function ajax
     * Envoyer un mail à l'administrateur pour une demande de compte premium
     * La valeur du post meta 'itjob_meta_account' de l'entreprise sera 2 si la demande à bien étés envoyer
     * NB: 0: Standart, 1: Premium et 2: En attente
     * 
     * DEPRECATE:  Ne plus mettre les professionels en mode premium
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
          do_action('request-premium-account', $this->Company);
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
        $itModel    = new itModel();
        $Candidates = [];
        /** @var array $interest_ids - Array of int, user id */
        $interests    = $itModel->get_interests( $this->Company->getId() );
        // Récuperer seulement les identifiants des candidats
        $candidat_ids = array_map( function ( $interest ) {
          return $interest->id_candidate;
        }, $interests );
        // Fusionner les foublons
        $candidat_ids = array_unique( $candidat_ids );
        // Retourner des object candidats
        foreach ( $candidat_ids as $candidat_id ) {
          $candidateInterest = new Candidate( $candidat_id );
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
      $itModel        = new itModel();
      $listsCandidate = $itModel->get_lists();
      if ( is_null( $listsCandidate ) || ! $listsCandidate || empty( $listsCandidate ) ) {
        wp_send_json_success( [] );
      }
      $listsCandidate = array_map( function ( $list ) {
        return (int) $list->id_candidate;
      }, $listsCandidate );
      wp_send_json_success( $listsCandidate );
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
      $Model = new itModel();
      $User    = wp_get_current_user();

      if ( $itJob->services->isClient() === 'company' ) {
        $alert            = get_field( 'itjob_company_alerts', $this->Company->getId() );
        $interest_page_id = jobServices::page_exists( 'Interest candidate' );
        $listsCandidate   = $Model->get_lists();
        $listsCandidate   = array_map( function ( $list ) {
          return (int) $list->id_candidate;
        }, $listsCandidate );

        $Company = Company::get_company_by( $User->ID );
        $clients = [
          'iClient'        => $Company,
          'Alerts'         => explode( ',', $alert ),
          'ListsCandidate' => $listsCandidate,
          'post_type'      => 'company',
          'Helper'         => [
            'add_formation_url' => get_the_permalink(ADD_FORMATION_PAGE),
            'add_annonce_url'   => get_the_permalink( ADD_ANNONCE_PAGE),
            'interest_page_uri' => get_the_permalink( $interest_page_id ),
            'archive_candidate_link' => get_post_type_archive_link('candidate')
          ]
        ];
        if ($Company->sector === 1) {
          $clients['Offers'] = $this->__get_company_offers();
          $clients['formation_count'] = (int)$this->__get_count_formation();
        }

        wp_send_json( $clients );
      }

      if ( $itJob->services->isClient() === 'candidate' ) {
        $notification = get_field( 'itjob_cv_notifEmploi', $this->Candidate->getId() );
        $Candidate    = Candidate::get_candidate_by( $User->ID );
        $Candidate->_activated = $Candidate->is_activated();
        $Candidate->isMyCV();
        $alerts = explode( ',', $notification['job_sought'] );
        wp_send_json( [
          'iClient'   => $Candidate,
          'Alerts'    => $alerts,
          'post_type' => 'candidate',
          'Helper'    => [
            'add_annonce_url'   => get_the_permalink( ADD_ANNONCE_PAGE),
            'archive_offer_link' => get_post_type_archive_link('offers')
          ]
        ] );
      }
    }


    public function __get_company_offers($Company = null) {
      $resolve      = [];
      if ($Company === null) {
        $User         = wp_get_current_user();
        $Company = Company::get_company_by( $User->ID );
      } else {
        if (!$Company instanceof Company) {
          return false;
        }
      }
      
      $offers       = get_posts( [
        'posts_per_page' => - 1,
        'post_type'      => 'offers',
        'orderby'        => 'post_date',
        'order'          => 'ASC',
        'post_status'    => [ 'publish', 'pending' ],
        'meta_key'       => 'itjob_offer_company',
        'meta_value'     => $Company->getId(),
        'meta_compare'   => '='
      ] );
      foreach ( $offers as $offer ) {
        $objOffer = new Offers( $offer->ID );
        $objOffer->__get_access();

        $rspCompany = new \stdClass();
        $rspCompany->name = $Company->title;
        $rspCompany->ID = $Company->getId();
        $objOffer->_company = $rspCompany;

        array_push( $resolve, $objOffer );
      }

      return $resolve;
    }

    public function __get_count_formation() {
      global $wpdb, $itJob;
      $user = $itJob->services->getUser();
      $sql = "SELECT COUNT(*) FROM {$wpdb->posts} pst WHERE pst.post_type = %s AND pst.ID IN (
SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'email' AND pm.meta_value = %s
) ";
      $prepare = $wpdb->prepare($sql, 'formation', $user->user_email);
      $count = $wpdb->get_var($prepare);

      return $count;
    }

  }
endif;

return new scClient();
