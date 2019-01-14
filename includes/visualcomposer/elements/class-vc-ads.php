<?PHP
namespace includes\vc;

if (!defined('ABSPATH')) {
   exit;
}

if (!class_exists('WPBakeryShortCode')) {
   new \WP_Error('WPBakery', 'WPBakery plugins missing!');
}

use Http;

class vcAds
{
   public function __construct()
   {
      add_action( 'init', [ &$this, 'vc_ads_mapping' ] );
      if ( ! shortcode_exists( 'ads_render_html' ) )
         add_shortcode( 'vc_itjob_ads', [ &$this, 'ads_render_html' ] );
   }

   public function vc_ads_mapping()
   {
         // Stop all if VC is not enabled
      if (!defined('WPB_VC_VERSION')) {
         return;
      }
         // Map the block with vc_map()
      vc_map(
         array(
            'name' => 'ADS',
            'base' => 'vc_itjob_ads',
            'content_element' => true,
            'show_settings_on_create' => true,
            "js_view" => 'VcColumnView',
            'description' => 'Ajouter une séction publicité',
            'category' => 'itJob',
            'params' => array(
               array(
                  'type' => 'dropdown',
                  'class' => 'vc-ij-position',
                  'heading' => '',
                  'param_name' => 'position',
                  'value' => [
                     'Aucune'                                 => null,
                     'Home Top (position-1)'                  => 'position-1',
                     'Home Side Right (position-2)'           => 'position-2',
                     'Archive CV Top (position-3)'            => 'position-3',
                     'Archive CV Side Right (position-4)'     => 'position-4',
                     'Archive Offer Top (position-5)'         => 'position-5',
                     'Archive Offer Side Right (position-6)'  => 'position-6',
                     'Single Offer (position-7)'              => 'position-7',
                     'Single CV (position-8)'                 => 'position-8',
                     'Inscription Particular (position-9)'    => 'position-9',
                     'Inscription Professional (position-10)' => 'position-10',
                     'Search Side Right (position-11)'        => 'position-11', // Need post type attr
                  ],
                  'std' => null,
                  'description' => "Ajouter une position",
                  'admin_label' => true
               ),
               array(
                  'type' => 'dropdown',
                  'class' => 'vc-ij-size',
                  'heading' => '',
                  'param_name' => 'size',
                  'value' => [
                     'Default'    => 'medium',
                     '1120 x 210' => '1120x210',
                     '354 x 330'  => '354x330',
                     '354 x 570'  => '354x570',
                  ],
                  'std' => 'medium',
                  'description' => "Ajouter une resolution pour l'affiche d'invitation",
                  'admin_label' => true
               ),
               array(
                  "type" => "textfield",
                  'class'       => 'vc-ij-attr',
                  'heading'     => 'Attributs',
                  'param_name'  => 'attr',
                  'value'       => '',
                  'description' => "Ajouter des attributs. Exemple: [\"post_type\": \"candidate\"]",
                  'admin_label' => true
               )
            )
         )
      );
   }

   public function ads_render_html($attrs)
   {
      extract(
         shortcode_atts(
            array(
               'position' => null,
               'size' => 'full',
               'attr' => ''
            ),
            $attrs
         ),
         EXTR_OVERWRITE
      );

      $Model = new \includes\model\itModel();
      if (null == $position) return null;

      $Ads = $Model->get_ads_by_position($position);
      if (empty($Ads)) {
         $preview = get_template_directory_uri() . "/img/position/" . $size . '.jpg';
         $sizes = \explode('x', $size);
         $width = $sizes[0];
         $height = $sizes[1];
         $content = '<div class="row">';
         $content .= '<div class="col-12 mb-4">';
         $content .= '<div class="vc_single_image-wrapper text-center">';
         $content .= '<img src="' . $preview . '" class="vc_single_image-img  rounded" alt="Votre publicité ici" width="'.$width.'" height="'.$height.'">';
         $content .= '</div>';
         $content .= '</div>';
         $content .= '</div>';
         return $content;
      } else {
         foreach ($Ads as $ad) {
            $attachment = wp_get_attachment_image_src( $ad->attachment_id, 'full' );
            $code = sprintf('[vc_single_image image="%d" img_link_target="_blank" img_size="%s" alignment="center"]', $attachment[0], $ad->img_size);
            echo do_shortcode( $code );
         }
      }
   }
}

return new vcAds();