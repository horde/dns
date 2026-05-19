<?php

/**
 * The Changeset Mapper
 * NOTE: It is Rdo specific, maybe include in the regular Rdo update process of the record?
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
class Horde_DNS_Changeset_Mapper extends Horde_Rdo_Mapper
 {
    protected $_table = 'horde_dns_changesets';
    protected $_classname = 'Horde_DNS_Changeset_Entity';
    protected $_lazyRelationships = array(
        'zone' => array('type' => Horde_Rdo::MANY_TO_ONE,
                         'foreignkey' => 'zone_id',
                         'mapper' => 'Horde_DNS_Zone_Mapper')
    );

    public function toArray($criteria = null)
    {
        $changeset = array();
        foreach($this->find($criteria) as $changeset) {
            $changesets[$changeset->changeset_id] = $changeset->toArray();
        }
        return $changesets;
    }

    public function create($fields)
    {
        return  parent::create($fields);
    }

    // deletes all entries in Changeset table
    public function deleteChangeset(array $criteria)
    {
        $csmEntries = $this->find($criteria);
        foreach ($csmEntries as $csmEntry) {
            $csmEntry->delete();
        }
    }

    // updates Changeset to Records table and clears Changeset table
    public function updateChangesets(array $criteria)
    {
        $recm = $this->factory->create('Horde_DNS_Record_Mapper');
        $csmEntries = $this->find($criteria);

        foreach ($csmEntries as $csmEntry) {
            if ($csmEntry->transaction == "update") {
                $record = $recm->findOne(array( 'zone_id' => $csmEntry->zone_id, 'record_id' => $csmEntry->record_id));
                if (!empty($record)) {
                    $record->update($csmEntry->toRecArray());
                }
            } elseif ($csmEntry->transaction == "delete") {
                $record = $recm->findOne(array( 'zone_id' => $csmEntry->zone_id, 'record_id' => $csmEntry->record_id));
                if (!empty($record)) {
                    $record->delete();
                }
            } elseif ($csmEntry->transaction == "create") {
                $recm->create($csmEntry->toRecArray());
            }
        }
        $this->deleteChangeset($criteria);
    }
}
