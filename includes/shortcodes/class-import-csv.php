<?php

namespace includes\shortcode;

if (!defined('ABSPATH')) {
  exit;
}

use Http;
use includes\import\importUser;
use includes\object\JHelper;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;
use Underscore\Types\Arrays;

if (!class_exists('scImport')) :
  class scImport
{
  public function __construct()
  {

    add_action('init', function () {
      add_action('wp_ajax_get_latest_update_experience', [&$this, 'get_latest_update_experience']);
      add_action('wp_ajax_nopriv_get_latest_update_experience', [&$this, 'get_latest_update_experience']);

      if (current_user_can("remove_users")) {
        add_shortcode('itjob_import_csv', [$this, 'sc_render_html']);

        add_action('wp_ajax_import_csv', [&$this, 'import_csv']);
        add_action('wp_ajax_delete_post', [&$this, 'delete_post']);
        add_action('wp_ajax_delete_users', [&$this, 'delete_users']);
        add_action('wp_ajax_delete_term', [&$this, 'delete_term']);
        add_action('wp_ajax_remove_all_experiences', [&$this, 'remove_all_experiences']);
        add_action('wp_ajax_active_career', [&$this, 'active_career']);
        add_action('wp_ajax_added_offer_sector_activity', [&$this, 'added_offer_sector_activity']);
      }

    });

    add_action('admin_init', function () {
      add_action('delete_offers', function ($pId) {
        delete_post_meta($pId, '__offer_publish_date');
        delete_post_meta($pId, '__offer_create_date');
        delete_post_meta($pId, '__offer_count_view');
        delete_post_meta($pId, '__id_offer');
      }, 10);
    });
  }

  public function import_csv()
  {
    if (!is_user_logged_in()) {
      wp_send_json_error("Accès refuser");
    }
    $type = Http\Request::getValue('entry_type');
    $content_type = Http\Request::getValue('content_type');
    switch ($type) {
      case 'taxonomy':
        $taxonomy = $content_type;
        $this->add_term($taxonomy);
        break;
      case 'user':
        $this->add_user($content_type);
        break;
      case 'offers':
        $this->add_offer($content_type);
        break;
    }
  }

  public function get_latest_update_experience()
  {
    $id_experience_latest = get_option("last_added_experience_id");
    var_dump($id_experience_latest);
    exit;
  }


  /**
   * Function ajax
   * Effacer tous les experiences dans le site
   * @url /wp-admin/admin-ajax.php?action=remove_all_experiences
   */
  public function remove_all_experiences()
  {
    global $wpdb;
    $sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = 'candidate'";
    $prepare = $wpdb->prepare($sql);
    $rows = $wpdb->get_var($prepare);
    $posts_per_page = 20;
    $paged = $rows / $posts_per_page;
    for ($i = 1; $i <= $paged; $i++) {
      $argc = [
        'post_type' => 'candidate',
        'post_status' => 'any',
        'fields' => 'ids',
        'paged' => $i,
        'posts_per_page' => $posts_per_page
      ];
      $post_ids = get_posts($argc);
      foreach ($post_ids as $post_id) {
        delete_field('itjob_cv_experiences', $post_id);
      }
    }

    wp_send_json_success("Tous les experiences sont effacer; Nombre des candidats: {$rows}");
  }

  /**
   * Function ajax
   * Effacer tous les post d'une type ajouter dans le parametre
   * 
   * @url /wp-admin/admin-ajax.php?action=delete_post&post_type=<post_type>
   */
  public function delete_post()
  {
    global $wpdb;
    $post_type = Http\Request::getValue('post_type');
    if (!post_type_exists($post_type)) {
      wp_send_json_error("Le post type n'existe pas");
    }
    $results = [];

    $sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = '%s'";
    $prepare = $wpdb->prepare($sql, $post_type);
    $rows = $wpdb->get_var($prepare);
    $posts_per_page = 20;
    $paged = $rows / $posts_per_page;
    for ($i = 1; $i <= $paged; $i++) {
      $args = [
        'paged' => $i,
        'posts_per_page' => $posts_per_page,
        'post_type' => $post_type,
        'fields' => 'ids',
        'post_status' => 'any'
      ];
      $post_ids = get_posts($args);
      foreach ($post_ids as $post_id) {
        $response = wp_delete_post($post_id);
        array_push($results, $response);
      }
    }

    wp_send_json_success($results);
  }

  /**
   * Function ajax
   * Effacer tous les ustilisateur sauf l'administrateur
   * 
   * @url /wp-admin/admin-ajax.php?action=delete_users
   */
  public function delete_users()
  {
    if (!is_user_logged_in()) {
      wp_send_json_error("Accès refuser");
    }
    global $wpdb;

    $sql = "SELECT * FROM {$wpdb->users} usr WHERE usr.ID != %d";
    $prepare = $wpdb->prepare($sql, 1); // Not delete admin user
    $users = $wpdb->get_results($prepare);

    $posts_per_page = 20;
    $paged = count($users) / $posts_per_page;
    foreach ($users as $user) {
      wp_delete_user($user->ID);
      $count += 1;
    }
    wp_send_json_succes(['UserCount' => $count]);

  }

  /**
   * Mettre à jour les secteur d'activité des offres par la secteur d'activité de l'entreprise
   */
  public function added_offer_sector_activity()
  {
    global $wpdb;
    $post_type = "offers";
    $sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = '%s'";
    $prepare = $wpdb->prepare($sql, $post_type);
    $rows = $wpdb->get_var($prepare);
    $posts_per_page = 20;
    $paged = $rows / $posts_per_page;

    $numberOffer = 0;
    $numberPage = 0;

    for ($i = 1; $i <= $paged; $i++) {
      $args = [
        'paged' => $i,
        'posts_per_page' => $posts_per_page,
        'post_type' => $post_type,
        'post_status' => 'any'
      ];
      $posts = get_posts($args);
      foreach ($posts as $post) {
        $Offer = new Offers($post->ID);
        $postCompany = $Offer->getCompany();
        $abranch = wp_get_post_terms($postCompany->ID, 'branch_activity');
        $this->__set_field_term($Offer, $abranch);
        $numberOffer += 1;
      }
      $numberPage += 1;
    }
    wp_send_json_success([
      'msg' => 'Tous les offres sont à jours',
      "Nombre d'offre à jour" => $numberOffer,
      "Nombre de page" => $numberPage
    ]);
  }

  private function __set_field_term($offer, $term)
  {
    $term = is_array($term) && !empty($term) ? $term[0] : null;
    if (!is_null($term)) {
      update_field("itjob_offer_abranch", $term->term_id, $offer->ID);
    }
  }

  /**
   * Function ajax
   * 
   * @url /wp-admin/admin-ajax.php?action=delete_term&taxonomy=<taxonomy>
   */
  public function delete_term()
  {
    $taxonomy = Http\Request::getValue('taxonomy');
    if (!taxonomy_exists($taxonomy)) {
      wp_send_json_error("Le taxonomy n'existe pas");
    }
    $terms = get_terms($taxonomy, ['hide_empty' => false]);
    foreach ($terms as $term) {
      $result = wp_delete_term($term->term_id, $taxonomy);
      if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
      }
    }
    wp_send_json_success("Tous les terms sont effacé avec succès");
  }

  // Function ajax
  public function active_career()
  {
    $column = Http\Request::getValue('column');
    $lines = json_decode($column);
    list($id_cv, $id_demandeur) = $lines;
    if (!(int)$id_cv) {
      wp_send_json_success("Passer à la colonne suivante");
    }
    if (current_user_can("remove_users")) {
      $Helper = new JHelper();
      $Candidate_post_id = $Helper->is_cv((int)$id_cv);
      if ($Candidate_post_id) {
        $candidate_id = (int)$Candidate_post_id;
        $Experiences = get_field('itjob_cv_experiences', $candidate_id);
        if ($Experiences && is_array($Experiences)) {
          $Experiences = array_map(function ($experience) {
            $experience['validated'] = 1;

            return $experience;
          }, $Experiences);
          update_field('itjob_cv_experiences', $Experiences, $candidate_id);
        }

        $Formations = get_field('itjob_cv_trainings', $candidate_id);
        if ($Formations && is_array($Formations)) {
          $Formations = array_map(function ($formation) {
            $formation['validated'] = 1;

            return $formation;
          }, $Formations);
          update_field('itjob_cv_trainings', $Formations, $candidate_id);
        }
      } else {
        wp_send_json_success("Impossible de trouver le candidat");
      }

      wp_send_json_success("All carrer is succefuly update");
    } else {
      wp_send_json_error("Votre compte n'as pas l'accès a ce niveau");
    }
  }

  protected function add_term($taxonomy)
  {
    if (!is_user_logged_in()) {
      wp_send_json_error("Accès refuser");
    }
    $row = Http\Request::getValue('column');
    $row = json_decode($row);
    switch ($taxonomy) {
        // Ajouter la ville d'une code postal
      case 'city':
        list($parent, $child) = $row;
        $parent_term = term_exists(trim($parent), 'city');
        if (0 === $parent_term || null === $parent_term || !$parent_term) {
          $parent_term = wp_insert_term($parent, $taxonomy, ['slug' => $parent]);
          if (is_wp_error($parent_term)) {
            wp_send_json_error($parent_term->get_message());
          }
        }
        $child_term = term_exists(trim($child), 'city', $parent_term['term_id']);
        if (0 === $child_term || null === $child_term || !$child_term) {
          $child_term = wp_insert_term($child, $taxonomy, ['parent' => $parent_term['term_id']]);
          if (is_wp_error($child_term)) {
            wp_send_json_error($child_term->get_message());
          }
        }
        wp_send_json_success("({$parent}) - {$child}");
        break;
      case 'software':
        $titles = &$row;
        if (is_array($titles)) {
          foreach ($titles as $title) {
              // return bool
            $this->insert_term($title, $taxonomy);
          }
          wp_send_json_success("Ajouter avec succès");
        } else {
          wp_send_json_error("Un probléme detecter, Erreur:" . $titles);
        }

        break;
      case 'branch_activity':
      case 'job_sought':
        list($id, $title, $status, $slug) = $row;
        $id = (int)$id;
        if (!is_numeric($id)) {
          wp_send_json_success("Passer aux colonnes suivantes");
        }
        if (!is_array($title) && (int)$status) {
          $term = $this->insert_term($title, $taxonomy, $slug);
          if ($term) {
            update_term_meta($term['term_id'], '__job_id', $id);
            wp_send_json_success("Emploie ajouter avec succès");
          }
        }
        wp_send_json_success("Emploi désactiver par l'administrateur, impossible de l'ajouter");
        break;
      default:

        break;
    }
  }

  protected function insert_term($name, $taxonomy, $slug = null)
  {
    $term = term_exists($name, $taxonomy);
    if (0 === $term || null === $term || !$term) {
      $arg = null === $slug ? [] : ['slug' => $slug];
      $term = wp_insert_term(ucfirst($name), $taxonomy, $arg);
      if (is_wp_error($term)) {
        $term = get_term_by('name', $name);
        if (!$term) {
          return false;
        }
      }
    }
      // Activer le taxonomie
    update_term_meta($term['term_id'], "activated", 1);

    return $term;
  }

    // FEATURED: Ajouter les utilisateurs du site itjobmada
  protected function add_user($content_type)
  {
    if (!is_user_logged_in()) {
      return false;
    }
    $Helper = new JHelper();
    $lines = Http\Request::getValue('column');
    $lines = json_decode($lines);
    switch ($content_type) {
      case 'user':
        $rows = [
          'id_user' => (int)$lines[0],
          'name' => $lines[1],
          'seoname' => $lines[2],
          'email' => $lines[3],
          'password' => $lines[4],
          'description' => $lines[5],
          'status' => (int)$lines[6],
          'id_role' => (int)$lines[7],
          'created' => $lines[8],
          'last_login' => $lines[9],
          'subscriber' => (int)$lines[10]
        ];
        // Effacer les espace pour les textes
        $rows = array_map(function ($row) {
          return is_numeric($row) ? $row : trim($row);
        }, $rows);

        $rows_object = (object)$rows;
        if (!$rows_object->id_role) {
          wp_send_json_success("Passer sur une autres colonne");
        }

         // Désactiver les mail envoyer par le system wordpress
         add_filter('user_registration_email', '__return_false');
         add_filter('send_password_change_email', '__return_false');

        // (WP_User|false) WP_User object on success, false on failure.
        $isUser = get_user_by('email', $rows_object->email);
        // Vérifier si l'utilisateur existe déja
        $hasAccount = $isUser ? true : false;
        if ($hasAccount) {
            // Supprimer le compte
          if ($isUser instanceof \WP_User) {
            wp_delete_user($isUser->ID);
          }
        }
       
        // Ajouter l'ancien role dans un meta
        switch ($rows_object->id_role) :
          case 11:
          case 12:
          case 14:
          case 15:
          case 16:
          case 18:
          case 19:
            // COMPANY
            // Ajouter une entreprise
            $args = [
              "user_pass" => $rows_object->password,
              "user_login" => "user-{$rows_object->id_user}",
              "user_email" => $rows_object->email,
              "display_name" => $rows_object->name,
              "first_name" => $rows_object->name,
              "role" => 'company'
            ];
            if (username_exists("user-{$rows_object->id_user}")) {
              wp_send_json_success("Cet identifiant existe déjà !");
            }
            $user_id = wp_insert_user($args);
            if (!is_wp_error($user_id)) {
                  // Stocker les anciens information dans des metas
                  // Ajouter un meta '__recovery_password' pour que l'utilisateur reinitialise sont mot de passe pendant
                  // la prémiere connexion sur le site itjob
              $importUser = new importUser($user_id);
              $importUser->update_user_meta($rows_object);

                  // Ajouter une post type 'company'
              $status = $rows_object->status ? 'publish' : 'pending';
              $create_date = strtotime($rows_object->created);
              $publish = date('Y-m-d H:i:s', $create_date);
              $argc = [
                'post_title' => $rows_object->name,
                'post_status' => $status,
                'post_type' => 'company',
                'post_date' => $publish,
                'post_author' => $user_id
              ];
              $insert_company = wp_insert_post($argc, true);
              if (is_wp_error($insert_company)) {
                wp_send_json_error($insert_company->get_error_message());
              }

              $id_company = (int)$insert_company;
              // Activer ou désactiver l'utilisateur
              update_field('activated', $rows_object->status, $id_company);

              update_field('itjob_company_email', $rows_object->email, $id_company);
              update_field('itjob_company_name', $rows_object->name, $id_company);
              update_field('itjob_company_newsletter', $rows_object->subscriber, $id_company);

              $user = new \WP_User($user_id);
              get_password_reset_key($user);
              wp_send_json_success(['msg' => "Utilisateur ajouter avec succès", 'utilisateur' => $user]);

            } else {
              wp_send_json_error($user_id->get_error_message());
            }
            break;

          case 1:
          case 13:
          case 17:
            // CANDIDATE
            $names = $rows_object->name;
            $first_name = &$names;
            $last_name = '';
            $args = [
              "user_pass" => $rows_object->password,
              "user_login" => "user-{$rows_object->id_user}",
              "user_email" => $rows_object->email,
              "display_name" => $rows_object->name,
              "first_name" => $first_name,
              "last_name" => $last_name,
              "role" => 'candidate'
            ];
            if (username_exists("user-{$rows_object->id_user}")) {
              wp_send_json_success("Cet identifiant existe déjà !");
            }
            $user_id = wp_insert_user($args);
            if (!is_wp_error($user_id)) {
                  // Stocker les anciens information dans des metas
              $importUser = new importUser($user_id);
              $importUser->update_user_meta($rows_object);

              // Ajouter une post type 'candidate'
              $create_date = strtotime($rows_object->created);
              $publish = date('Y-m-d H:i:s', $create_date);
              $argc = [
                'post_title' => $rows_object->name,
                'post_status' => 'pending',
                'post_type' => 'candidate',
                'post_author' => $user_id,
                'post_date' => $publish
              ];
              $insert_candidate = wp_insert_post($argc, true);
              if (is_wp_error($insert_candidate)) {
                wp_send_json_error($insert_candidate->get_error_message());
              }
              
              $id_candidate = (int)$insert_candidate;
              // Désactiver le candidat par default
              update_field('activated', 0, $id_candidate);

              update_field('itjob_cv_email', $rows_object->email, $id_candidate);
              update_field('itjob_cv_firstname', $first_name, $id_candidate);
              update_field('itjob_cv_lastname', $last_name, $id_candidate);
              update_field('itjob_cv_newsletter', $rows_object->subscriber, $id_candidate);

              $user = new \WP_User($user_id);
              get_password_reset_key($user);
              wp_send_json_success(['msg' => "Utilisateur ajouter avec succès", 'utilisateur' => $user]);
            } else {
              wp_send_json_error($user_id->get_error_message());
            }
            break;

          case 21:
          case 22:
                // FEATURED: Ajouter les utilisateurs pour role modérateur
            $args = [
              "user_pass" => $rows_object->password,
              "user_login" => "editor-{$rows_object->id_user}",
              "user_email" => $rows_object->email,
              "display_name" => $rows_object->name,
              "first_name" => $rows_object->name,
              "role" => 'editor'
            ];
            if (username_exists("editor-{$rows_object->id_user}")) {
              wp_send_json_success("Cet identifiant existe déjà !");
            }
            $user_id = wp_insert_user($args);
            if (!is_wp_error($user_id)) {
              update_user_meta($user_id, '__id_user', $rows_object->id_user);
              update_user_meta($user_id, '__recovery_password', 1);
              wp_send_json_success("Modérateur ajouter avec succès");
            } else {
              wp_send_json_error("Une erreru s'est produite, user: {$rows_object->name}");
            }
            break;

          default:
            wp_send_json_success("Impossible d'ajouter ce type de compte: " . $rows_object->id_role);
            break;
        endswitch;

        break;

      case 'user_company':

        list(
          $id_entreprise, $id_user, $company_name, $address, $nif,
          $stat, $phone, $greeting, $first_name,
          $last_name, $cellphones, $email,
          $branch_activity, $old_branch_activity, $newsletter, $notification, $alert, $create
        ) = $lines;

        $id_user = (int)$id_user;
        $newsletter = (int)$newsletter;
        $notification = (int)$notification;
        $activated = (int)$activated;

        if ((int)$id_user === 0) {
          wp_send_json_success("Passer sur une autre colonne");
        }
          // Retourne false or WP_User
        $User = $Helper->has_user($id_user);
        if ($User && in_array('company', $User->roles)) {
          $Company = Company::get_company_by($User->ID);
          update_field('itjob_company_address', $address, $Company->getId());
          update_field('itjob_company_nif', $nif, $Company->getId());
          update_field('itjob_company_stat', $stat, $Company->getId());
          update_field('itjob_company_name', $first_name . ' ' . $last_name, $Company->getId());
          update_field('itjob_company_newsletter', $newsletter, $Company->getId());
          update_field('itjob_company_notification', $notification === '0' || empty($notification) ? '' : $notification, $Company->getId());
          update_field('itjob_company_phone', $phone, $Company->getId());
          update_field('itjob_company_alerts', $alert === '0' ? '' : $alert, $Company->getId());
          update_field('itjob_company_greeting', $greeting, $Company->getId());

          //update_field('activated', $activated, $Company->getId());

          $values = [];
          $cellphones = explode(';', $cellphones);
          foreach ($cellphones as $phone) {
            $values[] = ['number' => $phone];
          }
          update_field('itjob_company_cellphone', $values, $Company->getId());

          $create_date = strtotime($create);
          $publish = date('Y-m-d H:i:s', $create_date);
          wp_update_post([
            'ID' => $Company->getId(), 
            'post_status' => 'publish', 
            'post_title'  => $company_name,
            'post_date'   => $publish
          ]);

            // Enregistrer la date de creation original & l'ancienne secteur d'activité
          update_post_meta($Company->getId(), '__create', $create);
          update_post_meta($Company->getId(), '__company_branch_activity', $old_branch_activity);
          update_post_meta($Company->getId(), '__id_company', (int)$id_entreprise);

            // Ajouter le term dans le secteur d'activité
          $taxonomy = 'branch_activity';
          if (!$branch_activity || $branch_activity == null || empty($branch_activity)) {
            wp_send_json_success("Entreprise mis a jour avec succes, Secteur d'activité non define");
          }
          $term = term_exists($branch_activity, $taxonomy);
          if (null === $term || 0 === $term || !$term) {
            $term = wp_insert_term($branch_activity, $taxonomy);
            if (is_wp_error($term)) {
              $term = get_term_by('name', $branch_activity);
            }
          }

          if (!is_array($term) || !isset($term['term_id'])) {
            wp_send_json_success("L'entreprise ajouter avec succès. Impossible d'ajouter la categorie");
          }
          /**
           * (array) An array of the terms affected if successful,
           * (boolean) false if integer value of $post_id evaluates as false (if ( ! (int) $post_id )),
           * (WP_Error) The WordPress Error object on invalid taxonomy ('invalid_taxonomy').
           * (string) The first offending term if a term given in the $terms parameter is named incorrectly. (Invalid term ids are accepted and inserted).
           */
          $terms = [];
          $terms[] = $term['term_id'];
          $insert_result = wp_set_post_terms($Company->getId(), $terms, $taxonomy);
          if (is_wp_error($insert_result) || !$insert_result) {
            wp_send_json_error($insert_result->get_error_message());
          }

          // Cette fonction permet de mettre à jours le secteur d'activité des offres de l'entreprise
          $this->add_offers_branch_activity( $Company->getId(), $term );

          wp_send_json_success(['msg' => "L'entreprise ajouter avec succès"]);
        } else {
          wp_send_json_success("L'utilisateur est refuser de s'inscrire ID:{$id_user}");
        }
        break;

      case 'user_candidate_informations':
        list(
          $id, $id_user, $audition, $find_job, $greeting, $firstname, $lastname, $email, $address, $region, $cellphones, $birthday,
          $choice_job, $choice_training, $notif_job, $notif_training, $activated, $newsletter
        ) = $lines;

        $old_user_id = (int)$id_user;
        $greeting = strtolower(trim($greeting));

        if (!$old_user_id) {
          wp_send_json_success("Passer à la colonne suivante");
        }
          // Retourne false or WP_User
        $User = $Helper->has_user($old_user_id);
        if ($User && in_array('candidate', $User->roles)) {
          $candidatePost = $Helper->get_candidate_by_email($User->user_email);
          if (!$candidatePost instanceof \WP_Post) {
            wp_send_json_error("Candidat introuvable");
          }
          if (!$candidatePost) {
            wp_send_json_error("Le candidat n'existe pas email: {$User->user_email}");
          }

            // Update ACF field
          $condition = $greeting === 'NULL' || empty($greeting) || null === $greeting || !$greeting;
          $greeting = $condition ? '' : $greeting;
          update_field('itjob_cv_greeting', $greeting, $candidatePost->ID);
          update_field('itjob_cv_firstname', $firstname, $candidatePost->ID);
          update_field('itjob_cv_lastname', $lastname, $candidatePost->ID);
          update_field('itjob_cv_address', $address, $candidatePost->ID);

          $birthday = \DateTime::createFromFormat("Y-m-d", $birthday);
          $acfBirthdayValue = $birthday->format('Ymd');
          update_field('itjob_cv_birthdayDate', $acfBirthdayValue, $candidatePost->ID);

          update_field('itjob_cv_notifEmploi', ['notification' => (int)$notif_job], $candidatePost->ID);
          update_field('itjob_cv_notifFormation', ['notification' => (int)$notif_training], $candidatePost->ID);
            // Ici on active ou désactive un CV
          update_field('activated', (int)$activated, $candidatePost->ID);
          update_field('itjob_cv_newsletter', (int)$newsletter, $candidatePost->ID);

          $values = [];
          $cellphones = explode(',', $cellphones);
          foreach ($cellphones as $phone) {
            $values[] = ['number' => preg_replace('/\s+/', '', trim($phone))];
          }
          update_field('itjob_cv_phone', $values, $candidatePost->ID);

            // Meta for old value
          update_post_meta($candidatePost->ID, '__cv_audition', (int)$audition); // int value
          update_post_meta($candidatePost->ID, '__cv_id_demandeur', (int)$id); // int value
          update_post_meta($candidatePost->ID, '__cv_find_job', (int)$find_job); // int value
          update_post_meta($candidatePost->ID, '__cv_choice_job', $choice_job); // int value
          update_post_meta($candidatePost->ID, '__cv_choice_training', $choice_training); // int value

            // Ajouter term region
          $region = ucfirst(strtolower($region));
          $term = term_exists($region, 'region');
          if (0 === $term || null === $term || !$term) {
            $term = wp_insert_term($region, 'region');
            if (is_wp_error($term)) {
              $term = get_term_by('name', $region);
            }
          }
          if (isset($term['term_id'])) {
            wp_set_post_terms($candidatePost->ID, [$term['term_id']], 'region');
          }
          wp_send_json_success("Information candidat mis à jours avec succès");
        } else {
          wp_send_json_success("Le candidat n'existe pas, utilisateur id: {$old_user_id}");
        }
        break;

      case 'user_candidate_cv':
          // Publier le CV
        /** @var int $id_cv */
        /** @var int $id_demandeur - post candidat with __cv_id_demandeur (meta) */
        list(
          $id_cv,
          $id_demandeur,
          $drive_licences,
          $emploi_rechercher,
          $logiciel_ids,
          $langues,
          $statut,
          $reference,
          $certificat_id,
          $interets,
          $date_creation,
          $date_publish
        ) = $lines;
        $id_cv = (int)$id_cv;
        $id_demandeur = (int)$id_demandeur;
        $data_import_dir = get_template_directory() . "/includes/import/data";
        if (!$id_cv) {
          wp_send_json_success("Passer à la colonne suivante");
        }
        /**
         * Cette methode 'is_applicant' permet dé récuperer l'identifiant du candidat
         * par le billet de son ancien identifiant sur le site ITJobmada
         */
        $Candidate_post_id = $Helper->is_applicant($id_demandeur);
        if ($Candidate_post_id) {
          $candidat_id = (int)$Candidate_post_id;
          $driveLicences = [
            ['label' => 'A`', 'value' => 0],
            ['label' => 'A', 'value' => 1],
            ['label' => 'B', 'value' => 2],
            ['label' => 'C', 'value' => 3],
            ['label' => 'D', 'value' => 4]
          ];

          update_post_meta($candidat_id, '__id_cv', $id_cv);
          $dls = explode(",", $drive_licences);
          $dlValues = [];
          foreach ($dls as $dl) {
            if (empty($dl)) {
              continue;
            }
            $result = Arrays::find($driveLicences, function ($licence) use ($dl) {
              return $licence['label'] == $dl;
            });
            $dlValues[] = $result['value'];
          }
            // Permis de conduire
          update_field('itjob_cv_driveLicence', $dlValues, $candidat_id);

            // Ajouter les emplois recherchers
          $emploi_ids = explode(",", $emploi_rechercher);
          $emploi_ids = array_filter($emploi_ids, function ($id) {
            return (int)$id !== 0;
          });
          if (!empty($emploi_ids)) :
            $JOBS = self::get_csv_contents($data_import_dir . "/emplois.csv");
          $emploi_values = self::collect_data_from_array($JOBS, $emploi_ids);
          unset($JOBS);
          update_post_meta($candidat_id, '_old_job_sought', implode(', ', $emploi_values));
          endif;

            // Ajouter les logiciel
          $logiciel_ids = explode(",", $logiciel_ids);
          $logiciel_ids = array_filter($logiciel_ids, function ($id) {
            return (int)$id !== 0;
          });
          if (!empty($logiciel_ids)) :
            $SOFTWARES = self::get_csv_contents($data_import_dir . "/softwares.csv");
          $logiciel_values = self::collect_data_from_array($SOFTWARES, $logiciel_ids);
          unset($SOFTWARES);
          update_post_meta($candidat_id, '_old_softwares', implode(', ', $logiciel_values));
          endif;

            // CERTIFICATS
          $certificat_id = (int)$certificat_id;
          if ($certificat_id) :
            $CERTIFICATS = self::get_csv_contents($data_import_dir . "/certifications.csv");
          $certificat_ids = [];
          $certificat_ids[] = (int)$certificat_id;
          $certificat_values = self::collect_data_from_array($CERTIFICATS, $certificat_ids);
          unset($CERTIFICATS);
          endif;

            // Langue
          $languages = explode(',', $langues);
          $languages = Arrays::reject($languages, function ($lang) {
            return empty($lang) || $lang === '' || !$lang;
          });
          $languages = array_map(function ($langue) {
            return strtolower(trim($langue));
          }, $languages);
          $langValues = [];
          foreach ($languages as $language) {
            $langTerm = term_exists($language, 'language');
            if (0 === $langTerm || null === $langTerm || !$langTerm) {
              $langTerm = wp_insert_term(ucfirst($language), 'language');
              if (is_wp_error($langTerm)) {
                $langTerm = get_term_by('name', $language);
              }
            }
            if (isset($langTerm['term_id'])) {
              $langValues[] = $langTerm['term_id'];
            }
          }
          wp_set_post_terms($candidat_id, $langValues, 'language');

          // Status de l'utilisateur pour son CV (1,2,3)
          update_field('itjob_cv_status', (int)$statut, $candidat_id);
          // Marque comme un utilisateur qui posséde un CV
          update_field('itjob_cv_hasCV', 1, $candidat_id);

          update_field('itjob_cv_centerInterest', [
            'projet' => $interets,
            'various' => isset($certificat_values) && !empty($certificat_values) ? implode(', ', $certificat_values) : ''
          ], $candidat_id);

          update_post_meta($candidat_id, '__date_publish', $date_publish);
          update_post_meta($candidat_id, '__date_create', $date_creation);
          /**
           * Ce champ est utiliser pour ajouter les expériences et les formations
           */
          update_post_meta($candidat_id, '__id_cv', $id_cv);

            // Mettre à jour le titre du post ou ajouter le titre du CV
            // $create_date    = \DateTime::createFromFormat( "Y-m-d H:i:s", $date_creation );
            // $publish        = $create_date->format( 'Y-m-d H:i:s' );
          $create_date = strtotime($date_creation);
          $publish = date('Y-m-d H:i:s', $create_date);
          $argc = [
            'ID' => $candidat_id,
            'post_title' => strtoupper(trim($reference)),
            'post_date' => $publish,
            'post_status' => 'publish'
          ];
          $update_results = wp_update_post($argc);
          if (is_wp_error($update_results)) {
            wp_send_json_error($update_results->get_error_message());
          }

          wp_send_json_success("CV ajouté avec succès ID:{$candidat_id}");
        }
        wp_send_json_success("Utilisateur abscent. Ancien CV:{$id_cv}");
        break;

      case 'user_candidate_experiences':
        list(
          $id_experience,
          $id_demandeur,
          $id_cv,
          $date_begin,
          $date_end,
          $ville_id,
          $pays,
          $entreprise,
          $secteuractivite_id,
          $postoccuper_id,
          $mission
        ) = $lines;
        $data_import_dir = get_template_directory() . "/includes/import/data";
        $id_experience = (int)$id_experience;
        $id_cv = (int)$id_cv;
        if (!$id_cv) {
          wp_send_json_success("Passer à la colonne suivante");
        }
        if (empty($entreprise)) {
          wp_send_json_success("Annuler pour raison de manque d'information");
        }
        $candidat_id = $Helper->is_cv($id_cv);
          // Réinitialiser cette post meta 'listUpdateExperiences' avant d'ajouter à nouveaux des experiences
          // Cette variable ou ce champ est utiliser pour réinitaliser les experiences d'une utilisateur
          // si son identifiant ne figure pas dans la liste.

          // Pour ajouter une nouvelle liste d'experience sans reinitialiser l'experience on ajoute l'id du CV dan sla liste
        $listUpdateExperiences = get_option('listUpdateExperiences', []);
        if (is_array($listUpdateExperiences) && !in_array($candidat_id, $listUpdateExperiences)) {
          delete_field('itjob_cv_experiences', $candidat_id);
        }

        if ($candidat_id) {
          $Experiences = get_field('itjob_cv_experiences', $candidat_id);
          $listOfExperiences = [];

          $city = '';
          if (!empty($ville_id) && (int)$ville_id) {
            $VILLES = self::get_csv_contents("{$data_import_dir}/villes.csv");
            $ville_id = (int)$ville_id;
            $city = Arrays::find($VILLES, function ($ville) use ($ville_id) {
              return (int)$ville['id'] == $ville_id;
            });
            $city = !isset($city['value']) || empty($city) ? '' : $city['value'];
          }

          // Récuperer le poste qui correspond à son identifiant
          $poste = '';
          if (!empty($postoccuper_id) && (int)$postoccuper_id) {
            $POSTES = self::get_csv_contents("{$data_import_dir}/poste_occupes.csv");
            $postoccuper_id = (int)$postoccuper_id;
            $poste = Arrays::find($POSTES, function ($occuped) use ($postoccuper_id) {
              return (int)$occuped['id'] == $postoccuper_id;
            });
            $poste = !isset($poste['value']) ? "" : $poste['value'];
          }

          // SECTEUR D'ACTIVITES
          $secteuractivite_id = (int)$secteuractivite_id;
          if ($secteuractivite_id) :
            $BRANCHACTIVITY = self::get_csv_contents("{$data_import_dir}/secteur_activites.csv");
          $abranch_ids = [];
          $abranch_ids[] = (int)$secteuractivite_id;
          $abranch_values = self::collect_data_from_array($BRANCHACTIVITY, $abranch_ids);
          unset($BRANCHACTIVITY);
          endif;

          if ($Experiences) {
            foreach ($Experiences as $Experience) {
              $listOfExperiences[] = $Experience;
            }
          }

          $state = ucfirst(strtolower($pays));
          $listOfExperiences[] = [
            'exp_city' => $city,
            'exp_country' => $state,
            'exp_company' => $entreprise,
            'exp_positionHeld' => $poste,
            'exp_mission' => nl2br($mission),
            'old_value' => [
              'exp_dateBegin' => $date_begin,
              'exp_dateEnd' => $date_end,
              'exp_branch_activity' => isset($abranch_values) && is_array($abranch_values) ? implode(', ', $abranch_values) : ''
            ],
            'validated' => 1
          ];

          update_field('itjob_cv_experiences', $listOfExperiences, $candidat_id);

          if (is_array($listUpdateExperiences)) {
            $listUpdateExperiences[] = $candidat_id;
            update_option('listUpdateExperiences', $listUpdateExperiences);
          }

          update_option('last_added_experience_id', $id_experience);
          wp_send_json_success("Experience ajouter avec succès ID: {$id_experience}");
        } else {
          wp_send_json_success("Aucun utilisateur ne correpond à cette identifiant ID:" . $id_demandeur);
        }

        break;

      case 'user_candidate_trainings':
        list($id_formation, $id_demandeur, $id_cv, $year, $ville_id, $pays, $diplome_id, $universite_id) = $lines;
        $id_formation = (int)$id_formation;
        $year = (int)$year;
        $ville_id = (int)$ville_id;
        $id_cv = (int)$id_cv;
        $data_import_dir = get_template_directory() . "/includes/import/data";
        if (!$id_cv) {
          wp_send_json_success("Passer à la colonne suivante");
        }
        if (empty($diplome_id) || empty($universite_id) || !$ville_id) {
          wp_send_json_success("Annuler pour raison de manque d'information");
        }
        $candidat_id = $Helper->is_cv($id_cv);
        if ($candidat_id) {
          $Trainings = get_field('itjob_cv_trainings', $candidat_id);
          $listOfTrainings = [];
          if ($Trainings) {
            foreach ($Trainings as $Training) {
              $listOfTrainings[] = $Training;
            }
          }

            // Récuperer le nom de la ville
          $city = '';
          if (!empty($ville_id) && (int)$ville_id) {
            $VILLES = self::get_csv_contents("{$data_import_dir}/villes.csv");
            $city = Arrays::find($VILLES, function ($el) use ($ville_id) {
              return $el['id'] == (int)$ville_id;
            });
            $city = empty($city) || !isset($city['value']) ? '' : $city['value'];
            unset($VILLES);
          }

            // Récuperer le diplome
          $diploma = '';
          if (!empty($diplome_id) && (int)$diplome_id) {
            $DIPLOMES = self::get_csv_contents("{$data_import_dir}/diplomes.csv");
            $diploma = Arrays::find($DIPLOMES, function ($el) use ($diplome_id) {
              return $el['id'] == (int)$diplome_id;
            });
            $diploma = empty($diploma) || !isset($diploma['value']) ? '' : ucfirst($diploma['value']);
            unset($DIPLOMES);
          }

            // Récuperer l'université
          $university = '';
          if (!empty($universite_id) && (int)$universite_id) {
            $UNIVERSITE = self::get_csv_contents("{$data_import_dir}/universites.csv");
            $university = Arrays::find($UNIVERSITE, function ($el) use ($universite_id) {
              return $el['id'] == (int)$universite_id;
            });
            $university = empty($university) || !isset($university['value']) ? '' : $university['value'];
            unset($UNIVERSITE);
          }

          if (empty($diploma) || empty($university)) {
            wp_send_json_success("Impossible d'ajouter cette formation pour une raison de manque d'information");
          }

          $state = ucfirst(strtolower($pays));
          $listOfTrainings[] = [
            'training_dateBegin' => $year,
            'training_dateEnd' => $year,
            'training_city' => $city,
            'training_country' => $state,
            'training_diploma' => $diploma,
            'training_establishment' => $university,
            'validated' => 1
          ];
          update_field('itjob_cv_trainings', $listOfTrainings, $candidat_id);
          update_option('last_added_training_id', $id_formation);
          wp_send_json_success("Formation ajouter avec succès ID: {$id_formation}");
        } else {
          wp_send_json_success("Aucun utilisateur ne correpond à cette identifiant ID:" . $candidat_id);
        }
        break;

      case 'user_candidate_status':
          // Candidate informations content type
        list(, $id_user,,,,,,,,,,,,,,, $activated, ) = $lines;

        $old_user_id = (int)$id_user;

        if (!$old_user_id) {
          wp_send_json_success("Passer à la colonne suivante");
        }
          // Retourne false or WP_User
        $User = $Helper->has_user($old_user_id);
        if ($User && in_array('candidate', $User->roles)) {
          $candidate = $Helper->get_candidate_by_email($User->user_email);
          $activated = (int)$activated;
          update_field('activated', $activated, $candidate->ID);
          $message = $activated ? "activé" : "désactivé";
          wp_send_json_success("Utilisateur {$message} avec succès");
        } else {
          wp_send_json_error("Utilisateur n'existe pas");
        }

        break;

      case 'user_update_publish_date':
          // User content type
        list($id_user,,,,,,,,, $created,, ) = $lines;
        $id_user = (int)$id_user;
        if (!$id_user) {
          wp_send_json_success("Passer la ligne");
        }
        if ($current_user_id = username_exists("user-{$id_user}")) {
          $User = new \WP_User($current_user_id);
          if (in_array('candidate', $User->roles)) {
            $Candidate = Candidate::get_candidate_by($User->ID);

            if (get_post_type($Candidate->getId()) == 'candidate') {
              wp_update_post([
                'ID' => $Candidate->getId(),
                'post_date' => $created
              ]);
            } else {
              wp_send_json_success("Le post n'existe pas");
            }

            wp_send_json_success("Post date mise à jour avec succès. Date: {$created}");
          } else {
            wp_send_json_success("L'utilisateur n'est pas un candidat");
          }
        } else {
          wp_send_json_success("Utilisateur n'existe pas");
        }
        break;
    }
  }

    // Mettre à jour la secteur d'activité pour les offres d'une entreprise definie
  private function add_offers_branch_activity($company_id, $term)
  {
    $args = [
      'post_type' => 'offers',
      'post_status' => 'any',
      'meta_key' => 'itjob_offer_company',
      'meta_value' => $company_id
    ];
    $offers = get_posts($args);
    foreach ($offers as $offer) {
      update_field('itjob_offer_abranch', $term['term_id'], $offer->ID);
    }

    return true;
  }

  /**
   * @param array $handlers
   * @param array $finds
   *
   * @return array
   */
  private static function collect_data_from_array($handlers, $finds = [])
  {
    if (!is_array($handlers)) {
      return [];
    }
    $contents = [];
    foreach ($finds as $find) {
      if (empty($find)) {
        continue;
      }
      $results = Arrays::find($handlers, function ($handler) use ($find) {
        return (int)$handler['id'] == (int)$find;
      });
      if (!empty($results) || $results == 'undefined' || !$results) {
        $contents[] = $results['value'];
      }
    }

    return $contents;
  }

    // Retourne une date formater
  private function get_format_date($custom_date_format)
  {
      //$custom_date_format = mb_convert_encoding($custom_date_format,"ISO-8859-1","auto");
    if (empty($custom_date_format) || $custom_date_format == "null" || $custom_date_format == null) {
      return '';
    }
    if (strpos($custom_date_format, '-') === false) {
      return '';
    }
    $dateTime = \DateTime::createFromFormat("M-y", $custom_date_format);
    $dateFormat = $dateTime->format('m/d/Y');

    return $dateFormat;
  }

    // Retourne les contenues d'un fichier d'extention CSV
  private static function get_csv_contents($file)
  {
    if (file_exists($file)) {
      $Helper = new JHelper();

      return $Helper->parse_csv($file);
    } else {
      return false;
    }
  }

    // Retourne la listes des emplois
  private function get_jobs()
  {
    $args = ['taxonomy' => 'job_sought', 'hide_empty' => false, 'parent' => 0, 'posts_per_page' => -1];
    $terms = get_terms($args);
    foreach ($terms as $term) {
      $old_job_sought_id = get_term_meta($term->term_id, '__job_id', true);
      $term->__old_job_id = (int)$old_job_sought_id;
    }

    return $terms;
  }

    // Ajouter les anciens offres
  protected function add_offer($content_type)
  {
    if (!is_user_logged_in()) {
      return;
    }
    $column = Http\Request::getValue('column');
    $row = json_decode($column);
    $obj = new \stdClass();
    list(
      $obj->id, $obj->id_user, $obj->status, $obj->created, $obj->type_contrat,
      $obj->date_limit_candidature, $obj->poste_base_a, $obj->poste_a_pourvoir, $obj->mission, $obj->profil_recherche,
      $obj->autre_info, $obj->reference, $obj->salaire_net, $obj->published, $obj->nbr_vue, $obj->position
    ) = $row;
    $Helper = new JHelper();

    $User = $Helper->has_user((int)$obj->id_user);
    if (!$User && !in_array('company', $User->roles)) {
      wp_send_json_success("Utilisateur non inscrit, ID:" . $obj->id_user);
    }

      // Verifier si l'offre est déja publier ou ajouter dans le site
    $offers_exists = $Helper->has_old_offer_exist($obj->id);
    if ($offers_exists) {
      wp_send_json_success("Offre déja publier");
    }
      // Ajouter une offre
    $publish_date = strtotime($obj->published);
    $publish = date('Y-m-d H:i:s', $publish_date);

    $args = [
      "post_type" => 'offers',
      "post_title" => $obj->poste_a_pourvoir,
      "post_status" => (int)$obj->status ? 'publish' : 'pending',
      'post_author' => $User->ID,
      'post_date' => $publish
    ];
    $post_id = wp_insert_post($args, true);
    if (!is_wp_error($post_id)) {
      $Company = Company::get_company_by($User->ID);
        // Ajouter une secteur d'activité à cette offre
      if ($Company->branch_activity !== null && is_object($Company->branch_activity)) {
        wp_set_post_terms($post_id, [$Company->branch_activity->term_id], 'branch_activity');
      }

      update_field('itjob_offer_post', $obj->poste_a_pourvoir, $post_id);
      update_field('itjob_offer_reference', $obj->reference, $post_id);

      $dateLimit = \DateTime::createFromFormat("Y-m-d", $obj->date_limit_candidature);
      $acfDateLimit = $dateLimit->format('Ymd');
      update_field('itjob_offer_datelimit', $acfDateLimit, $post_id);

      update_field('activated', (int)$obj->status, $post_id);
      update_field('itjob_offer_contrattype', (int)$obj->type_contrat, $post_id);
      update_field('proposedsallary', (int)$obj->salaire_net, $post_id);
      update_field('itjob_offer_profil', html_entity_decode(htmlspecialchars_decode($obj->profil_recherche)), $post_id);
      update_field('itjob_offer_mission', html_entity_decode(htmlspecialchars_decode($obj->mission)), $post_id);
      update_field('itjob_offer_otherinformation', html_entity_decode(htmlspecialchars_decode($obj->autre_info)), $post_id);
      update_field('itjob_offer_company', $Company->getId(), $post_id);

        // Ajouter autres information meta
      update_post_meta($post_id, '__offer_count_view', (int)$obj->nbr_vue);
      update_post_meta($post_id, '__id_offer', (int)$obj->id);

        // Ajouter le term region
      $term = term_exists(trim($obj->poste_base_a), 'region');
      if (0 === $term || null === $term || !$term) {
        $term = wp_insert_term(trim($obj->poste_base_a), 'region');
        if (is_wp_error($term)) {
          $term = get_term_by('name', $obj->poste_base_a);
        }
      }
      if (isset($term['term_id'])) {
        wp_set_post_terms($post_id, [$term['term_id']], 'region');
      }

      wp_send_json_success(['msg' => "Offre ajouter avec succès", 'term' => $term]);
    } else {
      wp_send_json_error("Une erreur s'est produite pendant l'ajout :" . $post_id->get_error_message());
    }
  }

    // Rendre le shortcode HTML
  public function sc_render_html($atts, $content = "")
  {
    global $Engine, $itJob;
    extract(
      shortcode_atts(
        array(
          'title' => ''
        ),
        $atts
      )
    );
    if (!is_user_logged_in()) {
        // Access refuser au public
      return "<p class='align-center'>Accès interdit à toute personne non autorisée</p>";
    }
    wp_enqueue_script('import-csv', get_template_directory_uri() . '/assets/js/app/import/importcsv.js', [
      'angular',
      'angular-ui-route',
      'angular-messages',
      'angular-animate',
      'angular-aria',
      'papaparse'
    ], $itJob->version, true);
    wp_localize_script('import-csv', 'importOptions', [
      'ajax_url' => admin_url("admin-ajax.php"),
      'partials_url' => get_template_directory_uri() . '/assets/js/app/import/partials',
    ]);
    try {
      /** @var STRING $title */
      return $Engine->render('@SC/import-csv.html.twig', [
        'title' => $title
      ]);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }
  }
}
endif;

return new scImport();