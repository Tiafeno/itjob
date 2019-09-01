<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 22/05/2019
 * Time: 17:04
 */

namespace includes\post;


use includes\model\paiementModel;

class PaiemetHistory {
  public $id = 0;
  public $date_create;
  public $data = [];

  private $object_id = 0;
  private $product_id = 0;
  private $order_id = 0;

  public $error = false;
  public function __construct($history_id) {
    if (!is_numeric($history_id)) {
      $this->error = new \WP_Error("error_type","L'id n'est pas de type numerique");
      return false;
    }
    $this->id = intval($history_id);

    $paiementMdl = new paiementModel();
    $result = $paiementMdl->get_history($history_id);
    $data = unserialize($result->data);
    $this->object_id = (int)$data['object_id'];
    $this->product_id = (int)$data['product_id'];
    $this->order_id = (int)$data['order_id'];

    $restProductController = new \WC_REST_Products_V2_Controller();
    $restOrderController = new \WC_REST_Orders_V2_Controller();
    $request = new \WP_REST_Request();
    $request->set_param('dp', '1'); // Chiffre apres virgule (les prix, tax)
    $request->set_param('context', 'view');

    $order = wc_get_order($this->order_id);
    $user_id = $order->get_customer_id();

    $this->data = [
      'object' => get_post($this->object_id),
      'product' => $restProductController->prepare_object_for_response(new \WC_Product($this->product_id), 'view')->data,
      'order' => $restOrderController->prepare_object_for_response(new \WC_Order($this->order_id), $request)->data
    ];

  }
}