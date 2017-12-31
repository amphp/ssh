<?php

namespace Amp\SSH\Transport;

/**
 * A byte represents an arbitrary 8-bit value (octet).  Fixed length
 * data is sometimes represented as an array of bytes, written
 * byte[n], where n is the number of bytes in the array.
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_byte(&$payload) {
    $byte = unpack('C', $payload)[1];
    $payload = substr($payload, 1);

    return $byte;
}

function read_bytes(&$payload, $length) {
    $bytes = substr($payload, 0, $length);
    $payload = substr($payload, $length);

    return $bytes;
}

/**
 * A boolean value is stored as a single byte.  The value 0
 * represents FALSE, and the value 1 represents TRUE.  All non-zero
 * values MUST be interpreted as TRUE; however, applications MUST NOT
 * store values other than 0 and 1.
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_boolean (&$payload) {
    return (bool) read_byte($payload);
}

/**
 * Represents a 32-bit unsigned integer.  Stored as four bytes in the
 * order of decreasing significance (network byte order).  For
 * example: the value 699921578 (0x29b7f4aa) is stored as 29 b7 f4
 * aa.
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_uint32(&$payload) {
    $uint32 = unpack('N', $payload)[1];
    $payload = substr($payload, 4);

    return $uint32;
}

/**
 * Represents a 64-bit unsigned integer.  Stored as eight bytes in
 * the order of decreasing significance (network byte order).
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_uint64(&$payload) {
    $uint64 = unpack('J', $payload)[1];
    $payload = substr($payload, 8);

    return $uint64;
}

/**
 * Arbitrary length binary string.  Strings are allowed to contain
 * arbitrary binary data, including null characters and 8-bit
 * characters.  They are stored as a uint32 containing its length
 * (number of bytes that follow) and zero (= empty string) or more
 * bytes that are the value of the string.  Terminating null
 * characters are not used.
 *
 * Strings are also used to store text.  In that case, US-ASCII is
 * used for internal names, and ISO-10646 UTF-8 for text that might
 * be displayed to the user.  The terminating null character SHOULD
 * NOT normally be stored in the string.  For example: the US-ASCII
 * string "testing" is represented as 00 00 00 07 t e s t i n g.  The
 * UTF-8 mapping does not alter the encoding of US-ASCII characters.
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_string(&$payload) {
    $length = read_uint32($payload);
    $string = substr($payload, 0, $length);
    $payload = substr($payload, $length);

    return $string;
}

/**
 * Represents multiple precision integers in two's complement format,
 * stored as a string, 8 bits per byte, MSB first.  Negative numbers
 * have the value 1 as the most significant bit of the first byte of
 * the data partition.  If the most significant bit would be set for
 * a positive number, the number MUST be preceded by a zero byte.
 * Unnecessary leading bytes with the value 0 or 255 MUST NOT be
 * included.  The value zero MUST be stored as a string with zero
 * bytes of data.
 *
 * By convention, a number that is used in modular computations in
 * Z_n SHOULD be represented in the range 0 <= x < n.
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_mpint(&$payload) {
    return read_string($payload);
}

/**
 * A string containing a comma-separated list of names.  A name-list
 * is represented as a uint32 containing its length (number of bytes
 * that follow) followed by a comma-separated list of zero or more
 * names.  A name MUST have a non-zero length, and it MUST NOT
 * contain a comma (",").  As this is a list of names, all of the
 * elements contained are names and MUST be in US-ASCII.  Context may
 * impose additional restrictions on the names.  For example, the
 * names in a name-list may have to be a list of valid algorithm
 * identifiers (see Section 6 below), or a list of [RFC3066] language
 * tags.  The order of the names in a name-list may or may not be
 * significant.  Again, this depends on the context in which the list
 * is used.  Terminating null characters MUST NOT be used, neither
 * for the individual names, nor for the list as a whole.
 *
 * @see https://tools.ietf.org/html/rfc4251#section-5
 */
function read_namelist(&$payload) {
    $nameListString = read_string($payload);

    if (empty($nameListString)) {
        return [];
    }

    return explode(',', $nameListString);
}