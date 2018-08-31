<?php

namespace includes\shortcode;

use includes\post\Company;
use includes\post\Offers;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'scClient' ) ) :
  class scClient {
    public function __construct() {
      add_shortcode( 'itjob_client', [ &$this, 'sc_render_html' ] );
    }

    public function sc_render_html( $attrs, $content = '' ) {
      global $Engine, $itJob;
      if ( ! is_user_logged_in() ) {
        $customer_area_url = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : get_permalink();

        return do_shortcode( '[itjob_login title="SE CONNECTER" redirect_url="' . $customer_area_url . '"]', true );
      }
      extract(
        shortcode_atts(
          array(),
          $attrs
        )
      );
      wp_enqueue_style( 'themify-icons', get_template_directory_uri() . '/assets/vendors/themify-icons/css/themify-icons.css' );
      wp_enqueue_script( 'datatable', get_template_directory_uri() . '/assets/vendors/dataTables/datatables.min.js', [ 'jquery' ], $itJob->version, true );
      wp_enqueue_script( 'client', get_template_directory_uri() . '/assets/js/app/client/clients.js', [
        'angular',
        $itJob->version
      ], true );
      wp_localize_script( 'client', 'itOptions', [
        'offers'   => $this->get_company_offers(),
        'ajax_url' => admin_url( 'admin-ajax.php' )
      ] );
      $user         = wp_get_current_user();
      $client       = get_userdata( $user->ID );
      $client_roles = $client->roles;
      $allCompany   = get_posts(
        [
          'numberposts'  => 1,
          'post_type'    => 'company',
          'post_status'  => [ 'publish', 'pending' ],
          'meta_key'     => 'itjob_company_email',
          'meta_value'   => $user->user_email,
          'meta_compare' => '='
        ] );
      $company      = reset( $allCompany );
      try {
        if ( in_array( 'company', $client_roles, true ) ) {
          return $Engine->render( '@SC/client-company.html.twig', [
            'client' => new Company( $company->ID ),
            'offers' => $this->get_company_offers(),
            'url'    => [
              'add_offer' => get_permalink( (int) ADD_OFFER_PAGE )
            ]
          ] );
        }

        if ( in_array( 'candidate', $client_roles, true ) ) {

        }
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

    public function update_user() {

    }

    private function get_company_offers() {
      $resolve = [];
      $User    = wp_get_current_user();
      $offers  = get_posts( [
        'numberposts' => - 1,
        'post_type'   => 'offers',
        'orderby'     => 'post_date',
        'order'       => 'ASC',
        'author'      => $User->ID,
        'post_status' => 'publish',
      ] );
      foreach ( $offers as $offer ) {
        setup_postdata( $offer );
        array_push( $resolve, new Offers( get_the_ID() ) );
      }
      wp_reset_postdata();

      return $resolve;
    }
  }
endif;

return new scClient();