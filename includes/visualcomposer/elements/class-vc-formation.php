<?PHP
namespace includes\vc;

if (!defined('ABSPATH')) {
   exit;
}

if (!class_exists('WPBakeryShortCode')) {
   new \WP_Error('WPBakery', 'WPBakery plugins missing!');
}

use Http;

class vcFormation
{
   public function __construct()
   {
      add_action( 'init', [ &$this, 'vc_formation_mapping' ] );
      add_action( 'wp_ajax_new_formation', [ &$this, 'new_formation' ] );
      add_action( 'wp_ajax_nopriv_new_formation', [ &$this, 'new_formation' ] );
      if ( ! shortcode_exists( 'formation_render_html' ) )
         add_shortcode( 'vc_itjob_formation', [ &$this, 'formation_render_html' ] );
   }

   public function vc_formation_mapping()
   {
         // Stop all if VC is not enabled
      if (!defined('WPB_VC_VERSION')) {
         return;
      }
         // Map the block with vc_map()
      vc_map(
         array(
            'name' => "Ajouter une formation (Form)",
            'base' => 'vc_itjob_formation',
            'content_element' => true,
            'show_settings_on_create' => true,
            "js_view" => 'VcColumnView',
            'description' => "Une formulaire d'ajout de formation",
            'category' => 'itJob',
            'params' => array(
                array(
                    "type" => "textfield",
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

   public function formation_render_html($attrs)
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
      // Votre code ici ...

      wp_enqueue_style( 'b-datepicker-3' );
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_script( 'form-formation', get_template_directory_uri() . '/assets/js/app/register/form-formation.js', [
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
      ], $itJob->version, true );

      wp_localize_script( 'form-formation', 'itOptions', [
        'version'      => $theme->get('Version'),
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'helper'    => [
            'partials' => get_template_directory_uri() . '/assets/js/app/register/partials',
            'template' => get_template_directory_uri(),
            'redir'    => get_post_type_archive_link( 'candidate' )
        ]
      ] );

      $content = <<<EOF
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

   // ajax function
   public function new_formation() {
      if ( ! wp_doing_ajax() || ! is_user_logged_in() ) {
         return;
      }
      $user_id = get_current_user_id();
      $form = (object)[
         'title' => wp_strip_all_tags(\Http\Request::getValue('title')),
         'region' => (int)\Http\Request::getValue('region'),
         'address' => \Http\Request::getValue('address'),
         'duration' => \Http\Request::getValue('duration'),
         'date_limit' => \Http\Request::getValue('date_limit'),
         'activity_area' => (int)\Http\Request::getValue('activity_area'),
         'description'    => \Http\Request::getValue('description'),
         'establish_name'   => \Http\Request::getValue('establish_name'),
      ];
      $wp_error = true;

      $thing = wp_insert_post( [
         'post_type'   => 'formation',
         'post_author' => $user_id,
         'post_status' => 'pending',
         'post_title'  =>  $form->title,
         'post_content' => $form->description,
         'post_excerpt' => $form->description
      ], $wp_error );

      if (!is_wp_error( $thing )) {
         $post_id = &$thing;
         update_field('establish_name', $form->establish_name, $post_id);
         update_field('date_limit', $form->date_limit, $post_id);
         update_field('duration', $form->duration, $post_id);
         update_field('address', $form->address, $post_id);

         // Ajouter les taxonomy
         wp_set_post_terms( $post_id, [ $form->activity_area ], 'branch_activity' );
         wp_set_post_terms( $post_id, [ $form->region ], 'region' );

         // Ajouter son adresse email
         $current_user = wp_get_current_user();
         update_field('email', $current_user->user_email, $post_id);

         $term = get_term($form->activity_area, 'branch_activity');
         if ($term && $term instanceof WP_Term) {
            update_field('reference', "{$term->slug}{$post_id}", $post_id);
         }

         // ********************* Notification ***********************
         do_action('notice-new-formation', $post_id);
         // *********************************************************

         wp_send_json_success( "Formation ajouter avec succès" );
      }

      wp_send_json_error( $thing->get_message() );
   }
}

return new vcFormation();