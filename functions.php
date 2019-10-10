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
define('minify', false);
define('__CREDITS__', 10);
define('__CREDIT_PRICE__', 2000); // En ariary
define('__google_api__', 'QUl6YVN5Qng3LVJKbGlwbWU0YzMtTGFWUk5oRnhiV19xWG5DUXhj');
define('TWIG_TEMPLATE_PATH', get_template_directory() . '/templates');
if (!defined('VENDOR_URL')) {
  define('VENDOR_URL', get_template_directory_uri() . '/assets/vendors');
}
global $wp_version;
$theme = wp_get_theme('itjob');

// Utiliser ces variables apres la fonction: the_post()
$offers = null;
$company = null;
$candidate = null;
// Variable pour les alerts
$it_alerts = [];

require 'includes/configs.php';
require 'includes/itjob-functions.php';
require 'includes/class/class-token.php';
require 'includes/class/class-admin-manager.php';

// Importation dependancy
//require 'includes/import/class-import-user.php';

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
include 'includes/class/model/class-model-wallet.php';
include 'includes/class/model/paiementModel.php';

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
require_once 'includes/class/class-wallet.php';
require_once 'includes/class/class-work-temporary.php';
require_once 'includes/class/class-paiement-history.php';

$itJob = (object) [
  'version' => $wp_version,
  'root' => require 'includes/class/class-itjob.php',
  'services' => require 'includes/class/class-jobservices.php'
];

// shortcodes
$shortcode = (object) [
  //'scImport' => require 'includes/shortcodes/class-import-csv.php',
  'scLogin' => require 'includes/shortcodes/class-login.php',
  'scInterests' => require 'includes/shortcodes/class-interests.php',
];

add_action('init', function () {
  global $shortcode;
  $shortcode->scClient = require 'includes/shortcodes/scClient.php';
  $page_oc_id = \includes\object\jobServices::page_exists('Espace client');
  add_rewrite_rule('^espace-client/?', "index.php?page_id={$page_oc_id}", 'top');
});

// Visual composer elements
$elementsVC = (object) [
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
  'vcAnnonce' => require 'includes/visualcomposer/elements/class-vc-annonce.php',
  'Credits' => require 'includes/class/class-credit.php'
];

require 'includes/class/class-wp-city.php';
require 'includes/class/class-notification.php';
require 'includes/class/class-http-request.php';
require 'includes/class/class-jhelper.php';
require 'includes/class/class-menu-walker.php';
require 'includes/filters/function-filters.php';

$itHelper = (object) [
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
    'capability' => 'delete_users',
    'menu_title' => 'ITJOB General Settings',
    'autoload' => true,
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

add_action('admin_init', function () {
  $administrator = get_role('administrator');
  $editor = get_role('editor');


  $caps = [
    'edit_formation', 'edit_formations', 'read_private_formation', 'read_formation',
    'edit_published_formations', 'edit_others_formations', 'edit_private_formations', 'delete_formation', 'delete_formations',
    'delete_others_formations', 'delete_private_formations', 'delete_published_formations', 'publish_formations',

    'edit_offer', 'edit_offers', 'read_private_offer', 'read_offer', 'edit_published_offers', 'edit_others_offers',
    'edit_private_offers', 'delete_offer', 'delete_offers', 'delete_others_offers', 'delete_private_offers', 'delete_published_offers',
    'publish_offers',

    'edit_work', 'edit_works', 'read_private_work', 'read_work', 'edit_published_works', 'edit_others_works',
    'edit_private_works', 'delete_work', 'delete_works', 'delete_others_works', 'delete_private_works', 'delete_published_works',
    'publish_works',

    'edit_annonce', 'edit_annonces', 'read_private_annonce', 'read_annonce', 'edit_published_annonces', 'edit_others_annonces',
    'edit_private_annonces', 'delete_annonce', 'delete_annonces', 'delete_others_annonces', 'delete_private_annonces',
    'delete_published_annonces', 'publish_annonces',

    'edit_candidate', 'edit_candidates', 'read_private_candidate', 'read_candidate', 'edit_published_candidates', 'edit_others_candidates',
    'edit_private_candidates', 'delete_candidate', 'delete_candidates', 'delete_others_candidates', 'delete_private_candidates',
    'delete_published_candidates', 'publish_candidates',

    'edit_wallet', 'edit_wallets', 'read_private_wallet', 'read_wallet', 'edit_published_wallets', 'edit_others_wallets',
    'edit_private_wallets', 'delete_wallet', 'delete_wallets', 'delete_others_wallets', 'delete_private_wallets',
    'delete_published_wallets', 'publish_wallets'
  ];

  foreach ($caps as $cap) {
    if ( ! $administrator->has_cap($cap)):
      $administrator->add_cap( $cap );
    endif;

    if ( ! $editor->has_cap($cap)):
      $editor->add_cap( $cap );
    endif;

  }
});

add_action('init', function () {

  // Yoast filter
  add_filter('wpseo_metadesc', function ($description) {
    global $post;
    if (is_object($post))
      switch ($post->post_type) {
        case 'offers':
          $mission = get_field('itjob_offer_profil', $post->ID);
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
          $regions = wp_get_post_terms($post->ID, 'region', ["fields" => "all"]);
          $region = is_array($regions) && !empty($regions) ? $regions[0] : '';
          $region = $region ? ' à ' . $region->name : '';
          $branch_activity = get_field('itjob_offer_abranch', $post->ID);
          if ( ! is_object($branch_activity)) {
            $branch_activity = get_term(intval($branch_activity));
          }

          $branch_activity = is_wp_error($branch_activity) ? '' : (isset($branch_activity->name) ? ', ' . $branch_activity->name : '');
          return 'Emploi - ' . $post->post_title . $region . $branch_activity;
          break;

        default:
          # code...
          break;
      }
    return $title;
  }, PHP_INT_MAX);

  function request_phone_number() {
    global $itJob;
    $msg_contact_error = "Vous ne pouvez pas contactez cette annonceur car c'est votre annonce. Merci";
    $post_id = Http\Request::getValue('ad_id');
    $post = get_post(intval($post_id));
    $post_type = get_post_type($post->ID);
    if ( ! in_array($post_type, ['annonce', 'works'])) :
      wp_send_json_error("Ce type de post ne possède pas un numéro de téléphone");
    endif;

    if ($post_type === 'annonce') {
      $Annonce = new \includes\post\Annonce($post->ID, true);
      $User = $itJob->services->getUser();
      if (is_wp_error( $User )) {
        wp_send_json_error( "Merci de vous connecter pour voir le contact. Merci" );
        return false;
      }

      if ($User->ID === $Annonce->get_author()->ID) {
        wp_send_json_error($msg_contact_error);
        return false;
      }
      if (!in_array($User->ID, $Annonce->contact_sender)) {
        $Annonce->add_contact_sender($User->ID);
      }
      wp_send_json_success($Annonce->cellphone);
    }

    if ($post_type === 'works') {
      $Works = new \includes\post\Works($post->ID, true);
      $User = $itJob->services->getUser();
      if ($User->ID === $Works->get_user()->ID) {
        wp_send_json_error($msg_contact_error);
        return false;
      }
      $Wallet = \includes\post\Wallet::getInstance($User->ID, 'user_id', true);
      $credit = $Wallet->credit;
      if ( ! $credit ) wp_send_json_error("Il ne vous reste plus de credit.");
      if ( ! $Works->has_contact($User) ) {
        $credit = $credit - 1;
        $Wallet->update_wallet($credit);
        $Works->add_contact_sender($User->ID);
      }
      $greet = '';
      if (in_array('candidate', $User->roles)) {
        $Candidate = \includes\post\Candidate::get_candidate_by($User->ID);
        $first_name = ucfirst($Candidate->getFirstName());
        $greet  = $Candidate->greeting['label'];
      }

      if (in_array('company', $User->roles)) {
        $Company = \includes\post\Company::get_company_by($User->ID);
        $first_name = $Company->name;
        $greet = $Company->greeting == 'mrs' ? "Monsieur" : "Madame";
      }
      wp_send_json_success(['phone' => $Works->cellphone, 'first_name' => $first_name, 'greet' => $greet ]);
    }
  }
  add_action('wp_ajax_request_phone_number', 'request_phone_number');
  add_action('wp_ajax_nopriv_request_phone_number', 'request_phone_number');

  // Status de paiement
  add_action('woocommerce_order_status_completed', 'payment_complete', 100, 1);
  add_action('woocommerce_payment_complete', 'payment_complete', 100, 1);
  add_action('woocommerce_thankyou', function ($order_id) {
    $Order = new WC_Order($order_id);
    $payment_method = $Order->get_payment_method(); // cheque, vanillapay
  }, 100, 1);

  // Custom action order
  add_action('woocommerce_order_action_send_admin_order_details', function ($order) {
    // Send the admin new order email.
    WC()->payment_gateways();
    WC()->shipping();
    WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order );
    // Note the event.
    $order->add_order_note( __( 'Order details manually sent to admin.', 'woocommerce' ), false, true );
    do_action( 'woocommerce_after_resend_order_email', $order, 'new_order' );
    add_filter( 'redirect_post_location', array( 'WC_Meta_Box_Order_Actions', 'set_email_sent_message' ) );
  }, 10, 1);

  // Ajouter cette action dans le code du plugins vanilla pay enfin de mettre à jour la commande
  add_action('itjob_wc_payment_success', 'payment_complete', 100, 1);

  // Cette action est utilisé par le plugins mailChimp
  // Plugin Name: MailChimp User Sync
  // @url https://fr.wordpress.org/plugins/mailchimp-sync/
  add_filter( 'mailchimp_sync_user_data', function( $data, $user ) {
    $role = is_array($user->roles) ? $user->roles[0] : '';
    $data['ROLE'] = $role;
    return $data;
  }, 10, 2 );

});

add_action('wp_loaded', function () {
  // Activer seulement vanillapay pour acheter de credit
  add_filter('woocommerce_available_payment_gateways', function ($allowed_gateways) {
    global $woocommerce;

    $User = wp_get_current_user();
    if ( is_admin() && in_array('administrator', $User->roles) ) return $allowed_gateways;

    $items = $woocommerce->cart->get_cart();
    $vanillapay = isset($allowed_gateways['vanillapay']) ? $allowed_gateways['vanillapay'] : $allowed_gateways['ariarynet'];
    if ( ! isset($vanillapay) ) return $allowed_gateways;
    foreach ($items as $key => $value) {
      $_product = wc_get_product( $value['data']->get_id() );
      $type = $_product->get_meta("__type");
      if ($type === 'credit') {
        $allowed_gateways = [];
        $allowed_gateways[] = &$vanillapay;
      }
    }
    
    return $allowed_gateways;
  }, 10);

  add_action('wp_enqueue_scripts', function () {
    wp_register_style( 'owlCarousel', get_stylesheet_directory_uri() . '/assets/js/libs/owlcarousel/assets/owl.carousel.min.css', '', '2.0.0' );
    wp_register_style( 'owlCarousel-green', get_stylesheet_directory_uri() . '/assets/js/libs/owlcarousel/assets/owl.theme.green.min.css', '', '2.0.0' );
    wp_register_script( 'owlCarousel', get_stylesheet_directory_uri() . '/assets/js/libs/owlcarousel/owl.carousel.min.js', ['jquery'], '2.0.0', true );
  });
});

function payment_complete ($order_id) {

  $order = wc_get_order($order_id);
  if ( ! $order->has_status('completed')):
    $order->update_status('completed');
  endif;

  foreach ($order->get_items() as $item_key => $item ):
    $product = $item->get_product(); // WP_Product
    $type    = $product->get_meta( '__type' );
    if ($type) {
      $__id   = $product->get_meta( '__id' );
      $object_id = intval($__id);
      if (0 === $object_id) return false;
      switch ($type):
        case 'credit':

          $user_id = intval($__id);
          $wallet_id = (int) get_user_meta($user_id, 'wallet', true);
          if ($wallet_id) {
            $Wallet = new \includes\post\Wallet($wallet_id);
            $credit = (int) $item->get_quantity();
            $new_credit = $credit + $Wallet->credit;

            $Wallet->update_wallet($new_credit);
            do_action('payment_complete_credit', $user_id, $credit);
          }

          break;
        case 'offers':
          update_field('itjob_offer_paid', 1, (int)$object_id);
          // Envoyer un mail au administrateur pour informer un paiement
          do_action('update_offer_rateplan', (int)$object_id);
          break;

        case 'formation':
          update_field('paid', 1, $object_id);
          break;
        // Mettre à la une des posts
        case 'featured':
          $post_type = get_post_type( $object_id );
          switch ($post_type):
            case 'formation':
            case 'works':
            case 'annonce':
              update_field('featured', 1, $object_id);
              break;
            case 'offers':
              update_field('itjob_offer_featured', 1, $object_id);
              break;
            case 'candidate':
              update_field('itjob_cv_featured', 1, $object_id);
              break;
          endswitch;
          break;
          
      endswitch;

      // Ajouter une historique de paiement
      $modelPaiement = new \includes\model\paiementModel();
      $args = [
        'data' => [
          'type' => $type,
          'object_id' => $object_id,
          'product_id' => $product->get_id(),
          'order_id' => $order_id
        ]
      ];

      $modelPaiement->add($args);
    }
  endforeach;
}





