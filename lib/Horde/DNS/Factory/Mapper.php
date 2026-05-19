<?php
use Horde\Injector\Injector;

/**
 * Factory Manager
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
class Horde_DNS_Factory_Mapper extends Horde_Rdo_Factory
{
    protected $_adapter = null;
    protected $_factory = null;

    protected $_injector = null;

    public function __construct(Horde_Injector|Injector $injector)
    {
        $this->_injector = $injector;
    }

    public function count()
    {
        return $this->_factory->count();
    }

    public function __get($key)
    {
        switch($key) {
            case 'adapter':
            case 'factory':
                return $this->{'_' . $key};
        }
        return null;
    }

    public function create($name = 'factory', $adapter = array() )
    {
        if (!$this->_factory) {
            try {
                if (empty($adapter)) {
                    $this->_adapter = $this->_injector->getInstance('Horde_Db_Adapter');
                } else {
                    $this->_adapter = $this->_injector->getInstance('Horde_Core_Factory_Db')->create('dns', $adapter);
                }
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Db_Exception($e);
            }
            $this->_factory = new Horde_Rdo_Factory($this->_adapter);
        }
        if ($name == 'factory') {
            return $this->_factory;
        }
        if (preg_match('/_/', $name)) {
            return $this->_factory->create($name)->setFactory($this);
        }
        switch($name) {
            case 'Changeset':
            case 'Record':
            case 'Zone':
                $name = $name.'_';
        }
        return $this->_factory->create('Horde_DNS_' . $name . 'Mapper')->setFactory($this);
    }
}
