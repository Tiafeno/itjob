<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 05/09/2018
 * Time: 12:17
 */

class Widget_Publicity extends WP_Widget {
  public function __construct() {
    parent::__construct( 'widget_publicity', 'ITJOB > Publicité',
      [ 'description' => 'Une publicité que vous voulez afficher.' ] );
  }

  // Creating widget front-end
  public function widget( $args, $instance ) {
    $base = isset( $instance['base'] ) ? $instance['base'] : '316x335';
    echo $args['before_widget'];
    echo 'WIDGET ITJOB > PUBLICITY : ' . $base;
    echo $args['after_widget'];
  }

  // Widget Backend
  public function form( $instance ) {
    $base = isset( $instance['base'] ) ? $instance['base'] : '316x335';
    ?>
    <p>
      <label for="<?= $this->get_field_id( 'base' ); ?>">
        Base size:
      </label>
      <select class="widefat" placeholder="Ajoute la taille du publicité"
              id="<?= $this->get_field_id( 'base' ); ?>"
              name="<?= $this->get_field_name( 'base' ); ?>">
        <option value="945x210" <?= ( $base === '945x210' ) ? 'selected="selected"' : '' ?> >945x210</option>
        <option value="316x335" <?= ( $base === '316x335' ) ? 'selected="selected"' : '' ?> >316x335</option>
        <option value="316x600" <?= ( $base === '316x600' ) ? 'selected="selected"' : '' ?> >316x600</option>
      </select>
    </p>
    <?php
  }

  public function update( $newI, $oldI ) {
    $instance         = array();
    $instance['base'] = ( ! empty( $newI['base'] ) ) ? $newI['base'] : '';

    return $instance;
  }
}