<?php

/**
 * Ce fichier utilise le plugin WP Crontrol (https://wordpress.org/plugins/wp-crontrol/)
 */

require_once 'model/class-cron-model.php';
require_once 'class/class-cron.php';

function getModerators() {
    // Les address email des administrateurs qui recoivent les notifications
    // La valeur de cette option est un tableau
    $admin_email = get_field( 'admin_mail', 'option' ); // return string (mail)
    $admin_email = strpos( $admin_email, ',' ) ? explode( ',', $admin_email ) : $admin_email;

    return $admin_email;
}

/**
 * Action @tous_les_jours: 
 */

/**
 * Cette action permet de supprimer un offre à la une si la date limite est atteinte
 */
add_action('tous_les_jours', function () {
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
});

/**
 * Cette action permet d'envoyer des mails au administrateurs du site tous les jours
 * les taches en attente.
 */
add_action('tous_les_jours', function () {
    $cronModel = new cronModel();
    $admin_emails = getModerators();
    $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
    if ( ! $admin_emails ) {
      return false;
    }

    /**
     * Envoyer les candidats que les entreprises s'interresent
     */

    $pendingInterests = $cronModel->getPendingInterest();
    $msg = "Bonjour, <br/>";
    $msg .= "<p>Voici la liste des candidats qui ont été sélectionnés par les recruteurs, en attente de validation :</p>";
    foreach ($pendingInterests as $interest) {
        $msg .= "<p> * <strong>{$interest->company->title}</strong> s'interesse à un candidat pour réference
         « <strong>{$interest->candidate->title}</strong> » sur l'offre <b>{$interest->offer->postPromote}</b> ({$interest->offer->reference}) à {$interest->date}.</p>";
    }
    if (empty($pendingInterests))
        $msg .= "<b>Aucun</b>";
    $msg .= "<br>";
    $msg .= "A bientôt. <br/><br/><br/>";
    $msg .= "<p style='text-align: center'>ITJobMada © 2018</p>";
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = "Les CV sélectionner en attente";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";
    
    wp_mail( $to, $subject, $msg, $headers );

    /**
     * Envoyer les candidats qui ont postuler encore en attente
     */
    $pendingApply = $cronModel->getPendingApply();
    $msg = "Bonjour, <br/>";
    $msg .= "<p>Voici la liste des candidats qui ont postulé sur des offres, en attente de validation :</p> ";
    foreach ($pendingApply as $apply) {
        $name = $apply->candidate->getFirstName();
        $msg .= "<p> * <strong>{$name}</strong> portant la reférence « <strong>{$apply->candidate->title}</strong> »
         à postuler sur l'offre <b>{$apply->offer->postPromote}</b> ({$interest->offer->reference}) à {$apply->date}.</p>";
    }
    if (empty($pendingApply))
        $msg .= "<b>Aucun</b>";
    $msg .= "<br>";
    $msg .= "A bientôt. <br/><br/><br/>";
    $msg .= "<p style='text-align: center'>ITJobMada © 2018</p>";
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = "Liste des postulants en attente";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

    wp_mail( $to, $subject, $msg, $headers );


    /**
     * Envoyer les CV modifiers qui sont en attente
     */
    $candidats = $cronModel->getPendingEditingCV();
    $msg = "Bonjour, <br/>";
    $msg .= "<p>Voici la liste des candidats qui ont modifié leurs CV, en attente de validation :</p> ";
    foreach ($candidats as $candidate) {
        $msg .= "<p> * <strong>{$candidate['name']}</strong> portant la reférence « <strong>{$candidate['reference']}</strong> ». </p>";
    }
    if (empty($candidats))
        $msg .= "<b>Aucun</b>";
    $msg .= "<br>";
    $msg .= "A bientôt. <br/><br/><br/>";
    $msg .= "<p style='text-align: center'>ITJobMada © 2018</p>";
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = "Les CV avec des modifications en attente";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <no-reply-notification@itjobmada.com>";

    wp_mail( $to, $subject, $msg, $headers );
});

add_action('tous_les_15_minutes', function () {
    // Mettre à jours la clé
    $now = date('Y-m-d H:i:s');
    $new_key = password_hash($now . rand(10, 80 * date('s')), PASSWORD_DEFAULT);
    update_field('bo_key', $new_key, 'option');
});