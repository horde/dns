<?php

/**
 * The old and outdated Manager
 * TODO: Cut it out, check if something is required before
 * No need for additional comments here
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
class Horde_DNS_Manager
{

    /* below is code originally by Diana for the old design  remove once we know what we need to keep */

    protected $_adapter = null;
    protected $_factory = null;

    public function __get($key)
    {
        switch($key) {
            case 'adapter':
            case 'factory':
            case 'mapper':
                return $this->{'_' . $key};
        }
        return null;
    }

    public function setFactory(Horde_DNS_Factory_Manager $factory = null)
    {
        $this->_factory = $factory;
        if (!is_null($factory) && is_string($this->_mapper)) {
            $this->_mapper = $factory->mapper->create($this->_mapper);
        }
        return $this;
    }

    // verifys a changeset, used before write into table
    public function verifyChangeset(Horde_DNS_Changeset_Entity $changeset)
    {
        if (empty($changeset)) {
            throw new Horde_DNS_Exception();
        }

        if (!($changeset->transaction == "create" || $changeset->transaction == "update" || $changeset->transaction == "delete")) {
            throw new Horde_DNS_Exception(sprintf("%s is no valid transaction value",$changeset->transaction));
        }

        switch (strtoupper($changeset->type)) {
            case "A":
                if (!ip2long($changeset->rdata)) { throw new Horde_DNS_Exception(sprintf("%s not a valid ipv4 (required by type A)", $changeset->rdata)); } break;
            case "AAAA":
                if ( !filter_var($changeset->rdata, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) { throw new Horde_DNS_Exception(sprintf("%s not a valid ipv6 (required by type AAAA)", $changeset->rdata)); } break;
            case "AFSDB": break;
            case "APL": break;
            case "A6": break;
            case "CAA": break;
            case "CDNSKEY": break;
            case "CDS": break;
            case "CERT": break;
            case "CNAME": break;
            case "DHCID": break;
            case "DLV": break;
            case "DNAME": break;
            case "DNSKEY": break;
            case "DS": break;
            case "GPOS": break;
            case "HIP": break;
            case "HINFO": break;
            case "IPSECKEY": break;
            case "ISDN": break;
            case "KEY": break;
            case "KX": break;
            case "LOC": break;
            case "MB": break;
            case "MD": break;
            case "MF": break;
            case "MG": break;
            case "MINFO": break;
            case "MR": break;
            case "MX": break;
            case "NAPTR": break;
            case "NS": break;
            case "NSAP": break;
            case "NSEC": break;
            case "NSEC3": break;
            case "NSEC3PARAM": break;
            case "NULL": break;
            case "NXT": break;
            case "OPT": break;
            case "PTR": break;
            case "RP": break;
            case "RRSIG": break;
            case "SIG": break;
            case "SOA": break;
            case "SPF": break;
            case "SRV": break;
            case "SSHFP": break;
            case "TA": break;
            case "TKEY": break;
            case "TLSA": break;
            case "TSIG": break;
            case "TXT": break;
            case "WKS": break;
            case "X25": break;
            default:
                throw new Horde_DNS_Exception(sprintf("%s is no valid RR type",$changeset->type));
                break;
        }

        if (!($changeset->class == "IN" || $changeset->class == "CH" || $changeset->class == "HS")) {
            throw new Horde_DNS_Exception(sprintf("%s is no valid BIND class", $changeset->class));
        }

        if (!empty($changeset->ttl) && !is_num($changeset->ttl)) {
            throw new Horde_DNS_Exception(sprintf("ttl must be an integer (%s)", $changeset->ttl));
        }
    }

}
