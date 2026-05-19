<?php

/**
 * The Zone RDO Entity
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
class Horde_DNS_Backend_Rdo_ZoneEntity extends Horde_Rdo_Base
{
    /**
     * The magic method to return all properties as an array
     *
     * @return array The array of all class properties
     */
    public function toArray($lazy = false, $relationships = false)
    {
        return array(
            'zone_id'    => $this->zone_id,
            'name'       => $this->domain_name,
            'ttl'        => $this->ttl,
            'primary'    => $this->primary,
            'ip_adress'  => $this->ip_adress,
            'mail'       => $this->admin_mail,
            'serial'     => $this->serial,
            'refresh'    => $this->refresh,
            'retry'      => $this->retry,
            'expire'     => $this->expire,
            'min_ttl'    => $this->min_ttl,
        );
    }
}
