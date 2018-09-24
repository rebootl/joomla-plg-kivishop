<?php
defined('_JEXEC') or die( 'Restricted access' );


class KivishopApiResourceVersion extends ApiResource
{
    public function get()
    {
        // get com_api version
        $comapi_xml_file = JPATH_SITE . '/administrator/components/com_api/api.xml';
        if (file_exists($comapi_xml_file)) {
            $comapi_xml = simplexml_load_file($comapi_xml_file);
            $comapi_ver = (string)$comapi_xml->version;
        } else {
            $comapi_ver = "UNKNOWN";
        }

        // get this plugins version
        $plg_xml_file = JPATH_SITE . '/plugins/api/kivishop/kivishop.xml';
        if (file_exists($plg_xml_file)) {
            $plg_xml = simplexml_load_file($plg_xml_file);
            $plg_ver = (string)$plg_xml->version;
        } else {
            $plg_ver = "UNKNOWN";
        }
        // debug
        //var_dump($plg_xml);

        // set results
        $result = new \stdClass;
        $result->joomla_ver = JVERSION;
        $result->com_api_ver = $comapi_ver;
        $result->kivishop_plg_ver = $plg_ver;

        $this->plugin->setResponse( $result );
    }
}
