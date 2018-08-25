<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 16/08/2018
 * Time: 15:44
 */

interface iCompany {
  public static function getAllCompany( $paged );

  public function is_company();

  public function updateCompany();

  public function removeCompany();
}