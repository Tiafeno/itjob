<?php
class Widget_Header_Search extends WP_Widget {
  public function __construct() {
    parent::__construct( 'widget_header_search', 'ITJOB > Archive Header',
      [ 'description' => 'Affiche la formulaire de recherche sur une image de fond' ] );
  }

  // Creating widget front-end
  public function widget( $args, $instance ) {
    $post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : null;
    $bg_image = isset( $instance['bg_image'] ) ? $instance['bg_image'] : '';
    echo $args['before_widget'];
    echo do_shortcode("[vc_itjob_search type='{$post_type}' bg_image='{$bg_image}']");
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    $post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : null;
    $bg_image = isset( $instance['bg_image'] ) ? $instance['bg_image'] : '';
    ?>
    <p>
      <label for="<?= $this->get_field_id( 'post_type' ); ?>">
        Poste type:
      </label>
      <select class="widefat" placeholder="Ajouter une poste type"
              id="<?= $this->get_field_id( 'post_type' ); ?>"
              name="<?= $this->get_field_name( 'post_type' ); ?>">
        <option value="default" <?= ( $post_type === 'default' ) ? 'selected="selected"' : '' ?> >Default</option>
        <option value="company" <?= ( $post_type === 'company' ) ? 'selected="selected"' : '' ?> >Professionel</option>
        <option value="candidate" <?= ( $post_type === 'candidate' ) ? 'selected="selected"' : '' ?> >Candidates</option>
      </select>
    </p>

    <p>
      <label for="<?= $this->get_field_id( 'bg_image' ); ?>">
        Identification de l'image:
      </label>
      <input class="widefat" placeholder="ID image" id="<?= $this->get_field_id( 'bg_image' ); ?>" type="number"
             name="<?= $this->get_field_name( 'bg_image' ); ?>" value="<?= $bg_image ?>" />
    </p>
    <?php
  }

  public function update( $newI, $oldI ) {
    $instance         = array();
    $instance['post_type'] = ( ! empty( $newI['post_type'] ) ) ? $newI['post_type'] : '';
    $instance['bg_image'] = ( ! empty( $newI['bg_image'] ) ) ? $newI['bg_image'] : '';

    return $instance;
  }

}