<?php

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class KivishopApiResourceVersion extends ApiResource
{
    public function get()
    {
        $result = new \stdClass;
        $result->version = 0;
        $result->revision = 1;
        $this->plugin->setResponse( $result );
    }
}
