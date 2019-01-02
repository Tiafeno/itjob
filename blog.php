<?php
/**
 * Template Name: Blog
 */

get_header();
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-small">
        <h4 class="m-0">LES ARTICLES</h4>
        <header class="page-header">
        <?php
            the_archive_title( '<h1 class="page-title">', '</h1>' );
            the_archive_description( '<div class="taxonomy-description">', '</div>' );
        ?>
        </header><!-- .page-header -->

			<?php
			// Start the Loop.
			while ( have_posts() ) : the_post();

                ?>
                <div class="entry-content">
                <?php the_content(); ?>
	            </div><!-- .entry-content -->
                
                <?php

			// End the loop.
			endwhile;

      ?>
    </div>
  </div>
  </div>
<?php
get_footer();