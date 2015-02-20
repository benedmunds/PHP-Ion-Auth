<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 8/18/14
 * Time: 5:23 PM
 */

namespace IonAuth\IonAuth\Config;

class Config
{
    function __construct(array $array)
    {
        $this->data = $array;
    }

    public function get($var)
    {
        if (isset($this->data[$var]))
        {
            return $this->data[$var];
        }

        throw new ConfigurationException("$var is not set in config object");
    }
}
