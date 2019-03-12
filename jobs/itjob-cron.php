<?php

/**
 * Ce fichier utilise le plugin WP Crontrol (https://wordpress.org/plugins/wp-crontrol/)
 */

require_once 'model/class-cron-model.php';
require_once 'class/class-cron.php';

function getModerators ()
{
  // Les address email des administrateurs qui recoivent les notifications
  // La valeur de cette option est un tableau
  $admin_email = get_field('admin_mail', 'option'); // return string (mail)
  $admin_email = strpos($admin_email, ',') ? explode(',', $admin_email) : $admin_email;
  return $admin_email;
}

function update_hash_key_field ()
{
  // Mettre à jours la clé
  $now = date('Y-m-d H:i:s');
  $new_key = password_hash($now . rand(10, 80 * date('s')), PASSWORD_DEFAULT);
  update_field('bo_key', $new_key, 'option');
}

function fix_duplicates_cv_reference ()
{
  // Corriger les doublons
  global $wpdb;
  $query_doublon
    = "SELECT COUNT(post_title) as nbr, ID, post_title, post_date FROM {$wpdb->posts} 
        WHERE post_type = 'candidate' AND post_title REGEXP '(^CV).*$' GROUP BY post_title HAVING COUNT(post_title) > 1";
  $rows = $wpdb->get_results($query_doublon);

  if ($rows) {
    $Increment = get_field('cv_increment', 'option');
    $Increment = (int)$Increment;
    foreach ($rows as $row) {
      $sql = "SELECT * FROM {$wpdb->posts} pst WHERE pst.post_title = '{$row->post_title}' ORDER BY pst.post_date ASC";
      $posts = $wpdb->get_results($sql);
      foreach ($posts as $key => $post) {
        if ($key === 0) continue;
        $title = "CV{$Increment}";
        $date_create = $post->post_date;
        wp_update_post(['ID' => (int)$post->ID, 'post_title' => $title, 'post_date' => $date_create]);
        $Increment = $Increment + 1;
      }
    }

    update_field('cv_increment', $Increment, 'option');
  }
}

function update_offer_featured ()
{
  $cronModel = new cronModel();
  $featuredOffers = $cronModel->getFeaturedOffers();
  foreach ($featuredOffers as $offer) {
    $isFeatured = $offer->isFeatured();
    if ($isFeatured) {
      $featuredDateLimit = $offer->featuredDateLimit;
      if (strtotime($featuredDateLimit) < strtotime(date("Y-m-d H:i:s"))) {
        update_field('itjob_offer_featured', 0, $offer->ID);
        update_field('itjob_offer_featured_datelimit', '', $offer->ID);
      }
    }
  }
}

function remove_notice_after_5days ()
{
  $cronModel = new cronModel();
  $user_query = $cronModel->getUserAdmin();
  //An array containing a list of found users.
  $users = $user_query->results;
  $cronModel->deleteNoticeforLastDays(5, $users);
}

function review_offer_limit ()
{
  global $Engine;
  $cronModel = new cronModel();
  $results = $cronModel->getOffer5DaysLimitDate();
  $offers = [];
  if ($results) {
    foreach ($results as $result) {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

      $mail->CharSet = 'UTF-8';
      $mail->isHTML(true);
      $mail->setFrom("no-reply@itjobmada.com", "ITJob Team");
      $mail->addReplyTo('david@itjobmada.com', 'David Andrianaivo');

      // Envoyer une mail de notification au entreprise
      $Offer = new includes\post\Offers((int)$result->offer_id);
      $msg = '';
      try {
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        $msg .= $Engine->render('@MAIL/review-offer-limit.html', [
          'offer'    => $Offer,
          'logo'     => $logo[0],
          'home_url' => home_url("/")
        ]);

        $mail->addAddress($Offer->getAuthor()->user_email);
        $mail->Body = $msg;
        $mail->Subject = "Date limite offre";
        // Envoyer le mail
        $mail->send();

      } catch (Twig_Error_Loader $e) {
      } catch (Twig_Error_Runtime $e) {
      } catch (Twig_Error_Syntax $e) {
        continue;
      }

      $offers[] = $Offer;
    }

    // Envoyer un mail au administrateur et modérateur
    if (!empty($offers)) {
      try {
        $msg = $Engine->render('@MAIL/admin/review-admin-offer-limit.html', [
          'offers'              => $offers,
          'dashboard_offer_url' => "https://admin.itjobmada.com/offer-lists",
          'Year'                => Date('Y')
        ]);

      } catch (\Twig_Error_Loader $e) {
      } catch (\Twig_Error_Runtime $e) {
      } catch (\Twig_Error_Syntax $e) {
        return false;
      }

      $subject = "Date limite des offres ";
      $to = getModerators();
      $headers = [];
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

      wp_mail($to, $subject, $msg, $headers);
    }
  }
}

function newsletter_daily_company ()
{
  global $wpdb, $Engine;
  $today = Date("Y-m-d");
  $sql
    = "SELECT * FROM {$wpdb->posts} as pts WHERE pts.post_type = 'candidate' 
    AND pts.post_status = 'publish'
    AND pts.post_date REGEXP '(^{$today})'
    AND pts.ID IN ( SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'activated' AND  pm.meta_value = 1 )
    AND pts.ID IN ( SELECT pm2.post_id as ID FROM {$wpdb->postmeta} as pm2 WHERE pm2.meta_key = 'itjob_cv_hasCV' AND pm2.meta_value = 1 )";

  $query = $wpdb->get_results($sql);
  if (is_array($query) && !empty($query)) {
    $posts = &$query;
    $candidates = [];
    foreach ($posts as $post) {
      $candidates[] = new \includes\post\Candidate((int)$post->ID, true);
    }

    $queryCompany = "SELECT * FROM {$wpdb->posts} as pts  WHERE pts.post_type ='company' AND pts.post_status = 'publish'";
    $postCompany = $wpdb->get_results($queryCompany, OBJECT);

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->addAddress('david@itjobmada.com', 'David Andrianaivo');
    $mail->addReplyTo('david@itjobmada.com', 'David Andrianaivo');
    foreach ($postCompany as $pts) {
      $company = new \includes\post\Company((int)$pts->ID);
      // Envoyer au abonnée au notification seulement
      if (!$company->notification) continue;
      $sender = $company->author->user_email;
      $mail->addBCC($sender);
    }
    $count_candidate = count($candidates);
    $subject = "{$count_candidate} nouveaux candidats publiés sur Itjobmada.com";
    $content = '';
    try {
      $custom_logo_id = get_theme_mod('custom_logo');
      $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
      $content .= $Engine->render('@MAIL/notification-company-new-cv.html', [
        'Year'       => Date('Y'),
        'logo'       => $logo[0],
        'candidates' => $candidates
      ]);
    } catch (Twig_Error_Loader $e) {
    } catch (Twig_Error_Runtime $e) {
    } catch (Twig_Error_Syntax $e) {
      return false;
    }
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom("no-reply@itjobmada.com", "Équipe ITJob");
    $mail->Body = $content;
    $mail->Subject = $subject;

    try {
      // Envoyer le mail
      $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
      return false;
    }
  }
}

// TODO: Envoyer un mail au candidats tous les jours
function newsletter_daily_candidate ()
{

}


function alert_daily_company()
{
  global $wpdb, $itHelper;
  $today = Date("Y-m-d");
  $sql = "SELECT * FROM {$wpdb->posts} as pts WHERE pts.post_type = 'candidate' 
    AND pts.post_status = 'publish'
    AND pts.post_date REGEXP '(^{$today})'
    AND pts.ID IN ( SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'activated' AND  pm.meta_value = 1 )
    AND pts.ID IN ( SELECT pm2.post_id as ID FROM {$wpdb->postmeta} as pm2 WHERE pm2.meta_key = 'itjob_cv_hasCV' AND pm2.meta_value = 1 )";
  $query = $wpdb->get_results($sql);
  if (is_array($query) && !empty($query)) {
    $posts = &$query;
    foreach ($posts as $post) {
      // Envoyer une alert au entreprise
      $itHelper->Mailing->alert_for_new_candidate( $post->ID );
    }
  }
}

function fix_pending_cv ()
{
  global $wpdb;
  $walk_cv = get_option('walk_fix_last_offset', 0);
  $walk_cv = intval($walk_cv);
  $number = 20;
  $sql_count
    = "SELECT COUNT(*) as nbr FROM {$wpdb->posts} as pst
                WHERE
                  pst.post_status = 'publish'
                  AND pst.post_type = 'candidate'
                  AND pst.ID IN( SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'activated' AND pm.meta_value = 1)
                  AND pst.ID IN( SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'itjob_cv_hasCV' AND pm.meta_value = 1)";

  $count = $wpdb->get_var($sql_count);
  if ($count < $walk_cv) return false;
  $sql
    = "SELECT pst.ID, pst.post_title FROM {$wpdb->posts} as pst
            WHERE
              pst.post_status = 'publish'
              AND pst.post_type = 'candidate'
              AND pst.ID IN( SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'activated' AND pm.meta_value = 1)
              AND pst.ID IN( SELECT pm.post_id as ID FROM {$wpdb->postmeta} as pm WHERE pm.meta_key = 'itjob_cv_hasCV' AND pm.meta_value = 1)
              LIMIT $walk_cv, $number";
  $candidates = $wpdb->get_results($sql);
  foreach ($candidates as $candidate) {
    $post_id = intval($candidate->ID);
    $experiences = get_field("itjob_cv_experiences", $post_id);
    $formations = get_field('itjob_cv_trainings', $post_id);
    $update_experience = false;
    $update_formation = false;
    if (is_array($experiences))
      foreach ($experiences as $key => $experience) {
        if (!$experience['validated']) {
          $experiences[$key]['validated'] = false;
          $update_experience = true;
        }
      }
    if ($update_experience) {
      update_field('itjob_cv_experiences', $experiences, $post_id);
    }

    if (is_array($formations)) {
      foreach ($formations as $key => $formation) {
        if (!$formation['validated']) {
          $formations[$key]['validated'] = false;
          $update_formation = true;
        }
      }
    }
    if ($update_formation) {
      update_field('itjob_cv_trainings', $formations, $post_id);
    }
  }

  if (($walk_cv + $number) > $count) {
    $walk_cv = $walk_cv + ($count - $walk_cv);
  } else {
    $walk_cv += $number;
  }
  update_option('walk_fix_last_offset', $walk_cv, true);
}

add_action('action_scheduler_run_queue', function () {
  // fix_pending_cv();
});


add_action('tous_les_15_minutes', function () {
  // Mettre a jour la cle du telechargement
  update_hash_key_field();
  // Corriger les CV en doublons
  fix_duplicates_cv_reference();
  // Effacer les notifications des administrateur vieux de 15 jours
  remove_notice_after_5days();
});

add_action("woocommerce_tracker_send_event", function () { // at 14h41 (Une fois par jour)
  // review_offer_limit();
  // send_pending_cv();
});

add_action('jp_purge_transients_cron', function () { // at 10h 24 (Une fois par jour)
  // send_pending_cv();
  // send_pending_offer();
});

// Envoyer les CV validés au entreprises
add_action('end_of_the_day', function () { // at 16h38 (Une fois par jour)
  newsletter_daily_company();
  newsletter_daily_candidate();
  send_pending_cv();
  send_pending_offer();

  // Envoyer les alerts au entreprise
  alert_daily_company();
});


/**
 * Action @tous_les_jours:
 */

/**
 * Cette action permet de supprimer un offre à la une si la date limite est atteinte
 */
add_action('tous_les_jours', function () { // at 06h00
  // Mettre a jour la position de l'offre
  update_offer_featured();
  // Envoyer les offres avec date de fin d'inscription de 5 jours et 1 jours
  review_offer_limit();
});


/**
 * Cette action permet d'envoyer des mails au administrateurs du site tous les jours
 * les taches en attente.
 */
add_action('tous_les_jours', function () { // at 06h00

  // Envoyer les candidats que les entreprises s'interresent
  send_pending_interest();

  // Envoyer les candidats qui ont postuler encore en attente
  send_pending_postuled_candidate();

  // Envoyer les CV modifiers qui sont en attente
  send_edit_pending_cv();

  // Envoyer les CV en attente de validation
  send_pending_cv();

  // Envoyer les offres en attente de validation
  send_pending_offer();
});

function send_pending_cv() {
  $cronModel = new cronModel();
  $year = Date('Y');
  $admin_emails = getModerators();
  $admin_emails = empty($admin_emails) ? false : $admin_emails;
  if (!$admin_emails) {
    return false;
  }
  $candidats = $cronModel->getPendingCV();
  if (empty($candidats)) return false;
  $msg = "Bonjour, <br/>";
  $msg .= "<p>Voici la liste des candidats en attente de validation :</p> ";
  foreach ($candidats as $candidate) {
    $msg .= "<p> * <a href='https://admin.itjobmada.com/candidate/{$candidate['ID']}/edit' target='_blank' title='{$candidate['name']}'><strong>{$candidate['name']}</strong></a> portant la reférence « <strong>{$candidate['reference']}</strong> ». </p>";
  }
  $msg .= "<br>";
  $msg .= "A bientôt. <br/><br/><br/>";
  $msg .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
  $to = is_array($admin_emails) ? implode(',', $admin_emails) : $admin_emails;
  $subject = "Les CV en attente de validation";
  $headers = [];
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

  wp_mail($to, $subject, $msg, $headers);
}

function send_edit_pending_cv() {
  $cronModel = new cronModel();
  $year = Date('Y');
  $admin_emails = getModerators();
  $candidats = $cronModel->getPendingEditingCV();
  if (empty($candidats)) return false;

  $msg = "Bonjour, <br/>";
  $msg .= "<p>Voici la liste des candidats qui ont modifié leurs CV, en attente de validation :</p> ";
  foreach ($candidats as $candidate) {
    $msg .= "<p> * <strong>{$candidate['name']}</strong> portant la reférence « <strong>{$candidate['reference']}</strong> ». </p>";
  }
  if (empty($candidats))
    $msg .= "<b>Aucun</b>";
  $msg .= "<br>";
  $msg .= "A bientôt. <br/><br/><br/>";
  $msg .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
  $to = is_array($admin_emails) ? implode(',', $admin_emails) : $admin_emails;
  $subject = "Les CV avec des modifications en attente";
  $headers = [];
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

  wp_mail($to, $subject, $msg, $headers);
}

function send_pending_postuled_candidate() {
  $cronModel = new cronModel();
  $year = Date('Y');
  $admin_emails = getModerators();
  $pendingApply = $cronModel->getPendingApply();
  if (empty($pendingApply)) return false;

  $msg = "Bonjour, <br/>";
  $msg .= "<p>Voici la liste des candidats qui ont postulé sur des offres, en attente de validation :</p> ";
  $msg .= "<ul>";
  foreach ($pendingApply as $apply) {
    $first = $apply->candidate->getFirstName();
    $last = $apply->candidate->getLastName();
    $msg
      .= "<li> * <strong>{$first} {$last}</strong> portant la reférence « <strong>{$apply->candidate->title}</strong> »
         à postuler sur l'offre <a href='https://admin.itjobmada.com/offer/{$apply->offer->ID}/edit' target='_blank'>" .
      "<b>{$apply->offer->postPromote}</b></a> de reference {$apply->offer->reference} à {$apply->date}.</li>";
  }
  $msg .= "</ul>";
  if (empty($pendingApply))
    $msg .= "<b>Aucun</b>";
  $msg .= "<br>";
  $msg .= "A bientôt. <br/><br/><br/>";
  $msg .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
  $to = is_array($admin_emails) ? implode(',', $admin_emails) : $admin_emails;
  $subject = "Liste des postulants en attente";
  $headers = [];
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

  wp_mail($to, $subject, $msg, $headers);
}

function send_pending_offer() {
  $cronModel = new cronModel();
  $year = Date('Y');
  $admin_emails = getModerators();
  $admin_emails = empty($admin_emails) ? false : $admin_emails;
  if (!$admin_emails) {
    return false;
  }
  $offers = $cronModel->getPendingOffer();
  if (empty($offers)) return false;

  $msg = "Bonjour, <br/>";
  $msg .= "<p>Voici la liste des offres en attente de validation :</p> ";
  foreach ($offers as $offer) {
    $msg .= "<p> * <a href='https://admin.itjobmada.com/offer/{$offer['ID']}/edit' target='_blank' title='{$offer['title']}'>" .
      "<strong>{$offer['title']}</strong></a> portant la reférence « <strong>{$offer['reference']}</strong> ». </p>";
  }
  $msg .= "<br>";
  $msg .= "A bientôt. <br/><br/><br/>";
  $msg .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
  $to = is_array($admin_emails) ? implode(',', $admin_emails) : $admin_emails;
  $subject = "Les offres en attente de validation";
  $headers = [];
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

  wp_mail($to, $subject, $msg, $headers);
}

function send_pending_interest() {
  $year = Date('Y');
  $cronModel = new cronModel();
  $admin_emails = getModerators();
  $admin_emails = empty($admin_emails) ? false : $admin_emails;
  if (!$admin_emails) {
    return false;
  }
  $pendingInterests = $cronModel->getPendingInterest();
  if (empty($pendingInterests)) return false;
  $msg = "Bonjour, <br/>";
  $msg .= "<p>Voici la liste des candidats qui ont été sélectionnés par les recruteurs, en attente de validation :</p>";
  foreach ($pendingInterests as $interest) {
    $offer_id = $interest->offer->ID;
    $candidate_id = $interest->candidate->getId();
    $msg
      .= "<p> * <b>{$interest->company->title}</b> s'interesse à un candidat pour réference
         « <a href='https://admin.itjobmada.com/candidate/{$candidate_id}/edit' target='_blank'><b>{$interest->candidate->title}</b></a> » sur l'offre <a href='https://admin.itjobmada.com/offer/{$offer_id}/edit' target='_blank'>" .
      "<b>{$interest->offer->postPromote}</b></a> portant la réference " .
      "{$interest->offer->reference} à {$interest->date}.</p>";
  }
  if (empty($pendingInterests))
    $msg .= "<b>Aucun</b>";
  $msg .= "<br>";
  $msg .= "A bientôt. <br/><br/><br/>";
  $msg .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
  $to = is_array($admin_emails) ? implode(',', $admin_emails) : $admin_emails;
  $subject = "Les CV sélectionner en attente";
  $headers = [];
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

  wp_mail($to, $subject, $msg, $headers);
}