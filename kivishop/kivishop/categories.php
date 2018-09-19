<?php

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');


class KivishopApiResourceCategories extends ApiResource
{
    public function get()
    {

        // initiate VM
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);
        if (!class_exists( 'VmConfig' )) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
        VmConfig::loadConfig();

        if (!class_exists( 'VmModel' )) require(VMPATH_ADMIN.DS.'helpers'.DS.'vmmodel.php');

        // get category model
        $categoryModel = VmModel::getModel('Category');

        //$category_id = $params->get('Parent_Category_id', 0);
        //$category_id = 0;
        //$vendor_id = 0;
        //$vendorId = 0;

        // get categories from VirtueMart
        //$categories = $categoryModel->getChildCategoryList($vendorId, $category_id);
        //$categories = $categoryModel->getChildCategoryList($vendor_id, 9);

        // get all categories
        $cat_tree = $categoryModel->getCategoryTree();

        //var_dump($cat_tree);

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

        print_r($categories);

        $result = new \stdClass;
        //$result->version = 0;
        //$result->revision = 1;
        $result->categories = json_encode($categories);

        $this->plugin->setResponse( $result );
    }
}
