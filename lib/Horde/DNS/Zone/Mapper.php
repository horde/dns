<?php

/**
 * The old Zone Mapper
 * NOTE: I don't think this is necessary anymore as this rdo class isn't used
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
class Horde_DNS_Zone_Mapper extends Horde_Rdo_Mapper
 {
    protected $_classname = 'Horde_DNS_Zone';
    protected $_table = 'horde_dns_zones';
    protected $_lazyRelationships = array(
        'records' => array('type' => Horde_Rdo::ONE_TO_MANY,
                         'foreignkey' => 'name',
                         'mapper' => 'Horde_DNS_Record_Mapper')
    );
}
