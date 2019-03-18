<?php
add_action('init', function () {
    /**
   * Combined with the manage_{$post_type}_posts_columns filter,
   * this allows you to add or remove (unset) custom columns to the list
   * post/page/custom post type pages (which automatically appear in Screen Options).
   */
  add_filter('manage_posts_columns', function ($columns) {
    return array_merge($columns,
      array('activated' => __('Activé'))
    );
  });

  add_filter('manage_wallets_posts_columns', function ($columns) {
    return array_merge($columns,
      array('wallet' => __('Credit'))
    );
  });

  add_action('manage_posts_custom_column', function ($column, $post_id) {
    $activate = get_field('activated', $post_id);
    switch ($column) {
      CASE 'activated':
        echo $activate ? 'Oui' : 'Non';
        BREAK;

      CASE 'wallet':
        $wallet = get_field('wallet', $post_id);
        echo intval($wallet);
        BREAK;
    }
  }, 10, 2);

  // Users
  add_filter('manage_users_custom_column', function ($val, $column_name, $user_id) {
    switch ($column_name) {
      case 'CV' :
        $User = get_user_by('ID', $user_id);
        if (in_array('candidate', $User->roles)) {
          $Candidate = \includes\post\Candidate::get_candidate_by($user_id);
          $edit_link = get_edit_post_link($Candidate->getId());
          return "<a target='_blank' href='{$edit_link}'>{$Candidate->title}</a>";
        }
        break;
      default:
    }
    return $val;
  }, 10, 3);

  add_filter('manage_users_columns', function ($column) {
    $column['CV'] = 'CV';
    return $column;
  });
});