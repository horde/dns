<?php

/**
 * A DNS Record with all required attributes, but no backend handling
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
class Horde_DNS_Record
{
    //The attributes array
    //TODO: Just use an empty array
    protected $_attributes = array(
        'record_id' => '',
        'zone' => '',
        'name' => '',
        'ttl' => '',
        'class' => '',
        'type' => '',
        'special' => '',
        'rdata' => '',
        'length' => ''
    );

    /**
     * The Constructor, creates and validates the attribute array of the record
     * TODO: The validation should be wrapped in another function
     * TODO: Don't use an empty array as default. A new Record should always have attributes, but check if empty and throw exception
     *
     * @param array $attributeArray The definition of the attributes for a record
     */
    public function __construct($attributeArray = array())
    {
        $supportedClasses = array(
            'IN',
            'CH',
            'HS'
        );

        if (!empty($attributeArray['class']) && !in_array($attributeArray['class'], $supportedClasses)) {
            throw new Horde_Exception(sprintf("%s is not a valid resource record class", $attributeArray['class']));
        }

        if (empty($attributeArray['ttl']) || !is_numeric($attributeArray['ttl'])) {
            throw new Horde_Exception(sprintf("%s is not in the right ttl format and cannot be empty",$attributeArray['ttl']));
        }

        if (!empty($attributeArray['length']) && !is_numeric($attributeArray['length'])) {
            throw new Horde_Exception(sprintf("%s must be a numeric value",$attributeArray['length']));
        }

        if (empty($attributeArray['zone'])) {
            throw new Horde_Exception("Zone cannot be empty");
        }

        if (empty($attributeArray['name'])) {
            throw new Horde_Exception("name cannot be empty");
        }

        if (empty($attributeArray['rdata'])) {
            throw new Horde_Exception("rdata cannot be empty");
        }

        $this->_attributes['record_id'] = $attributeArray['record_id'];
        $this->_attributes['zone'] = $attributeArray['zone'];
        $this->_attributes['name'] = $attributeArray['name'];
        $this->_attributes['ttl'] = $attributeArray['ttl'];
        $this->_attributes['class'] = $attributeArray['class'];
        $this->_attributes['special'] = $attributeArray['special'];
        $this->_attributes['rdata'] = $attributeArray['rdata'];
        $this->_attributes['length'] = $attributeArray['length'];
        $this->setType($attributeArray['type']);
    }

    /**
     * Validates and sets the type of the record
     * NOTE: AWS limits the possible Record types
     * TODO: Set private or protected
     *
     * @param string $type The type of the Record (like A, AAAA, ...)
     */
    public function setType($type)
    {
        /*$supportedTypes = array(
            'A',
            'AAAA',
            'AFSDB',
            'APL',
            'A6',
            'CAA',
            'CDNSKEY',
            'CDS',
            'CERT',
            'CNAME',
            'DHCID',
            'DLV',
            'DNAME',
            'DNSKEY',
            'DS',
            'GPOS',
            'HIP',
            'HINFO',
            'IPSECKEY',
            'ISDN',
            'KEY',
            'KX',
            'LOC',
            'MB',
            'MD',
            'MF',
            'MG',
            'MINFO',
            'MR',
            'MX',
            'NAPTR',
            'NS',
            'NSAP',
            'NSEC',
            'NSEC3',
            'NSEC3PARAM',
            'NULL',
            'NXT',
            'OPT',
            'PTR',
            'RP',
            'RRSIG',
            'SIG',
            'SOA',
            'SPF',
            'SRV',
            'SSHFP',
            'TA',
            'TKEY',
            'TLSA',
            'TSIG',
            'TXT',
            'WKS',
            'X25'
        );*/

        //SupportedTypes adjusted to the types usable by route53
        $supportedTypes = array(
            'SOA',
            'A',
            'TXT',
            'NS',
            'CNAME',
            'MX',
            'PTR',
            'SRV',
            'SPF',
            'AAAA'
        );

        if (!in_array($type, $supportedTypes)) {
            throw new Horde_Exception(sprintf("%s is not a valid resource record type",$type));
        }

        if ($type == 'A' && !filter_var($this->_attributes['rdata'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new Horde_Exception(sprintf("%s is not a valid ipv4 (required by type A)",$this->_attributes['rdata']));
        }

        if ($type == 'AAAA' && !filter_var($this->_attributes['rdata'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new Horde_Exception(sprintf("%s is not a valid ipv6 (required by type AAAA)",$this->_attributes['rdata']));
        }

        $this->_attributes['type'] = $type;
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
        }
        /* TODO: Throw exception on unknown attribute */
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
    public function __isset($attribute)
    {
        if (isset($this->_attributes[$attribute])) {
            return (false === empty($this->_attributes[$attribute]));
        } else {
            return null;
        }
    }
}
