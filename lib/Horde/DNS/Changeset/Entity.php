<?php

/**
 * The Changeset Entity
 * NOTE: It is Rdo specific, maybe include in the regular Rdo update process of the record?
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
class Horde_DNS_Changeset_Entity extends Horde_Rdo_Base
{
    public function toArray($lazy = false, $relationships = false)
    {
        return array(
            'change_id'   => $this->change_id,
            'zone_id'     => $this->zone_id,
            'record_id'   => $this->record_id,
            'transaction' => $this->transaction,
            'name'        => $this->name,
            'ttl'         => $this->ttl,
            'class'       => $this->class,
            'type'        => $this->type,
            'special'     => $this->special,
            'rdata'       => $this->rdata,
            'length'      => $this->length
        );
    }

    public function toRecArray()
    {
        return array(
            'zone_id'     => $this->zone_id,
            'name'        => $this->name,
            'ttl'         => $this->ttl,
            'class'       => $this->class,
            'type'        => $this->type,
            'special'     => $this->special,
            'rdata'       => $this->rdata,
            'length'      => $this->length
        );
    }

    // verifys a changeset, used before writen into table
    public function verifyChangeset()
    {
        $tmp = (array)$this;
        if (empty($tmp)) {
            throw new Horde_Exception();
        }

        if (!($this->transaction == "create" || $this->transaction == "update" || $this->transaction == "delete")) {
            throw new Horde_Exception(sprintf("%s is no valid transaction value",$this->transaction));
        }

        switch (strtoupper($this->type)) {
            case "A":
                if (!ip2long($this->rdata)) { throw new Horde_Exception(sprintf("%s not a valid ipv4 (required by type A)", $this->rdata)); } break;
            case "AAAA":
                if ( !filter_var($this->rdata, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) { throw new Horde_Exception(sprintf("%s not a valid ipv6 (required by type AAAA)", $this->rdata)); } break;
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
                throw new Horde_Exception(sprintf("%s is no valid RR type",$this->type));
                break;
       }

       if (!($this->class == "IN" || $this->class == "CH" || $this->class == "HS")) {
           throw new Horde_Exception(sprintf("%s is no valid BIND class",$this->class));
       }

       if ($this->ttl != '' && !is_numeric($this->ttl)) {
           throw new Horde_Exception(sprintf("ttl must be an integer (%s)",$this->ttl));
       }

       if ($this->length !='' && !is_numeric($this->length)) {
           throw new Horde_Exception(sprintf("length must be an integer (%s)",$this->length));
       }
    }

    public function delete()
    {
        parent::delete();
    }

    public function save()
    {
        $this->verifyChangeset();
        parent::save();
    }
}
