<?php

/**
 * Factory Lock
 * NOTE: I don't think this is necessary anymore as this factory isn't used
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
class Horde_DNS_Factory_Lock extends Horde_Core_Factory_Base
{
    protected $_db = null;
    protected $_lock = null;

    public function create()
    {
        try {
            $this->_db = $this->_injector->getInstance('Horde_Db_Adapter');
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Db_Exception($e);
        }
        return new Horde_Lock_Sql(array('db' => $this->_db));
    }
}
