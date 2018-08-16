<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 16/08/2018
 * Time: 12:21
 */

trait Auth {
  public $authUser;

  /**
   * Vérifie si l'utilisateur peut modifier l'offre
   *
   * @param $postId
   *
   * @return bool|null
   */
  public function canEdit( $postId ) {
    if ( ! $this->authUser instanceof WP_User ) {
      return null;
    }
    /** @var WP_Post|array|null $post */
    $post = get_post( $postId );

    return $this->authUser->ID === $post->post_author;
  }
}