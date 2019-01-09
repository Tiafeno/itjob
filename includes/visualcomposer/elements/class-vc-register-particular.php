<?php

namespace includes\vc;

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('WPBakeryShortCode')) {
  new \WP_Error('WPBakery', 'WPBakery plugins missing!');
}

use Http;
use includes\object\WP_City;

if (!class_exists('vcRegisterParticular')) :
  class vcRegisterParticular extends \WPBakeryShortCode
{
  public function __construct()
  {
    add_action('init', [&$this, 'register_particular_mapping']);

    add_shortcode('vc_register_particular', [&$this, 'register_render_html']);

      // Crée une utilisateur pour le post candidate
    add_action('acf/update_value/name=itjob_cv_email', [&$this, 'create_particular_user'], 10, 2);

    add_action('wp_ajax_get_city', [&$this, 'get_city']);
    add_action('wp_ajax_nopriv_get_city', [&$this, 'get_city']);

    add_action('wp_ajax_insert_user_particular', [&$this, 'insert_user_particular']);
    add_action('wp_ajax_nopriv_insert_user_particular', [&$this, 'insert_user_particular']);
  }

  public function register_particular_mapping()
  {
      // Stop all if VC is not enabled
    if (!defined('WPB_VC_VERSION')) {
      return;
    }
    
    \vc_map(
      array(
        'name' => 'Particular Form (SingUp)',
        'base' => 'vc_register_particular',
        'description' => 'Formulaire d\'enregistrement d\'un utilisateur particulier',
        'category' => 'itJob',
        'params' => array(
          array(
            'type' => 'textfield',
            'holder' => 'h3',
            'class' => 'vc-ij-title',
            'heading' => 'Titre',
            'param_name' => 'title',
            'value' => '',
            'description' => "Une titre pour le formulaire",
            'admin_label' => true,
            'weight' => 0
          )
        )
      )
    );
  }

  /**
   * Rendre le formulaire d'inscription
   * @param array $attrs
   *
   * @return string - Shortcode template
   */
  public function register_render_html($attrs)
  {
    global $Engine, $itJob;
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

    if (is_user_logged_in()) {
      return '<div class="d-flex align-items-center">' .
        '<div class="uk-margin-large-top uk-margin-auto-left uk-margin-auto-right text-uppercase">Access refuser</div></div>';
    }
    wp_enqueue_style('b-datepicker-3');
    wp_enqueue_style('sweetalert');
    wp_enqueue_script('form-particular', get_template_directory_uri() . '/assets/js/app/register/form-particular.js?ver='.$itJob->version, [
      'angular',
      'angular-ui-route',
      'angular-sanitize',
      'angular-messages',
      'b-datepicker',
      'fr-datepicker',
      'sweetalert'
    ], $itJob->version, true);

    /** @var STRING $redir */
    $redirection = Http\Request::getValue('redir');
    $redirection = $redirection ? $redirection : $redir;

    wp_localize_script('form-particular', 'itOptions', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
      'template_url' => get_template_directory_uri(),
      'version' => $itJob->version,
      'urlHelper' => [
        'singin' => home_url('/connexion/candidate'),
        'redir' => is_null($redirection) ? null : $redirection
      ]
    ]);

    try {
      do_action('get_notice');
      /** @var STRING $title */
      return $Engine->render('@VC/register/particular.html.twig', [
        'title' => $title,
      ]);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      echo $e->getRawMessage();
    }
  }

  public function insert_user_particular()
  {
    /**
     * @func wp_doing_ajax
     * (bool) True if it's a WordPress Ajax request, false otherwise.
     */
    if (!\wp_doing_ajax()) {
      return;
    }

    $userEmail = Http\Request::getValue('email', false);
    if (!$userEmail) return false;
    $userExist = get_user_by('email', $userEmail);
    if ($userExist) {
      wp_send_json(['success' => false, 'msg' => 'L\'adresse e-mail ou l\'utilisateur existe déja']);
    }

    $form = (object)[
      'firstname' => Http\Request::getValue('firstname'),
      'lastname' => Http\Request::getValue('lastname'),
      'birthdayDate' => Http\Request::getValue('birthdayDate'),
      'address' => Http\Request::getValue('address'),
      'region' => Http\Request::getValue('region'), // region ID
      'city' => Http\Request::getValue('country'), // city ID
      'cellphone' => Http\Request::getValue('cellphone'),
      'greeting' => Http\Request::getValue('greeting'),
      'email' => $userEmail
    ];

      // Press CTRL + Q, for documentation
    $result = wp_insert_post([
      'post_content' => '',
      'post_status' => 'pending',
      'post_author' => 1,
      'post_type' => 'candidate'
    ]);
    if (is_wp_error($result)) {
      wp_send_json(['success' => false, 'msg' => $result->get_error_message()]);
    }

    // Récupérer l'incrementation des CV
    $cvIncrement = get_field('cv_increment', 'option');
    $cvIncrement = (int) $cvIncrement;
    
    $post_id = (int)$result;
    $Id = $cvIncrement === 0 ? $post_id : $cvIncrement;
    // Ajouter un titre au CV
    wp_update_post(['ID' => $post_id, 'post_title' => 'CV' . $Id]);

    $cvIncrement += 1;
    update_field('cv_increment', $cvIncrement, 'option');

    // save repeater field
    $value = [];
    $phones = json_decode($form->cellphone);
    foreach ($phones as $row => $phone) {
      $value[] = ['number' => $phone->value];
    }
    update_field('itjob_cv_phone', $value, $post_id);

    $this->update_acf_field($post_id, $form);
    wp_set_post_terms($post_id, [(int)$form->region], 'region');
    wp_set_post_terms($post_id, [(int)$form->city], 'city');

      // featured: Envoie une email de confirmation pour le changement de mot de passe
    $user = get_user_by('email', trim($form->email));

    // Envoyer une email pour une nouvelle utilisateur
    do_action('register_user_particular', $user->ID);

    // Ne pas activer le CV de l'utilisateur
    update_field('activated', 0, $post_id);

    wp_send_json(['success' => true, 'msg' => 'Vous avez réussi votre inscription']);
  }

  /**
   * Mettre à jours les champs ACF
   * @param $post_id
   * @param $form
   */
  private function update_acf_field($post_id, $form)
  {
    foreach (get_object_vars($form) as $key => $value) {
      update_field("itjob_cv_" . $key, $value, $post_id);
    }
  }

  /**
   * Ajouter un utilisateur après l'enregistrement d'un candidat (post) s'il n'existe pas
   * @action acf/save_post
   *
   * @param $post_id
   *
   * @return bool
   */
  public function create_particular_user($value, $post_id)
  {

    $post_type = get_post_type($post_id);
    if ($post_type != 'candidate') {
      return $value;
    }
    $email = &$value;
    $post = get_post($post_id);
      // (WP_User|false) WP_User object on success, false on failure.
    $isUser = get_user_by('email', $email);
    if ($isUser) {
      return $value;
    }

    $userFirstName = get_field('itjob_cv_firstname', $post_id);
    $userLastName = get_field('itjob_cv_lastname', $post_id);
    $args = [
      "user_pass" => substr(str_shuffle($this->chars), 0, 8),
      "user_login" => 'user-' . $post_id,
      "user_email" => $email,
      "display_name" => $post->post_title,
      "first_name" => $userFirstName,
      "last_name" => $userLastName,
      "role" => $post_type
    ];
      // Hook user_register fire ...
    $user_id = wp_insert_user($args);
    if (!is_wp_error($user_id)) {
      $user = new \WP_User($user_id);
      get_password_reset_key($user);

      return $value;
    } else {
      return $value;
    }
  }

  /**
   * Récuperer un objet WP_City
   * @return array
   */
  public function get_city()
  {
    $taxonomy = 'city';
    $allCity = [];
    $postal_code_terms = get_terms($taxonomy, [
      'hide_empty' => false,
      'fields' => 'all',
      'parent' => 0
    ]);
    foreach ($postal_code_terms as $postal_code_term) {
        // @return List of Term IDs
      $children_ids = get_term_children($postal_code_term->term_id, $taxonomy);
      foreach ($children_ids as $children_id) {
        array_push($allCity, WP_City::get_instance($children_id));
      }
    }
    if (wp_doing_ajax()) {
      wp_send_json($allCity);
      exit;
    }

    return $allCity;
  }
}
endif;

return new vcRegisterParticular();
