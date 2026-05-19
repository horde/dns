<?php

/**
 * The old Record Entity
 * NOTE: I don't think this is necessary anymore as this rdo class isn't used
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
class Horde_DNS_Record_Entity extends Horde_Rdo_Base
{
    public function toArray($lazy = false, $relationships = false)
    {
        return array(
            'record_id'        => $this->record_id,
            'zone_id'          => $this->zone_id,
            'name'             => $this->name,
            'ttl'              => $this->ttl,
            'class'            => $this->class,
            'type'             => $this->type,
            'special'          => $this->special,
            'rdata'            => $this->rdata,
            'length'           => $this->length
        );
    }

    public function delete()
    {
        parent::delete();
    }

    public function update(array $updateData)
    {
        foreach ($updateData as $data => $content) {
            $this->$data = $content;
        }
        $this->save();
    }
}
