<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 10/02/2019
 * Time: 11:25
 */

namespace includes\vc;

use Http;
use includes\post\Annonce;

class vcAnnonce
{

  public function __construct ()
  {
    add_action('init', [$this, 'mapping']);
    if (!shortcode_exists('vc_annonce')) {
      add_shortcode('vc_annonce', [$this, 'form_annonce_render']);
    }
    if (!shortcode_exists('vc_featured_annonce')) {
      add_shortcode('vc_featured_annonce', [$this, 'featured_annonce_render']);
    }

    if (!shortcode_exists('vc_annonce_list')) {
      // Affiche la liste des travail temporaire
      add_shortcode('vc_annonce_list', [$this, 'annonce_list_render']);
    }

    add_action('wp_ajax_add_annonce', [&$this, 'add_annonce']);
    add_action('wp_ajax_nopriv_add_annonce', [&$this, 'add_annonce']);


    add_action('wp_ajax_upload_annonce_img', [&$this, 'upload_annonce_img']);
    add_action('wp_ajax_nopriv_upload_annonce_img', [&$this, 'upload_annonce_img']);

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

    add_action('rest_api_init', function () {
      $post_type = ['annonce', 'works'];
      $formation_meta = ["gallery"];
      foreach ($post_type as $type):
        foreach ($formation_meta as $meta):
          register_rest_field($type, $meta, array(
            'update_callback' => function ($value, $object, $field_name) {
              return update_post_meta((int)$object->ID, $field_name, $value);
            },
            'get_callback'    => function ($object, $field_name) {
              $post_id = $object['id'];
              return get_post_meta($post_id, $field_name, true);
            },
          ));
        endforeach;
      endforeach;
    });
  }

  public function mapping ()
  {
    // Formulaire d'ajout
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

    vc_map(
      [
        'name'        => 'Les annonces',
        'base'        => 'vc_annonce_list',
        'description' => 'Afficher les 4 premiers annonce',
        'category'    => 'itJob',
        'params'      => [
          [
            'type'        => 'textfield',
            'holder'      => 'h3',
            'class'       => 'vc-ij-title',
            'heading'     => 'Ajouter un titre',
            'param_name'  => 'title',
            'value'       => '',
            'admin_label' => false,
            'weight'      => 0
          ],
          [
            'type'        => 'dropdown',
            'class'       => 'vc-ij-type',
            'heading'     => 'Afficher',
            'param_name'  => 'post_type',
            'value'       => [
              '--Selectionnez--' => '',
              'Travail Temporaire'  => 'works',
              'Annonce' => 'annonce'
            ],
            'std'         => '',
            'admin_label' => true,
            'weight'      => 0
          ]
        ]
      ]
    );

    // Les annonces à la une
    vc_map(
      array(
        'name'        => 'Annonce à la une',
        'base'        => 'vc_featured_annonce',
        'description' => 'Afficher les travail temporaire ou les petites annonces à la une.',
        'category'    => 'itJob',
        'params'      => array(
          array(
            'type'        => 'textfield',
            'holder'      => 'h3',
            'class'       => 'vc-ij-title',
            'heading'     => 'Titre',
            'param_name'  => 'title',
            'value'       => 'Nos annonces à la une',
            'description' => "Une titre de mise en avant",
            'admin_label' => false,
            'weight'      => 0
          ),
          array(
            'type'        => 'dropdown',
            'class'       => 'vc-ij-position',
            'heading'     => 'Position',
            'param_name'  => 'position',
            'value'       => array(
              'Dans la liste'  => 2,
              'A la une' => 1
            ),
            'std'         => 'content',
            'description' => "Modifier le mode d'affichage",
            'admin_label' => false,
            'weight'      => 0
          ),
          array(
            'type'        => 'dropdown',
            'class'       => 'vc-ij-type',
            'heading'     => 'Post type',
            'param_name'  => 'type',
            'value'       => array(
              'Petit annonce'  => 'annonce',
              'Travail temporaire' => 'works'
            ),
            'std'         => 'annonce',
            'description' => "",
            'admin_label' => false,
            'weight'      => 0
          ),
        )
      )
    );
  }

  /**
   * function ajax
   * Cette fonction permet d'enregistrer une annonce ou une travail temporaire
   */
  public function add_annonce ()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || !wp_doing_ajax() || !is_user_logged_in()) {
      wp_send_json_error("Votre session a expirer");
    }
    // 1: Service ou travail temporaire, 2: Autres annonce
    $service_or_annonce = (int)Http\Request::getValue('annonce', false);
    // Type d'annonce: 1 & 2
    $type  = (int)Http\Request::getValue('type', 0);
    $title = Http\Request::getValue('title', ' ');
    $description = Http\Request::getValue('description', null);
    $region = Http\Request::getValue('region', 0);
    $town   = Http\Request::getValue('town', 0);
    $address   = Http\Request::getValue('address', null);
    $cellphone = Http\Request::getValue('cellphone', null);
    $price = (int)Http\Request::getValue('price', 0);
    $email = Http\Request::getValue('email', null);
    $activity_area = Http\Request::getValue('activity_area', 0);
    $categorie = Http\Request::getValue('categorie', 0);

    // Featured: Gallery, Image à la une

    $User = wp_get_current_user();

    $post_type = $service_or_annonce === 1 ? "works" : "annonce";
    $result = wp_insert_post([
      'post_title'   => $title,
      'post_content' => $description,
      'post_excerpt' => $description,
      'post_status'  => 'pending',
      'post_author'  => $User->ID,
      'post_type'    => $post_type
    ], true);
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
      return false;
    }
    $post_id = (int)$result;
    wp_set_post_terms($post_id, [(int)$region], 'region');
    wp_set_post_terms($post_id, [(int)$town], 'city');
    update_field('type', (int)$type, $post_id);
    if ($service_or_annonce === 2) {
      wp_set_post_terms($post_id, [(int)$categorie], 'categorie');
    }

    if ($service_or_annonce === 1) {
      wp_set_post_terms($post_id, [(int)$activity_area], 'branch_activity');
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
    update_post_meta($post_id, 'date_create', date_i18n('Y-m-d H:i:s'));

    // Envoyer un mail à l'administrateur
    switch ($service_or_annonce) {
      case 2: // Annonce
        do_action('new_pending_annonce', $post_id);
        break;

      case 1: // Travails temporaire
        do_action('new_pending_works', $post_id);
        break;
    }

    $annonce = new Annonce($post_id);
    wp_send_json_success($annonce);
  }

  /**
   * Afficher les 4 premier annonces
   * @param $attrs Array
   * @return string
   */
  public function annonce_list_render ($attrs) {
    global $Engine, $itJob;
    extract(shortcode_atts(
      array(
        'title' => '',
        'post_type' => ''
      ),
      $attrs
    ), EXTR_OVERWRITE);

    /** @var string $post_type */
    /** @var string $title - Shortcode variable attribute */
    $posts = $itJob->services->getRecentlyPost($post_type, 4);
    $args = [
      'title' => $title,
      'archive_work_url' => get_post_type_archive_link('works'),
      'archive_annonce_url' => get_post_type_archive_link('annonce')
    ];
    if ($post_type === "works") {
      $title = $title ? $title : "Les travails temporaire ajouter récements";
      $template = "work-list.html";
      $args = array_merge($args, [
        'title' => $title,
        'works' => $posts
      ]);
    } else {
      $title = $title ? $title : "Les annonces ajouter récements";
      $template = "annonce-list.html";
      $args = array_merge($args, [
        'title' => $title,
        'annonces' => $posts
      ]);
    }

    try {
      return $Engine->render("@VC/annonce/{$template}", $args);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }

  }

  public function form_annonce_render ($attrs)
  {
    global $itJob, $wp_version;
    extract(shortcode_atts(
      array(
        'title' => '',
        'type'  => null // Service ou travail temporaire, voir form.html (annonce) at line 18
      ),
      $attrs
    ), EXTR_OVERWRITE);

    if (!is_user_logged_in()) {
      $redirection = get_the_permalink();
      return do_shortcode('[itjob_login role="candidate" redir="' . $redirection . '"]', true);
    }

    wp_enqueue_style('sweetalert');
    wp_enqueue_style('alertify');
    wp_enqueue_script('wp-api');
    wp_enqueue_script('form-annonce', get_template_directory_uri() . '/assets/js/app/register/form-annonce.js', [
      'tinymce',
      'angular',
      'angular-ui-select2',
      'angular-ui-tinymce',
      'angular-ui-route',
      'angular-messages',
      'angular-cookies',
      'moment-locales',
      'typeahead',
      'alertify',
      'ngFileUpload',
      'sweetalert'
    ], $itJob->version, true);

    $httpType = Http\Request::getValue('type', false);
    $httpType = $httpType ? (intval($httpType) === 0 ? '' : intval($httpType)): '';
    /** @var integer $type */
    $type = empty($httpType) ? $type : $httpType;
    $theme = wp_get_theme();
    wp_localize_script('form-annonce', 'itOptions', [
      'version'  => $theme->get('Version'),
      'type' => $type,
      'ajax_url' => admin_url('admin-ajax.php'),
      'helper'   => [
        'redir'    => home_url('/'),
        'partials' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template' => get_template_directory_uri(),
        'home_url' => home_url('/'),
        'client_area_url' => get_the_permalink(ESPACE_CLIENT_PAGE),
        'archive_annonce_url' => get_post_type_archive_link('annonce')
      ]
    ]);

    $content = <<<EOF
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

  public function featured_annonce_render($attrs) {
    global $itJob, $Engine;
    extract(
      shortcode_atts(
        array(
          'title'   => 'Nos annonces à la une',
          'position' => 'front',
          'type' => 'annonce',
          'orderby' => 'ID',
          'order'   => 'DESC'
        ),
        $attrs
      ), EXTR_OVERWRITE);
    /** @var string $type */
    /** @var string $position */
    /** @var string $title */
    $type = !$type ? 'annonce' : $type;
    $annonces = $itJob->services->getRecentlyPost($type, 4, [
      [
        'key'     => 'activated',
        'compare' => '=',
        'value'   => 1,
        'type'    => 'NUMERIC'
      ],
      [
        'key'     => 'featured',
        'compare' => '=',
        'value'   => 1,
        'type'    => 'NUMERIC'
      ],
      [
        'key'     => 'featured_position',
        'compare' => '=',
        'value'   => intval($position),
        'type'    => 'NUMERIC'
      ]
    ]);
    $args = [
      'title'      => $title,
      'annonces' => $annonces,
      'profil'   => $type === 'annonce' ? "/manager/profil/annonces" : "/manager/profil/works",
      'client_area_url' => get_permalink((int) ESPACE_CLIENT_PAGE)
    ];
    return intval($position) === 2 ? $this->get_position_two($args) : $this->get_position_one($args);
  }

  private function get_position_two($args) {
    global $Engine;
    try {
      return $Engine->render('@VC/annonce/featured-two.html', $args);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }
  }

  private function get_position_one($args) {
    global $Engine;
    try {
      return $Engine->render('@VC/annonce/featured-one.html', $args);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }
  }

  public function upload_annonce_img ()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || !wp_doing_ajax()) {
      return false;
    }

    $post_id = (int)Http\Request::getValue('post_id');
    if ($post_id === 0) wp_send_json_error( "Le post n'est pas definie dans la requete (post_id)", Requests_Exception_HTTP_405 );

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    if (empty($_FILES)) {
      return false;
    }

    $featured = $_FILES["featured"];
    $gallery = $_FILES["gallery"];

    if (!empty($featured)):
      $attachment_id = media_handle_upload('featured', $post_id);
      if (is_wp_error($attachment_id)) {
        wp_send_json_error($attachment_id->get_error_message());
      } else {
        update_post_meta($post_id, '_thumbnail_id', $attachment_id);
      }
    endif;

    if (!empty($gallery)):
      $gls = []; // Cette variable permet de stocker les ids de la gallerie
      foreach ($gallery['name'] as $key => $value) {
        if ($gallery['name'][$key]) {
          $file = array(
            'name'     => $gallery['name'][$key],
            'type'     => $gallery['type'][$key],
            'tmp_name' => $gallery['tmp_name'][$key],
            'error'    => $gallery['error'][$key],
            'size'     => $gallery['size'][$key]
          );
          $_FILES = array("upload_file" => $file);
          $attach_id = media_handle_upload('upload_file', $post_id);
          if (is_wp_error($attach_id)) {
            wp_send_json_error($attach_id->get_error_message());
          } else {
            $gls[] = $attach_id;
          }
        }
      }
      update_field('gallery', $gls, $post_id);
    endif;

    wp_send_json_success("Media uploader avec succès");

  }


}

return new vcAnnonce();