<?php

class cronModel
{
    private $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = &$wpdb;

    }

    public function getFeaturedOffers()
    {
        $results = [];
        $args = [
            'post_type' => 'offers',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'itjob_offer_featured',
                    'value' => 1,
                    'compare' => '='
                ]
            ]
        ];
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) : $query->the_post();
                $results[] = new includes\post\Offers(get_the_ID());
            endwhile;
        }

        return $results;
    }
}