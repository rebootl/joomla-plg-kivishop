<?php
defined('_JEXEC') or die( 'Restricted access' );

// initiate VM
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('VmConfig'))
    require(JPATH_ROOT . DS . 'administrator' . DS .
            'components' . DS . 'com_virtuemart' . DS .
            'helpers' . DS . 'config.php');
if (!class_exists('VmModel'))
    require(VMPATH_ADMIN . DS . 'helpers' . DS . 'vmmodel.php');

if (!class_exists('VirtueMartModelProduct'))
    require(VMPATH_ADMIN . DS . 'models' . DS . 'product.php');


function get_vmid_by_anum($anum_input) {
    // get vm product id by article number input
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    /* example from: https://docs.joomla.org/Secure_coding_guidelines
       $query = 'SELECT * FROM #__table WHERE `field` = ' .
           $db->quote( $field );

       more examples:
       - https://docs.joomla.org/Selecting_data_using_JDatabase

       db query
       ========
       SELECT virtuemart_product_id FROM #__virtuemart_products
       WHERE product_sku = ?
    */
    $query
        ->select($db->quoteName('virtuemart_product_id'))
        ->from($db->quoteName('#__virtuemart_products'))
        ->where($db->quoteName('product_sku') . ' = ' .
            $db->quote($anum_input));

    $db->setQuery($query);
    return $db->loadResult();
}

class KivishopApiResourceArticle extends ApiResource {

    public function get() {
        /* response for the get_article function

           request parameter: anum      article number

           -> apparentry only a few fields are actually needed, so
              implementing only these w/ the respective mapping should
              be easy
        */

        // get requested article number
        $app = JFactory::getApplication();
        $anum_input = $app->input->get('anum', "0", 'STRING');

        // debug
        //print("id: $vm_product_id\n");

        // the VM sortSearchListQuery method seems overly complicated to
        // search for the product, doing it manually here instead

        $this->vm_product_id = get_vmid_by_anum($anum_input);

        // debug
        //print("VM PROD ID: $this->vm_product_id\n");

        if ($this->vm_product_id === null) {
            // set empty result arr.
            /* (using obj. here because setResult creates an obj.
                in json output below...) */
            $this->prod_array = new stdClass();
            $found = false;
        } else {
            $this->set_product_array();
            $found = true;
        }

        // create and set result
        $result = new \stdClass;
        $result->product = $this->prod_array;
        $result->found = $found;
        $result->success = true;
        // (the result is JSON encoded by below)
        $this->plugin->setResponse($result);
    }

    protected function set_product_array() {
        // get product and assemble res. arr.

        // load VM config
        VmConfig::loadConfig();
        // get product model
        $productModel = VmModel::getModel('Product');
        // get product by id
        // -> use getProductSingle instead ?
        $product = $productModel->getProductSingle($this->vm_product_id);

        // debug
        //var_dump($product);

        /* SL/Controller/ShopPart.pm

           sub action_show_stock uses:
             $stock_onlineshop = $shop_article->{data}
                                 ->{mainDetail}->{inStock};
             $active_online = $shop_article->{data}->{active};

           sub action_get_n_write_categories uses:
             $online_cat = $online_article->{data}->{categories};
             $active->{active} = $online_article->{data}->{active};
        */

        $categories = [];
        foreach ($product->categoryItem as $cat_item) {
            $cat_arr = [
                "id"    => $cat_item["virtuemart_category_id"],
                "name"  => $cat_item["category_name"]
            ];
            array_push($categories, $cat_arr);
        }
        // debug
        //var_dump($categories);

        $this->prod_array = [
            "virtuemart_prod_id"    => $product->virtuemart_product_id,
            "art_num"               => $product->product_sku,
            "name"                  => $product->product_name,
            "mainDetail"            => [
               "inStock" => $product->product_in_stock
            ],
            "active"                => (bool) $product->published,
            "categories"            => $categories,
        ];
    }

    public function post() {
        /* post, response for the update_part function
           - can either be to update an article or create a new one
             (since com_api does not provide a put() method)

           parameters:
           - GET anum:  article number
           - POST ...:  article data as JSON obj.

            1) check whether article exists or not
                yes -> update article
                no  -> create new article
        */

        // get requested article number
        $app = JFactory::getApplication();
        $anum_input = $app->input->get('anum', "0", 'STRING');

        $this->vm_product_id = get_vmid_by_anum($anum_input);

        // load VM config
        VmConfig::loadConfig();

        if ($this->vm_product_id === null) {
            $this->create_new_article();
        } else {
            $this->update_article();
        }
    }

    protected function create_new_article() {
        // -> retrieve and check for some minimal parameters
        // -> load an empty product and populate it
        // -> store it to db

        VmConfig::loadConfig();
        //$product_model = VmModel::getModel('Product');
        $product_model = new VirtueMartModelProduct;
        // (this creates an empty product obj.)
        $product = $product_model->getProductSingle("0");

        // debug
        //var_dump($product);

        // -> update product information here !!!

        /*
            product_sku
            product_name
            product_s_desc
            product_desc
            product_weight
            product_weight_uom
            product_length
            product_width
            product_height
            product_lwh_uom
            product_in_stock
            virtuemart_manufacturer_id
            virtuemart_product_price_id
            selectedPrice
            allPrices
                product_price
                virtuemart_product_price_id
                product_currency
            categories
        */
        $product->product_sku = "009001";
        $product->product_name = "Test Article Kivishop 1";
        $product->product_s_desc = "a, b, c, d";
        $product->product_desc = "long text, bla bla bla";
        /*$product->allPrices = [
            product_price => "5000.00"
        ];*/
        //$product->categories = [ 1, 2 ];

        $res = $product_model->store($product);
        //$res = $this->store($product);

        print("RES: $res");

    }

    protected function update_article() {

    }

    /* store function copied here to debug,
       from administrator/components/com_virtuemart/models/product.php

       calling it from the model does not work, maybe perm. issue?!

       -> evtl. adapt and use it here
       -> evtl. better inherit and override class method
    */
    protected function store(&$product) {
        print("FOOOOOOFOOOOOFOOOOOO");

        $product_model = VmModel::getModel('Product');

        // this seems to cause an "API Plugin not found" error
        // commented out
        //vRequest::vmCheckToken();

        // -> verify if this actually works
        if (!vmAccess::manager('product.edit')) {
            vmError('You are not a vendor or administrator, storing of product cancelled');
            return FALSE;
        }

        if ($product) {
            $data = (array)$product;
        }

        $isChild = FALSE;

        if(!empty($data['isChild'])) $isChild = $data['isChild'];

        if (isset($data['intnotes'])) {
            $data['intnotes'] = trim ($data['intnotes']);
        }

        // Setup some place holders
        $product_data = $product_model->getTable('products');
/*
        $data['new'] = '1';
        if(!empty($data['virtuemart_product_id'])){
            $product_data -> load($data['virtuemart_product_id']);
            $data['new'] = '0';
        }

                if( (empty($data['virtuemart_product_id']) or empty($product_data->virtuemart_product_id)) and !vmAccess::manager('product.create')){

                        vmWarn('Insufficient permission to create product');

                        return false;

                }

                $vendorId = vmAccess::isSuperVendor();

                $vM = VmModel::getModel('vendor');

                $ven = $vM->getVendor($vendorId);

                if(VmConfig::get('multix','none')!='none' and !vmAccess::manager('core')){

                        if($ven->max_products!=-1){

                                $this->setGetCount (true);

                                //$this->setDebugSql(true);

                                parent::exeSortSearchListQuery(2,'virtuemart_product_id',' FROM #__virtuemart_products',' WHERE ( `virtuemart_vendor_id` = "'.$vendorId.'" AND `published`="1") ');

                                $this->setGetCount (false);

                                if($ven->max_products<($this->_total+1)){

                                        vmWarn('You are not allowed to create more than '.$ven->max_products.' products');

                                        return false;

                                }

                        }

                }

                if($ven->force_product_pattern>0 and empty($data['product_parent_id'])){

                        $data['product_parent_id'] = $ven->force_product_pattern;

                }

                if(!vmAccess::manager('product.edit.state')){

                        if( (empty($data['virtuemart_product_id']) or empty($product_data->virtuemart_product_id))){

                                $data['published'] = 0;

                        } else {

                                $data['published'] = $product_data->published;

                        }

                }

                //Set the decimals like product packaging

                foreach(self::$decimals as $decimal){

                        if (array_key_exists ($decimal, $data)) {

                                if(!empty($data[$decimal])){

                                        $data[$decimal] = str_replace(',','.',$data[$decimal]);

                                        //vmdebug('Store product '.$data['virtuemart_product_id'].', set $decimal '.$decimal.' = '.$data[$decimal]);

                                } else {

                                        $data[$decimal] = null;

                                        $product_data->$decimal = null;

                                        //vmdebug('Store product '.$data['virtuemart_product_id'].', set $decimal '.$decimal.' = null');

                                }

                        }

                }

                //We prevent with this line, that someone is storing a product as its own parent

                if(!empty($product_data->product_parent_id) and $product_data->product_parent_id == $data['virtuemart_product_id']){

                        $product_data->product_parent_id = 0;

                        unset($data['product_parent_id']);

                }

                JPluginHelper::importPlugin('vmcustom');

                JPluginHelper::importPlugin('vmextended');

                $dispatcher = JDispatcher::getInstance();

                $dispatcher->trigger('plgVmBeforeStoreProduct',array(&$data, &$product_data));

                $stored = $product_data->bindChecknStore ($data, false);

                if(!$stored ){

                        vmError('You are not an administrator or the correct vendor, storing of product cancelled');

                        return FALSE;

                }

                $this->_id = $data['virtuemart_product_id'] = (int)$product_data->virtuemart_product_id;

                if (empty($this->_id)) {

                        vmError('Product not stored, no id');

                        return FALSE;

                }

                //We may need to change this, the reason it is not in the other list of commands for parents

                if (!$isChild) {

                        $modelCustomfields = VmModel::getModel ('Customfields');

                        $modelCustomfields->storeProductCustomfields ('product', $data, $product_data->virtuemart_product_id);

                }

                // Get old IDS

                $old_price_ids = $this->loadProductPrices($this->_id,array(0),false);

                if (isset($data['mprices']['product_price']) and count($data['mprices']['product_price']) > 0){

                        foreach($data['mprices']['product_price'] as $k => $product_price){

                                $pricesToStore = array();

                                $pricesToStore['virtuemart_product_id'] = $this->_id;

                                $pricesToStore['virtuemart_product_price_id'] = (int)$data['mprices']['virtuemart_product_price_id'][$k];

                                if (!$isChild){

                                        //$pricesToStore['basePrice'] = $data['mprices']['basePrice'][$k];

                                        $pricesToStore['product_override_price'] = $data['mprices']['product_override_price'][$k];

                                        $pricesToStore['override'] = isset($data['mprices']['override'][$k])?(int)$data['mprices']['override'][$k]:0;

                                        $pricesToStore['virtuemart_shoppergroup_id'] = (int)$data['mprices']['virtuemart_shoppergroup_id'][$k];

                                        $pricesToStore['product_tax_id'] = (int)$data['mprices']['product_tax_id'][$k];

                                        $pricesToStore['product_discount_id'] = (int)$data['mprices']['product_discount_id'][$k];

                                        $pricesToStore['product_currency'] = (int)$data['mprices']['product_currency'][$k];

                                        $pricesToStore['product_price_publish_up'] = $data['mprices']['product_price_publish_up'][$k];

                                        $pricesToStore['product_price_publish_down'] = $data['mprices']['product_price_publish_down'][$k];

                                        $pricesToStore['price_quantity_start'] = (int)$data['mprices']['price_quantity_start'][$k];

                                        $pricesToStore['price_quantity_end'] = (int)$data['mprices']['price_quantity_end'][$k];

                                }

                                if (!$isChild and isset($data['mprices']['use_desired_price'][$k]) and $data['mprices']['use_desired_price'][$k] == "1") {

                                        $calculator = calculationHelper::getInstance ();

                                        if(isset($data['mprices']['salesPrice'][$k])){

                                                $data['mprices']['salesPrice'][$k] = str_replace(array(',',' '),array('.',''),$data['mprices']['salesPrice'][$k]);

                                        }

                                        $pricesToStore['salesPrice'] = $data['mprices']['salesPrice'][$k];

                                        $pricesToStore['product_price'] = $data['mprices']['product_price'][$k] = $calculator->calculateCostprice ($this->_id, $pricesToStore);

                                        unset($data['mprices']['use_desired_price'][$k]);

                                } else {

                                        if(isset($data['mprices']['product_price'][$k]) ){

                                                $pricesToStore['product_price'] = $data['mprices']['product_price'][$k];

                                        }

                                }

                                if ($isChild) $childPrices = $this->loadProductPrices($this->_id,array(0),false);

                                if ((isset($pricesToStore['product_price']) and $pricesToStore['product_price']!='' and $pricesToStore['product_price']!=='0') || (isset($childPrices) and count($childPrices)>1)) {

                                        if ($isChild) {

                                                if(is_array($old_price_ids) and count($old_price_ids)>1){

                                                        //We do not touch multiple child prices. Because in the parent list, we see no price, the gui is

                                                        //missing to reflect the information properly.

                                                        $pricesToStore = false;

                                                        $old_price_ids = array();

                                                } else {

                                                        unset($data['mprices']['product_override_price'][$k]);

                                                        unset($pricesToStore['product_override_price']);

                                                        unset($data['mprices']['override'][$k]);

                                                        unset($pricesToStore['override']);

                                                }

                                        }

                                        if($pricesToStore){

                                                $toUnset = array();

                                                if (!empty($old_price_ids) and count($old_price_ids) ) {

                                                        foreach($old_price_ids as $key => $oldprice){

                                                                if($pricesToStore['virtuemart_product_price_id'] == $oldprice['virtuemart_product_price_id'] ){

                                                                        $pricesToStore = array_merge($oldprice,$pricesToStore);

                                                                        $toUnset[] = $key;

                                                                }

                                                        }

                                                }

                                                $this->updateXrefAndChildTables ($pricesToStore, 'product_prices',$isChild);

                                                foreach($toUnset as $key){

                                                        unset( $old_price_ids[ $key ] );

                                                }

                                        }

                                }

                        }

                }

                if (!empty($old_price_ids) and count($old_price_ids) ) {

                        $oldPriceIdsSql = array();

                        foreach($old_price_ids as $oldPride){

                                $oldPriceIdsSql[] = $oldPride['virtuemart_product_price_id'];

                        }

                        $db = JFactory::getDbo();

                        // delete old unused Prices

                        $db->setQuery( 'DELETE FROM `#__virtuemart_product_prices` WHERE `virtuemart_product_price_id` in ("'.implode('","', $oldPriceIdsSql ).'") ');

                        $db->execute();

                        $err = $db->getErrorMsg();

                        if(!empty($err)){

                                vmWarn('In store prodcut, deleting old price error',$err);

                        }

                }

                if (!empty($data['childs'])) {

                        foreach ($data['childs'] as $productId => $child) {

                                if(empty($productId)) continue;

                                if($productId!=$data['virtuemart_product_id']){

                                        if(empty($child['product_parent_id'])) $child['product_parent_id'] = $data['virtuemart_product_id'];

                                        $child['virtuemart_product_id'] = $productId;

                                        if(!empty($child['product_parent_id']) and $child['product_parent_id'] == $child['virtuemart_product_id']){

                                                $child['product_parent_id'] = 0;

                                        }

                                        $child['isChild'] = $this->_id;

                                        $this->store ($child);

                                }

                        }

                }

                if (!$isChild) {

                        $data = $this->updateXrefAndChildTables ($data, 'product_shoppergroups');

                        $data = $this->updateXrefAndChildTables ($data, 'product_manufacturers');

                        $storeCats = true;

                        if (empty($data['categories']) or (!empty($data['categories'][0]) and $data['categories'][0]!="-2")){

                                $storeCats = true;

                        }

                        if($storeCats){

                                if (!empty($data['categories']) && count ($data['categories']) > 0) {

                                        if(VmConfig::get('multix','none')!='none' and !vmAccess::manager('managevendors')){

                                                if($ven->max_cats_per_product>=0){

                                                        while($ven->max_cats_per_product<count($data['categories'])){

                                                                array_pop($data['categories']);

                                                        }

                                                }

                                        }

                                        $data['virtuemart_category_id'] = $data['categories'];

                                } else {

                                        $data['virtuemart_category_id'] = array();

                                }

                                $data = $this->updateXrefAndChildTables ($data, 'product_categories');

                        }

                        // Update waiting list

                        //TODO what is this doing?

                        if (!empty($data['notify_users'])) {

                                if ($data['product_in_stock'] > 0 && $data['notify_users'] == '1') {

                                        $waitinglist = VmModel::getModel ('Waitinglist');

                                        $waitinglist->notifyList ($data['virtuemart_product_id']);

                                }

                        }

                        // Process the images

                        $mediaModel = VmModel::getModel ('Media');

                        $mediaModel->storeMedia ($data, 'product');

                }

                $cache = VmConfig::getCache('com_virtuemart_cat_manus','callback');

                $cache->clean();

                $dispatcher->trigger('plgVmAfterStoreProduct',array(&$data, &$product_data));

                return $product_data->virtuemart_product_id;
*/
        }

}
