<?php
defined('_JEXEC') or die( 'Restricted access' );


class KivishopApiResourceLogin extends ApiResource
{
    // DUMMY TEST STUFF...

    public function get()
    {
        $result = new \stdClass;
        $result->id = 45;
        $result->name = "John Doe";
         
        $this->plugin->setResponse( $result );
    }

    public function post()
    {
        // Add your code here
        $result = new \stdClass;

        $result->foo = "BAZLFOOGAGATUTUTU";

        var_dump($this);

        $this->plugin->setResponse( $result );
    }
}

