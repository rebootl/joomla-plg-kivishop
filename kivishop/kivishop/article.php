<?php
defined('_JEXEC') or die( 'Restricted access' );

// initiate VM
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('VmConfig'))
    require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
if (!class_exists('VmModel'))
    require(VMPATH_ADMIN.DS.'helpers'.DS.'vmmodel.php');


class KivishopApiResourceArticle extends ApiResource {

    public function get() {
        // response for the get_article function
        //
        // - by default use the virtuemart product id !
        //   -> evtl. implement get by article number later, if needed
        //      (a search function would be needed for this)
        //
        // - mapping analog to the shopware mapping ?
        //   -> apparentry only a few fields are actually needed, so
        //      implementing only these w/ the respective mapping should
        //      be easy

        // get requested VirtueMart product id
        $app = JFactory::getApplication();
        $vm_product_id = $app->input->get('id', 0, 'INT');

        // debug
        print("id: $vm_product_id\n");


        // load VM config
        VmConfig::loadConfig();

        // get product model
        $productModel = VmModel::getModel('Product');

        // get product by id
        $product = $productModel->getProduct($vm_product_id);

        // get all categories
        //$cat_tree = $categoryModel->getCategoryTree();

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

        // create a new array w/ the data in a format similar to shopware
        // mapping:
        //
        // id               virtuemart_category_id
        // active           published
        // name             category_name
        // position         -
        // parentId         category_parent_id
        // childrenCount    -
        // articleCount     -

        //$categories = array();

        /*foreach ($cat_tree as $cat) {
            $cat_array = [
                "id"        => $cat->virtuemart_category_id,
                "active"    => $cat->published,
                "name"      => $cat->category_name,
                "parentId"  => $cat->category_parent_id,
            ];
            array_push($categories, $cat_array);
        }*/

        // debug
        //print_r($categories);

        // create and set result
        $result = new \stdClass;
        //$result->categories = json_encode($categories);
        //$result->categories = $categories;

        $this->plugin->setResponse($result);
    }
}
