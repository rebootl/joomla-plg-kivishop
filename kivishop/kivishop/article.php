<?php
defined('_JEXEC') or die( 'Restricted access' );

// initiate VM
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('VmConfig'))
    require(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS .
            'com_virtuemart' . DS . 'helpers' . DS . 'config.php');

/*** helper functions ***/

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

/*** api resource ***/

class KivishopApiResourceArticle extends ApiResource {

    public function get() {
        /* get VirtueMart article/product information

           request parameter:
           - anum      article number

           the kivishop interface only needs a few fields,
           so currently only these are implemented

           return (as JSON response):
           - product    obj.    product informations
           - found      bool    product found
           - success    bool    request successful
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
        /* create or update a VirtueMart article/product

           gets the anum parameter from the URL (GET) and checks if
           that article already exists

           if yes it updates it, else it creates a new article

           (com_api does not provide a PUT request method)

           parameters:

            -> use only POST later ?!...

           - GET anum:  article number
           - POST Content/Body:  article data as JSON string.
                parameters:

           return (as JSON response):

           - vm_product_id  int/false
           - success        bool        creation successful
        */

        $res = $this->update_create_article();

        if ($res) {
            //print("Info: Creating/Updating article successful!\n");
            //print("Info: VM product Id: " . $res . "\n");
            JLog::add("Creating/Updating article succesful!",
                JLog::INFO, "com_api plg_kivishop");
            JLog::add("VM product Id: $res", JLog::INFO, "com_api plg_kivishop");
            $success = true;
        } else {
            //print("Warning: Creating/Updating article failed :(...\n");
            JLog::add("Creating/Updating article failed :(...",
                JLog::WARNING, "com_api plg_kivishop");
            $success = false;
        }

        // create and set result
        $result = new \stdClass;
        $result->vm_product_id = $res;
        $result->success = $success;
        // (the result is JSON encoded by below)
        $this->plugin->setResponse($result);
    }

    protected function update_create_article() {
        /* create/update article based on POST input

           returns vm_product_id on success or false
        */

        // get requested article number
        $app = JFactory::getApplication();
        $anum_input = $app->input->get('anum', "0", 'STRING');

        // check it
        if ($vm_product_id === "0") {
            JLog::add("No article No. supplied, aborting...",
                JLog::INFO, "com_api plg_kivishop");
            return false;
        }

        // get VM product Id for art. no.
        $vm_product_id = get_vmid_by_anum($anum_input);

        // get JSON/POST data
        $product_data = json_decode(file_get_contents('php://input'), true);

        // load VM config
        //-> move that up maybe, into constructor maybe?
        VmConfig::loadConfig();

        if ($vm_product_id === null) {
            JLog::add("Creating new article!", JLog::INFO, "com_api plg_kivishop");
            $res = $this->create_new_article($product_data);
        } else {
            JLog::add("Updating article No.: $anum_input",
                JLog::INFO, "com_api plg_kivishop");
            $res = $this->update_article();
        }
        return $res;
    }

    protected function create_new_article($product_data) {
        /* creates a new VM product

           returns vm_product_id on success or false

           JSON payload parameters:

           mandatory:
            'product_sku',
            'product_name',
            'product_s_desc',
            'product_desc',

           additional:
            'product_weight',
            'product_weight_uom',
            'product_length',
            'product_width',
            'product_height',
            'product_lwh_uom',
            'product_in_stock',
            'virtuemart_manufacturer_id',

            'product_price'

            'categories'                    ARRAY

        */

        if (!isset($product_data)) {
            //print("Error: Getting 'article_data' failed, aborting creation...\n");
            JLog::add("Getting 'article_data' failed, aborting creation...",
                JLog::WARNING, "com_api plg_kivishop");
            return false;
        }

        // load an empty product and populate it
        $product_model = VmModel::getModel('Product');
        // this creates an empty product obj.
        $product = $product_model->getProductSingle("0");

        // debug
        //var_dump($product);

        /* some probably useful contents of the product array:
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
            virtuemart_product_price_id         -> ??
            selectedPrice                       -> set to "0" for now
            allPrices
                product_price
                virtuemart_product_price_id
                product_currency
            categories
        */

        // minimally required parameters
        // define a set of simple parameters and set them
        // simple in this context means basically strings (as opposed to list
        // parameters)
        $req_simple_params = [
            'product_sku',
            'product_name',
            'product_s_desc',
            'product_desc',
        ];

        foreach ($req_simple_params as $p) {
            $param_value = $product_data[$p];
            if (!isset($param_value)) {
                //print("Error: Required parameter '$p' not found, aborting storing...\n");
                JLog::add("Required parameter '$p' not found, aborting storing...",
                    JLog::WARNING, "com_api plg_kivishop");
                return false;
            }
            $product->$p = $param_value;
        }

        // set the slug
        // the vm store function should create a slug, but for some
        // reason it seems to fail if there is no slug provided...
        // try to provide the product name
        $product->slug = $product_data['product_name'];

        // additional simple parameters
        $add_simple_params = [
            'product_weight',
            'product_weight_uom',
            'product_length',
            'product_width',
            'product_height',
            'product_lwh_uom',
            'product_in_stock',
            'virtuemart_manufacturer_id',
        ];

        foreach ($add_simple_params as $p) {
            $param_value = $product_data[$p];
            if (isset($param_value)) {
                $product->$p = $param_value;
            }
        }

        // price(s)
        // use selectedPrice = 0 by default for now
        $product->selectedPrice = "0";
        // the store function uses an mprices array to get the prices
        //   product->mprices->product_price->product_price
        if (isset($product_data['product_price'])) {
            $product->mprices = [];
            $product->mprices['product_price'] = [
                product_price => $product_data['product_price']
            ];
        }

        // categories
        if (isset($product_data['categories']) &&
            is_array($product_data['categories'])) {
            $product->categories = $product_data['categories'];
        }

        // debug outputs
        //var_dump($product->mprices);
        //var_dump($product);

        // store

        // get a form token and add it to the request
        // this is necessary to override the vRequest::vmCheckToken(); check,
        // if I understand it correctly this is a CSRF token, which means
        // it's protecting against a session based attack, since we're
        // not using a session at all i think it should be okay
        // also I see no other way to do it since there is no form or
        // prior request where we could send the token in the first place
        // also see: http://forum.virtuemart.net/index.php?topic=142610.0
        $_REQUEST['token'] = JSession::getFormToken();
        // debug
        //print("Debug: $_REQUEST:\n");
        //var_dump($_REQUEST);

        $res = $product_model->store($product);
        // debug
        //print("Debug: value: " . $res . "\n");
        //print("Debug: value type: " . gettype($res) . "\n");
        return $res;
    }

    protected function update_article() {

    }

}
