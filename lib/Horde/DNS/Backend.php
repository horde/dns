<?php

/**
 * The interface defining the functions for the available backends
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
interface Horde_DNS_Backend
{
    public function addZone(Horde_DNS_Zone $zone);
    public function deleteZone(Horde_DNS_Zone $zone);
    public function addRecord(Horde_DNS_Record $record);
    public function updateRecord(Horde_DNS_Record $record);
    public function deleteRecord(Horde_DNS_Record $record);
}
