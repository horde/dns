<?php

/**
 * The wrapper function to call the available functions on a given backend
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
class Horde_DNS
{
    //The designated backend
    protected $_backends = array();

    /**
     * The Constructor
     *
     * @param Horde_DNS_Backend $backend An instance of one of the available backends
     */
    public function __construct($backend)
    {
        $this->addBackend($backend);
    }

    /**
     * Adds the specified backend, to make it available
     * TODO: Make it protected or private
     *
     * @param Horde_DNS_Backend $backend An instance of one of the available backends
     */
    public function addBackend($backend)
    {
        array_push($this->_backends, $backend);
    }

    /**
     * Wrapper of the addZone function
     *
     * @param Horde_DNS_Zone $zone The definition of a zone
     */
    public function addZone(Horde_DNS_Zone $zone)
    {
        /*TODO: Do any sanity checks which are not specific to a backend here */
        foreach ($this->_backends as $backend)
        {
            $backend->addZone($zone);
        }
    }

    /**
     * Wrapper of the deleteZone function
     * TODO: Just use a unique identifier instead?
     *
     * @param Horde_DNS_Zone $zone The definition of a zone
     */
    public function deleteZone(Horde_DNS_Zone $zone)
    {
        foreach ($this->_backends as $backend)
        {
            $backend->deleteZone($zone);
        }
    }

    /**
     * Wrapper of the addRecord function
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function addRecord(Horde_DNS_Record $record)
    {
        /*TODO: Do any sanity checks which are not specific to a backend here */
        foreach ($this->_backends as $backend)
        {
            $backend->addRecord($record);
        }
    }

    /**
     * Wrapper of the updateRecord function
     * TODO: Just use a unique identifier instead?
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function updateRecord(Horde_DNS_Record $record)
    {
        /* Do any sanity checks which are not specific to a backend here */
        foreach ($this->_backends as $backend)
        {
            $backend->updateRecord($record);
        }
    }

    /**
     * Wrapper of the deleteRecord function
     * TODO: Just use a unique identifier instead?
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function deleteRecord(Horde_DNS_Record $record)
    {
        foreach ($this->_backends as $backend)
        {
            $backend->deleteRecord($record);
        }
    }
}
