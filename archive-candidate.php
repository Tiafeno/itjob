<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-small">
      <h4 class="m-0">LES CANDIDATES</h4>
      <?php
      while ( have_posts() ) : the_post();
        get_template_part( 'partials/content', 'candidate' );
      endwhile;
      echo '<div class="navigation">';
      echo paginate_links( array(
        'base'     => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'format'   => '?paged=%#%',
        'current'  => max( 1, get_query_var( 'paged' ) ),
        'total'    => $total,
        'mid_size' => 4,
        'type'     => 'list'
      ) );
      echo '</div>';
      ?>
    </div>
  </div>
  </div>
<?php
get_footer();