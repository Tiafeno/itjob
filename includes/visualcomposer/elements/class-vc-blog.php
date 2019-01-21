<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 18/10/2018
 * Time: 11:20
 */

namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

if ( ! class_exists( 'vcBlog' ) ):
  final class vcBlog {
    public function __construct() {
      add_action( 'init', function () {
        // Stop all if VC is not enabled
        if ( ! defined( 'WPB_VC_VERSION' ) ) {
          return;
        }

        // $post_type_objects = get_post_types( [], 'objects' );
        // $post_type_values  = [];
        // foreach ( $post_type_objects as $post_type => $object ) {
        //   $post_type_values = array_merge( $post_type_values, [ $object->labels->name => $post_type ] );
        // }
        vc_map(
          array(
            'name'        => "Blog lists",
            'base'        => 'vc_blog_lists',
            'description' => 'Affiche les blogs',
            'category'    => 'itJob',
            'params'      => array(
              array(
                'type'        => 'textfield',
                'holder'      => 'h3',
                'class'       => 'vc-ij-title',
                'heading'     => 'Titre',
                'param_name'  => 'title',
                'value'       => '',
                'description' => "Une titre pour le blog",
                'admin_label' => false,
                'weight'      => 0
              ),
              array(
                'type'        => 'dropdown',
                'class'       => 'vc-ij-post-type',
                'heading'     => 'Post type',
                'param_name'  => 'post_type',
                'value'       => ['Post' => 'post'],
                'std'         => 'content',
                'description' => "Type de post à afficher dans le blog",
                'admin_label' => true,
                'weight'      => 0
              ),
              array(
                'type'        => 'textfield',
                'class'       => 'vc-ij-category',
                'heading'     => 'Categories',
                'param_name'  => 'category',
                'value'       => 'blog',
                'description' => "Categorie",
                'admin_label' => true,
                'weight'      => 0
              ),
              array(
                'type'        => 'textfield',
                'class'       => 'vc-ij-archived-link',
                'heading'     => 'Lien pour voir les articles',
                'param_name'  => 'link',
                'value'       => '',
                'description' => "Ajouter un lien e.g: https://www.itjobmada.com/les-articles",
                'admin_label' => true,
                'weight'      => 0
              ),
            )
          )
        );
      }, 10, 0 );
      // Crée une shortcode pour le blog
      add_shortcode( 'vc_blog_lists', [ &$this, 'vc_blog_lists_render' ] );
    }

    /**
     * Exécuter l'affichage du shortcode du blog
     * @param array $attrs
     *
     * @return string
     */
    public function vc_blog_lists_render( $attrs ) {
      global $Engine;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title'     => "Articles recommandés",
            'post_type' => 'post',
            'category'  => 'blog',
            'link'      => ''
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
      /** @var STRING $post_type */
      $post_type = ! ( empty( $post_type ) ) ? $post_type : 'post';
      $category = ! ( empty( $category ) ) ? $category : 'blog';
      $link = ! ( empty( $link ) ) ? $link : '';
      $contents  = $this->getBlogContents( $post_type, $category );
      try {
        /** @var STRING $title - Titre de l'element VC */
        // TODO: Crée une page pour afficher les blogs
        return $Engine->render( "@VC/blog.html.twig", [
          'title'    => $title,
          'contents' => $contents,
          'archive_link' => $link
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }

    }

    /**
     * Récuperer les contenues du blog via un port type definie dans le shortcode
     * Afficher seulement 3 articles
     * @param string $post_type
     *
     * @return array
     */
    protected function getBlogContents( $post_type, $category = 'blog' ) {
      if ( empty( $post_type ) ) {
        return [];
      }
      $blogs      = [];
      $args       = [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'order'          => 'ASC',
        'orderby'        => 'date',
        'tax_query'      => [
          [
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => $category
          ]
        ],
        'posts_per_page' => 3
      ];
      $blog_posts = get_posts( $args );
      foreach ( $blog_posts as $blog ) {
        setup_postdata( $blog );
        array_push( $blogs, [
          'thumbnail' => get_the_post_thumbnail_url($blog, 'thumbnail'),
          'title'     => apply_filters('the_title', $blog->post_title, $blog->ID),
          'date'      => get_the_date("d M Y", $blog),
          'content'   => apply_filters('the_content', $blog->post_content),
          'permalink' => get_the_permalink($blog)
        ] );
      }
      wp_reset_postdata();

      return $blogs;

    }
  }
endif;

return new vcBlog();