<?php
class permissionCallback
{
  public function __construct()
  {
  }

  /**
   * This is our callback function that embeds our resource in a WP_REST_Response
   */
  public function private_data_permission_check($data)
  {
    return current_user_can('delete_users');
  }
}