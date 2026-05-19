<?php

/**
 * The AWS backend of the DNS service
 * NOTE: works only with aws-cli installed
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
class Horde_DNS_Backend_Route53 implements Horde_DNS_Backend
{

    /**
     * The constructor checks if awscli, for communicating with AWS, is installed
     *
     * @throws Horde_Exception
     */
    public function __construct()
    {
        exec("which aws", $output);
        if (empty($output)) {
            throw new Horde_Exception('The awscli package for the command shell operations is not installed on this system');
        }
    }

    /**
     * The function to add a Zone to the AWS backend
     * TODO: Add the timezone to the function call
     * TODO: Insert the code and check if its actually working
     *
     * @param Horde_DNS_Zone $zone The definition of a DNS Zone
     */
    public function addZone(Horde_DNS_Zone $zone)
    {
        /*date_default_timezone_set("Europe/Berlin");
        $timestamp = time();
        $caller = date("YmdHis",$timestamp);

        exec("aws route53 create-hosted-zone --name ".$zone->name." --caller-reference ".$caller);*/
    }

    /**
     * The function to delete a Zone from the AWS backend
     * TODO: Insert the code and check if its actually working
     *
     * @param Horde_DNS_Zone $zone The definition of a DNS Zone
     */
    public function deleteZone(Horde_DNS_Zone $zone)
    {
        /*exec("aws route53 list-hosted-zones-by-name --dns-name ".$zone->name,$output);
        $json = json_decode(implode(' ',$output));

        exec("aws route53 delete-hosted-zone --id ".$json->HostedZones[0]->Id);*/
    }

    /**
     * The function to add a DNS record to a defined zone in the AWS backend
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function addRecord(Horde_DNS_Record $record)
    {
        $json = $this->writeJsonFile($record, 'CREATE');

        file_put_contents('/dev/shm/createRecordJson_' . $record->name . '.json',$json);

        exec('aws route53 change-resource-record-sets --hosted-zone-id '.$record->zone.' --change-batch file:///dev/shm//createRecordJson_' . $record->name . '.json');
        exec('rm /dev/shm/createRecordJson_' . $record->name . '.json');
    }

    /**
     * The function to update a DNS record to in a defined zone in the AWS backend
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function updateRecord(Horde_DNS_Record $record)
    {
        $json = $this->writeJsonFile($record, 'UPSERT');

        file_put_contents('/dev/shm/upsertRecordJson_' . $record->name . '.json',$json);

        exec('aws route53 change-resource-record-sets --hosted-zone-id '.$record->zone.' --change-batch file:///dev/shm/upsertRecordJson_' . $record->name . '.json');
        exec('rm /dev/shm/upsertRecordJson_' . $record->name . '.json');
    }

    /**
     * The function to delete a DNS record from a defined zone in the AWS backend
     *
     * @param Horde_DNS_Record $record The definition of a record
     */
    public function deleteRecord(Horde_DNS_Record $record)
    {
        $json = $this->writeJsonFile($record, 'DELETE');

        file_put_contents('/dev/shm/deleteRecordJson_' . $record->name . '.json',$json);

        exec('aws route53 change-resource-record-sets --hosted-zone-id '.$record->zone.' --change-batch file:///dev/shm/deleteRecordJson_' . $record->name .'.json');
        exec('rm /dev/shm/deleteRecordJson_' . $record->name . '.json');
    }

    /**
     * The function to write the JSON file necessary for record operations in the AWS backend
     *
     * @param Horde_DNS_Record $record The definition of a record
     * @param string $transaction The definition of the transaction (UPSERT, DELETE, CREATE)
     */
    public function writeJsonFile($record, $transaction)
    {
        $json = array(
            'Comment' => '',
            'Changes' => array(array(
                'Action' => $transaction,
                'ResourceRecordSet' => array(
                    'Name' => $record->name,
                    'Type' => $record->type,
                    'TTL' => $record->ttl,
                    'ResourceRecords' => array(array(
                        'Value' => $record->rdata
        ))))));

        return json_encode($json);
    }
}
