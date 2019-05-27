<?php
defined('_JEXEC') or die( 'Restricted access' );

// initiate VM
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('VmConfig'))
    require(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS .
            'com_virtuemart' . DS . 'helpers' . DS . 'config.php');

class KivishopApiResourceCategories extends ApiResource {

    public function get() {
        /* response for the get_categories function
           (analog to the shopware mapping)
        */

        // load VM config
        VmConfig::loadConfig();

        // get category model
        $categoryModel = VmModel::getModel('Category');

        // get all categories
        $cat_tree = $categoryModel->getCategoryTree();

        // debug
        //var_dump($cat_tree);

        /* create a new array w/ the data in a format similar to shopware
           mapping:

           id               virtuemart_category_id
           active           published
           name             category_name
           position         -
           parentId         category_parent_id
           childrenCount    -
           articleCount     -
        */

        $categories = array();

        foreach ($cat_tree as $cat) {
            $cat_array = [
                "id"        => $cat->virtuemart_category_id,
                "active"    => $cat->published,
                "name"      => $cat->category_name,
                "parentId"  => $cat->category_parent_id,
            ];
            array_push($categories, $cat_array);
        }

        // debug
        //print_r($categories);

        // create and set result
        $result = new \stdClass;
        $result->categories = $categories;
        $result->success = true;
        // (the result is JSON encoded by below)
        $this->plugin->setResponse($result);
    }
}
