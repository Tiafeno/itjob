<?php

namespace includes\shortcode;

use Http;
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
      if (is_user_logged_in()) {
        if ( class_exists( 'includes\post\Company' ) && class_exists( 'includes\post\Candidate' ) ) {
          $userTypes  = [ 'company', 'candidate' ];
          $this->User = wp_get_current_user();
          if ( $this->User->ID !== 0) {
            $userRole   = $this->User->roles[0];
            if ( ! in_array( $userRole, $userTypes )) return;
            $class_name_ucfirst    = ucfirst( $userRole );
            $class_name    = "includes\\post\\$class_name_ucfirst";
            $this->{$class_name_ucfirst} = call_user_func( [ $class_name, "get_{$userRole}_by" ], $this->User->ID );
          }
        }

        add_action( 'wp_ajax_trash_offer', [ &$this, 'client_trash_offer' ] );
        add_action( 'wp_ajax_client_area', [ &$this, 'client_area' ] );
        add_action( 'wp_ajax_update_offer', [ &$this, 'update_offer' ] );
        add_action( 'wp_ajax_update_profil', [ &$this, 'update_profil' ] );
        add_action( 'wp_ajax_update_alert_filter', [ &$this, 'update_alert_filter' ] );
        add_action( 'wp_ajax_get_postuled_candidate', [ &$this, 'get_postuled_candidate' ] );
        add_action( 'wp_ajax_update_experiences', [ &$this, 'update_experiences' ] );
        add_action( 'wp_ajax_update-user-password', [ &$this, 'change_user_password' ] );
      }

      add_shortcode( 'itjob_client', [ &$this, 'sc_render_html' ] );
    }

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
        'datatable',
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
          ]
        ];

        // Load company template
        if ( in_array( 'company', $client_roles, true ) ) {
          wp_enqueue_script('app-company', get_template_directory_uri() . '/assets/js/app/client/configs-company.js', [
            'espace-client'
          ], $itJob->version, true);
          // Template recruteur ici ...
          $wp_localize_script_args['Helper']['add_offer_url'] = get_permalink( (int) ADD_OFFER_PAGE );
          $wp_localize_script_args['client_type'] = 'company';
          // Script localize for company customer area
          wp_localize_script( 'espace-client', 'itOptions', $wp_localize_script_args);

          return $Engine->render( '@SC/client-company.html.twig', [
            'Helper' => [
              'template_url' => get_template_directory_uri()
            ]
          ] );
        }

        // Load candidate template
        if ( in_array( 'candidate', $client_roles, true ) ) {
          wp_enqueue_script('app-candidate', get_template_directory_uri() . '/assets/js/app/client/configs-candidate.js', [
            'espace-client'
          ], $itJob->version, true);

          $add_cv_id = \includes\object\jobServices::page_exists('Ajouter un CV');
          $wp_localize_script_args['Helper']['add_cv'] = get_permalink( (int) $add_cv_id );
          $wp_localize_script_args['client_type'] = 'candidate';
          wp_localize_script( 'espace-client', 'itOptions', $wp_localize_script_args);

          // Template candidat ici ...
          return $Engine->render( '@SC/client-candidate.html.twig', [
            'display_name' => $this->Candidate->get_display_name(),
            'Helper' => [
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
      $post_id = (int)$post_id;
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
      wp_update_post([
        'ID' => $post_id,
        'post_title' => $form->post
      ]);
      foreach ( $form as $key => $value ) {
        update_field( "itjob_offer_{$key}", $value, $post_id );
      }
      wp_send_json( [ 'success' => true, 'form' => $form ] );
    }

    /**
     Modifier le profil de l'utilisateur
     */
    public function update_profil() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $candidate_id = Http\Request::getValue( 'candidate_id', null );
      $company_id = Http\Request::getValue( 'company_id', null );
      $terms = [
        'branch_activity' => Http\Request::getValue( 'branch_activity' ),
        'region'          => Http\Request::getValue( 'region' ),
        'city'            => Http\Request::getValue( 'country' ),
      ];
      if ( ! empty( $company_id ) ) {
        $form  = [
          //'address'  => Http\Request::getValue( 'address' ),
          'greeting' => Http\Request::getValue( 'greeting', null ),
          'name'     => Http\Request::getValue( 'name' ),
          'stat'     => Http\Request::getValue( 'stat', null ),
          'nif'      => Http\Request::getValue( 'nif', null )
        ];
        foreach ( $form as $key => $value ) {
          if ( ! is_null($value))
            update_field( "itjob_company_{$key}", $value, $company_id );
        }
      } else {
        $input  = [
          //'address'  => Http\Request::getValue( 'address' ),
          'greeting' => Http\Request::getValue( 'greeting' ),
        ];
        foreach ( $input as $key => $value ) {
          if ($value)
            update_field( "itjob_cv_{$key}", $value, $candidate_id );
        }
      }
      $post_id = is_null($candidate_id) ? $company_id : $candidate_id;
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
     Modifier le mot de passe de l'utilisateur
     @route admin-ajax.php?action=update-user-password
     */
    public function change_user_password() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $oldPwd = Http\Request::getValue('oldpwd');
      $pwd = Http\Request::getValue("pwd");
      if (wp_check_password( $oldPwd, $this->User->data->user_pass, $this->User->ID) ) :
        wp_set_password( $pwd, $this->User->ID );
        wp_send_json(['success' => true]);
      else:
        wp_send_json(['success' => false, 'msg' => 'Une erreur s\est produit,
        Il est probable que l\'ancien mot de passe n\'est pas correct']);
      endif;

    }
    /**
     Ajouter une offre dans la corbeille
     @route admin-ajax.php?action=trash_offer&pId=<int>
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
        wp_send_json( false );
      }
      $alerts = Http\Request::getValue('alerts');
      $alerts = \json_decode($alerts);
      if ($itJob->services->isClient() === 'company') {
        // Company
        $alerts = array_map( function ( $std ) {
          return $std->text;
        }, $alerts );
        $data   = update_field( 'itjob_company_alerts', implode( ',', $alerts ), $this->Company->getId() );
        if ( $data ) {
          wp_send_json( [ 'success' => true ] );
        }
      } else {
        $notification = get_field('itjob_cv_notifEmploi', $this->Candidate->getId());
        // Candidate
        $alerts = array_map( function ( $std ) {
          return $std->text;
        }, $alerts );
        $values = [
          'notification'    => $notification['notification'],
          'branch_activity' => $notification['branch_activity'],
          'job_sought'      => implode( ',', $alerts )
        ];
        $data   = update_field( 'itjob_cv_notifEmploi', $values, $this->Candidate->getId() );
        wp_send_json( [ 'success' => $data ] );
      }
    }

    /**
     * Function ajax
     * @route admin-ajax.php?action=update_experiences&experience=<json>
     */
    public function update_experiences() {
      if ( ! is_user_logged_in() || ! wp_doing_ajax() ) {
        wp_send_json( false );
      }
      $new_experiences = [];
      $experiences = Http\Request::getValue('experiences');
      $experiences = json_decode($experiences);
      foreach ( $experiences as $experience ) {
        $new_experiences[] = [
          'exp_dateBegin'    => $experience->exp_dateBegin, // Formated value; ERROR
          'exp_dateEnd'      => $experience->exp_dateEnd, // Frmated value; ERROR
          'exp_country'      => $experience->exp_country,
          'exp_city'         => $experience->exp_city,
          'exp_company'      => $experience->exp_company,
          'exp_positionHeld' => $experience->exp_positionHeld,
          'exp_mission'      => $experience->exp_mission
        ];
      }
      $resolve = update_field( 'itjob_cv_experiences', $new_experiences, $this->Candidate->getId() );
      if ($resolve) {
        $experiences = get_field('itjob_cv_experiences', $this->Candidate->getId());
        wp_send_json(['success' => true, 'experiences' => $experiences]);
      } else {
        wp_send_json(['success' => false]);
      }
    }

    /**
     * Retourne les candidates qui ont postuler
     */
    public function get_postuled_candidate() {
      $offer_id = Http\Request::getValue('oId');
      $offer_id = (int)$offer_id;
      $postuledCandidates = [];
      $Offer = new Offers($offer_id);
      if ($Offer->is_offer() && $Offer->count_candidat_apply >= 1) {
        $candidate_ids = $Offer->candidat_apply;
        foreach ($candidate_ids as $key => $candidate_id) {
          array_push($postuledCandidates, Candidate::get_candidate_by($candidate_id));
        }
        wp_send_json($postuledCandidates);
      } else {
        wp_send_json(false);
      }

      die;
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
      $User = wp_get_current_user();
      if ( $itJob->services->isClient() === 'company' ) {
        $alert = get_field( 'itjob_company_alerts', $this->Company->getId() );
        wp_send_json( [
          'Company' => Company::get_company_by( $User->ID ),
          'Offers'  => $this->__get_company_offers(),
          'Alerts'  => explode( ',', $alert ),
          'post_type' => 'company'
        ] );
      } else {
        // candidate

        $notification = get_field( 'itjob_cv_notifEmploi', $this->Candidate->getId() );
        $Candidate = Candidate::get_candidate_by( $User->ID );
        $Candidate->isMyCV();
        $alerts = explode( ',', $notification['job_sought'] );
        wp_send_json( [
          'Candidate' => $Candidate,
          'Alerts'  => $alerts,
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
        array_push( $resolve, new Offers( $offer->ID ) );
      }

      return $resolve;
    }
  }
endif;

return new scClient();
