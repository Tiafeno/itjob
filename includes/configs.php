<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( function_exists( 'get_field' ) ):
  define( 'ESPACE_CLIENT_PAGE', get_field( 'espace_client', 'option' ) );
  define( 'LOGIN_PAGE', get_field( 'login_page_id', 'option' ) );
  define( 'ADD_OFFER_PAGE', get_field( 'add_offer_page_id', 'option' ) );
  define( 'ADD_FORMATION_PAGE', get_field( 'add_formation_page_id', 'option' ) );
  define( 'REGISTER_COMPANY_PAGE_ID', get_field( 'register_company_page_id', 'option' ) );
  define( 'REGISTER_CANDIDATE_PAGE_ID', get_field( 'register_candidate_page_id', 'option' ) );
  define( 'REGISTER_PARTICULAR_PAGE_ID', get_field( 'register_particular_page_id', 'option' ) );
  define( 'DOWNLOAD_CV_PAGE', get_field('download_cv_page', 'option'));
else:
  echo "Unable to active ACF Plugin";
endif;