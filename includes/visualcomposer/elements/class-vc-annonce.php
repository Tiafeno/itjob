<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 10/02/2019
 * Time: 11:25
 */

namespace includes\vc;

use Http;

class vcAnnonce
{

  public
  function __construct ()
  {
    add_action('init', [$this, 'mapping']);
    if (!shortcode_exists('vc_annonce')) {
      add_shortcode('vc_annonce', [$this, 'form_annonce_render']);
    }

    add_action('wp_ajax_add_annonce', [&$this, 'add_annonce']);
    add_action('wp_ajax_nopriv_add_annonce', [&$this, 'add_annonce']);

    add_filter('manage_annonce_posts_columns', function ($columns) {
      return array_merge($columns,
        array('categorie' => __('Categorie')));
    });

    add_action('manage_annonce_custom_column', function ($column, $post_id) {
      $ctg = wp_get_post_terms($post_id, 'categorie', ["fields" => "name"]);
      if ($column == 'categorie') {
        $name = is_array($ctg) ? $ctg[0] : $ctg;
        echo "<span>{$name}</span>";
      }
    }, 10, 2);
  }

  public
  function mapping ()
  {
    vc_map([
      'name'     => 'Le formulaire annonce',
      'base'     => 'vc_annonce',
      'category' => 'itJob',
      'params'   => [
        array(
          'type'        => 'textfield',
          'holder'      => 'h3',
          'class'       => 'vc-ij-title',
          'heading'     => 'Titre',
          'param_name'  => 'title',
          'value'       => 'Formulaire annonce',
          'description' => "Ajouter un titre",
          'admin_label' => true,
          'weight'      => 0
        ),
      ]
    ]);
  }

  /**
   * function ajax
   * Cette fonction permet d'enregistrer une annonce ou une travail temporaire
   */
  public
  function add_annonce ()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || !wp_doing_ajax() || !is_user_logged_in()) {
      return false;
    }
    // 1: Service ou travail temporaire, 2: Autres annonce
    $service_or_annonce = (int)Http\Request::getValue('annonce', false);
    // Type d'annonce: 1 & 2
    $type = (int)Http\Request::getValue('type', 0);

    $title = Http\Request::getValue('title', ' ');
    $description = Http\Request::getValue('description', null);

    $region = Http\Request::getValue('region', 0);
    $town = Http\Request::getValue('town', 0);
    $address = Http\Request::getValue('address', null);
    $cellphone = Http\Request::getValue('cellphone', null);
    $price = Http\Request::getValue('price', 0);
    $email = Http\Request::getValue('email', null);
    $activity_area = Http\Request::getValue('activity_area', 0);
    $categorie = Http\Request::getValue('categorie', 0);

    // Featured: Gallery, Image à la une

    $User = wp_get_current_user();

    $post_type = $service_or_annonce === 1 ? "work-temporary" : "annonce";
    $result = wp_insert_post( [
      'post_title'   => $title,
      'post_content' => $description,
      'post_status'  => 'pending',
      'post_author'  => $User->ID,
      'post_type'    => $post_type
    ], true );
    if ( is_wp_error( $result ) ) {
      wp_send_json_error($result->get_error_message());
      return false;
    }
    $post_id = (int)$result;
    wp_set_post_terms( $post_id, [ (int) $region ], 'region' );
    wp_set_post_terms( $post_id, [ (int) $town ], 'city' );
    wp_set_post_terms( $post_id, [ (int) $activity_area ], 'branch_activity' );

    if ($service_or_annonce === 2) {
      wp_set_post_terms( $post_id, [ (int) $categorie ], 'categorie' );
      update_field('type', (int) $type, $post_id);
    }

    // Add acf field
    update_field('activated', 0, $post_id);
    update_field('featured', 0, $post_id);

    update_field('email', $email, $post_id);
    update_field('annonce_author', $User->ID, $post_id);
    update_field('address', $address, $post_id);
    update_field('cellphone', $cellphone, $post_id);
    update_field('price', $price, $post_id);

    // Reference
    $slug = $service_or_annonce === 2 ? 'ANN' : 'SRC';
    update_field('reference', $slug . $post_id, $post_id);

    wp_send_json_success("Annonce ajouter avec succès");
  }

  public
  function form_annonce_render ($attrs)
  {
    global $itJob, $theme;
    extract(shortcode_atts(
      array(
        'title' => '',
        'type'  => 1 // Service ou travail temporaire, voir form.html (annonce) at line 18
      ),
      $attrs
    ), EXTR_OVERWRITE);


    wp_enqueue_style('sweetalert');
    wp_enqueue_style('alertify');
    wp_enqueue_script('form-annonce', get_template_directory_uri() . '/assets/js/app/register/form-annonce.js', [
      'tinymce',
      'angular',
      'angular-ui-select2',
      'angular-ui-tinymce',
      'angular-ui-route',
      'angular-sanitize',
      'angular-messages',
      'angular-animate',
      'angular-cookies',
      'ngFileUpload',
      'moment-locales',
      'typeahead',
      'alertify',
      'sweetalert'
    ], $itJob->version, true);

    wp_localize_script('form-annonce', 'itOptions', [
      'version'  => $theme->get('Version'),
      'ajax_url' => admin_url('admin-ajax.php'),
      'helper'   => [
        'redir'    => home_url('/'),
        'partials' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template' => get_template_directory_uri(),
      ]
    ]);

    $content
      = <<<EOF
<div class="ibox candidate-content uk-margin-large-top" ng-app="AnnonceApp">
  <ui-view>
    <div class="mt-4 pt-4 ">
      <h4 class="font-light text-center">Ajouter une annonce</h4>
      <p class="text-center pb-4">Chargement...</p>
    </div>
  </ui-view>
</div>
EOF;

    return $content;

  }


}

return new vcAnnonce();