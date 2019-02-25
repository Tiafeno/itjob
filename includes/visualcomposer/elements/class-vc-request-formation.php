<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 08/02/2019
 * Time: 15:58
 */

namespace includes\vc;


use includes\model\Model_Request_Formation;

class vcRequestFormation
{
  public function __construct ()
  {
    add_action( 'init', [ $this, 'mapping' ] );
    if ( ! shortcode_exists( 'cv_request_formation_tag' ) ) {
      add_shortcode( 'cv_request_formation_tag', [ $this, 'cv_request_formation_tag_render' ] );
    }

    add_action( 'wp_ajax_request_formation_concerned', [ &$this, 'request_formation_concerned' ] );
  }

  public function mapping () {
    vc_map( [
      'name'     => 'Les demandes formations (Tags)',
      'base'     => 'cv_request_formation_tag',
      'category' => 'itJob',
      'params'   => [
        array(
          'type'        => 'textfield',
          'holder'      => 'h3',
          'class'       => 'vc-ij-title',
          'heading'     => 'Titre',
          'param_name'  => 'title',
          'value'       => '',
          'description' => "Ajouter un titre",
          'admin_label' => false,
          'weight'      => 0
        ),
      ]
    ] );
  }

  /**
   * @param $attrs
   * @return string
   */
  public function cv_request_formation_tag_render ($attrs) {
    extract(
      shortcode_atts(
        array(
          'title'    => '',
        ),
        $attrs
      ), EXTR_OVERWRITE );

    wp_localize_script('itjob', 'itOptions', [
      'ajax' => admin_url("admin-ajax.php")
    ]);
    $request_formations = Model_Request_Formation::collect_validate_resources();
    if (empty($request_formations->results)) return null;

    $title = empty($title) ? "Formation les plus demanders" : $title;
    $content = "<div class='mt-4 '>";
    $content .= "<h5 class='vc-element-title request-formation-title'>
                  {$title}
                  <div class='text-muted'>Cliquer sur une formation pour s'inscrire</div>
                </h5>";
    $content .= "<div class='row'> <div class='col-sm-12'> ";

    foreach ($request_formations->results as $formation) {
      $content .= "<span data-id='{$formation->ID}' data-subject='{$formation->subject}' class='request-formation ml-2 badge badge-primary'>{$formation->subject}</span>";
    }
    $content .= "</div></div>";
    $content .= "</div>";

    return $content;
  }

  /**
   * function ajax
   * Cette fonction permet de s'inscrire sur une demande de formation
   */
  public function request_formation_concerned() {
    if (!is_user_logged_in()) wp_send_json_error("Veuillez vous connecter pour continuer");
    $User = wp_get_current_user();
    $request_training_id = (int)\Http\Request::getValue('request_formation_id', 0);
    if (is_numeric($request_training_id) && 0 !== $request_training_id) {
      if (Model_Request_Formation::isConcerned($request_training_id, $User)) wp_send_json_success("Vous etes déja inscrit");

      $result = Model_Request_Formation::set_concerned($request_training_id, $User);
      if (!$result) wp_send_json_error("Une erreur s'est produite pendant l'inscription. Réessayer plus tard");
      wp_send_json_success("Inscription reussie");
    } else {
      wp_send_json_error("Erreur de parametre (request_formation_id)");
    }
  }
}

return new vcRequestFormation();