<?php

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');


class plgAPIKivishop extends ApiPlugin {

    public function __construct(&$subject, $config = array()) {

        parent::__construct($subject, $config = array());

        // Set resource path
        ApiResource::addIncludePath(dirname(__FILE__).'/kivishop');

        // Load language files
        //$lang = JFactory::getLanguage();
        //$lang->load('com_users', JPATH_ADMINISTRATOR, '', true);

        // Set the login resource to be public
        //$this->setResourceAccess('login', 'public', 'get');
        //$this->setResourceAccess('login', 'public', 'post');
    }
}
