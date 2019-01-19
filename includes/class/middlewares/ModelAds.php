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
        $this->Table = $wpdb->prefix . 'ads';
    }

    public function get_ads_by_position($position, $date, $paid = 1)
    {
        global $wpdb;
        if (empty($position)) return false;
        $query = "SELECT * FROM {$this->Table} as ads WHERE ads.position = %s AND ads.paid = %d
                    AND '%s' BETWEEN ads.start AND ads.end";
        $ads = $wpdb->get_results($wpdb->prepare($query, $position, $paid, $date));
;        return $ads;

    }

    public function get_ads($id_ads)
    {
        global $wpdb;
        if (!is_numeric($id_ads)) return false;
        $ads = $wpdb->get_row("SELECT * FROM {$this->Table} WHERE id_ads = {$id_ads}");

        return $ads;
    }

    public function get_beetween_ads($start, $end)
    {
        global $wpdb;
        $sql = "SELECT * FROM $this->Table as ads 
                    WHERE ads.start 
                    BETWEEN CAST('$start' AS DATE) AND CAST('$end' AS DATE)
                    OR ads.end BETWEEN CAST('$start' AS DATE) AND CAST('$end' AS DATE)
                    OR ads.start <= CAST('$start' AS DATE) <= ads.end";
        $results = $wpdb->get_results($sql);
        return $results;
    }

}