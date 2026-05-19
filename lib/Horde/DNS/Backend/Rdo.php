<?php

/**
 * The RDO backend of the DNS service
 * TODO: Needs Refactoring
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
class Horde_DNS_Backend_Rdo
{
    /**
     * The mapper variables
     */
    protected $_rm;
    protected $_zm;

    /**
     * The Constructor of the class
     * Sets the Mapper for the Record and the Zone
     *
     * @param Horde_Rdo_Factory $factory The factory for creating a Record and a Zone
     */
    public function __construct(Horde_Rdo_Factory $factory)
    {
        $this->_rm = $factory->create("Horde_DNS_Backend_Rdo_RecordMapper");
        $this->_zm = $factory->create("Horde_DNS_Backend_Rdo_ZoneMapper");
    }

    /**
     * Adds a zone to the RDO backend
     * TODO: Check whenever it is necessary to always add a new zone
     * TODO: Zones with the same name should not exist (as records are added to the first zone found)
     * TODO: Add a full array instead of a zone or do the validation checks somewhere else
     *
     * @param Horde_DNS_Zone $zone The definition of a Zone
     */
    public function addZone(Horde_DNS_Zone $zone)
    {
        //TODO: Check if necessary
        if(!$zone instanceof Horde_DNS_Zone) {
            throw new Horde_Exception("Not the right zone format");
            exit;
        }

        //TODO: Simplify the checks, if possible
        //TODO: Check, if all these are necessary
        if (!empty($zone->name)) {  $backendZone['name'] = $zone->name; }
        if (!empty($zone->domain)) {  $backendZone['domain'] = $zone->domain; }
        if (!empty($zone->ttl)) { $backendZone['ttl'] = $zone->ttl; }
        if (!empty($zone->primary_server)) { $backendZone['primary_server'] = $zone->primary_server; }
        if (!empty($zone->ip_adress)) { $backendZone['ip_adress'] = $zone->ip_adress; }
        if (!empty($zone->mail)) { $backendZone['mail'] = $zone->mail; }
        if (!empty($zone->serial)) { $backendZone['serial'] = $zone->serial; }
        if (!empty($zone->refresh)) { $backendZone['refresh'] = $zone->refresh; }
        if (!empty($zone->retry)) { $backendZone['retry'] = $zone->retry; }
        if (!empty($zone->expire)) { $backendZone['expire'] = $zone->expire; }
        if (!empty($zone->min_ttl)) { $backendZone['min_ttl'] = $zone->min_ttl; }

        //Duplicate check for the zone, if everything is the same don't add it to the database
        //TODO: use similar variable names for all functions
        if (!empty($backendZone)) { $testZone = $this->_zm->findOne($backendZone); }

        //TODO: Change check to zone not empty
        //TODO: If the Zone exists, don't throw an exception
        if (empty($testZone)) {
            $this->_zm->create($backendZone);
        } else {
            throw new Horde_Exception("Zone already in use");
        }
    }

    /** Deletes a zone from the RDO backend
     *
     * @param Horde_DNS_Zone $zone The definition of a Zone
     * TODO: Use the zone_id instead of the full Horde_DNS_zone
     */
    public function deleteZone(Horde_DNS_Zone $zone)
    {
        //TODO: Check if necessary
        if(!$zone instanceof Horde_DNS_Zone) {
            throw new Horde_Exception("Not the right zone format");
            exit;
        }

        //TODO: That's all not necessary
        if (isset($zone->zone_id) && !empty($zone->zone_id)) { $backendZone['zone_id'] = $zone->zone_id; }
        if (isset($zone->name) && !empty($zone->name)) {  $backendZone['name'] = $zone->name; }
        if (isset($zone->domain) && !empty($zone->domain)) {  $backendZone['domain'] = $zone->domain; }
        if (isset($zone->ttl) && !empty($zone->ttl)) { $backendZone['ttl'] = $zone->ttl; }
        if (isset($zone->primary_server) && !empty($zone->primary_server)) { $backendZone['primary_server'] = $zone->primary_server; }
        if (isset($zone->ip_adress) && !empty($zone->ip_adress)) { $backendZone['ip_adress'] = $zone->ip_adress; }
        if (isset($zone->mail) && !empty($zone->mail)) { $backendZone['mail'] = $zone->mail; }
        if (isset($zone->serial) && !empty($zone->serial)) { $backendZone['serial'] = $zone->serial; }
        if (isset($zone->refresh) && !empty($zone->refresh)) { $backendZone['refresh'] = $zone->refresh; }
        if (isset($zone->retry) && !empty($zone->retry)) { $backendZone['retry'] = $zone->retry; }
        if (isset($zone->expire) && !empty($zone->expire)) { $backendZone['expire'] = $zone->expire; }
        if (isset($zone->min_ttl) && !empty($zone->min_ttl)) { $backendZone['min_ttl'] = $zone->min_ttl; }

        //TODO: only check if the $zone is alright and use the zone_id if possible
        if (!empty($backendZone)) { $backendZone = $this->_zm->findOne($backendZone); }

        if (!empty($backendZone)) {
            $records = $this->_rm->find(array('zone' => $zone->name));
            if (!empty($records)) {
                //Delete all records from a zone before actually deleting the zone
                foreach ($records as $record) {
                    $record->delete();
                }
            }
            $backendZone->delete();
        }
        //TODO: Throw an exception if Zone was not found
    }

    /** Updates a record in the RDO backend
     *
     * @param Horde_DNS_Record $record The definition of a Record
     * TODO: Use record_id instead of the full Horde_DNS_Record
     */
    public function updateRecord(Horde_DNS_Record $record)
    {
        //TODO: Check if necessary
        if (!$record instanceof Horde_DNS_Record) {
            throw new Horde_Exception("Not the right record format");
            exit;
        }

        //TODO: That's all not necessary
        if (!empty($record->record_id)) {  $backendRecord['record_id'] = $record->record_id; }
        if (!empty($record->zone)) {  $backendRecord['zone'] = $record->zone; }
        if (!empty($record->name)) { $backendRecord['name'] = $record->name; }
        if (!empty($record->ttl)) { $backendRecord['ttl'] = $record->ttl; }
        if (!empty($record->class)) { $backendRecord['class'] = $record->class; }
        if (!empty($record->type)) { $backendRecord['type'] = $record->type; }
        if (!empty($record->special)) { $backendRecord['special'] = $record->special; }
        if (!empty($record->rdata)) { $backendRecord['rdata'] = $record->rdata; }
        if (!empty($record->length)) { $backendRecord['length'] = $record->length; }

        //TODO: findOne($record->record_id) is everything it takes
        if (!empty($record->record_id)) { $testRecord = $this->_rm->findOne(array('record_id' => $record->record_id)); }

        //TODO: Add Test for validate that a changed type does not violates the cname rule

        if (!empty($testRecord)) {
            $this->_rm->update($record->record_id,$backendRecord);
        } else {
            throw new Horde_Exception("Record not found");
        }
    }

    /** Adds a record to the RDO backend
     *
     * @param Horde_DNS_Record $record The definition of a Record
     * TODO: Add a full array instead of a record or do the validation checks somewhere else
     */
    public function addRecord(Horde_DNS_Record $record)
    {
        //TODO: Check if necessary
        if (!$record instanceof Horde_DNS_Record) {
            throw new Horde_Exception("Not the right record format");
            exit;
        }

        //TODO: Only one zone should have this name then! Otherwise the records could be mixed up
        if (!empty($record->zone)) { $zoneEntry = $this->_zm->findOne(array('name' => $record->zone)); }

        //TODO: Simplify the checks, if possible
        if (!empty($record->zone)) {  $backendRecord['zone'] = $record->zone; }
        if (!empty($record->name)) { $backendRecord['name'] = $record->name; }
        if (!empty($record->ttl)) { $backendRecord['ttl'] = $record->ttl; }
        if (!empty($record->class)) { $backendRecord['class'] = $record->class; }
        if (!empty($record->type)) { $backendRecord['type'] = $record->type; }
        if (!empty($record->special)) { $backendRecord['special'] = $record->special; }
        if (!empty($record->rdata)) { $backendRecord['rdata'] = $record->rdata; }
        if (!empty($record->length)) { $backendRecord['length'] = $record->length; }

        //Duplicate check for the record, if everything is the same don't add it to the database
        if (!empty($backendRecord)) { $testRecord = $this->_rm->findOne($backendRecord); }

        //Check if it is a change name request
        if (!empty($record->type) && $record->type == 'CNAME') {
            $cnameEntry = $this->_rm->findOne(array('name' => $record->name));
        }

        //TODO: Validate the zone earlier
        if (empty($zoneEntry)) {
            throw new Horde_Exception(sprintf("%s is not in the database",$record->zone));
        } elseif (!empty($testRecord)) {
            //TODO: Validate the record earlier
            throw new Horde_Exception("Record already in use");
        } elseif (!empty($cnameEntry)) {
            throw new Horde_Exception(sprintf("%s is already used as record resource and cannot be used with cname", $record->name));
        } else {
            $this->_rm->create($backendRecord);
        }
    }

    /** Deletes a record from the RDO backend
     *
     * @param Horde_DNS_Record $record The definition of a Record
     * TODO: Use the record_id instead of the full Horde_DNS_Record
     */
    public function deleteRecord(Horde_DNS_Record $record)
    {
        //TODO: Check if necessary
        if(!$record instanceof Horde_DNS_Record) {
            throw new Horde_Exception("Not the right record format");
            exit;
        }

        //TODO: That's all not necessary
        if (isset($record->record_id) && !empty($record->record_id)) { $backendRecord['record_id'] = $record->record_id; }
        if (isset($record->zone) && !empty($record->zone)) {  $backendRecord['zone'] = $record->zone; }
        if (isset($record->name) && !empty($record->name)) { $backendRecord['name'] = $record->name; }
        if (isset($record->ttl) && !empty($record->ttl)) { $backendRecord['ttl'] = $record->ttl; }
        if (isset($record->class) && !empty($record->class)) { $backendRecord['class'] = $record->class; }
        if (isset($record->type) && !empty($record->type)) { $backendRecord['type'] = $record->type; }
        if (isset($record->special) && !empty($record->special)) { $backendRecord['special'] = $record->special; }
        if (isset($record->rdata) && !empty($record->rdata)) { $backendRecord['rdata'] = $record->rdata; }
        if (isset($record->length) && !empty($record->length)) { $backendRecord['length'] = $record->length; }

        if (!empty($backendRecord)) { $backendRecord = $this->_rm->findOne($backendRecord); }

        if (!empty($backendRecord)) {
            $backendRecord->delete();
        }
    }
}
