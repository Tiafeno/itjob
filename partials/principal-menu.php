<?php
use Underscore\Types\Arrays;
?>
<style type="text/css">
  .list-group.list-group-divider .list-group-item:not(:first-child) {
    border-top-color: #f6fafb1c;
  }
  #sidebar h6 {
    color: #17adc7;
    font-size: 18px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
  }
  #sidebar ul li {
    font-family: 'Montserrat', sans-serif;
  }
</style>
<nav id="sidebar">
  <div id="sidebar-collapse">
    <?php
    if ( has_nav_menu( 'primary' ) ):
      $locations       = get_nav_menu_locations();
      $primary_menu_id = $locations["primary"];
      $menu = wp_get_nav_menu_object( $primary_menu_id );
      $menu_items = wp_get_nav_menu_items( $menu->term_id );

      foreach ($menu_items as $menu_item) {
        if ( ! $menu_item instanceof WP_Post) return;
        if ($menu_item->menu_item_parent == 0) {
          // is parent
          echo sprintf('<h6 class="mt-5 mb-2">%s</h6>', $menu_item->post_title);
          $parent_id = $menu_item->ID;
          $childs = Arrays::filter($menu_items, function ($item) use ($parent_id) {
            return (int)$item->menu_item_parent === (int)$parent_id;
          });
          if ( ! empty($childs)) {
            // has child
            echo '<ul class="list-group list-group-divider">';
            foreach ($childs as $child) {
              ?>
              <li class="list-group-item flexbox">
                <a class="flexbox-b" href="<?= $child->url ?>">
                  <?= ((int)$child->object_id === get_the_ID()) ? '<i class="fa fa-asterisk mr-3 font-18"></i>' : '' ?>
                  <?= $child->title ?>
                </a>
              </li>
              <?php
            }
            echo '</ul>';
          }
        }
      }
    endif;
    ?>

  </div>
</nav>