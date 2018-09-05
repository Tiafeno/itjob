<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 05/09/2018
 * Time: 13:07
 */

/**
 * Cette classe peuvent executer des elements VC ou un shortcode standart
 * Class Widget_Shortcode
 */
class Widget_Shortcode extends WP_Widget {
  public function __construct() {
    parent::__construct( 'widget_shortcode', 'ITJOB > Shortcode',
      [
        'description' => 'Cette classe peuvent executer des elements VC ou un shortcode standart'
      ] );
  }

  // Creating widget front-end
  public function widget( $args, $instance ) {

    echo $args['before_widget'];
    echo do_shortcode( $instance['code'] );
    echo $args['after_widget'];
  }

  // Widget Backend
  public function form( $instance ) {
    $code = isset( $instance['code'] ) ? $instance['code'] : '';
    ?>
    <p>
      <label for="<?= $this->get_field_id( 'code' ); ?>">
        Le shortcode:
      </label>
      <input class="widefat" placeholder="Ajoute ici votre shortcode"
             id="<?= $this->get_field_id( 'code' ); ?>"
             name="<?= $this->get_field_name( 'code' ); ?>"
             value="<?= $code ?>"
      />
    </p>
    <?php
  }

  public function update( $newI, $oldI ) {
    $instance         = array();
    $instance['code'] = ( ! empty( $newI['code'] ) ) ? $newI['code'] : '';

    return $instance;
  }
}