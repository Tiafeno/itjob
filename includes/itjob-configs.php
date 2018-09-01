<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( function_exists( 'get_field' ) ):
  define( 'ESPACE_CLIENT_PAGE', get_field( 'espace_client', 'option' ) );
  define( 'LOGIN_PAGE', get_field( 'login_page_id', 'option' ) );
  define( 'REGISTER_PAGE', get_field( 'register_page_id', 'option' ) );
  define( 'ADD_OFFER_PAGE', get_field( 'add_offer_page_id', 'option' ) );
  define( 'ADD_COMPANY_PAGE', get_field( 'add_company_page_id', 'option' ) );
else:
  die( "Unable to active ACF Plugin" );
endif;