<?php

/**
 * The Bind9 backend of the DNS service
 * TODO: Only usable for a SLES12 server (paths change for every distribution)! For later usage it is recommended to read the file path from the named.conf-file to avoid inconsistencies.
 * TODO: Test, if it really does what it should
 * TODO: If the DNS server is external the constructor needs everything for a SSH call and the position of the nsKey
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
class Horde_DNS_Bind9 implements Horde_DNS_Backend
{
    protected $_server;
    protected $_nsKeyLocation;

    /**
     * The Constructor checks if bind is installed on the destinated system
     *
     * @throws Horde_Exception
     */
    public function __construct() {
        exec("ssh " . $this->_server);
        exec("rpm -qa | grep bind", $output);
        if (empty($output)) {
            throw new Horde_Exception('The bind package is not installed on this system');
        }
        exec("exit");
    }

    /**
     * Adds a zone to the Bind9 backend
     * TODO: The paths should be variable based on the OS
     *
     * @param Horde_DNS_Zone $zone The definition of a zone
     */
    public function addZone(Horde_DNS_Zone $zone)
    {
        $zoneBase = $this->writeZoneFile($zone);

        exec("ssh " . $this->_server);
        exec("touch /var/lib/named/slave/" . $zone->name);
        exec("echo \"" . $zoneBase . "\" > /var/lib/named/slave/" . $zone->name);
        exec("rndc addzone " . $zone->name . " '{ type slave; file \"slave/" . $zone->name . "\"; };'");
        exec("exit");
    }

    /**
     * Deletes a zone from the Bind9 backend
     * TODO: The paths should be variable based on the OS
     *
     * @param Horde_DNS_Zone $zone The definition of a zone
     */
    public function deleteZone(Horde_DNS_Zone $zone)
    {
        exec("ssh " . $this->_server);
        exec("rndc delzone " . $zone->name);
        exec("rm /var/lib/named/slave/" . $zone->name);
        exec("exit");
    }

    /**
     * Adds a record to the Bind9 backend
     * TODO: The paths should be variable based on the OS
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function addRecord(Horde_DNS_Record $record)
    {
        $addRecord = $this->writeNsUpdate($record, 'add');

        exec("touch NsUpdateFile");
        exec("echo \"" . $addRecord . "\" > NsUpdateFile");
        exec("/usr/local/bin/nsupdate -k " . $this->_nsKeyLocation . " -d NsUpdateFile");
        exec("rm NsUpdateFile");
    }

    //TODO: Add update for the record

    /**
     * Deletes a record from the Bind9 backend
     * TODO: The paths should be variable based on the OS
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function deleteRecord(Horde_DNS_Record $record)
    {
        $deleteRecord = $this->writeNsUpdate($record, 'delete');

        exec("touch NsUpdateFile");
        exec("echo \"" . $deleteRecord . "\" > NsUpdateFile");
        exec("/usr/local/bin/nsupdate -k " . $this->_nsKeyLocation . " -d NsUpdateFile");
        exec("rm NsUpdateFile");
    }

    /**
     * Define the zone file skeletton
     *
     * @param Horde_DNS_Zone $zone The definition of a zone
     */
    public function writeZoneFile(Horde_DNS_Zone $zone)
    {
        $content  = "ORIGIN .\n";
        $content .= "\$TTL " . $zone->ttl . "\n";
        $content .= $zone->domain . " IN SOA " . $zone->primary . ". " . $zone->mail . ".\n";
        $content .= "(\n" . $zone->serial . "\n" . $zone->refresh . "\n" . $zone->retry . "\n" . $zone->expire . "\n" . $zone->min_ttl . "\n)\n";
        $content .= "NS " . $zone->primary . ".\n";
        $content .= "\$ORIGIN " . $zone->domain . ".\n";

        if (filter_var($zone->ip_adress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $content .= "ns A " . $zone->ip_adress . "\n"; 
        } else {
            $content .= "ns AAAA " . $zone->ip_adress . "\n";
        }

        return nl2br($content);
    }

    /**
     * Define the ns update file for record changes
     *
     * @param Horde_DNS_Record $record The definition of a record
     * @param string $transaction The definition of the transaction (add, delete)
     */
    public function writeNsUpdate(Horde_DNS_Record $record, $transaction)
    {
        $content  = "nsupdate\n";
        $content .= "server " . $this->_server . "\n";
        $content .= "zone " . $record->zone . "\n";

        if ($transaction == 'add') {
            $content .= "update add " . $record->name . " " . $record->ttl . " " . $record->class . " " . $record->type . " " . $record->rdata . "\n";
        } else { //delete
            $content .= "update delete " . $record->name . " " . $record->type . "\n";
        }

        $content .= "\n";

        return nl2br($content);
    }
}
