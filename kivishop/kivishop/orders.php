<?php
defined('_JEXEC') or die( 'Restricted access' );

// initiate VM
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('VmConfig'))
    require(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS .
            'com_virtuemart' . DS . 'helpers' . DS . 'config.php');

function get_new_orders($last_order_id, $limit) {
    /* check for new orders and return them if present
    */
    if ($last_order_id === 0) return [];

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //$query = 'SELECT virtuemart_order_id
    //  FROM #__virtuemart_orders
    //  WHERE virtuemart_order_id >= ' . $db->quote($last_order_id + 1) . '
    //  ORDER BY virtuemart_order_id DESC
    //  LIMIT ' . $db->quote($limit);
    //
    //->order($db->quoteName('virtuemart_order_id') . 'DESC')
    $query
        ->select($db->quoteName('virtuemart_order_id'))
        ->from($db->quoteName('#__virtuemart_orders'))
        ->where($db->quoteName('virtuemart_order_id') . ' >= ' . $db->quote($last_order_id + 1))
        ->setLimit($db->quote($limit));
    $db->setQuery($query);
    $new_orders_ids = $db->loadColumn();

    //print("Debug: new_orders_ids: \n");
    //var_dump($new_orders_ids);

    if (empty($new_orders_ids)) {
        // no new orders present
        // debug
        //print("Debug: No new orders present!\n");
        return [];
    }

    // create an array with the limit amount of new order numbers
    VmConfig::loadConfig();
    $order_model = VmModel::getModel('Orders');
    // (test output)
    //$order = $order_model->getMyOrderDetails($new_orders_ids[0]);
    //var_dump($order);

    $orders = [];
    foreach ($new_orders_ids as $order_id) {
        // debug
        //print("Debug: order_id: " . $order_id . "\n");

        $order = $order_model->getMyOrderDetails($order_id);
        array_push($orders, $order);
        // $order_arr = [
        //   "virtuemart_order_id"     => $order['details']['BT']->virtuemart_order_id,
        //   "virtuemart_user_id"      => $order['details']['BT']->virtuemart_user_id,
        //   "virtuemart_vendor_id"    => $order['details']['BT']->virtuemart_vendor_id,
        //   "order_number"            => $order['details']['BT']->order_number,
        //   "customer_number"         => $order['details']['BT']->customer_number,
        //   "order_total"             => $order['details']['BT']->order_total,
        //   "order_salesPrice"        => $order['details']['BT']->order_salesPrice,
        //   "order_billTaxAmount"     => $order['details']['BT']->order_billTaxAmount,
        //   "order_billTax"           => $order['details']['BT']->order_billTax,
        //   "order_billDiscountAmount" => $order['details']['BT']->order_billDiscountAmount,
        //   "order_discountAmount"    => $order['details']['BT']->order_discountAmount,
        //   "order_subtotal"          => $order['details']['BT']->order_subtotal,
        //   "order_tax"               => $order['details']['BT']->order_tax,
        //   "order_shipment"          => $order['details']['BT']->order_shipment,
        //   "order_shipment_tax"      => $order['details']['BT']->order_shipment_tax,
        //   "order_payment"           => $order['details']['BT']->order_payment,
        //   "order_payment_tax"       => $order['details']['BT']->order_payment_tax,
        //   "order_status"            => $order['details']['BT']->order_status
        // ];

        //print("Debug: order_arr: \n");
        //var_dump($order_arr);
    }
    return $orders;
}

class KivishopApiResourceOrders extends ApiResource {

    public function get() {
        /* get VirtueMart orders information

           for now returning orders "quick and dirty" as we get it from VirtueMart,
           the mapping has to be done on the kivitendo/ERP side, but since we
           have to map it there anyways, it makes little sense to do it twice

           request parameters (GET):
           - last_order_id       int    internal vm order id, last order id
                                        present in the ERP, will return orders
                                        from this id + 1 (!!)
                                        (defaults to 0)
           - limit               int    max number of orders to get (defaults to 10)

           return (as JSON response):
           - success            true/false request success
           - orders              array of orders
             - ..
             - ..
             - ..
        */

        // get parameters
        $app = JFactory::getApplication();
        $last_order_id = $app->input->get('last_order_id', 0, 'INT');
        $limit = $app->input->get('limit', 10, 'INT');

        // debug
        //print("Debug: last_order_id: " . $last_order_id . "\n");
        //print("Debug: limit: " . $limit . "\n");

        $res = get_new_orders($last_order_id, $limit);

        if (is_array($res)) {
            // set empty result arr.
            //
            // -> make warnings like for post article
            $success = true;
        } else {
            // -> make warnings like for post article
            $success = false;
            $res = [];
        }

        // create and set result
        $result = new \stdClass;
        $result->orders = $res;
        $result->success = $success;
        // (the result is JSON encoded by below)
        $this->plugin->setResponse($result);
    }
}
