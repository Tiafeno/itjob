<footer class="uk-section uk-section-secondary">
  <div class="uk-container uk-container-medium">
    <div class="row mt-4 mb-5">
      <div class="col-md-4 col-sm-6 mt-4">
        <div class="footer-body">
          <?php
          $locations        = get_nav_menu_locations();
          if ( has_nav_menu( "menu-footer-left" ) ) :
            $menu_left_id = $locations["menu-footer-left"];
            $menuLeftObject = wp_get_nav_menu_object( $menu_left_id );
            ?>
            <h4 class="footer-title d-inline-block text-uppercase"><?= $menuLeftObject->name ?></h4>
            <?php
            wp_nav_menu( [
              'menu_class'      => "list-group footer-menu",
              'theme_location'  => 'menu-footer-left',
              'container'       => '',
              'container_class' => ''
            ] );
          endif;
          ?>
        </div>
      </div>
      <div class="col-md-4 col-sm-6 mt-4">
        <div class="footer-body">
          <?php
          if ( has_nav_menu( "menu-footer-middle" ) ) :
            $menu_middle_id = $locations["menu-footer-middle"];
            $menuMiddleObject = wp_get_nav_menu_object( $menu_middle_id );
            if ($menuMiddleObject) {
              ?>
              <h4 class="footer-title d-inline-block text-uppercase"><?= $menuMiddleObject->name ?></h4>
              <?php
              wp_nav_menu( [
                'menu_class'      => "list-group footer-menu",
                'theme_location'  => 'menu-footer-middle',
                'container'       => '',
                'container_class' => ''
              ] );
            }
          endif;
          ?>
        </div>
      </div>
      <div class="col-md-4 col-sm-6 mt-4">
        <!-- Les reseaux sociaux -->
        <div class="footer-body">
          <?php
          if ( has_nav_menu( 'social-network' ) ):
            $menu_social_id = $locations["social-network"];
            $menuSocialObject = wp_get_nav_menu_object( $menu_social_id );
            $menu_items = wp_get_nav_menu_items( $menuSocialObject->term_id );
            ?>
            <h4 class="footer-title d-inline-block text-uppercase"><?= $menuSocialObject->name ?></h4>
            <ul id="menu-social-network" class="pl-0">
              <?php
              foreach ( (array) $menu_items as $key => $menu_item ) {
                echo sprintf( "<a href='%s' class='d-inline-block mr-3'><i class='la la-%s'></i></a>", $menu_item->url, $menu_item->title );
              }
              ?>
            </ul>
          <?php
          endif;
          ?>
        </div>
      </div>
    </div>
  </div>
</footer>
<style type="text/css">
  .footer-title {
    margin-bottom: 15px;
  }

  ul.footer-menu li a {
    padding-top: 3px;
    padding-bottom: 3px;
    display: block;
  }

  ul.footer-menu li a:hover {
    color: #7ac943 !important;
  }

  ul.footer-menu li {
    list-style: none;
  }

  ul#menu-social-network i.la {
    font-size: 4em;
  }

  h4.footer-title::after {
    margin-top: 2px;
    width: 50%;
    content: " ";
    display: block;
    height: 1px;
    background-color: #7AC943;
  }
</style>
<?php wp_footer(); ?>
</div> <!-- .end offcanvas-content -->
</body>
</html>