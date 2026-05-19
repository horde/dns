<?php

/**
 * The Zone RDO Mapper
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
class Horde_DNS_Backend_Rdo_ZoneMapper extends Horde_Rdo_Mapper
{
    protected $_classname = 'Horde_DNS_Backend_Rdo_ZoneEntity';
    protected $_table = 'horde_dns_zones';
    protected $_lazyRelationships = array(
        'records' => array('type' => Horde_Rdo::ONE_TO_MANY,
                         'foreignkey' => 'name',
                         'mapper' => 'Horde_DNS_Backend_Rdo_RecordMapper')
    );
}
