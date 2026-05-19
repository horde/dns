<?php

/**
 * The base migration file for the zones and the records
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
class HordeDNSTable extends Horde_Db_Migration_Base
{
    public function up()
    {
        //Adding the Zone in the database with the necessary information
        //TODO: Add zone_ to every entry for convenience
        if (!in_array('horde_dns_zones', $this->tables())) {
            $t = $this->createTable('horde_dns_zones', array('autoincrementKey' => 'zone_id' ));
            $t->column('name', 'string', array('limit' => 255, 'null' => false));
            $t->column('domain', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 10, 'null' => false));
            $t->column('primary_server', 'string', array('limit' => 255, 'null' => false));
            $t->column('ip_adress', 'string', array('limit' => 255, 'null' => false));
            $t->column('mail', 'string', array('limit' => 255, 'null' => false));
            $t->column('serial', 'integer', array('limit' => 255, 'null' => false, 'default' => 100));
            $t->column('refresh', 'string', array('limit' => 10, 'null' => false));
            $t->column('retry', 'string', array('limit' => 10, 'null' => false));
            $t->column('expire', 'string', array('limit' => 10, 'null' => false));
            $t->column('min_ttl', 'string', array('limit' => 10, 'null' => false));
            $t->end();
        }

        //Adding the Record in the database with the necessary information
        //TODO: Add record_ to every entry for convenience
        if (!in_array('horde_dns_records', $this->tables())) {
            $t = $this->createTable('horde_dns_records', array('autoincrementKey' => 'record_id' ));
            $t->column('zone', 'string', array('limit' => 255, 'null' => false));
            $t->column('name', 'string', array('limit' => 255, 'null' => true));
            $t->column('ttl', 'integer', array('limit' => 10, 'null' => true));
            $t->column('class', 'string', array('limit' => 2, 'null' => true));
            $t->column('type', 'string', array('limit' => 10, 'null' => false));
            $t->column('special', 'string', array('limit' => 255, 'null' => true));
            $t->column('rdata', 'string', array('limit' => 255, 'null' => true));
            $t->column('length', 'integer', array('limit' => 255, 'null' => true));
            $t->end();
        }
    }

    public function down()
    {
        if (in_array('horde_dns_zones', $this->tables())) { $this->dropTable('horde_dns_zones'); }
        if (in_array('horde_dns_records', $this->tables())) { $this->dropTable('horde_dns_records'); }
    }
}
