<?php
require_once 'class/class-permission-callback.php';
require_once 'class/class-api-candidate.php';

/**
 * WP_REST_Server::READABLE = ‘GET’
 * WP_REST_Server::EDITABLE = ‘POST, PUT, PATCH’
 * WP_REST_Server::CREATABLE = ‘POST’
 * WP_REST_Server::DELETABLE = ‘DELETE’
 * WP_REST_Server::ALLMETHODS = ‘GET, POST, PUT, PATCH, DELETE’
 */
add_action( 'rest_api_init', function () {
  // @route {POST} http://[DOMAINE_URL]/wp-json/it-api/candidate/<id>
  register_rest_route( 'it-api', '/candidate/(?P<id>\d+)', [
    array(
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => [ new apiCandidate(), 'get_candidate' ],
      'permission_callback' => [ new permissionCallback(), 'private_data_permission_check' ],
      'args'                => array(
        'id' => array(
          'validate_callback' => function ( $param, $request, $key ) {
            return is_numeric( $param );
          }
        ),
      ),
    ),
    array(
      'methods'             => WP_REST_Server::EDITABLE,
      'callback'            => [ new apiCandidate(), 'update_candidate' ],
      'permission_callback' => [ new permissionCallback(), 'private_data_permission_check' ],
      'args'                => array(
        'id' => array(
          'validate_callback' => function ( $param, $request, $key ) {
            return is_numeric( $param );
          }
        ),
      ),
    )
  ]);

} );