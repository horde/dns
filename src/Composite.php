<?php
/**
 * A DNS API Client wrapping multiple backends
 *
 * This should work with any number of backends but it is really designed to
 * work with one primary full-access backend and a second backend which might
 * be limited in reliability or scope.
 *
 * Have a leading Db based backend and a secondary AWS backend with
 * limited privileges (might not be allowed to create or list zones, for example).
 *
 * Strategy:
 *
 * Always run on the backends in the order they are configured.
 * Write statements run on all backends unless masked.
 * Read Single Zone or Record statements will generally return the
 * version of the first backend which has the item at all and is not masked.
 * Read Multiple will get the collection from the first backend
 * which has it and is not masked.
 *
 * Backends may make internal reads only from their own data
 *
 * Based on an initial version by Diana Hille
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
namespace Horde\Dns;

use Horde;

class Composite implements Client
{
    use HasMethodBlacklistTrait;

    /**
     * @var Clients[]
     */
    private $clients;
    /**
     * @var string
     */
    private $defaultZoneId;

    /**
     * Create a Horde\Dns\Client wrapper for Route53 SDK
     *
     * @param Client[] $clients A keyed hash of Client implementations
     * @param array    $parameters See below
     *
     * Parameters format:
     *   'blacklist' => string[] A list of methods this driver will silently skip
     *   'defaultZoneId' => The zone some commands operate on if no zone is given
     *
     * In some cases it might be desirable to configure blacklist per-client instead
     *
     */
    public function __construct(array $clients, array $parameters = [])
    {
        $this->clients       = $clients;
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
        foreach ($this->clients as $client) {
            $client->setDefaultZoneId($zoneId);
        }
    }


    /**
     * Get a Zone by Id
     *
     * The first sub client who has the zone will return his version
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
        foreach ($this->clients as $client) {
            // First wins
            $res = $client->getZone($zoneId);
            if ($res) {
                return $res;
            }
        }
        return null;
    }

    /**
     * Get a Zone's records by Zone Id
     *
     * The first sub client who has the zone will return his version of the record list
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
        foreach ($this->clients as $client) {
            // First wins
            $res = $client->getZoneRecords($zoneId, $filters, $maxResults);
            if ($res) {
                return $res;
            }
        }
        return [];
    }

    /**
     * Get a single record if it exists
     *
     * Note: The returned comment will always be empty
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
        foreach ($this->clients as $client) {
            try {
                $record = $client->getSingleRecord($name, $zoneId, $filters);
                if ($record) {
                    return $record;
                }
            } catch (\Exception $e) {
                Horde::log($e);
            }
        }
        return null;
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

        foreach ($this->clients as $client) {
            try {
                $client->createRecord($zoneId, $name, $type, $value, $ttl, $comment);
            } catch (\Exception $e) {
                Horde::log($e);
            }
        }
    }

    public function createRecords(string $zoneId, array $records, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return;
        }

        foreach ($this->clients as $client) {
            try {
                $client->createRecords($zoneId, $records, $comment);
            } catch (\Exception $e) {
                Horde::log($e);
            }
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

        foreach ($this->clients as $client) {
            try {
                $client->deleteRecord($zoneId, $name, $type, $comment);
            } catch (\Exception $e) {
                Horde::log($e);
            }
        }
    }

    public function deleteRecords(string $zoneId, array $records, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return;
        }

        foreach ($this->clients as $client) {
            try {
                $client->deleteRecords($zoneId, $records, $comment);
            } catch (\Exception $e) {
                Horde::log($e);
            }
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

        foreach ($this->clients as $client) {
            try {
                $client->updateRecord($zoneId, $name, $type, $value, $ttl, $comment);
            } catch (\Exception $e) {
                Horde::log($e);
            }
        }
    }

    public function updateRecords(string $zoneId, array $records, string $comment = '')
    {
        if ($this->methodIsBlacklisted(__FUNCTION__)) {
            return;
        }

        foreach ($this->clients as $client) {
            try {
                $client->updateRecords($zoneId, $records, $comment);
            } catch (\Exception $e) {
                Horde::log($e);
            }
        }
    }
}
