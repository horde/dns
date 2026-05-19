<?php
/**
 * A DNS API Client wrapping the AWS Route53 SDK
 *
 * Based on an initial version by Diana Hille
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
namespace Horde\Dns;

use Exception;
use Horde\Dns\Db\ZoneRepo;
use Horde\Dns\Db\RecordRepo;
use Horde_Rdo_Query;

class Db implements Client
{
    use HasMethodBlacklistTrait;

    /**
     * @var ZoneRepo
     */
    private $zoneRepo;

    /**
     * @var RecordRepo
     */
    private $recordRepo;

    /**
     * @var string
     */
    private $defaultZoneId;

    /**
     * Create a Horde\Dns\Client for databases
     *
     * @param ZoneRepo   $zoneRepo The ZoneRepo ORM implementation
     * @param RecordRepo $zoneRepo The RecordRepo ORM implementation
     * @param array      $parameters See below
     *
     * Parameters format:
     *   'blacklist' => string[] A list of methods this driver will silently skip
     *   'defaultZoneId' => The zone some commands operate on if no zone is given
     *
     */
    public function __construct(ZoneRepo $zoneRepo, RecordRepo $recordRepo, array $parameters = [])
    {
        $this->zoneRepo      = $zoneRepo;
        $this->recordRepo    = $recordRepo;
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
        $record = $this->zoneRepo->findOne(['name' => $zoneId]);
        return $record;
    }

    /**
     * Get an array of Records
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
    public function getZoneRecords(string $zoneId = '', array $filters = null, int $maxResults = 0): iterable
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return [];
        }

        if (empty($zoneId)) {
            $zoneId = $this->defaultZoneId;
        }
        $records = $this->recordRepo->find(['zone' => $zoneId]);
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
        $record = $this->recordRepo->findOne(
            [
                'zone' => $zoneId,
                'name' => $name
            ]
        );
        return $record;
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
            return;
        }

        $this->recordRepo->create(
            [
                'zone'    => $zoneId,
                'name'    => $name,
                'ttl'     => $ttl,
                'class'   => 'IN',
                'type'    => $type,
                'special' => null,
                // TODO: This might break on multi-value records
                'rdata'   => $value,
                'length'  => null
            ]
        );
    }

    public function createRecords(string $zoneId, array $records, string $comment = '')
    {
        foreach($records as $record) {
            $dbRecord = $this->getSingleRecord($record->getName(), $zoneId);
            if ($dbRecord) {
                throw new Exception("record '{$record->getName()}' already exists in zone {$zoneId}");
            }
            $this->recordRepo->create([
                'zone' => $zoneId,
                'name' => $record->getName(),
                'ttl' => $record->getTtl(),
                'class' => 'IN',
                'type' => $record->getType(),
                'special' => null,
                'rdata'   => $record->getValue(),
                'length'  => null
            ]);
        }
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
            return;
        }

        $found = $this->getSingleRecord($name, $zoneId);
        if ($found) {
            $found->delete();
        }
    }

    public function deleteRecords(string $zoneId, array $records, string $comment = '')
    {
        if (!$records) {
            return;
        }
        $names = array_map(fn($r) => $r->getName(), $records);
        $q = new Horde_Rdo_Query($this->recordRepo);
        $q->addTest('zone', '=', $zoneId)
            ->addTest('name', 'IN', $names);
        foreach($this->recordRepo->find($q) as $record) {
            $record->delete();
        }
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
            return;
        }

        $found = $this->getSingleRecord($name, $zoneId);
        if ($found) {
            $found->type = $type;
            // workaroundey ormy
            $found->rdata = $value;
            $found->ttl = $ttl;
            $found->comment = $comment;
            $found->save();
            return;
        }
        $this->createRecord($zoneId, $name, $type, $value, $ttl, $comment);
    }

    public function updateRecords(string $zoneId, array $records, string $comment = '')
    {
        foreach($records as $record) {
            $dbRecord = $this->getSingleRecord($record->getName(), $zoneId);
            if ($dbRecord) {
                $dbRecord->type = $record->getType();
                $dbRecord->rdata = $record->getValue();
                $dbRecord->ttl = $record->getTtl();
                $dbRecord->comment = $record->getComment();
                $dbRecord->save();
            } else {
                $this->createRecord($zoneId, $record->getName(), $record->getType(), $record->getValue(), $record->getTtl(), $record->getComment());
            }
        }
    }
}
