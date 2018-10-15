<?php
namespace includes\widgets;

use Underscore\Types\Arrays;
class Widget_Accordion extends \WP_Widget {
  public function __construct() {
    parent::__construct( 'widget_accordion', 'ITJOB > Accordéon',
      [
        'description' => 'Un accordéon'
      ] );
  }

  public function widget( $args, $instance ) {
    global $Engine;
    echo $args['before_widget'];
    $ids = $instance['page_ids'];
    $contents = Arrays::each($ids, function ($id) {
      $post = get_post((int)$id);
      return $post;
    });
    try {
      echo $Engine->render( '@WG/accordion.html.twig', [
        'id' => $this->get_field_id('page_ids'),
        'contents' => $contents
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      echo $e->getRawMessage();
    }
    echo $args['after_widget'];
  }

  public function update( $newI, $oldI ) {
    $instance = array();
    $instance['page_ids'] = ! empty( $newI['page_ids'] ) ? $newI['page_ids'] : [];
    return $instance;
  }


  public function form( $instance ) {
    wp_enqueue_style( 'admin-adminca' );
    wp_enqueue_script( 'adminca' );

    $page_ids = isset( $instance['page_ids'] ) ? $instance['page_ids'] : '';
    $page_ids = empty($page_ids) ? [] : $page_ids;
    $page_ids = array_values($page_ids);
    ?>
    <script type="text/javascript">
      (function($) {
        $(document).ready(function () {
          jQuery("#<?= $this->get_field_id('page_ids') ?>").selectpicker();
        });
      })(jQuery)
    </script>
    <div class="form-group mt-4">
      <label class="form-control-label">Les posts</label>
      <select id="<?= $this->get_field_id('page_ids') ?>" class="form-control selectpicker" name="<?= $this->get_field_name('page_ids') ?>[]"
              data-selected-text-format="count" multiple="">
        <?php
        foreach ((array)$this->getArticles() as $article):
          ?>
          <option data-subtext="<?= $article->post_title ?>" <?= in_array($article->ID, $page_ids) ? 'selected' : '' ?>>
            <?= $article->ID ?>
          </option>
          <?php
        endforeach;
        ?>
      </select>
    </div>
    <?php
  }

  private function getArticles() {
    $args = [
      'post_type' => 'post',
      'post_status' => 'publish',
      'posts_per_page' => -1
    ];
    $articles = get_posts($args);
    return $articles;
  }

  private function get_accordion_content($ids) {

  }
}