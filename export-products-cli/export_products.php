<?php

// (from finder_indexer.php)
// Make sure we're being called from the command line, not a web interface
if (PHP_SAPI !== 'cli') {
  die('This is a command line only application.');
}

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));

// Load system defines
if (file_exists(JPATH_BASE . '/defines.php')) {
  require_once JPATH_BASE . '/defines.php';
}

if (!defined('_JDEFINES')) {
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

class UpdateShortdesc extends JApplicationCli {

  public function execute() {

    // lultest
    $this->out('Hello World, Ho ho ho!');

    JFactory::getApplication('site');

    // initiate vm
    defined('DS') or define('DS', DIRECTORY_SEPARATOR);
    if (!class_exists( 'VmConfig' )) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
    VmConfig::loadConfig();
    //VmConfig::showDebug('all');

    //if (!class_exists( 'VmController' )) require(VMPATH_ADMIN.DS.'helpers'.DS.'vmcontroller.php');
    if (!class_exists( 'VmModel' )) require(VMPATH_ADMIN.DS.'helpers'.DS.'vmmodel.php');


    // get all product ids
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName('virtuemart_product_id'))
    ->from($db->quoteName('j34_virtuemart_products'))
    ->setLimit('1');
    $db->setQuery($query);

    // -> use array here?
    $results = $db->loadObjectList();

    var_dump($results);

    $product_model = VmModel::getModel('product');

    /* CSV Fields / Mapping:

    Kivitendo:

    bin 	Lagerplatz (Name)
    bin_id 	Lagerplatz (Datenbank-ID)
    buchungsgruppe 	Buchungsgruppe (name)
    buchungsgruppen_id 	Buchungsgruppe (database ID)
    classification_id
    description 	Beschreibung
    drawing 	Zeichnung
    ean 	EAN
    formel 	Formel
    gv 	Geschäftsvolumen
    has_sernumber 	Hat eine Serienummer
    image 	Grafik
    lastcost 	Einkaufspreis
    lastcost_X 	Einkaufspreis (X ist eine fortlaufende Zahl) [1]
    listprice 	Listenpreis
    make_X 	Lieferant (Datenbank-ID, Nummer oder Name des Lieferanten; X ist eine fortlaufende Zahl) [1]
    microfiche 	Mikrofilm
    model_X 	Lieferanten-Art-Nr. (X ist eine fortlaufende Zahl) [1]
    not_discountable 	Nicht rabattierfähig
    notes 	Bemerkungen
    obsolete 	Ungültig
    onhand 	Auf Lager [2]
    part_classification 	Artikel-Klassifizierung [3]
    part_type
    partnumber 	Artikelnummer
    partsgroup 	Warengruppe (Name)
    partsgroup_id 	Warengruppe (Datenbank-ID)
    payment 	Zahlungsbedingungen (Name)
    payment_id 	Zahlungsbedingungen (Datenbank-ID)
    price_factor 	Preisfaktor (Name)
    price_factor_id 	Preisfaktor (Datenbank-ID)
    rop 	Mindestlagerbestand
    sellprice 	Verkaufspreis
    shop 	Shopartikel
    type 	Artikeltyp [3]
    unit 	Einheit (falls nicht vorhanden oder leer wird die Standardeinheit benutzt)
    ve 	Verrechnungseinheit
    warehouse 	Lager (Name)
    warehouse_id 	Lager (database ID)
    weight 	Gewicht

    Mapping:

    description
    drawing
    ean             ??
    image
    make_X
    model_X
    obsolete        evtl?
    onhand
    partnumber
    partsgroup
    partsgroup_id
    sellprice
    shop            '1'
    type            'part'
    unit
    weight

    Custom Fields:


    */

    // use fputcsv to output csv
    // uses arrays
    // header/fields:
    $list = array(
      array(
        'description',
        'image',
        'make_1',
        'onhand',
        'partnumber',
        'partsgroup',
        'partsgroup_id',
        'sellprice',
        'shop',
        'type',
        'unit',
        'weight',
        'vm_product_weight_uom',
        'vm_product_length',
        'vm_product_width',
        'vm_product_height',
        'vm_product_lwh_uom',
        'vm_product_s_desc'
      )
    );

    foreach ($results as $res) {
      // debug print
      //print($res->virtuemart_product_id);

      // retrieve product data
      $product = $product_model->getProductSingle($res->virtuemart_product_id, FALSE);
      // debug print
      var_dump($product);

      $partlist = array(
        $product->product_desc,
        '<image path>',
        $product->mf_name,
        "$product->product_in_stock",
        $product->product_sku,
        '<partsgroup>',
        '<partsgroup_id>',
        $product->allPrices[0]['product_price'],
        '1',
        'part',
        $product->product_unit,
        $product->product_weight,
        $product->product_weight_uom,
        $product->product_length,
        $product->product_width,
        $product->product_height,
        $product->product_lwh_uom,
        $product->product_s_desc,
      );
      //var_dump($partlist);
      $list[] = $partlist;
    }
    // debug
    var_dump($list);
    // write csv
    $fp = fopen('products.csv', 'w');
    foreach ($list as $fields) {
      fputcsv($fp, $fields);
    }
    fclose($fp);
  }
}

try {
  JApplicationCli::getInstance('UpdateShortdesc')->execute();
}
catch (Exception $e) {
  fwrite(STDOUT, $e->getMessage() . "\n");
  exit($e->getCode());
}
