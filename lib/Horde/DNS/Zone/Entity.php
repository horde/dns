<?php

/**
 * The old Zone Entity
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
class Horde_DNS_Zone_Entity extends Horde_Rdo_Base
{
    public function toArray($lazy = false, $relationships = false)
    {
        return array(
            'zone_id'    => $this->zone_id,
            'domain_name'=> $this->domain_name,
            'ttl'        => $this->ttl,
            'primary'    => $this->primary,
            'ip_adress'  => $this->ip_adress,
            'admin_mail' => $this->admin_mail,
            'serial'     => $this->serial,
            'refresh'    => $this->refresh,
            'retry'      => $this->retry,
            'expire'     => $this->expire,
            'min_ttl'    => $this->min_ttl,
        );
    }

    //Deletes zone from DB und from named.conf
    public function deleteZone($server)
    {
        foreach ( $this->records as $record ) {
            $record->delete();
        }
        foreach ( $this->changesets as $changeset ) {
             $changeset->delete();
        }

        //only zones created by rndc
        exec("ssh ".$server);
        exec("rndc delzone " . $this->domain_name);
        exec("rm /var/lib/named/slave/" . $this->domain_name);
        exec("exit");
        parent::delete();
    }

    //Creates a new zone in database and the record entry for the primary server
    public function createZone($server)
    {
        $newZone = $this->writeZoneFile();

        exec("ssh " . $server);
        exec("touch /var/lib/named/slave/".$this->domain_name);
        exec("echo \"" . $newZone . "\" > /var/lib/named/slave/" . $this->domain_name);

        //Implies that the configuration allow-new-zones was set as yes.
        exec("rndc addzone ".$this->domain_name."'{ type slave; file \"slave/".$this->domain_name."\"; };'");
        exec("exit");

        parent::save();

        //for adding the primary to the list of zone records (for later changes)
        $firstRecord = array (
            'zone_id' => $this->zone_id,
            'name' => $this->primary,
            'class' => 'IN',
            'type' => 'NS'
        );
        $firstRecord = new Horde_DNS_Record_Entity($firstRecord);
        $firstRecord->save();
    }


    //Updates the zone information only in the database
    public function updateDB(array $updateData)
    {
        foreach ($updateData as $data => $content) {
            $this->$data = $content;
        }
        $this->save();
    }

    //Updates only the SOA information of a zone file in the database and on the server
    public function updateZone(array $updateData, $server, $keyLoc)
    {
        $this->updateDB($updateData);
        $updateZone = $this->writeNsUpdateZone($server);
        exec("touch NsUpdateFile");
        exec("echo \"" . $updateZone . "\" > NsUpdateFile");
        exec("/usr/local/bin/nsupdate -k " . $keyLoc . " -d NsUpdateFile");
        exec("rm NsUpdateFile");
    }

    //Updates the record information of a zone file based on changesets in the database and on the server
    public function updateRecords($server, $keyLoc)
    {
        $updateRecords = $this->writeNsUpdate($server);
        exec("touch NsUpdateFile");
        exec("echo \"".$updateRecords."\" > NsUpdateFile");
        exec("/usr/local/bin/nsupdate -k ".$keyLoc." -d NsUpdateFile");
        exec("rm NsUpdateFile");
        $csm = $GLOBALS['injector']->getInstance('Horde_DNS_Factory_Mapper')->create('Changeset');
        $csm->updateChangesets(array('zone_id'=>$this->zone_id));
    }

    //Write a full zone file from the database entries
    public function rewriteFullZone($server, $keyLoc)
    {
        $zone = $this->writeZoneFile();

        exec("ssh " . $server);
        exec("touch /var/lib/named/slave/".$this->domain_name);
        exec("echo \"" . $newZone . "\" > /var/lib/named/slave/" . $this->domain_name);

        //Implies that the configuration allow-new-zones was set as yes.
        exec("rndc addzone ".$this->domain_name."'{ type slave; file \"slave/".$this->domain_name."\"; };'");
        exec("exit");

        $fullZone = $this->writeZoneData($server);
        exec("touch NsUpdateFile");
        exec("echo \"".$fullZone."\" > NsUpdateFile");
        exec("/usr/local/bin/nsupdate -k ".$keyLoc." -d NsUpdateFile");
        exec("rm NsUpdateFile");
    }

    //Writes a Zone file
    public function writeZoneFile()
    {
        $soa = "$ORIGIN .\n";
        $soa .= "$TTL ".$this->ttl."\n";
        $soa .= $his->domain_name." IN SOA ".$this->primary.". ".$this->admin_mail.".\n";
        $soa .= "(\n".$soa .= $this->serial."\n".$this->refresh."\n".$this->retry."\n".$this->expire."\n".$this->min_ttl."\n)\n";
        $soa .= "NS ".$this->primary.".\n";
        $soa .= "$ORIGIN ".$this->domain_name.".\n";
        if(filter_var($this->ip_adress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {$soa .= "ns A ".$this->ip_adress."\n";}
        else {$soa .= "ns AAAA ".$this->ip_adress."\n";}

        return $soa;
    }

    //Returns update for soa-entry after adjusting zone information
    public function writeNsUpdateZone($server)
    {
        $nsupdate .= "nsupdate\n";
        $nsupdate .= "server ".$server."\n";
        $nsupdate .= "zone ".$this->domain_name."\n";

        //Important! Upate Serial number
        date_default_timezone_set("Europe/Berlin");
        $timestamp = time();
        $this->serial = date("YmdHis",$timestamp);

        //Update Serial in DB
        $this->update($this->serial);

        $nsupdate .= "update add ".$this->domain_name." ".$this->soa_ttl." SOA ".$this->primary." ".$this->admin_mail." ".$this->serial." ".$this->refresh." ".$this->retry." ".$this->expire." ".$this->min_ttl."\n";

        $nsupdate .= "\n";
        $nsupdate .= "send \n";
        $nsupdate .= "quit";

        return $nsupdate;
    }

    //Writes all changeset entries as classfied by transaction to a nsupdate
    public function writeNsUpdate($server)
    {
        $csman = $GLOBALS['injector']->getInstance('Horde_DNS_Factory_Manager')->create('Changeset');
        $changes = $csman->listChangesets(array('zone_id' => $zone_id));

        $nsupdate = "nsupdate\n";
        $nsupdate .= "server ".$server."\n";
        $nsupdate .= "zone ".$this->domain_name."\n";

        foreach ($changes as $change) {
            switch ($change->transaction) {
                case 'create': {
                    $nsupdate .= $this->createChangesetAction($change);
                    break;
                }
                case 'update': {
                    $nsupdate .= $this->updateChangesetAction($change);
                    break;
                }
                default: { //delete
                    $nsupdate .= $this->deleteChangesetAction($change);
                    break;
                }
            }
        }

        $nsupdate .= "\n";
        $nsupdate .= "send \n";
        $nsupdate .= "quit";

        return $nsupdate;
    }

    //Writes a new zone file, if needed and a nsupdate for all records related to that zone

    public function writeZoneData($server)
    {
        $nsupdate .= "nsupdate\n";
        $nsupdate .= "server ".$server."\n";
        $nsupdate .= "zone ".$this->domain_name."\n";

        //Important! Update Serial number
        date_default_timezone_set("Europe/Berlin");
        $timestamp = time();
        $this->serial = date("YmdHis",$timestamp);

        //Update Serial in DB
        $this->update($this->serial);

        $recm = $GLOBALS['injector']->getInstance('Horde_DNS_Factory_Mapper')->create('Horde_DNS_Record_Mapper');
        $records = $recm->find(array('zone_id' => $this->zone_id));

        if (!empty($records)) {
            foreach ($this->records as $record) {
                $nsupdate .= $this->createChangesetAction($record);
            }
        }

        $nsupdate .= "\n";
        $nsupdate .= "send \n";
        $nsupdate .= "quit";

        return $nsupdate;
    }


    //If a new record was created
    protected function createChangesetAction($changeset)
    {
        return "update add ".$changeset->name." ".$changeset->ttl." ".$changeset->class." ".$changeset->type." ".$changeset->rdata."\n";
    }

    //If a record is marked as deleted
    protected function deleteChangesetAction($changeset)
    {
        return "update delete ".$changeset->name." ".$changeset->type."\n";
    }

    //If a record was updated
    protected function updateChangesetAction($changeset)
    {
        $record->getRecord($changeset->record_id);
        $tmp = $this->deleteChangesetAction($record);
        $tmp .= $this->createChangesetAction($changeset);

        return $tmp;
    }

}
