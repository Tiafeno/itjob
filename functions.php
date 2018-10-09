<?php

/**
 *   Copyright (c) 2018, Falicrea
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy
 *   of this software and associated documentation files, to deal
 *   in the Software without restriction, including without limitation the rights
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *   copies of the Software, and to permit persons to whom the Software is
 *   furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in all
 *   copies or substantial portions of the Software.
 */

const CLIENT_ID = "kaleFli5PJb3o2kCstBKDMkU09m3ak2FcvcJHqcG";
const CLIENT_SECRET = "MiSw1PAHwg9bRiVr99KsZyYGrJr57nua7JXR2vdD";

const AUTHORIZATION_ENDPOINT = "http://localhost/managna/oauth/authorize";
const TOKEN_ENDPOINT = "http://localhost/managna/oauth/token";
const REDIRECT_URI = "http://localhost/managna/";



define( '__SITENAME__', 'itJob' );
define( '__google_api__', 'QUl6YVN5Qng3LVJKbGlwbWU0YzMtTGFWUk5oRnhiV19xWG5DUXhj' );
define( 'TWIG_TEMPLATE_PATH', get_template_directory() . '/templates' );
if ( ! defined('VENDOR_URL'))
  define( 'VENDOR_URL', get_template_directory_uri() . '/assets/vendors' );
$theme     = wp_get_theme( 'itjob' );

// Utiliser ces variables apres la fonction: the_post()
$offers    = null;
$company   = null;
$candidate = null;

// Variable pour les alerts
$it_alerts = [];


require 'includes/itjob-configs.php';
require 'includes/itjob-functions.php';

// middlewares
require 'includes/class/middlewares/OfferHelper.php';
require 'includes/class/middlewares/Auth.php';
require 'includes/class/middlewares/Register.php';

// widgets
require 'includes/class/widgets/widget-publicity.php';
require 'includes/class/widgets/widget-shortcode.php';
require 'includes/class/widgets/widget-accordion.php';
require 'includes/class/widgets/widget-header-search.php';

$interfaces = [
  'includes/class/interfaces/iOffer.php',
  'includes/class/interfaces/iCompany.php',
  'includes/class/interfaces/iCandidate.php'
];
foreach ( $interfaces as $interface ) {
  require $interface;
}

// post type object
require_once 'includes/class/class-offers.php';
require_once 'includes/class/class-particular.php';
require_once 'includes/class/class-company.php';
require_once 'includes/class/class-candidate.php';

$itJob = (object) [
  'version'  => $theme->get( 'Version' ),
  'root'     => require 'includes/class/class-itjob.php',
  'services' => require 'includes/class/class-jobservices.php'
];

// shortcodes
$shortcode = (object) [
  'scImport' => require 'includes/shortcodes/class-import-csv.php',
  'scLogin'  => require 'includes/shortcodes/class-login.php',
  'scInterests'  => require 'includes/shortcodes/class-interests.php'
];

add_action('init', function() {
  global $shortcode;
  $shortcode->scClient = require 'includes/shortcodes/scClient.php';
});

// Visual composer elements
$elementsVC = (object) [
  'vcSearch'   => require 'includes/visualcomposer/elements/class-vc-search.php',
  'vcOffers'   => require 'includes/visualcomposer/elements/class-vc-offers.php',
  'vcCandidate'   => require 'includes/visualcomposer/elements/class-vc-candidate.php',
  'vcJePostule'   => require 'includes/visualcomposer/elements/class-vc-jepostule.php',
  'vcRegisterCompany' => require 'includes/visualcomposer/elements/class-vc-register-company.php',
  'vcRegisterParticular' => require 'includes/visualcomposer/elements/class-vc-register-particular.php5',
  'vcRegisterCandidate' => require 'includes/visualcomposer/elements/class-vc-register-candidate.php'
];

require 'includes/class/class-wp-city.php';
require 'includes/class/class-http-request.php';
require 'includes/class/class-menu-walker.php';
require 'includes/filters/function-filters.php';
require 'api/itjob-api.php';
require 'jobs/itjob-cron.php';

// Autoload composer libraries
require 'composer/vendor/autoload.php';

//if (isset($_GET['OAuth'])) {
//  $client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);
//  if (!isset($_GET['code']))
//  {
//    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI);
//    header('Location: ' . $auth_url);
//    die('Redirect');
//  }
//  else
//  {
//    $params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
//    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params);
//    echo "<pre>";
//    print_r($response);
//  }
//}


try {
  $loader = new Twig_Loader_Filesystem();
  $loader->addPath( TWIG_TEMPLATE_PATH . '/vc', 'VC' );
  $loader->addPath( TWIG_TEMPLATE_PATH . '/shortcodes', 'SC' );
  $loader->addPath( TWIG_TEMPLATE_PATH . '/widgets', 'WG' );
  $loader->addPath( TWIG_TEMPLATE_PATH . '/error', 'ERROR' );

  /** @var Object $Engine */
  $Engine = new Twig_Environment( $loader, array(
    'debug'       => WP_DEBUG,
    'cache'       => TWIG_TEMPLATE_PATH . '/cache',
    'auto_reload' => WP_DEBUG
  ) );
  // Ajouter des filtres
  itjob_filter_engine( $Engine );

} catch ( Twig_Error_Loader $e ) {
  die( $e->getRawMessage() );
}


add_action( 'after_setup_theme', function () {
  load_theme_textdomain( 'twentyfifteen' );
  load_theme_textdomain( __SITENAME__, get_template_directory() . '/languages' );

  /** @link https://codex.wordpress.org/Post_Thumbnails */
  add_theme_support( 'post-thumbnails' );
  add_theme_support( 'category-thumbnails' );
  add_theme_support( 'automatic-feed-links' );
  add_theme_support( 'title-tag' );
  add_theme_support( 'custom-logo', array(
    'height'     => 100,
    'width'      => 250,
    'flex-width' => true,
  ) );

  /*
	 add_image_size('sidebar-thumb', 120, 120, true);
	 add_image_size('homepage-thumb', 220, 180);
	 add_image_size('singlepost-thumb', 590, 9999);
	 */

  /**
   * This function will not resize your existing featured images.
   * To regenerate existing images in the new size,
   * use the Regenerate Thumbnails plugin.
   */
  set_post_thumbnail_size( 50, 50, array(
    'center',
    'center'
  ) ); // 50 pixels wide by 50 pixels tall, crop from the center

  // Register menu location
  register_nav_menus( array(
    'primary'            => 'Menu Principal',
    'menu-top'           => 'Menu Supérieur (Top)',
    'menu-footer-left'   => 'Menu à gauche, bas de page',
    'menu-footer-middle' => 'Menu aux milieux, bas de page',
    'social-network'     => 'Réseau social',
  ) );
} );

if ( function_exists( 'acf_add_options_page' ) ) {
  $parent = acf_add_options_page( array(
    'page_title' => 'General Settings',
    'menu_title' => 'itJob Settings',
    'capability' => 'edit_posts',
    'redirect'   => false
  ) );
}

add_filter( 'body_class', function ( $classes ) {
  //$classes[] = 'uk-offcanvas-content';
  return $classes;
} );

/**
 * Personnaliser le menu d'accueil
 * (ajouter un walker)
 */
add_filter( 'wp_nav_menu_args', function ( $args ) {
  /**
   * [term_id] => 219
   * [name] => REF219M
   * [slug] =>
   * [term_group] => 0
   * [term_taxonomy_id] => 219
   * [taxonomy] => nav_menu
   * [description] =>
   * [parent] => 0
   * [count] => 6
   * [filter] => raw
   */
  $menu = $args['menu'];
  if ( empty( $menu ) ) {
    return $args;
  }
  if ( $menu->name === 'REF219M' ) :
    $args['menu_class']      = "it-home-menu uk-padding-remove";
    $args['container_class'] = "d-flex";
    $args['walker']          = new Home_Menu_Walker();
  endif;

  return $args;
} );



