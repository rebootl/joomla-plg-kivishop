<?php

// (from finder_indexer.php)
// Make sure we're being called from the command line, not a web interface
if (PHP_SAPI !== 'cli')
{
    die('This is a command line only application.');
}

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));

// Load system defines
if (file_exists(JPATH_BASE . '/defines.php'))
{
    require_once JPATH_BASE . '/defines.php';
}

if (!defined('_JDEFINES'))
{
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// from:
// - http://stackoverflow.com/questions/31397472/joomla-3-x-how-to-schedule-a-cron
// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

require_once JPATH_BASE . '/includes/framework.php';

// tests
// System configuration.
//$config = new JConfig;
// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

class UpdateShortdesc extends JApplicationCli
{
    public function execute()
    {
        $this->out('Hello World, Ho ho ho!');

        // include my generate shortdescr. function
        // function: generate_custom_shortdesc($customfields)
        //           return $shortdesc_str;
        require_once JPATH_BASE . '/administrator/templates/isis/html/com_virtuemart/product/autogen_shortdesc.php';

        //$config = new JConfig;

        //$config->host = '127.0.0.1';
        //echo $config->host;

        //$_SERVER['HTTP_HOST'] = 'domain.com';
        JFactory::getApplication('site');


        // BOOTSTRAP VM
        // --> from ...
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);
        if (!class_exists( 'VmConfig' )) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
        VmConfig::loadConfig();
        //VmConfig::showDebug('all');

        //if (!class_exists( 'VmController' )) require(VMPATH_ADMIN.DS.'helpers'.DS.'vmcontroller.php');
        if (!class_exists( 'VmModel' )) require(VMPATH_ADMIN.DS.'helpers'.DS.'vmmodel.php');


        // get all product ids ("manual" db conn.)
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('virtuemart_product_id'))
            ->from($db->quoteName('j34_virtuemart_products'));
        //    ->setLimit('100');
        $db->setQuery($query);

        // --> USE ARRAY HERE ????!!!!....
        $results = $db->loadObjectList();

        print_r($results);

        $product_model = VmModel::getModel('product');
        $customfields_model = VmModel::getModel('Customfields');

        foreach ($results as $res) {

            // retrieve product data
            //$product = $product_model->getProductSingle($product_id, FALSE);

            // retrieve custom fields
            //$product->allIds[] = $product->virtuemart_product_id;
            //$product->customfields = $customfields_model
            //    ->getCustomEmbeddedProductCustomFields($product->allIds);

            //print_r($res);
            //echo $res['virtuemart_product_id'];

            //$customfields = $customfields_model
            //    ->getCustomEmbeddedProductCustomFields($product_id);

            //print_r($customfields);

            /*if (!empty($product->customfields)) {
                echo "CUSTOMFIELD FOUND, product_id: ".$product->virtuemart_product_id."\n";

                //print_r($product->customfields);
            }*/
            /*    $shortdesc_str = generate_custom_shortdesc($customfields);

                echo "SHORTDESC: ".$shortdesc_str;

                // --> CONTINUE HERE (no second loop needed)

            }*/

        }

        foreach ($results as $product) {
            //echo "PRODUCT ID: ".$product->virtuemart_product_id."\n";
            if (!empty($product->customfields)) {
                echo "CUSTOMFIELD FOUND, product_id: ".$product->virtuemart_product_id."\n";
            //print_r($product->customfields);
            }
        }

        // MANUAL DB CONN

        // Get a db connection.
        //$db = JFactory::getDbo();

        // Create a new query object.
        //$query = $db->getQuery(true);

        // Select all records from the user profile table where key begins with "custom.".
        // Order it by the ordering field.
        /*$query->select($db->quoteName(array('user_id', 'profile_key', 'profile_value', 'ordering')));
        $query->from($db->quoteName('#__user_profiles'));
        $query->where($db->quoteName('profile_key') . ' LIKE '. $db->quote('\'custom.%\''));
        $query->order('ordering ASC');
        */
        /*$query
            ->select($db->quoteName('virtuemart_product_id'))
            ->from($db->quoteName('j34_virtuemart_products_de_de'))
            ->setLimit('10');
        */
        // Reset the query using our newly populated query object.
        //$db->setQuery($query);

        // Load the results as a list of stdClass objects (see later for more options on retrieving data).
        //$results = $db->loadObjectList();

        //print_r($results);

    }
}

//JApplicationCli::getInstance('HelloWorld')->execute();
try
{
    JApplicationCli::getInstance('UpdateShortdesc')->execute();
}
catch (Exception $e)
{
    fwrite(STDOUT, $e->getMessage() . "\n");
    exit($e->getCode());
}
