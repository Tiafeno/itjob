<?php

namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

use Http;
use includes\post\Candidate;

if ( ! class_exists( 'vcRegisterCandidate' ) ) :
  class vcRegisterCandidate extends \WPBakeryShortCode {
    public $User;
    public $Candidate;

    public function __construct() {
      add_action( 'init', [ &$this, 'register_candidate_mapping' ] );
      if ( ! shortcode_exists( 'vc_register_candidate' ) ) {
        add_shortcode( 'vc_register_candidate', [ &$this, 'register_render_html' ] );
      }

      add_action( 'wp_ajax_ajx_upload_media', [ &$this, 'upload_media_avatar' ] );
      add_action( 'wp_ajax_nopriv_ajx_upload_media', [ &$this, 'upload_media_avatar' ] );

      add_action( 'wp_ajax_update_user_cv', [ &$this, 'update_user_cv' ] );
      add_action( 'wp_ajax_nopriv_update_user_cv', [ &$this, 'update_user_cv' ] );

      add_action( 'wp_ajax_collect_candidat_informations', [ &$this, 'collect_candidat_informations' ] );

    }

    public function register_candidate_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Création d'un element Visual Composer
      \vc_map(
        array(
          'name'        => 'Candidate Form (SingUp)',
          'base'        => 'vc_register_candidate',
          'description' => 'Formulaire d\'activation d\'un candidate',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'textfield',
              'holder'      => 'h3',
              'class'       => 'vc-ij-title',
              'heading'     => 'Titre',
              'param_name'  => 'title',
              'value'       => '',
              'description' => "Une titre pour le formulaire",
              'admin_label' => true,
              'weight'      => 0
            )
          )
        )
      );

      /**
       * Initialiser les propriétés
       */
      $this->User = wp_get_current_user();
      if ( ! $this->Candidate instanceof Candidate && $this->User->ID !== 0) {
        $this->Candidate = Candidate::get_candidate_by( $this->User->ID );
      }
    }

    /**
     * Rendre le contenue du shortcode
     * @shortcode [vc_register_candidate title="Ajouter un CV"]
     *
     * @param array $attrs
     *
     * @return string
     */
    public function register_render_html( $attrs ) {
      global $Engine, $itJob, $wp_version;

      $message_access_refused = '<div class="d-flex align-items-center">';
      $message_access_refused .= '<div class="uk-margin-large-top uk-margin-auto-left uk-margin-auto-right text-uppercase">Access refuser</div></div>';

      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null,
            'redir' => null
          ),
          $attrs
        ),
        EXTR_OVERWRITE
      );

      /** @var url $redir */
      $httpRedir = Http\Request::getValue( 'redir' );
      $EC_Link   = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : home_url( '/' );
      $httpRedir = $httpRedir ? $httpRedir : $EC_Link;
      $redirect  = is_null( $redir ) ? $httpRedir : $redir;

      // Client non connecter
      if ( ! is_user_logged_in() ) {
        return do_shortcode( "[vc_register_particular title='Créer votre compte itJob' redir='$redirect']" );
      }
      // FEATURED: Ne pas autoriser les utilisateurs sauf les candidates avec un CV non activé
      $User = wp_get_current_user();
      if (!in_array('candidate', $User->roles)) {
        return $Engine->render('@ERROR/403.html.twig', [
          'home_url' => home_url('/')
        ]);
      }
      
      if ($this->Candidate->has_cv && $this->Candidate->state === 'pending' ) {
        return $Engine->render( '@VC/candidates/pending-cv.html.twig', [
          'offer_archive' => get_post_type_archive_link('offers')
        ] );
      }

      if ($this->Candidate->state === 'publish' && $this->Candidate->has_cv) {
        return $Engine->render('@VC/candidates/has-cv.html', [
          'offer_archive' => get_post_type_archive_link('offers')
        ]);
      }

      wp_enqueue_style( 'b-datepicker-3' );
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_style( 'alertify' );
      wp_enqueue_style( 'bootstrap-tagsinput' );
      wp_enqueue_style( 'ng-tags-bootstrap' );
      wp_enqueue_script( 'form-candidate', get_template_directory_uri() . '/assets/js/app/register/form-candidate.js', [
        'angular',
        'angular-ui-select2',
        'angular-ui-route',
        'angular-sanitize',
        'angular-messages',
        'angular-animate',
        'ngFileUpload',
        'b-datepicker',
        'moment-locales',
        'daterangepicker',
        'bootstrap-tagsinput',
        'ng-tags',
        'typeahead',
        'alertify',
        'sweetalert'
      ], $itJob->version, true );

      // Verifier si l'ajout du CV consiste à postuler sur une offre
      wp_localize_script( 'form-candidate', 'itOptions', [
        'version'      => $wp_version,
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template_url' => get_template_directory_uri(),
        'urlHelper'    => [
          'redir' => $redirect
        ]
      ] );

      try {
        do_action( 'get_notice' );

        /** @var STRING $title */
        return $Engine->render( '@VC/register/candidate.html.twig', [
          'title' => $title
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }

    /**
     * Function ajax
     * Cette function permet d'ajouter un CV pour un utilisateur 'candidate' qui ne possède pas encore.
     *
     * @return bool
     */
    public function update_user_cv() {
      global $itHelper;
      
      if ( $_SERVER['REQUEST_METHOD'] != 'POST' || ! wp_doing_ajax() || ! is_user_logged_in() ) {
        return false;
      }

      if ( ! $this->Candidate->is_candidate() ) {
        return false;
      }

      // Mette à jours le CV
      $status          = Http\Request::getValue( 'status', null );
      $project         = Http\Request::getValue( 'projet', ' ' );
      $activity_branch = Http\Request::getValue( 'abranch', null );
      $various         = Http\Request::getValue( 'various', null );
      $newsletter      = Http\Request::getValue( 'newsletter', 0 );

      $jobSougths = Http\Request::getValue( 'jobSougths' );
      $jobSougths = \json_decode( \stripslashes($jobSougths) );

      $driveLicences = Http\Request::getValue( 'driveLicence', [] );
      if ( $driveLicences && !empty($driveLicences) ) {
        $driveLicences = \json_decode( \stripslashes($driveLicences) );
      }

      $notiEmploi = Http\Request::getValue( 'notiEmploi', false );
      if ( $notiEmploi ) {
        $notiEmploi = \json_decode( \stripslashes($notiEmploi) );
      }

      $trainings = Http\Request::getValue( 'trainings' );
      $trainings = \json_decode( stripslashes($trainings) );

      $experiences = Http\Request::getValue( 'experiences' );
      $experiences = \json_decode( stripslashes($experiences) );

      $languages = \json_decode( Http\Request::getValue( 'languages' ) );
      $softwares = \json_decode( Http\Request::getValue( 'softwares' ) );
      $form      = [
        'status'        => $status ? 1 : 0,
        'projet'        => $project,
        'abranch'       => $activity_branch,
        'various'       => $various,
        'newsletter'    => $newsletter ? ( $newsletter === 'false' ? 0 : 1 ) : 0,
        'jobSougths'    => $jobSougths,
        'driveLicences' => $driveLicences,
        'notifEmploi'   => $notiEmploi,
        'trainings'     => $trainings,
        'experiences'   => $experiences,
        'languages'     => $languages,
        'softwares'     => $softwares
      ];

      foreach ( $form as $key => $value ) :
        if ( is_null( $value ) && $key !== 'softwares' ) {
          \wp_send_json( [
            'success' => false,
            'msg'     => "Le formulaire n'est pas valide. Veillez verifier les champs requis",
            'input'   => $key,
            'value'   => $value
          ] );
          break;
        }
      endforeach;

      $form = (object) $form;

      wp_set_post_terms( $this->Candidate->getId(), [ (int) $form->abranch ], 'branch_activity' );

      // Ajouter les emplois rechercher par le candidat (Existant et qui n'existe pas encore dans la base de donnée)
      $ids = $this->add_term_submit( $form->jobSougths, 'job_sought' );
      wp_set_post_terms( $this->Candidate->getId(), $ids, 'job_sought' );
      // Ajouter les logiciels
      $ids = $this->add_term_submit( $form->softwares, 'software' );
      wp_set_post_terms( $this->Candidate->getId(), $ids, 'software' );
      // Ajouter les languages
      $ids = [];
      if (is_array($form->languages)) {
        foreach ($form->languages as $language) {
          if (!isset($language->term_id)) continue;
          array_push($ids, $language->term_id);
        }
      }
      wp_set_post_terms( $this->Candidate->getId(), $ids, 'language' );

      unset( $ids );

      // Update ACF field
      update_field( 'itjob_cv_status', (int) $form->status, $this->Candidate->getId() );

      $centerInterest = [
        'various' => json_decode($form->various),
        'projet'  => json_decode($form->projet)
      ];
      update_field( 'itjob_cv_centerInterest', $centerInterest, $this->Candidate->getId() );
      update_field( 'itjob_cv_newsletter', $form->newsletter, $this->Candidate->getId() );

      // Update drive licence field
      $LicenceSchema = [ 'a_' => 0, 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4 ];
      $licences      = array_map( function ( $key ) use ( $form, $LicenceSchema ) {
        if ( (int) $form->driveLicences->{$key} ) {
          return $LicenceSchema[ $key ];
        }
      }, array_keys( (array) $form->driveLicences ) );
      $licences = empty($licences) ? '' : $licences;
      update_field( 'itjob_cv_driveLicence', $licences, $this->Candidate->getId() );

      // Update notification
      if ( $form->notifEmploi ) {
        if ( $form->notifEmploi->with ) {
          $notificationJob = [
            'notification'    => 1,
            'branch_activity' => isset( $form->notifEmploi->abranch ) ? $form->notifEmploi->abranch : ''
          ];
          // S'il existe des mots pour les alerts, ont ajoute
          if ( isset( $form->notifEmploi->job ) ) {
            $term_names = [];
            foreach ( $form->notifEmploi->job as $job ) {
              array_push( $term_names, $job->name );
            }
            $notificationJob = array_merge( $notificationJob, [ 'job_sought' => implode( ',', $term_names ) ] );
          }
          // Mettre à jour la notification pour les offres en ligne
          update_field( 'itjob_cv_notifEmploi', $notificationJob, $this->Candidate->getId() );
        }
      }
      // .end notification emploi

      // Ajouter les formations dans le CV
      $trainings = [];
      foreach ( $form->trainings as $training ) {
        $trainings[] = [
          'training_dateBegin'     => $training->start,
          'training_dateEnd'       => $training->end,
          'training_country'       => $training->country,
          'training_city'          => $training->city,
          'training_diploma'       => $training->diploma,
          'training_establishment' => $training->establishment,
          'validated'              => 0
        ];
      }
      update_field( 'itjob_cv_trainings', $trainings, $this->Candidate->getId() );

      // Ajouter les experiences dans le CV
      $experiences = [];
      foreach ( $form->experiences as $experience ) {
        $experiences[] = [
          'exp_dateBegin'    => $experience->start,
          'exp_dateEnd'      => $experience->end,
          'exp_country'      => $experience->country,
          'exp_city'         => $experience->city,
          'exp_company'      => $experience->company,
          'exp_positionHeld' => $experience->positionHeld,
          'exp_branch_activity' => isset($experience->abranch) ? (int)$experience->abranch : '',
          'exp_mission'      => $experience->mission,
          'validated'        => 0
        ];
      }
      update_field( 'itjob_cv_experiences', $experiences, $this->Candidate->getId() );

      // FEATURED: Ne pas activer le CV
      // En attente de la validation de l'administrateur
      update_field( 'activated', 0, $this->Candidate->getId() );
      // Ajouter que l'utilisateur ou le candidate possède un CV
      update_field( 'itjob_cv_hasCV', 1, $this->Candidate->getId() );
      add_post_meta($this->Candidate->getId(), 'date_create', date_i18n('Y-m-d H:i:s'));
      // Envoyer un email de confirmation
      //do_action('submit_particular_cv', $this->Candidate->getId());

      do_action('notice-admin-create-cv', $this->Candidate->getId());

      // Alerter les entreprises
     // $itHelper->Mailing->alert_for_new_candidate($this->Candidate->getId());
      wp_send_json( [ 'success' => true ] );

    } // .end update_user_cv

    /**
     * Function ajax
     * @return bool
     */
    public function collect_candidat_informations() {
      if ( ! \wp_doing_ajax() || ! \is_user_logged_in() ) {
        return false;
      }
      if (!$this->Candidate instanceof Candidate) wp_send_json_error("Utilisateur non definie");
      $Candidate = $this->Candidate;
      $Candidate->__get_access();
      wp_send_json_success($Candidate);
    }

    /**
     * Ajouter dans le taxonomie les valeurs dans le champs qui ne sont pas des terms
     *
     * @param array $tabs
     * @param string $taxonomy
     *
     * @return array
     */
    private function add_term_submit( $tabs = [], $taxonomy ) {
      $tabContainer = [];
      if (!is_array($tabs)) return [];
      foreach ( $tabs as $tab ) {
        if ( isset( $tab->term_id ) ) {
          array_push( $tabContainer, $tab->term_id );
        } else {
          $eT = term_exists($tab->name, $taxonomy);
          if ( 0 !== $eT || ! is_null($eT) ) {
            $eTI = is_array($eT) ? $eT[ 'term_id' ] : (is_int($eT) ? $eT : null);
            if ( ! is_null($eTI) )
              array_push( $tabContainer, $eTI );
          }
          $term = wp_insert_term(
            $tab->name,   // the term
            $taxonomy // the taxonomy
          );
          // Désactiver le term qu'on viens d'ajouter
          if ( ! is_wp_error( $term ) ) {
            update_term_meta( $term['term_id'], 'activated', 0 );
            if ($taxonomy === 'software' || $taxonomy === "job_sought") {
              do_action('notice-admin-new-' . $taxonomy, $term, $this->Candidate);
            }

            array_push( $tabContainer, (int) $term['term_id'] );
          }
        }
      }

      return $tabContainer;
    }


    /**
     * Ajouter une image à la une ou une image de profil
     * @return bool
     */
    public function upload_media_avatar() {
      if ( $_SERVER['REQUEST_METHOD'] != 'POST' || ! \wp_doing_ajax() || ! \is_user_logged_in() ) {
        return false;
      }

      require_once( ABSPATH . 'wp-admin/includes/image.php' );
      require_once( ABSPATH . 'wp-admin/includes/file.php' );
      require_once( ABSPATH . 'wp-admin/includes/media.php' );
      if ( empty( $_FILES ) ) {
        return false;
      }
      $file = $_FILES["file"];

      // Let WordPress handle the upload.
      // Remember, 'file' is the name of our file input in our form above.
      // @wordpress: https://codex.wordpress.org/Function_Reference/media_handle_upload
      $attachment_id = media_handle_upload( 'file', $this->Candidate->getId() );

      if ( is_wp_error( $attachment_id ) ) {
        // There was an error uploading the image.
        do_action('notice-admin-new-featured-image', $this->Candidate->getId());
        wp_send_json( [ 'success' => false, 'msg' => $attachment_id->get_error_message() ] );
      } else {
        // The image was uploaded successfully!
        update_post_meta( $this->Candidate->getId(), '_thumbnail_id', $attachment_id );
        wp_send_json( [ 'attachment_id' => $attachment_id, 'success' => true ] );
      }
    }
  }
endif;

return new vcRegisterCandidate();