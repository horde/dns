<?php

/**
 * A DNS Zone with all required attributes, but no backend handling
 * This is mostly a data object which holds attributes encapsulated behind sanity-checked setters
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Diana Hille <hille@b1-systems.de>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 *
 * @category   Horde
 * @package horde-dns
 */
class Horde_DNS_Zone
{
    //The attributes array
    //TODO: Just use an empty array
    protected $_attributes = array(
        'zone_id' => '',
        'name' => '',
        'domain' => '',
        'ttl' => '',
        'primary_server' => '',
        'ip_adress' => '',
        'mail' => '',
        'serial' => '',
        'refresh' => '',
        'retry' => '',
        'expire' => '',
        'min_ttl' => ''
    );

    /**
     * The Constructor, creates and validates the attribute array of the zone
     * TODO: The validation should be wrapped in another function
     * TODO: Don't use an empty array as default. A new Zone should always have attributes, but check if empty and throw exception
     *
     * @param array $attributeArray The definition of the attributes for a zone
     */
    public function __construct($attributes = array())
    {
        /*if (empty($attributes['serial']) || !is_numeric($attributes['serial'])) {
            throw new Horde_Exception(sprintf("%s is not a valid serial number",$attributes['serial']));
        }

        if (empty($attributes['ttl']) || $this->formatVerification($attributes['ttl']) == false) {
            throw new Horde_Exception(sprintf("%s is not a valid TTL format",$attributes['ttl']));
        }

        if (empty($attributes['refresh']) || $this->formatVerification($attributes['refresh']) == false) {
            throw new Horde_Exception(sprintf("%s is not a valid refresh format",$attributes['refresh']));
        }

        if (empty($attributes['retry']) || $this->formatVerification($attributes['retry']) == false) {
            throw new Horde_Exception(sprintf("%s is not a valid retry format",$attributes['retry']));
        }

        if (empty($attributes['expire']) || $this->formatVerification($attributes['expire']) == false) {
            throw new Horde_Exception(sprintf("%s is not a valid expire format",$attributes['expire']));
        }

        if (empty($attributes['min_ttl']) || $this->formatVerification($attributes['min_ttl']) == false) {
            throw new Horde_Exception(sprintf("%s is not a valid min_ttl format",$attributes['min_ttl']));
        }

        if (empty($attributes['ip_adress']) || (!filter_var($attributes['ip_adress'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($attributes['ip_adress'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
            throw new Horde_Exception(sprintf("%s is not a valid ip adress",$attributes['ip_adress']));
        }*/

        if (empty($attributes['name']) /*|| empty($attributes['primary_server']) || empty($attributes['mail'])*/) {
            throw new Horde_Exception("An empty value is not allowed for name");
        }

        //TODO: Check if there is a more simplified process to this
        $this->_attributes['zone_id']        = $attributes['zone_id'];
        $this->_attributes['name']           = $attributes['name'];
        $this->_attributes['domain']         = $attributes['domain'];
        $this->_attributes['ttl']            = $attributes['ttl'];
        $this->_attributes['primary_server'] = $attributes['primary_server'];
        $this->_attributes['ip_adress']      = $attributes['ip_adress'];
        $this->_attributes['mail']           = str_replace('@', '.', $attributes['mail']); //The @ is used as global identifier within a zonefile
        $this->_attributes['serial']         = $attributes['serial'];
        $this->_attributes['refresh']        = $attributes['refresh'];
        $this->_attributes['retry']          = $attributes['retry'];
        $this->_attributes['expire']         = $attributes['expire'];
        $this->_attributes['min_ttl']        = $attributes['min_ttl'];
    }

    /**
     * The validator for the time formats
     * TODO: The validation should be wrapped in another function
     * TODO: Don't use an empty array as default. A new Zone should always have attributes, but check if empty and throw exception
     *
     * @param array $attributeArray The definition of the attributes for a zone
     */
    public function formatVerification($attribute)
    {
        $rightFormat = true;
        $timeVars = array("s","m","h","d","w", "S", "M", "H", "D", "W");
        if (!is_numeric(str_replace($timeVars, '', $attribute))) {
             $rightFormat = false;
        }

        //TODO: Use better variable names
        for ($i = 0; $i < sizeof($timeVars); $i++) {
            if (substr_count($attribute, $timeVars[$i]) > 1) {
                $rightFormat = false;
            }
        }

        return $rightFormat;
    }

    /**
     * Magic method for getting the class attributes
     *
     * @param string $attribute The name of an attribute
     */
    public function __get($attribute)
    {
        if (isset($this->_attributes[$attribute])) {
            return $this->_attributes[$attribute];
        } else {
            throw new Horde_Exception(sprintf("%s is not a valid attribute",$attribute));
        }
    }

    /**
     * Magic method for setting the class attributes
     *
     * @param string $attribute The name of an attribute
     * @param mixed $value The value to set the class attribute to
     */
    public function __set($attribute, $value)
    {
        $method = 'set' . ucfirst($attribute);
        if (method_exists($this, $method)) {
            return call_user_func(array($this, $method), $value);
        } else {
            //TODO: Check if attribute exists
            $this->_attributes[$attribute] = $value;
        }
    }

    /**
     * Magic method for checking if attribute is set
     *
     * @param string $attribute The name of an attribute
     */
    public function __isset($attribute) {
        if (isset($this->_attributes[$attribute])) {
            return (false === empty($this->_attributes[$attribute]));
        } else {
            return null;
        }
    }
}
