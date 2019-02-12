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

define('__SITENAME__', 'itJob');
define('__google_api__', 'QUl6YVN5Qng3LVJKbGlwbWU0YzMtTGFWUk5oRnhiV19xWG5DUXhj');
define('TWIG_TEMPLATE_PATH', get_template_directory() . '/templates');
if (!defined('VENDOR_URL')) {
  define('VENDOR_URL', get_template_directory_uri() . '/assets/vendors');
}
$theme = wp_get_theme('itjob');

// Utiliser ces variables apres la fonction: the_post()
$offers    = null;
$company   = null;
$candidate = null;
// Variable pour les alerts
$it_alerts = [];

require 'includes/configs.php';
require 'includes/itjob-functions.php';
require 'includes/class/class-token.php';

// Importation dependancy
require 'includes/import/class-import-user.php';

// middlewares
require 'includes/class/middlewares/OfferHelper.php';
require 'includes/class/middlewares/Auth.php';
require 'includes/class/middlewares/Register.php';
require 'includes/class/middlewares/ModelInterest.php';
require 'includes/class/middlewares/ModelNotice.php';
require 'includes/class/middlewares/ModelCVLists.php';
require 'includes/class/middlewares/ModelAds.php';

// Model
require 'includes/class/class-model.php';
include 'includes/class/model/class-model-request-formation.php';
include 'includes/class/model/class-model-subscription-formation.php';

// widgets
require 'includes/class/widgets/widget-shortcode.php';
require 'includes/class/widgets/widget-accordion.php';
require 'includes/class/widgets/widget-header-search.php';

$interfaces = [
  'includes/class/interfaces/iOffer.php',
  'includes/class/interfaces/iCompany.php',
  'includes/class/interfaces/iCandidate.php'
];
foreach ($interfaces as $interface) {
  require $interface;
}

// post type object
require_once 'includes/class/class-request-formation.php';
require_once 'includes/class/class-formation.php';
require_once 'includes/class/class-offers.php';
require_once 'includes/class/class-particular.php';
require_once 'includes/class/class-company.php';
require_once 'includes/class/class-candidate.php';
require_once 'includes/class/class-annonce.php';

$itJob = (object)[
  'version' => $theme->get('Version'),
  'root' => require 'includes/class/class-itjob.php',
  'services' => require 'includes/class/class-jobservices.php'
];

// shortcodes
$shortcode = (object)[
  'scImport' => require 'includes/shortcodes/class-import-csv.php',
  'scLogin' => require 'includes/shortcodes/class-login.php',
  'scInterests' => require 'includes/shortcodes/class-interests.php'
];

add_action('init', function () {
  global $shortcode;
  $shortcode->scClient = require 'includes/shortcodes/scClient.php';
  $page_oc_id = \includes\object\jobServices::page_exists('Espace client');
  add_rewrite_rule('^espace-client/?', "index.php?page_id={$page_oc_id}", 'top');
});

// Visual composer elements
$elementsVC = (object)[
  'vcSearch' => require 'includes/visualcomposer/elements/class-vc-search.php',
  'vcOffers' => require 'includes/visualcomposer/elements/class-vc-offers.php',
  'vcBlog' => require 'includes/visualcomposer/elements/class-vc-blog.php',
  'vcCandidate' => require 'includes/visualcomposer/elements/class-vc-candidate.php',
  'vcJePostule' => require 'includes/visualcomposer/elements/class-vc-jepostule.php',
  'vcSlider' => require 'includes/visualcomposer/elements/class-slider.php',
  'vcRegisterCompany' => require 'includes/visualcomposer/elements/class-vc-register-company.php',
  'vcRegisterParticular' => require 'includes/visualcomposer/elements/class-vc-register-particular.php',
  'vcRegisterCandidate' => require 'includes/visualcomposer/elements/class-vc-register-candidate.php',
  'vcAds' => require 'includes/visualcomposer/elements/class-vc-ads.php',
  'vcFormation' => require 'includes/visualcomposer/elements/class-vc-formation.php',
  'vcRequestFormation' => require 'includes/visualcomposer/elements/class-vc-request-formation.php',
  'vcAnnonce' => require 'includes/visualcomposer/elements/class-vc-annonce.php'
];

require 'includes/class/class-wp-city.php';
require 'includes/class/class-notification.php';
require 'includes/class/class-http-request.php';
require 'includes/class/class-jhelper.php';
require 'includes/class/class-menu-walker.php';
require 'includes/filters/function-filters.php';

$itHelper = (object)[
  'Mailing' => require 'includes/class/class-mail.php'
];

// Autoload composer libraries
require 'composer/vendor/autoload.php';

try {
  $loader = new Twig_Loader_Filesystem();
  $loader->addPath(TWIG_TEMPLATE_PATH . '/vc', 'VC');
  $loader->addPath(TWIG_TEMPLATE_PATH . '/shortcodes', 'SC');
  $loader->addPath(TWIG_TEMPLATE_PATH . '/widgets', 'WG');
  $loader->addPath(TWIG_TEMPLATE_PATH . '/error', 'ERROR');
  $loader->addPath(TWIG_TEMPLATE_PATH . '/mail', 'MAIL');

  /** @var Object $Engine */
  $Engine = new Twig_Environment($loader, array(
    'debug' => false,
    'cache' => TWIG_TEMPLATE_PATH . '/cache',
    'auto_reload' => true
  ));
  // Ajouter des filtres
  itjob_filter_engine($Engine);

} catch (Twig_Error_Loader $e) {
  die($e->getRawMessage());
}

require 'api/itjob-api.php';
require 'jobs/itjob-cron.php';

add_action('after_setup_theme', function () {
  load_theme_textdomain('twentyfifteen');
  load_theme_textdomain(__SITENAME__, get_template_directory() . '/languages');

  /** @link https://codex.wordpress.org/Post_Thumbnails */
  add_theme_support('post-thumbnails');
  add_theme_support('category-thumbnails');
  add_theme_support('automatic-feed-links');
  add_theme_support('title-tag');
  add_theme_support('custom-logo', array(
    'height' => 100,
    'width' => 250,
    'flex-width' => true,
  ));


  add_image_size('sidebar-thumb', 120, 120, true);
  add_image_size('homepage-thumb', 220, 180);
  add_image_size('singlepost-thumb', 590, 9999);


  /**
   * This function will not resize your existing featured images.
   * To regenerate existing images in the new size,
   * use the Regenerate Thumbnails plugin.
   */
  set_post_thumbnail_size(50, 50, array(
    'center',
    'center'
  )); // 50 pixels wide by 50 pixels tall, crop from the center

  // Register menu location
  register_nav_menus(array(
    'primary' => 'Menu Principal',
    'menu-top' => 'Menu Supérieur (Top)',
    'menu-footer-left' => 'Menu à gauche, bas de page',
    'menu-footer-middle' => 'Menu aux milieux, bas de page',
    'social-network' => 'Réseau social',
  ));
});

if (function_exists('acf_add_options_page')) {
  $parent = acf_add_options_page(array(
    'page_title' => 'General Settings',
    'menu_title' => 'itJob Settings',
    'capability' => 'edit_posts',
    'redirect' => false
  ));
}

add_filter('body_class', function ($classes) {
  //$classes[] = 'uk-offcanvas-content';
  return $classes;
});

/**
 * Personnaliser le menu d'accueil
 * (ajouter un walker)
 */
add_filter('wp_nav_menu_args', function ($args) {
  $menu = $args['menu'];
  if (empty($menu)) {
    return $args;
  }
  if ($menu->name === 'REF219M') :
    $args['menu_class'] = "it-home-menu uk-padding-remove";
  $args['container_class'] = "d-flex";
  $args['walker'] = new Home_Menu_Walker();
  endif;

  return $args;
});


add_action('init', function () {
  
  // Yoast filter
  add_filter('wpseo_metadesc', function ($description) {
    global $post;
    if (is_object($post))
      switch ($post->post_type) {
        case 'offers':
          $mission = get_field( 'itjob_offer_profil', $post->ID );
          return strip_tags($mission);
          break;
        
        default:
          # code...
          break;
      }
    return $description;
  }, PHP_INT_MAX);

  add_filter('wpseo_title', function ($title) {
    global $post;
    if (is_object($post) && !is_archive())
      switch ($post->post_type) {
        case 'offers':
          $regions      = wp_get_post_terms( $post->ID, 'region', ["fields" => "all"] );
          $region = is_array($regions) && !empty($regions) ? $regions[0] : '';
          $region = $region ? ' à ' . $region->name : '';
          $branch_activity  = get_field( 'itjob_offer_abranch', $post->ID );
          $branch_activity = $branch_activity ? ', ' . $branch_activity->name : '';
          return 'Emploi - ' . $post->post_title . $region . $branch_activity;
          break;
        
        default:
          # code...
          break;
      }
    return $title;
  }, PHP_INT_MAX);
  
  //do_action('testUnits');

//  $Model = new includes\model\itModel();
//  add_action('repair_table', [$Model, 'repair_table'], 10);

  function add_sticky_column($columns) {
    return array_merge($columns,
      array('activated' => __('Activation')));
  }
  add_filter('manage_formation_posts_columns' , 'add_sticky_column');

  function display_posts_stickiness( $column, $post_id ) {
    $activate = get_field('activated', $post_id);
    if ($column == 'activated'){
      echo '<input type="checkbox" disabled', $activate ? ' checked' : '', '/>';
    }
  }
  add_action( 'manage_posts_custom_column' , 'display_posts_stickiness', 10, 2 );
});



