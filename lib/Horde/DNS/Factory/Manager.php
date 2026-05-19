<?php
use Horde\Injector\Injector;

/**
 * Factory Mapper
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
class Horde_DNS_Factory_Manager extends Horde_Core_Factory_Base
{
    protected $_mapper;
    protected $_adapter = null;
    protected $_managers = array();

    public function __construct(Horde_Injector|Injector $injector)
    {
        parent::__construct($injector);
        $this->_mapper = $injector->getInstance('Horde_DNS_Factory_Mapper');
        $this->_adapter = $injector->getInstance('Horde_Db_Adapter');
    }

    public function __get($key)
    {
        switch($key) {
            case 'adapter':
            case 'mapper':
                return $this->{'_' . $key};
        }
        return null;
    }

    public function create($name, $adapter = array() )
    {
        if (!empty($this->_managers[$name])) {
            return $this->_managers[$name];
        }
        $class = 'Horde_DNS_' . $name . '_Manager';
        if (!class_exists($class)) {
            throw new Horde_Rdo_Exception(sprintf('Class %s not found', $class));
        }
        if (!$adapter) {
            $adapter = $this->_adapter;
        }
        $this->_managers[$name] = new $class($adapter);
        return $this->_managers[$name]->setFactory($this);
    }
}
