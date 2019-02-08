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

    public function getUserAdmin() {
        $args = [
            'role__in' => ['administrator', 'editor', 'contributor']
        ];
        $user_query = new WP_User_Query($args);
        return $user_query;
    }

    /**
     * Recuperer les offres 5 jours avants 
     */
    public function getOffer5DaysLimitDate() {
      global $wpdb;
        $today = date('Y-m-d H:i:s');
        $todayDatetime = new DateTime($today);
        $date5Days = new DateTime("$today +5 day");
        $date5DaysFormat = $date5Days->format('Ymd');
        $todayFormat = $todayDatetime->format('Ymd');
        $sql = "SELECT pts.ID as offer_id FROM $wpdb->posts as pts 
                    WHERE 
                    pts.post_type = 'offers'
                    AND pts.post_status = 'publish'
                    AND pts.ID IN (SELECT pm.post_id as post_id 
                        FROM {$wpdb->postmeta} as pm
                        WHERE pm.meta_key REGEXP '(^itjob_offer_datelimit)$' AND pm.meta_value BETWEEN '$todayFormat' AND '$date5DaysFormat' )";
        $rows = $wpdb->get_results($sql);

        return $rows;
    }

    /**
     * Effacer les notifications
     */
    public function deleteNoticeforLastDays($day = 5, $users = []) {
        global $wpdb;
        $today = date('Y-m-d H:i:s');
        $lastDay = new DateTime("$today - $day day");
        $lastDayString = $lastDay->format('Y-m-d H:i:s');
        $query = '';
        foreach ($users as $user) {
            $endEl = end($users);
            $query .= "$user->ID";
            if ($endEl->ID !== $user->ID) $query .= ', ';
        }
        $sql = "DELETE FROM {$wpdb->prefix}notices as ntc WHERE ntc.date_create <= '$lastDayString' AND ntc.id_user IN ( $query )";
        $rows = $wpdb->query( $sql );
        
        return $rows;
    }
    
    /**
     * Récupérer les CV en attente de modifications
     */
    public function getPendingEditingCV() {
        global $wpdb;
        $return = [];
        $sql = "SELECT * FROM $wpdb->posts pts WHERE pts.post_type = %s AND pts.post_status = %s
        AND pts.ID IN (SELECT pta.post_id as post_id FROM $wpdb->postmeta pta WHERE pta.meta_key REGEXP '^itjob_cv_experiences_[0-9]{1,2}_validated' AND pta.meta_value != 1)
        AND pts.ID IN (SELECT pta.post_id as post_id FROM $wpdb->postmeta pta WHERE pta.meta_key = 'itjob_cv_hasCV' AND pta.meta_value = 1)
        AND pts.ID IN (SELECT pta.post_id as post_id FROM $wpdb->postmeta pta WHERE pta.meta_key = 'activated' AND pta.meta_value = 1)";
        $prepare = $wpdb->prepare($sql , 'candidate', 'publish');
        $rows    = $wpdb->get_results( $prepare );
        foreach ($rows as $candidate) {
            // Vérifier si l'utilisateur est un candidat
            $Candidate = new includes\post\Candidate((int) $candidate->ID);
            $pending = false;
            
            // On verifie si le candidat a une modification en attente
            $Experiences = $Candidate->experiences;
            $Formations  = $Candidate->trainings;
            foreach ($Experiences as $experience) {
                if (!$experience->validated) $pending = true;
            }

            foreach ($Formations as $formation) {
                if (!$formation->validated) $pending = true;
            }

            if (!$pending) continue;
            $name = $Candidate->getFirstName().' '.$Candidate->getLastName();
            $return[] = ['reference' => $Candidate->reference, 'name' => $name];
        }

        return $return;
    }

    /**
     * Récupérer les informations que les entreprises s'interresent
     * qui sont en attente
     */
    public function getPendingInterest() {
        global $wpdb;
        $interest_tb =  $wpdb->prefix . 'cv_request';
        $prepare = $wpdb->prepare( "SELECT * FROM $interest_tb WHERE type = %s AND status = %s", 'interested', 'pending' );
        $rows    = $wpdb->get_results( $prepare );
        $infos = [];
        foreach ($rows as $row) {
            $infos[] = (object)[
                'candidate' => new includes\post\Candidate( (int) $row->id_candidate ),
                'company'   => new includes\post\Company( (int) $row->id_company ),
                'offer'     => new includes\post\Offers( (int) $row->id_offer ),
                'date'      => $row->date_add
            ];
        }
        return $infos;
    }

    /**
     * Récupérer les informations postulant encore en attente sur des offres 
     */
    public function getPendingApply() {
        global $wpdb;
        $interest_tb =  $wpdb->prefix . 'cv_request';
        $prepare = $wpdb->prepare( "SELECT * FROM $interest_tb WHERE type = %s AND status = %s", 'apply', 'pending' );
        $rows    = $wpdb->get_results( $prepare );

        $infos = [];
        foreach ($rows as $row) {
            $infos[] = (object)[
                'candidate' => new includes\post\Candidate( (int) $row->id_candidate ),
                'offer'     => new includes\post\Offers( (int) $row->id_offer ),
                'date'      => $row->date_add
            ];
        }
        return $infos;
    }

    public function getPendingCV() {
        global $wpdb;
        $return = [];
        $sql = "SELECT * FROM $wpdb->posts pts WHERE pts.post_type = %s AND pts.post_status = %s
        AND pts.ID IN (SELECT pta.post_id as post_id FROM $wpdb->postmeta pta WHERE pta.meta_key = 'itjob_cv_hasCV' AND pta.meta_value = 1)
        AND pts.ID IN (SELECT pta.post_id as post_id FROM $wpdb->postmeta pta WHERE pta.meta_key = 'activated' AND pta.meta_value = 0)";
        $prepare = $wpdb->prepare($sql , 'candidate', 'pending');
        $candidates = $wpdb->get_results( $prepare );
        foreach ($candidates as $candidate) {
            // Vérifier si l'utilisateur est un candidat
            $Candidate = new includes\post\Candidate((int) $candidate->ID);
            $name = $Candidate->getFirstName() . ' ' . $Candidate->getLastName();
            $return[] = ['reference' => $Candidate->reference, 'name' => $name];
        }

        return $return;
    }
}