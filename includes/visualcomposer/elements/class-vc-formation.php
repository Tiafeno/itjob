<?PHP

namespace includes\vc;

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('WPBakeryShortCode')) {
  new \WP_Error('WPBakery', 'WPBakery plugins missing!');
}

use Http;
use includes\model\Model_Request_Formation;
use includes\post\Candidate;

class vcFormation
{
  public
  function __construct ()
  {
    add_action('init', [&$this, 'vc_formation_mapping']);
    add_action('wp_ajax_new_formation', [&$this, 'new_formation']);
    add_action('wp_ajax_new_request_formation', [&$this, 'new_request_formation']);
    add_action('wp_ajax_nopriv_new_request_formation', [&$this, 'new_request_formation']);
    if (!shortcode_exists('vc_itjob_formation'))
      add_shortcode('vc_itjob_formation', [&$this, 'form_formation_render_html']);
    if (!shortcode_exists('vc_featured_formation'))
      add_shortcode('vc_featured_formation', [&$this, 'featured_formation_render_html']);
    if (!shortcode_exists('vc_formation'))
      add_shortcode('vc_formation', [&$this, 'formation_render_html']);

    add_action('acf/save_post', function ($post_id) {
      // Mettre à jour automatiquement la référence
      update_field('reference', strtoupper("FOM{$post_id}"), $post_id);
    }, 10, 1);
  }

  public
  function vc_formation_mapping ()
  {
    // Stop all if VC is not enabled
    if (!defined('WPB_VC_VERSION')) {
      return;
    }

    // Les offres à la une
    vc_map(
      array(
        'name'        => 'Formation à la une',
        'base'        => 'vc_featured_formation',
        'description' => 'Afficher les offres à la une.',
        'category'    => 'itJob',
        'params'      => array(
          array(
            'type'        => 'textfield',
            'holder'      => 'h3',
            'class'       => 'vc-ij-title',
            'heading'     => 'Titre',
            'param_name'  => 'title',
            'value'       => '',
            'description' => "Une titre pour les formations à la une",
            'admin_label' => false,
            'weight'      => 0
          ),
          array(
            'type'        => 'dropdown',
            'class'       => 'vc-ij-position',
            'heading'     => 'Position',
            'param_name'  => 'position',
            'value'       => array(
              'Sur le côté'  => 'sidebar',
              'Sur le large' => 'content'
            ),
            'std'         => 'content',
            'description' => "Modifier le mode d'affichage",
            'admin_label' => false,
            'weight'      => 0
          ),
        )
      )
    );

    vc_map(
      [
        'name'        => 'Les Formations',
        'base'        => 'vc_formation',
        'description' => 'Afficher les 4 premiers formations',
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
            'class'       => 'vc-ij-orderby',
            'heading'     => 'Désigne l\'ascendant ou descendant',
            'param_name'  => 'orderby',
            'value'       => [
              'Date'  => 'date',
              'Titre' => 'title'
            ],
            'admin_label' => false,
            'weight'      => 0
          ],
          [
            'type'        => 'dropdown',
            'class'       => 'vc-ij-order',
            'heading'     => 'Trier',
            'param_name'  => 'order',
            'value'       => [
              'Ascendant'  => 'ASC',
              'Descendant' => 'DESC'
            ],
            'admin_label' => false,
            'weight'      => 0
          ]
        ]
      ]
    );

    // Map the block with vc_map()
    vc_map(
      array(
        'name'                    => "Ajouter une formation (Form)",
        'base'                    => 'vc_itjob_formation',
        'content_element'         => true,
        'show_settings_on_create' => true,
        "js_view"                 => 'VcColumnView',
        'description'             => "Une formulaire d'ajout de formation",
        'category'                => 'itJob',
        'params'                  => array(
          array(
            "type"        => "textfield",
            'class'       => 'vc-ij-title',
            'heading'     => 'Titre du formulaire',
            'param_name'  => 'title',
            'value'       => 'Formulaire formation',
            'description' => "Ajouter un titre au formulaire",
            'admin_label' => true
          )
        )
      )
    );
  }

  public
  function form_formation_render_html ($attrs)
  {
    global $itJob, $theme;
    extract(
      shortcode_atts(
        array(
          'title' => ''
        ),
        $attrs
      ),
      EXTR_OVERWRITE
    );
    $refused_access_msg = '<div class="text-left mt-5">';
    $refused_access_msg .= '<div class="font-bold text-left font-14 badge badge-pink" style="white-space: pre-wrap;">Seule un compte professionnel a le pouvoir d\'ajouté une formation. <br>';
    $refused_access_msg .= 'Votre compte ne vous permet pas de publier une formation. Vous devez se connecter avec votre compte professionnel.';
    $refused_access_msg .= '</div></div>';
    $redirection = Http\Request::getValue('redir');
    $redirection = $redirection ? $redirection : get_post_type_archive_link('candidate');
    if (!is_user_logged_in()) {
      $redirection = get_the_permalink();
      return do_shortcode('[itjob_login role="company" redir="' . $redirection . '"]', true);
    }

    $User = wp_get_current_user();
    if (in_array('company', $User->roles)) {
      // Autoriser à ajouter une formation
    } else {
      return $refused_access_msg;
    }

    wp_enqueue_style('b-datepicker-3');
    wp_enqueue_style('sweetalert');
    wp_enqueue_script('form-formation', get_template_directory_uri() . '/assets/js/app/register/form-formation.js', [
      'angular',
      'angular-ui-select2',
      'angular-ui-route',
      'angular-sanitize',
      'angular-messages',
      'angular-animate',
      'b-datepicker',
      'moment-locales',
      'daterangepicker',
      'typeahead',
      'alertify',
      'sweetalert'
    ], $itJob->version, true);

    wp_localize_script('form-formation', 'itOptions', [
      'version'  => $theme->get('Version'),
      'ajax_url' => admin_url('admin-ajax.php'),
      'helper'   => [
        'partials' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template' => get_template_directory_uri(),
        'redir'    => $redirection
      ]
    ]);

    $content
      = <<<EOF
      <div class="ibox candidate-content uk-margin-large-top" ng-app="FormationApp">
         <ui-view>
            <div class="pt-4 pb-4">
               <h4 class="font-light text-center">Formulaire formation</h4>
               <p class="text-center mb-5">Chargement du formulaire...</p>
            </div>
         </ui-view>
      </div>
EOF;
    return $content;
  }

  /**
   * ajax function
   * Cette fonction permet d'ajouter une formation
   */
  public
  function new_formation ()
  {
    if (!wp_doing_ajax() || !is_user_logged_in()) {
      return;
    }
    $user_id = get_current_user_id();
    $form = (object)[
      'title'          => wp_strip_all_tags(Http\Request::getValue('title')),
      'region'         => (int)Http\Request::getValue('region'),
      'address'        => Http\Request::getValue('address'),
      'diploma'        => Http\Request::getValue('diploma'),
      'duration'       => Http\Request::getValue('duration'),
      'date_limit'     => Http\Request::getValue('date_limit'),
      'activity_area'  => (int)Http\Request::getValue('activity_area'),
      'description'    => Http\Request::getValue('description'),
      'establish_name' => Http\Request::getValue('establish_name'),
    ];
    $wp_error = true;
    $thing = wp_insert_post([
      'post_type'    => 'formation',
      'post_author'  => $user_id,
      'post_status'  => 'pending',
      'post_title'   => $form->title,
      'post_content' => $form->description,
      'post_excerpt' => $form->description
    ], $wp_error);

    if (!is_wp_error($thing)) {
      $post_id = &$thing;
      update_field('establish_name', $form->establish_name, $post_id);
      $date_limit = date('YYYY-MM-DD', strtotime($form->date_limit));
      update_field('date_limit', $date_limit, $post_id);
      update_field('duration', $form->duration, $post_id);
      update_field('address', $form->address, $post_id);
      update_field('diploma', $form->diploma, $post_id);
      // Ajouter une valeur par default
      update_field('featured', 0, $post_id);
      update_field('activated', 0, $post_id);
      // Ajouter les taxonomy
      wp_set_post_terms($post_id, [$form->activity_area], 'branch_activity');
      wp_set_post_terms($post_id, [$form->region], 'region');
      $current_user = wp_get_current_user();
      update_field('email', $current_user->user_email, $post_id);
      update_field('reference', strtoupper("FOM{$post_id}"), $post_id);

      add_post_meta($post_id, 'date_create', date_i18n('Y-m-d H:i:s'));
      // ********************* Notification ***********************
      do_action('notice-admin-new-formation', $post_id);
      do_action('email_new_formation', $post_id);
      // *********************************************************
      wp_send_json_success("Formation ajouter avec succès");
    }

    wp_send_json_error($thing->get_message());
  }

  /**
   * ajax function
   * Cette fonction permet d'ajouter une demande de formation
   */
  public
  function new_request_formation ()
  {
    if (!wp_doing_ajax() || !is_user_logged_in()) {
      wp_send_json_error(["msg" => "Veuillez vous connecter pour continuer", "code" => "account"]);
    }
    $subject = Http\Request::getValue('subject', false);
    $topic = Http\Request::getValue('topic', false);
    $description = Http\Request::getValue('description');
    if (!$subject || !$topic || !$description) wp_send_json_error(["msg" => "Formulaire non valide", "code" => "broken"]);
    $User = wp_get_current_user();
    if (in_array('candidate', $User->roles)) {
      $args = [
        'user_id'     => $User->ID,
        'subject'     => $subject,
        'topic'       => $topic,
        'description' => $description,
        'date_create' => date_i18n( 'Y-m-d H:i:s' )
      ];
      $result = Model_Request_Formation::add_resources($args);
      if ($result) {
        $Candidate = Candidate::get_candidate_by($User->ID);
        do_action('notice-admin-new-request-formation', $subject, $Candidate); // Notification
        do_action('new_request_formation', $subject, $Candidate); // Email

        wp_send_json_success("Votre demande a bien été soumise");
      } else {
        wp_send_json_error(["msg" => "Une erreur s'est produite. Veuillez réessayer ultérieurement", "code" => "broken"]);
      }
    } else wp_send_json_error(["msg" => "Votre compte ne vous permet pas d'envoyer une demande de formation", "code" => "broken"]);
  }

  public
  function formation_render_html ($attrs)
  {
    global $itJob, $Engine;
    // Params extraction
    extract(
      shortcode_atts(
        array(
          'title'   => 'Nos formation à la une',
          'orderby' => 'ID',
          'order'   => 'DESC'
        ),
        $attrs
      ), EXTR_OVERWRITE);

    $formations = $itJob->services->getRecentlyPost('formation', 4, [
      // Afficher seulement les offres activé
      [
        'key'     => 'activated',
        'compare' => '=',
        'value'   => 1,
        'type'    => 'NUMERIC'
      ]
    ]);

    /** @var string $title */
    return $Engine->render('@VC/formation/formation-lists.html.twig', [
      'title'                 => $title,
      'formations'            => $formations,
      'archive_formation_url' => get_post_type_archive_link('formation')
    ]);
  }

  public
  function featured_formation_render_html ($attrs)
  {
    global $itJob;
    // Params extraction
    extract(
      shortcode_atts(
        array(
          'title'    => 'Nos formation à la une',
          'position' => ''
        ),
        $attrs
      ), EXTR_OVERWRITE);

    wp_enqueue_style('formation', get_template_directory_uri() . '/assets/css/formation.css', null, $itJob->version);

    /** @var string $position */
    /** @var string $title */
    // Recuperer dans le service les offres publier et à la une
    $formations = $itJob->services->getFeaturedPost('formation', [
      [
        'key'     => 'featured',
        'value'   => 1,
        'compare' => '='
      ],
      [
        'key'     => 'activated',
        'value'   => 1,
        'compare' => '='
      ]
    ]);
    $args = [
      'title'      => $title,
      'formations' => $formations,
    ];
    return (trim($position) === 'sidebar') ? $this->get_position_sidebar($args) : $this->get_position_wide($args);
  }

  private
  function get_position_sidebar ($args)
  {
    global $Engine;
    try {
      return $Engine->render('@VC/formation/sidebar.html.twig', $args);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }
  }

  private
  function get_position_wide ($args)
  {
    global $Engine;
    try {
      return $Engine->render('@VC/formation/wide.html.twig', $args);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }
  }
}

return new vcFormation();