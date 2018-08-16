<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 16/08/2018
 * Time: 13:00
 */

trait Register {
  private function createCompanyRole() {
    $capabilities = array(
      'read'                   => true,  // true allows this capability
      'upload_files'           => true,
      'edit_posts'             => true,
      'edit_users'             => true,
      'manage_options'         => false,
      'remove_users'           => false,
      'edit_others_posts'      => true,
      'delete_others_pages'    => true,
      'delete_published_posts' => true,
      'edit_others_posts'      => true, // Allows user to edit others posts not just their own
      'create_posts'           => true, // Allows user to create new posts
      'manage_categories'      => true, // Allows user to manage post categories
      'publish_posts'          => true, // Allows the user to publish, otherwise posts stays in draft mode
      'edit_themes'            => false, // false denies this capability. User can’t edit your theme
      'install_plugins'        => false, // User cant add new plugins
      'delete_plugins'         => false,
      'update_plugin'          => false, // User can’t update any plugins
      'update_core'            => false, // user cant perform core updatesy
      'create_users'           => false,
      'delete_themes'          => false,
      'install_themes'         => false,
    );

    return add_role(
      'company',
      'Entreprise',
      $capabilities
    );
  }

  private function createCandidateRole() {
    $capabilities = array(
      'read'                   => true,  // true allows this capability
      'upload_files'           => true,
      'edit_posts'             => true,
      'edit_users'             => true,
      'manage_options'         => false,
      'remove_users'           => false,
      'edit_others_posts'      => true,
      'delete_others_pages'    => true,
      'delete_published_posts' => true,
      'edit_others_posts'      => true, // Allows user to edit others posts not just their own
      'create_posts'           => true, // Allows user to create new posts
      'manage_categories'      => true, // Allows user to manage post categories
      'publish_posts'          => true, // Allows the user to publish, otherwise posts stays in draft mode
      'edit_themes'            => false, // false denies this capability. User can’t edit your theme
      'install_plugins'        => false, // User cant add new plugins
      'delete_plugins'         => false,
      'update_plugin'          => false, // User can’t update any plugins
      'update_core'            => false, // user cant perform core updatesy
      'create_users'           => false,
      'delete_themes'          => false,
      'install_themes'         => false,
    );

    return add_role(
      'candidate',
      'Candidat',
      $capabilities
    );
  }

  public function createRoles() {
    $this->createCandidateRole();
    $this->createCompanyRole();
  }

  public function postTypes() {
    register_post_type( 'offers', [
      'label'           => "Offres",
      'labels'          => [
        'name'               => "Offres",
        'singular_name'      => "Offre",
        'add_new'            => 'Ajouter',
        'add_new_item'       => "Ajouter une nouvelle offre",
        'edit_item'          => 'Modifier',
        'view_item'          => 'Voir',
        'search_items'       => "Trouver des offres",
        'all_items'          => "Tous les offres",
        'not_found'          => "Aucune offre trouver",
        'not_found_in_trash' => "Aucune offre dans la corbeille"
      ],
      'public'          => true,
      'hierarchical'    => false,
      'menu_position'   => null,
      'show_ui'         => true,
      'rewrite'         => [ 'slug' => 'offres' ],
      'capability_type' => 'post',
      'menu_icon'       => 'dashicons-businessman',
      'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ]
    ] );

    register_post_type( 'company', [
      'label'           => "Entreprises",
      'labels'          => [
        'name'               => "Entreprises",
        'singular_name'      => "Entreprise",
        'add_new'            => 'Ajouter',
        'add_new_item'       => "Ajouter une nouvelle entreprise",
        'edit_item'          => 'Modifier',
        'view_item'          => 'Voir',
        'search_items'       => "Trouver des entreprises",
        'all_items'          => "Tous les entreprises",
        'not_found'          => "Aucune entreprise trouver",
        'not_found_in_trash' => "Aucune entreprise dans la corbeille"
      ],
      'public'          => true,
      'hierarchical'    => false,
      'menu_position'   => null,
      'show_ui'         => true,
      'rewrite'         => [ 'slug' => 'entreprise' ],
      'capability_type' => 'post',
      'menu_icon'       => 'dashicons-welcome-widgets-menus',
      'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ]
    ] );

  }

  public function taxonomy() {

    // Now register the taxonomy (Secteur d'activité)
    register_taxonomy( 'branch_activity', [ 'company' ], [
      'hierarchical'      => true,
      'labels'            => array(
        'name'              => 'Secteur d\'activité',
        'singular_name'     => 'Secteur d\'activité',
        'search_items'      => 'Trouver une secteur d\'activité',
        'all_items'         => 'Trouver des secteur d\'activités',
        'parent_item'       => 'Activité parent',
        'parent_item_colon' => 'Activité parent:',
        'edit_item'         => 'Modifier l\'activité',
        'update_item'       => 'Mettre à jour l\'activité',
        'add_new_item'      => 'Ajouter une nouvelle activité',
        'menu_name'         => 'Secteur d\'activité',
      ),
      'show_ui'           => true,
      'show_admin_column' => false,
      'query_var'         => true,
      'rewrite'           => array( 'slug' => 'branch_activity' ),
    ] );

    // Now register the taxonomy (Région)
    register_taxonomy( 'region', [ 'offers' ], [
      'hierarchical'      => true,
      'labels'            => array(
        'name'              => 'Région',
        'singular_name'     => 'Région',
        'search_items'      => 'Trouver une région',
        'all_items'         => 'Trouver des région',
        'parent_item'       => 'Région parent',
        'parent_item_colon' => 'Région parent:',
        'edit_item'         => 'Modifier la région',
        'update_item'       => 'Mettre à jour la région',
        'add_new_item'      => 'Ajouter une nouvelle région',
        'menu_name'         => 'Région',
      ),
      'show_ui'           => true,
      'show_admin_column' => false,
      'query_var'         => true,
      'rewrite'           => array( 'slug' => 'region' ),
    ] );

  }
}