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
            $this->setProductArray();
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

    public function setProductArray() {
        // get product and assemble res. arr.

        // load VM config
        VmConfig::loadConfig();
        // get product model
        $productModel = VmModel::getModel('Product');
        // get product by id
        // -> use getProductSingle instead ?
        $product = $productModel->getProductSingle($this->vm_product_id);

        // debug
        var_dump($product);

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
}
