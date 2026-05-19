<?php
/**
 * A DNS API Client wrapping the AWS Route53 SDK
 *
 * Based on an initial version by Diana Hille
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Dns;

use Aws\Result;
use Aws\Route53\Route53Client;
use Exception;

class AwsRoute53 implements Client
{
    use HasMethodBlacklistTrait;
    /**
     * @var Route53Client
     */
    private $sdk;
    /**
     * @var string
     */
    private $defaultZoneId;

    /**
     * Create a Horde\Dns\Client wrapper for Route53 SDK
     *
     * @param Route53Client $sdk
     * @param array         $parameters
     *
     * Parameters format:
     *   'blacklist' => string[] A list of methods this driver will silently skip
     *   'defaultZoneId' => The zone some commands operate on if no zone is given
     *
     */
    public function __construct(Route53Client $sdk, array $parameters = [])
    {
        $this->sdk = $sdk;
        $this->setMethodBlacklist($parameters['blacklist'] ?? []);
        $this->defaultZoneId = $parameters['defaultZoneId'] ?? '';
    }

    /**
     * Return the default Zone ID
     *
     * This is used in some methods when no zone ID argument was present
     *
     * @PHP8: Refactor more methods to use this
     *
     * @return string The ID
     */
    public function getDefaultZoneId(): string
    {
        return $this->defaultZoneId;
    }

    /**
     * Set the default Zone ID
     *
     * This is used in some methods when no zone ID argument was present
     *
     * @PHP8: Refactor more methods to use this
     *
     * @param string $zoneId The ID
     */
    public function setDefaultZoneId(string $zoneId)
    {
        $this->defaultZoneId = $zoneId;
    }


    /**
     * Get a Zone by Id
     *
     * This is used in some methods when no zone ID argument was present
     *
     * @PHP8: Refactor more methods to use this
     *
     * @param string $zoneId The ID
     *
     * @return Zone  A zone representation
     */
    public function getZone(string $zoneId = ''): ?Zone
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }
        if (empty($zoneId)) {
            $zoneId = $this->defaultZoneId;
        }

        try {
            $res = $this->sdk->getHostedZone(['Id'=> $zoneId]);
        } catch (\Aws\Exception\AwsException $e) {
            return null;
        }
        // TOOD: Ok to return null here??
        if (is_null($res)) {
            return null;
        }
        // TODO: Handle errors
        $zoneHash = $res->get('HostedZone');
        $name = rtrim($zoneHash['Name'], '.');
        $id = $zoneHash['Id'];

        $idPrefix = '/hostedzone/';
        if (strpos($id, $idPrefix) === 0) {
            $id = substr($id, strlen($idPrefix));
        }
        return new ZonePlain($id, $name, $zoneHash['Config']['Comment'] ?? '');
    }


    /**
     * Converts ResourceRecordSets to an array of Records
     *
     * @param array $recordSets  The ResourceRecordSets as returned by Sdk
     *
     * @return Record[]  A list of record representations
     */
    private function convertRecordSetsToRecords(array $recordSets): iterable
    {
        $records = [];
        foreach ($recordSets as $record) {
            $name = rtrim($record['Name'], '.');
            $type = $record['Type'];
            $ttl  = $record['TTL'];
            // TODO: Some types like NS can have multiple values
            $value = $record['ResourceRecords'][0]['Value'];
            $records[] = new RecordPlain($name, $type, $ttl, $value);
        }
        return $records;
    }

    /**
     * ResourceRecordSets Iterator that queries listResourceRecordSets until
     * all results or maxResults are retrieved
     *
     * @param array $baseQuery  The query to use in the listResourceRecordSets calls
     * @param int $maxResults  Maximum amount of records to get. values <= 0 mean no limit
     *
     * @return iterable  A list of record representations
     */
    private function getResourceRecordSetsIterator(array $baseQuery = [], int $maxResults = 0): \Generator
    {
        $sdkMaxRecordsPerPage = 100;

        $gotTotal = 0;
        $query = array_merge([], $baseQuery);
        while (true) {
            if ($maxResults <= 0) {
                $query["MaxItems"] = $sdkMaxRecordsPerPage;
            } else {
                $resultsLeft = $maxResults - $gotTotal;
                if ($resultsLeft <= $sdkMaxRecordsPerPage) {
                    $query["MaxItems"] = max(0, min($resultsLeft, $sdkMaxRecordsPerPage));
                }
            }
            $res = $this->sdk->listResourceRecordSets($query);
            $recordSets = $res->get('ResourceRecordSets');
            yield $recordSets;
            $gotTotal += count($recordSets);

            if ($maxResults > 0 && $maxResults - $gotTotal <= 0) {
                break;
            }

            if ($res->get("IsTruncated")) {
                $query["StartRecordName"] = $res->get("NextRecordName");
                $query["StartRecordType"] = $res->get("NextRecordType");
            } else {
                break;
            }
        }
    }

    /**
     * Get a Zone's records by Zone Id
     *
     * @PHP8: Refactor more methods to use this
     *
     * @param string $zoneId  The ID (optional, falls back to default zone)
     * @param array  $filters A hash of additional filters to apply
     *                        TBD, not implemented yet
     * @param int $maxResults  Maximum amount of records to get. values <= 0 mean no limit
     *
     * @return Record[]  A list of record representations
     */
    public function getZoneRecords(string $zoneId = '', ?array $filters = null, int $maxResults = 0): iterable
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return [];
        }

        if (empty($zoneId)) {
            $zoneId = $this->defaultZoneId;
        }
        if (is_null($filters)) {
            $filters = [];
        }

        $records = [];

        $query = ["HostedZoneId" => $zoneId];
        foreach ($this->getResourceRecordSetsIterator($query, $maxResults) as $recordSets) {
            usleep(300000);
            $newRecords = $this->convertRecordSetsToRecords($recordSets);
            foreach ($newRecords as $record) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Get a single record if it exists
     *
     * Note: The returned comment will always be empty
     *
     * @PHP8: Refactor more methods to use this
     *
     * @param string $name    The DNS name of the record to retrieve. No end dot.
     * @param string $zoneId  The Zone Id (optional, falls back to default zone)
     * @param array  $filters A hash of additional filters to apply
     *                        TBD, not implemented yet
     *
     * @return Record|null    A record representation or null if not found
     */
    public function getSingleRecord(string $name, string $zoneId = '', array $filters = null): ?Record
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }

        if (empty($zoneId)) {
            $zoneId = $this->defaultZoneId;
        }

        $res = $this->sdk->listResourceRecordSets(
            [
                'HostedZoneId' => $zoneId,
                'StartRecordName' => $name,
                'MaxItems' => 1
            ]
        );
        $recordSets = $res->get('ResourceRecordSets');
        if (empty($recordSets)) {
            return null;
        }
        $records = $this->convertRecordSetsToRecords($recordSets);
        $record = $records[0];
        if ($record->getName() !== $name) {
            return null;
        }

        return $record;
    }

    /**
     * Gets the query for the changeResourceRecordSets api call in the correct format
     *
     * @param array $params  A hash that hold all the necessary values for the query
     *
     * @return array  The query to use for api call
     */
    private function getChangeRequestQuery(array $params): array
    {
        return [
            'ChangeBatch' => [
                'Changes' => [
                    [
                        'Action' => $params["action"],
                        'ResourceRecordSet' => [
                            'Name' => $params["name"],
                            'ResourceRecords' => [
                                [
                                    'Value' => $params["value"],
                                ],
                            ],
                            'TTL' => $params["ttl"],
                            'Type' => $params["type"],
                        ],
                    ],
                ],
                'Comment' => $params["comment"],
            ],
            'HostedZoneId' => $params["zoneId"],
        ];
    }

    /**
     * @param string $zoneId The zone id
     * @param string $action one of 'DELETE', 'CREATE' or 'UPSERT'
     * @param Array<Record> $records 
     * @param string $comment optional comment of the these change batches
     * @return Array<Result>
     */
    private function doActionForRecords(string $zoneId, string $action, array $records, string $comment = ''): array
    {
        if (!in_array($action, ['DELETE', 'CREATE', 'UPSERT'])) {
            throw new Exception("invalid action '$action'");
        }
        $changes = [];
        foreach($records as $record) {
            $changes[] = [
                'Action' => $action,
                'ResourceRecordSet' => [
                    'Name' => $record->getName(),
                    'ResourceRecords' => [
                        [
                            'Value' => $record->getValue(),
                        ],
                    ],
                    'TTL' => $record->getTtl(),
                    'Type' => $record->getType(),
                ],
            ];
        }
        $maxRecordsPerRecordSet = 400;
        $batchQueries = [];
        $changesChunks = array_chunk($changes, $maxRecordsPerRecordSet);
        foreach($changesChunks as $changesChunk) {
            $batchQueries[] = [
                'ChangeBatch' => [
                    'Changes' => $changesChunk,
                    'Comment' => $comment,
                ],
                'HostedZoneId' => $zoneId,
            ];
        }
        $results = [];
        foreach($batchQueries as $batchQuery) {
            $results[] = $this->sdk->changeResourceRecordSets($batchQuery);
        }
        return $results;
    }

    /**
     * Create a single DNS record in an existing zone
     *
     * @PHP8: Refactor for named arguments and more optionals
     * This will error on existing record. Use update for "create or update"
     *
     * @param string $zoneId  The Zone Id
     * @param string $name    The DNS name of the record. No end dot.
     * @param string $type    The DNS record type
     * @param string|string[] $value  The Value(s) of the DNS record
     * @param int    $ttl     The TTL of the record, defaults to 600
     * @param string $comment Set a comment for the operation
     *
     * @throws TBD
     */
    public function createRecord(string $zoneId, string $name, string $type, $value, int $ttl = 600, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }

        $query = $this->getChangeRequestQuery([
            "action" => 'CREATE',
            "name" => $name,
            "value" => $value,
            "ttl" => $ttl,
            "type" => $type,
            "comment" => $comment,
            "zoneId" => $zoneId,
        ]);
        $result = $this->sdk->changeResourceRecordSets($query);
    }

    /**
     * Delete multiple DNS records in an existing zone
     * 
     * @param Array<Record> $records an array of records to delete
     */
    public function createRecords(string $zoneId, array $records, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }
        return $this->doActionForRecords($zoneId, 'CREATE', $records, $comment);
    }

    /**
     * Delete a single DNS record in an existing zone
     *
     * @PHP8: Refactor for named arguments and more optionals
     * This will check on existing record and not fail on missing.
     *
     * @param string $zoneId  The Zone Id
     * @param string $name    The DNS name of the record. No end dot.
     * @param string $type    The DNS record type
     * @param string $comment Set a comment for the operation
     */
    public function deleteRecord(string $zoneId, string $name, string $type, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }

        $existing = $this->getSingleRecord($name, $zoneId);
        if (empty($existing)) {
            // TODO: Logging
            return;
        }

        $query = $this->getChangeRequestQuery([
            "action" => 'DELETE',
            "name" => $name,
            "value" => $existing->getValue(),
            "ttl" => $existing->getTtl(),
            "type" => $type,
            "comment" => $comment,
            "zoneId" => $zoneId,
        ]);
        $result = $this->sdk->changeResourceRecordSets($query);
    }

    /**
     * Delete multiple DNS records in an existing zone
     * 
     * @param Array<Record> $records an array of records to delete
     */
    public function deleteRecords(string $zoneId, array $records, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }
        return $this->doActionForRecords($zoneId, 'DELETE', $records, $comment);
    }

    /**
     * Update or create if missing a single DNS record
     *
     * @PHP8: Refactor for named arguments and more optionals
     *
     * If you do not want to overwrite existing records, use "create" instead
     *
     * @param string $zoneId  The Zone Id
     * @param string $name    The DNS name of the record. No end dot.
     * @param string $type    The DNS record type
     * @param string|string[] $value  The Value(s) of the DNS record
     * @param int    $ttl     The TTL of the record, defaults to 600
     * @param string $comment Set a comment for the operation
     *
     * @throws TBD
     */
    public function updateRecord(string $zoneId, string $name, string $type, $value, int $ttl = 600, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }
        
        $existing = $this->getSingleRecord($name, $zoneId);
        if (empty($existing)) {
            // TODO: Logging
        } else {
            $value = $value ?? $existing->getValue();
            $ttl = $ttl ?? $existing->getTtl();
        }

        if (is_null($value) || is_null($ttl)) {
            // TODO: throw Exception here?
            return;
        }
    
        $query = $this->getChangeRequestQuery([
            "action" => 'UPSERT',
            "name" => $name,
            "value" => $value,
            "ttl" => $ttl,
            "type" => $type,
            "comment" => $comment,
            "zoneId" => $zoneId,
        ]);
        $result = $this->sdk->changeResourceRecordSets($query);
    }

    /**
     * Update multiple DNS records in an existing zone
     * 
     * @param Array<Record> $records an array of records to delete
     */
    public function updateRecords(string $zoneId, array $records, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return null;
        }
        return $this->doActionForRecords($zoneId, 'UPSERT', $records, $comment);
    }
}
