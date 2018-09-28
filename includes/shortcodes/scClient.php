<?php

namespace includes\shortcode;

use Http;
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
      if ( class_exists( 'includes\post\Company' ) && class_exists( 'includes\post\Candidate' ) ) {
        $userTypes  = [ 'company', 'candidate' ];
        $this->User = wp_get_current_user();
        $userRole   = $this->User->roles[0];
        if ( $this->User->ID !== 0 && in_array( $userRole, $userTypes ) ) {
          $class_name    = ucfirst( $userRole );
          $class_name    = "includes\\post\\$class_name";
          $this->Company = call_user_func( [ $class_name, 'get_company_by' ], $this->User->ID );
        }
      } else {
        return;
      }

      add_shortcode( 'itjob_client', [ &$this, 'sc_render_html' ] );

      add_action( 'wp_ajax_trash_offer', [ &$this, 'client_trash_offer' ] );
      add_action( 'wp_ajax_client_area', [ &$this, 'client_area' ] );
      add_action( 'wp_ajax_update_offer', [ &$this, 'update_offer' ] );
      add_action( 'wp_ajax_update_profil', [ &$this, 'update_profil' ] );
      add_action( 'wp_ajax_update_alert_filter', [ &$this, 'update_alert_filter' ] );
    }

    public function sc_render_html( $attrs, $content = '' ) {
      global $Engine, $itJob;
      if ( ! is_user_logged_in() ) {
        $customer_area_url = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : get_permalink();

        return do_shortcode( '[itjob_login role="candidate" redirect_url="' . $customer_area_url . '"]', true );
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
      wp_enqueue_script( 'datatable', VENDOR_URL . '/dataTables/datatables.min.js', [ 'jquery' ], $itJob->version, true );
      wp_enqueue_script( 'espace-client', get_template_directory_uri() . '/assets/js/app/client/clients.js', [
        'angular',
        'angular-aria',
        'angular-messages',
        'angular-sanitize',
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

        if ( in_array( 'company', $client_roles, true ) ) {
          // Template recruteur ici ...

          // Script localize for company customer area
          wp_localize_script( 'espace-client', 'itOptions', [
            'Helper' => [
              'add_offer_url' => get_permalink( (int) ADD_OFFER_PAGE ),
              'ajax_url'      => admin_url( 'admin-ajax.php' ),
              'tpls_partials' => get_template_directory_uri() . '/assets/js/app/client/partials',
            ]
          ] );

          return $Engine->render( '@SC/client-company.html.twig', [
            'client' => Company::get_company_by( $this->User->ID ),
            'Helper' => [
              'template_url' => get_template_directory_uri()
            ]
          ] );
        }

        if ( in_array( 'candidate', $client_roles, true ) ) {
          // Template candidat ici ...
        }
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
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
      if ( is_null( $post_id ) ) {
        wp_send_json( false );
      }
      $form = [
        // TODO: Modifier le titre de l'offre et son champ ACF
        'post'             => Http\Request::getValue( 'postPromote' ),
        'datelimit'        => Http\Request::getValue( 'dateLimit' ),
        'contrattype'      => Http\Request::getValue( 'contractType' ),
        'profil'           => Http\Request::getValue( 'profil' ),
        'mission'          => Http\Request::getValue( 'mission' ),
        'proposedsallary'  => Http\Request::getValue( 'proposedSalary' ),
        'otherinformation' => Http\Request::getValue( 'otherInformation' ),
        'abranch'          => Http\Request::getValue( 'branch_activity' ),
      ];

      foreach ( $form as $key => $value ) {
        update_field( "itjob_offer_{$key}", $value, (int) $post_id );
      }
      wp_send_json( [ 'success' => true, 'form' => $form ] );
    }

    /**
     * Modifier le profil
     */
    public function update_profil() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
        wp_send_json( false );
      }
      $company_id = Http\Request::getValue( 'company_id', null );
      if ( is_null( $company_id ) ) {
        wp_send_json( false );
      }
      $form  = [
        'address'  => Http\Request::getValue( 'address' ),
        'greeting' => Http\Request::getValue( 'greeting' ),
        'name'     => Http\Request::getValue( 'name' ),
        'stat'     => Http\Request::getValue( 'stat' ),
        'nif'      => Http\Request::getValue( 'nif' )
      ];
      $terms = [
        'branch_activity' => Http\Request::getValue( 'branch_activity' ),
        'region'          => Http\Request::getValue( 'region' ),
        'city'            => Http\Request::getValue( 'country' ),
      ];

      foreach ( $terms as $key => $value ) {
        $isError = wp_set_post_terms( $company_id, [ (int) $value ], $key );
        if ( is_wp_error( $isError ) ) {
          wp_send_json( [ 'success' => false, 'msg' => $isError->get_error_message() ] );
        }
      }

      foreach ( $form as $key => $value ) {
        update_field( "itjob_company_{$key}", $value, $company_id );
      }

      wp_send_json( [ 'success' => true ] );

    }

    /**
     * Function ajax
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
        wp_send_json( false );
      }
      if ($itJob->services->isClient() === 'company'):
        $alerts = Http\Request::getValue('alerts');
        $alerts = \json_decode($alerts);
        $alerts = array_map(function($std) { return $std->text; }, $alerts);
        $data = update_field('itjob_company_alerts', implode(',', $alerts), $this->Company->getId());
        wp_send_json(['success' => true, 'data' => $data]);
      endif;
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
          'Alerts'  => explode( ',', $alert )
        ] );
      } else {
        // candidate

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