<?php

/**
 * The old Zone Manager
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
class Horde_DNS_Zone_Manager extends Horde_DNS_Manager
{
    protected $_mapper = 'Zone';

    public function getZone($id)
    {
        return $this->mapper->findOne($id);
    }

    public function listZones(array $criteria)
    {
        return $this->mapper->find($criteria);
    }

    public function getUpdateData(array $criteria = null)
    {
        $resultsArray = array();
        $results = $this->mapper->find($criteria);
        foreach ($results as $result) {
            $resultsArray[] = $result->toArray();
        }
        return $resultsArray;
    }

}
