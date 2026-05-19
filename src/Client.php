<?php
/**
 * A DNS API Client interface
 *
 * Based on an initial version by Diana Hille
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
namespace Horde\Dns;

use Aws\Result;

interface Client
{
    /**
     * Return the default Zone ID
     *
     * This is used in some methods when no zone ID argument was present
     *
     * @PHP8: Refactor more methods to use this
     *
     * @return string The ID
     */
    public function getDefaultZoneId(): string;

    /**
     * Set the default Zone ID
     *
     * This is used in some methods when no zone ID argument was present
     *
     * @PHP8: Refactor more methods to use this
     *
     * @param string $zoneId The ID
     */
    public function setDefaultZoneId(string $zoneId);

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
    public function getZone(string $zoneId = ''): ?Zone;

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
    public function getZoneRecords(string $zoneId = '', array $filters = null, int $maxResults = 0): iterable;

    /**
     * Get a single record if it exists
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
    public function getSingleRecord(string $name, string $zoneId = '', array $filters = null): ?Record;

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
    public function createRecord(string $zoneId, string $name, string $type, $value, int $ttl = 600, string $comment = '');

    /**
     * Delete multiple DNS records in an existing zone
     * 
     * @param Array<Record> $records an array of records to delete
     */
    public function createRecords(string $zoneId, array $records, string $comment = '');

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
    public function deleteRecord(string $zoneId, string $name, string $type, string $comment = '');

    /**
     * Delete multiple DNS records in an existing zone
     * 
     * @param Array<Record> $records an array of records to delete
     */
    public function deleteRecords(string $zoneId, array $records, string $comment = '');

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
    public function updateRecord(string $zoneId, string $name, string $type, $value, int $ttl = 600, string $comment = '');

    /**
     * Update multiple DNS records in an existing zone
     * 
     * @param Array<Record> $records an array of records to delete
     */
    public function updateRecords(string $zoneId, array $records, string $comment = '');
}
