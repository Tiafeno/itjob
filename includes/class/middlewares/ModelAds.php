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

  /**
   * @param $id_ads
   * @return array|bool|null|object|void
   */
  public function get_ads($id_ads)
    {
        global $wpdb;
        if (!is_numeric($id_ads)) return false;
        $ads = $wpdb->get_row("SELECT * FROM {$this->Table} WHERE id_ads = {$id_ads}");

        return $ads;
    }

  /**
   * @return array|null|object
   */
  public function collect_ads($length = 0, $offset) {
      global $wpdb;
    $sql = /** @lang sql */
      <<<SQL
SELECT ads.`id_ads`, ads.`title`, ads.`start`, ads.`end`, ads.`position`, pst.`post_title` as company, ads.`id_user`, 
 ads.`bill`, ads.`img_size`, ads.`id_attachment`, ads.`create`
  FROM $this->Table as ads 
  LEFT JOIN $wpdb->users as user ON user.ID = ads.id_user
  LEFT JOIN $wpdb->posts as pst ON pst.ID IN 
  (SELECT pm.post_id as ID FROM $wpdb->postmeta as pm WHERE pm.meta_key = 'itjob_company_email' AND pm.meta_value = user.user_email)
  LIMIT {$offset}, {$length}
SQL;
      $ads = $wpdb->get_results($sql);
      return $ads;
    }

  /**
   * @param $start integer
   * @param $end integer
   * @return array|null|object
   */
  public function get_beetween_ads($start, $end)
    {
        global $wpdb;
        $sql = /** @lang sql */
          <<<SQL
SELECT ads.`id_ads`, ads.`title`, ads.`start`, ads.`end`, ads.`position`, pst.`post_title` as company, ads.`id_user`, 
 ads.`bill`, ads.`img_size`, ads.`id_attachment`, ads.`create`
  FROM $this->Table as ads 
  LEFT JOIN $wpdb->users as user ON user.ID = ads.id_user
  LEFT JOIN $wpdb->posts as pst ON pst.ID IN 
  (SELECT pm.post_id as ID FROM $wpdb->postmeta as pm WHERE pm.meta_key = 'itjob_company_email' AND pm.meta_value = user.user_email)
  WHERE ads.start 
    BETWEEN CAST('$start' AS DATE) AND CAST('$end' AS DATE)
    OR ads.end BETWEEN CAST('$start' AS DATE) AND CAST('$end' AS DATE)
    OR ads.start <= CAST('$start' AS DATE) <= ads.end
SQL;
        $results = $wpdb->get_results($sql);
        return $results;
    }

}