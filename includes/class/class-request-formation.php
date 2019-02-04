<?php
namespace includes\object;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use includes\model\Model_Request_Formation;

final class WP_Request_Formation {
    public $ID;
    public $user_id = 0;
    public $formation_id = null;
    public $subject;
    public $topic; // Théme de la formation
    public $description;
    public $validated;
    public $disabled;
    public $concerned; // Array of ids (candidate)
    public $date_create;

    public function __construct( $request_formation_id = null ) {
        if (is_null($request_formation_id)) return false;
        $request = Model_Request_Formation::get_resources((int) $request_formation_id);
        if ($request) {
            $this->ID = (int)$request->ID;
            $this->user_id = (int)$request->user_id;
            $this->formation_id = is_null($request->formation_id) ? NULL : (int) $request->formation_id;
            $this->subject = apply_filters('the_title', $request->suject);
            $this->topic = $request->topic;
            $this->description = apply_filters('the_content', $request->description);
            $this->validated = boolval($request->validated);
            $this->disabled  = boolval($request->disabled);
            $this->concerned = unserialize($request->concerned);
            $this->date_create = $request->date_create;
        }
    }

    public function get_request_author() {
        if (is_numeric($this->user_id) && $this->user_id !== 0) {
            return new \WP_User($this->user_id);
        } else {
            return false;
        }
    }

    public function get_candidate_concerned() {
        if (is_array($this->concerned)) {
            $candidates = [];
            foreach ($this->concerned as $candidate_id) {
                $Candidate = new \includes\post\Candidate((int) $candidate_id);
                $candidates[] = $Candidate;
            }
            return $candidates;
        } else return [];
    }
    
}