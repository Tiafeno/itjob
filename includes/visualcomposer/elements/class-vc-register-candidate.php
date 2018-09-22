<?php

namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

use Http;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;

if ( ! class_exists( 'vcRegisterCandidate' ) ) :
  class vcRegisterCandidate extends \WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ &$this, 'register_candidate_mapping' ] );
      if ( ! shortcode_exists( 'vc_register_candidate' ) ) {
        add_shortcode( 'vc_register_candidate', [ &$this, 'register_render_html' ] );
      }

      add_action( 'wp_ajax_ajx_upload_media', [ &$this, 'upload_media_avatar' ] );
      add_action( 'wp_ajax_nopriv_ajx_upload_media', [ &$this, 'upload_media_avatar' ] );

      add_action( 'wp_ajax_update_user_cv', [ &$this, 'update_user_cv' ] );
      add_action( 'wp_ajax_nopriv_update_user_cv', [ &$this, 'update_user_cv' ] );

    }

    public function register_candidate_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
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
    }

    public function register_render_html( $attrs ) {
      global $Engine, $itJob;

      $message_access_refused = '<div class="d-flex align-items-center">';
      $message_access_refused .= '<div class="uk-margin-large-top uk-margin-auto-left uk-margin-auto-right text-uppercase">Access refuser</div></div>';

      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null,
          ),
          $attrs
        ),
        EXTR_OVERWRITE
      );

      $redirect = Http\Request::getValue('redir');

      // Client non connecter
      if ( ! is_user_logged_in() ) {
        // TODO: Afficher le formulaire d'inscription pour un utilisateur particulier
        $redirect = $redirect ? $redirect : get_the_permalink();
        do_action('add_notice', "Veuillez vous connecter ou s'inscrire en tant que candidat pour pouvoir postuler à un offre.", "warning");
        return do_shortcode("[vc_register_particular title='Créer votre compte itJob' redir='$redirect']");
      }

      // FEATURED: Ne pas autoriser les utilisateurs sauf les candidates avec un CV non activé
      $User = wp_get_current_user();
      $Candidate = Candidate::get_candidate_by($User->ID);
      if ( ! $Candidate || ! $Candidate->is_candidate()) return $message_access_refused;
      if ( $Candidate->hasCV()) {
        // TODO: Afficher le formulaire pour postuler sur l'offre.
        //do_action('add_notice', "Vous possédez déja un CV en ligne", "warning");
        return do_shortcode("[vc_jepostule]");
      } else {
        if (! is_null($redirect))
          do_action('add_notice', "Vous devez crée un CV avant de postuler", "warning");
      }

      wp_enqueue_style( 'b-datepicker-3' );
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_style( 'bootstrap-tagsinput' );
      wp_enqueue_style( 'ng-tags-bootstrap' );
      wp_enqueue_script( 'form-candidate', get_template_directory_uri() . '/assets/js/app/register/form-candidate.js', [
        'angular',
        'angular-ui-route',
        'angular-sanitize',
        'angular-messages',
        'angular-animate',
        'ngFileUpload',
        'b-datepicker',
        'daterangepicker',
        'sweetalert',
        'bootstrap-tagsinput',
        'ng-tags',
        'typeahead'
      ], $itJob->version, true );

      // Verifier si l'ajout du CV consiste à postuler sur une offre
      $redirection = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : home_url('/');;
      $offerId = Http\Request::getValue('offerId', false);
      $addedCVPage = jobServices::page_exists('Ajouter un CV');
      if ($offerId && $addedCVPage) {
        $redirection = sprintf('%s?offerId=%d', get_the_permalink($addedCVPage), (int)$offerId);
      }

      wp_localize_script( 'form-candidate', 'itOptions', [
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template_url' => get_template_directory_uri(),
        'urlHelper' => [
          'redir' => $redirection
        ]
      ] );

      try {
        do_action('get_notice');
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

    // Ajouter un CV
    public function update_user_cv() {
      if ( $_SERVER['REQUEST_METHOD'] != 'POST' || ! \wp_doing_ajax() || ! \is_user_logged_in() ) {
        return false;
      }

      $User      = wp_get_current_user();
      $Candidate = Candidate::get_candidate_by( $User->ID );

      if ( ! $Candidate->is_candidate()) return false;

      // Mette à jours le CV
      $status          = Http\Request::getValue( 'status', null );
      $project         = Http\Request::getValue( 'projet', ' ' );
      $activity_branch = Http\Request::getValue( 'abranch', null );
      $various         = Http\Request::getValue( 'various', null );
      $newsletter      = Http\Request::getValue( 'newsletter', 0 );

      $jobSougths = Http\Request::getValue( 'jobSougths' );
      $jobSougths = \json_decode( $jobSougths );

      $driveLicences = Http\Request::getValue( 'driveLicence', false );
      if ($driveLicences)
        $driveLicences = \json_decode( $driveLicences );

      $notiEmploi = Http\Request::getValue( 'notiEmploi', false );
      if ($notiEmploi)
        $notiEmploi = \json_decode( $notiEmploi );

      $trainings = Http\Request::getValue( 'trainings' );
      $trainings = \json_decode( $trainings );

      $experiences = Http\Request::getValue( 'experiences' );
      $experiences = \json_decode( $experiences );

      $languages = \json_decode( Http\Request::getValue( 'languages' ) );

      $form = [
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
        'languages'     => $languages
      ];

      foreach ( $form as $key => $value ) :
        if ( is_null( $value ) ) {
          \wp_send_json( [
            'success' => false,
            'msg'     => "Le formulaire n'est pas valide. Veillez verifier les formulaires",
            'input'   => $key,
            'value'   => $value
          ] );
          break;
        }
      endforeach;

      $form = (object) $form;

      wp_set_post_terms( $Candidate->getId(), [ (int) $form->abranch ], 'branch_activity' );

      $job_ids = array_map( function ( $jobs ) {
        return $jobs->term_id;
      }, $form->jobSougths );
      wp_set_post_terms( $Candidate->getId(), $job_ids, 'job_sought' );

      // Ajouter les languages
      $languageTermIDs = array_map( function ( $item ) {
        return $item->term_id;
      }, $form->languages );
      wp_set_post_terms( $Candidate->getId(), $languageTermIDs, 'language' );

      // Update ACF field
      update_field( 'itjob_cv_status', (int) $form->status, $Candidate->getId() );

      $centerInterest = [
        'various' => $form->various,
        'projet'  => $form->projet
      ];
      update_field( 'itjob_cv_centerInterest', $centerInterest, $Candidate->getId() );

      update_field( 'itjob_cv_newsletter', $form->newsletter, $Candidate->getId() );

      // Update drive licence field
      $LicenceSchema = [ 'a_' => 0, 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4 ];
      $licences      = array_map( function ( $key ) use ( $form, $LicenceSchema ) {
        if ( (int) $form->driveLicences->{$key} ) {
          return $LicenceSchema[ $key ];
        }
      }, array_keys( (array) $form->driveLicences ) );
      update_field( 'itjob_cv_driveLicence', implode( ',', $licences ) );

      // update notification 
      if ( $form->notifEmploi ) {
        if ( $form->notifEmploi->with ) {
          $notificationJob = [
            'notification'    => 1,
            'branch_activity' => isset( $form->notifEmploi->abranch ) ? $form->notifEmploi->abranch : ''
          ];

          if ( isset( $form->notifEmploi->job ) ) {
            $term_ids = [];
            foreach ( $form->notifEmploi->job as $job ) {
              if ( ! isset( $job->term_id ) || isset( $form->notifEmploi->abranch ) ) {
                $jobTerm = wp_insert_term(
                  $job->name,   // the term
                  'job_sought', // the taxonomy
                  array(//'parent' => (int) $form->notifEmploi->abranch,
                  )
                );

                // Désactiver le term qu'on viens d'ajouter
                if ( ! is_wp_error( $jobTerm ) ) {
                  update_field('activated', 0, "job_sought_{$jobTerm->term_id}");
                  array_push( $term_ids, $jobTerm->term_id );
                }
              } else {
                array_push( $term_ids, (int) $job->term_id );
              }
            }
            $notificationJob = array_merge( $notificationJob, [ 'job_sought' => implode( ',', $term_ids ) ] );

            // Mettre à jour la notification pour les offres en ligne
            update_field( 'itjob_cv_notifEmploi', $notificationJob, $Candidate->getId() );
          }
        }
      } // .end notification emploi

      // Ajouter les formations dans le CV
      $trainings = [];
      foreach ( $form->trainings as $training ) {
        $trainings[] = [
          'training_dateBegin'     => $training->start,
          'training_dateEnd'       => $training->end,
          'training_country'       => $training->country,
          'training_city'          => $training->city,
          'training_diploma'       => $training->diploma,
          'training_establishment' => $training->establishment
        ];
      }
      update_field( 'itjob_cv_trainings', $trainings, $Candidate->getId() );

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
          'exp_mission'      => $experience->mission
        ];
      }
      update_field( 'itjob_cv_experiences', $experiences, $Candidate->getId() );

      // TODO: Ne pas activer le CV
      //update_field('activated', 0, $Candidate->getId());

      wp_send_json( [ 'success' => true ] );

      // TODO: Ajouter une notification pour les formations ajouté (dev)

    } // .end update_user_cv

    // Ajouter une image à la une ou une image de profil
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
      $User      = wp_get_current_user();
      $Candidate = Candidate::get_candidate_by( $User->ID );

      // Let WordPress handle the upload.
      // Remember, 'file' is the name of our file input in our form above.
      // @wordpress: https://codex.wordpress.org/Function_Reference/media_handle_upload
      $attachment_id = media_handle_upload( 'file', $Candidate->getId() );

      if ( is_wp_error( $attachment_id ) ) {
        // There was an error uploading the image.
        wp_send_json( [ 'success' => false, 'msg' => $attachment_id->get_error_message() ] );
      } else {
        // The image was uploaded successfully!
        update_post_meta( $Candidate->getId(), '_thumbnail_id', $attachment_id );
        wp_send_json( [ 'attachment_id' => $attachment_id, 'success' => true ] );
      }
    }
  }
endif;

return new vcRegisterCandidate();