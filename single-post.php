<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">

    <div class="uk-container uk-container-small" style="min-height: 250px;">
        <?php
        while (have_posts()) : the_post();
        ?>
        <header class="entry-header">
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        </header><!-- .entry-header -->
    <?php
        the_content();
      endwhile;
      ?>
    </div>
  </div>
  </div>
<?php
get_footer();