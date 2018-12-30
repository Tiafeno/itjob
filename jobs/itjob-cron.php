<?php

/**
 * Ce fichier utilise le plugin WP Crontrol (https://wordpress.org/plugins/wp-crontrol/)
 */

require_once 'model/class-cron-model.php';
require_once 'class/class-cron.php';

add_action('action_scheduler_update_featured_offer', function () {
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