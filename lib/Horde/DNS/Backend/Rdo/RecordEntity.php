<?php

/**
 * The Record RDO Entity
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
class Horde_DNS_Backend_Rdo_RecordEntity extends Horde_Rdo_Base
{
    /**
     * The magic method to return all properties as an array
     *
     * @return array The array of all class properties
     */
    public function toArray($lazy = false, $relationships = false)
    {
        return array(
            'record_id'        => $this->record_id,
            'zone'             => $this->zone,
            'name'             => $this->name,
            'ttl'              => $this->ttl,
            'class'            => $this->class,
            'type'             => $this->type,
            'special'          => $this->special,
            'rdata'            => $this->rdata,
            'length'           => $this->length
        );
    }

    /**
     * The function to delete the entity from the database
     * TODO: Is this really necessary?
     */
    public function delete()
    {
        parent::delete();
    }

    /**
     * The function to update the entity in the database
     * TODO: Is this really necessary?
     *
     * @params array $updataData The array with the data to update
     */
    public function update(array $updateData)
    {
        foreach ($updateData as $data => $content) {
            $this->$data = $content;
        }
        $this->save();
    }
}
