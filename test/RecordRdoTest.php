<?php

/**
 * The record unit test class
 * TODO: Check the validation
 * TODO: More Testing!
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

namespace Horde\Dns\Test;

use Horde_DNS_Zone;
use Horde_DNS_Record;

class RecordRdoTest extends TestBase
{
    //Test the zone array
    //TODO: Put this in a specified zone test class
    public function testZone()
    {
        $zoneArray = array(
            'zone_id' => 1,
            'name' => 'test-domain.com',
            'domain' => 'test-domain.com',
            'ttl' => '1w20h',
            'primary_server' => 'ns.test-domain.com',
            'ip_adress' => '92.228.155.39',
            'mail' => 'test@test-domain.com',
            'serial' => 100,
            'refresh' => 3600,
            'retry' => 3600,
            'expire' => 3600,
            'min_ttl' => 3600
        );
        $zone = new Horde_DNS_Zone($zoneArray);
        $this->assertEquals('test.test-domain.com', $zone->mail);
    }

    //Test the record array
    public function testRecord()
    {
        $recordArray = array(
            'record_id' => 1,
            'zone' => 'test-domain.com',
            'name' => 'ns.test-domain.com',
            'ttl' => '630000',
            'class' => 'HS',
            'type' => 'A',
            'special' => '',
            'rdata' => '92.228.155.39',
            'length' => 128
        );
        $record = new Horde_DNS_Record($recordArray);
        $this->assertEquals('A', $record->type);
    }
}
