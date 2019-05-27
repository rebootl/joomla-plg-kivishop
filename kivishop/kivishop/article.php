<?php
defined('_JEXEC') or die( 'Restricted access' );

// initiate VM
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('VmConfig'))
    require(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS .
            'com_virtuemart' . DS . 'helpers' . DS . 'config.php');

function get_vmid_by_anum($anum_input) {
    /* get vm product id by article number input

       the VM sortSearchListQuery method seems kinda complicated
       to search for the product, doing it manually here instead
       -> maybe give it another try
    */
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

function get_product_array($vm_product_id) {
    /* get product by vm_product_id
       and assemble res. arr.
    */

    // load VM config
    VmConfig::loadConfig();
    // get product model
    $productModel = VmModel::getModel('Product');
    // get product by id
    $product = $productModel->getProductSingle($vm_product_id);

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

    return [
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

class KivishopApiResourceArticle extends ApiResource {

    public function get() {
        /* response for the get_article function

           request parameter: anum      article number

           apparentry only a few fields are actually needed, so
           implementing only these w/ the respective mapping should
           be easy
        */

        // get requested article number
        $app = JFactory::getApplication();
        $anum_input = $app->input->get('anum', "0", 'STRING');

        $vm_product_id = get_vmid_by_anum($anum_input);

        // debug
        //print("VM PROD ID: $vm_product_id\n");

        if ($vm_product_id === null) {
            // set empty result arr.
            /* (using obj. here because setResult creates an obj.
                in json output below...) */
            $prod_array = new stdClass();
            $found = false;
        } else {
            $prod_array = get_product_array($vm_product_id);
            $found = true;
        }

        // create and set result
        $result = new \stdClass;
        $result->product = $prod_array;
        $result->found = $found;
        $result->success = true;
        // (the result is JSON encoded by below)
        $this->plugin->setResponse($result);
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
            print("Debug: create new article...\n");
            $this->create_new_article();
        } else {
            $this->update_article();
        }
    }

    protected function create_new_article() {
        /* create a new article
           1) retrieve and check for some minimal parameters
           2) load an empty product and populate it
           3) store it to db
        */

        // retrieve and check for some minimal parameters
        // -> what are those ?

        // load an empty product and populate it
        //VmConfig::loadConfig(); // done above
        $product_model = VmModel::getModel('Product');
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
        $product->product_sku = "009002";
        $product->product_name = "Test Article Kivishop 2";
        // vm provides functions to create a slug, but for some
        // reason storing fails if there is no slug provided...
        // -> create a slug out of the product name and article number
        //$product->slug = "test-article-kivishop-1-009002";
        $product->slug = "test-article-kivishop-1-009002";
        $product->product_s_desc = "a, b, c, d";
        $product->product_desc = "long text, bla bla bla";
        //$product->product_weight = "5.2";
        //$product->product_weight_uom = "KG";
        //$product->product_length = "25.4";
        //$product->product_width = "20.3";
        //$product->product_height = "2.5";
        //$product->product_lwh_uom = "CM";
        //$product->product_in_stock = "5";
        //$product->virtuemart_manufacturer_id = "3";
        //$product->virtuemart_product_price_id = ;
        //$product->selectedPrice = "3000.0";
        $product->allPrices = [
            product_price => "5000.0"
        ];
        //$product->categories = [ 1, 2 ];

        // get a form token and add it to the request
        // this is necessary to override the vRequest::vmCheckToken(); check,
        // if I understand it correctly this is a CSRF token, which means
        // it's protecting against a session based attack, since we're
        // not using a session at all i think it should be okay
        // also I see no other way to do it since there is no form or
        // prior request where we could send the token in the first place
        // also see: http://forum.virtuemart.net/index.php?topic=142610.0
        $_REQUEST['token'] = JSession::getFormToken();
        //print("Debug: $_REQUEST:\n");
        //var_dump($_REQUEST);

        $res = $product_model->store($product);
        if ($res == true) print("Debug: result: TRUE");
        print("Debug: res: $res\n");
    }

    protected function update_article() {

    }

}
