<?php
if (!defined('ABSPATH')) {
    exit;
}

trait ModelAds
{

    private $Table;

    public function __construct()
    {
        global $wpdb;
        $this->Table = $wpdb->prefix . 'publicity';
    }

    public function get_ads_by_position($position, $paid = 1)
    {
        global $wpdb;
        if (!empty($position)) return false;
        $query = "SELECT * FROM {$this->Table} WHERE position = %s AND paid = %d";
        $ads = $wpdb->get_results($wpdb->prepare($query, $position, $paid));

        return $ads;

    }

    public function get_ads($id_ads)
    {
        global $wpdb;
        if (!is_numeric($id_ads)) return false;
        $ads = $wpdb->get_row("SELECT * FROM {$this->Table} WHERE id_ads = {$id_ads}");

        return $ads;
    }

}