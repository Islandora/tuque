<?php
/**
 * This file represents a Fedora Date. It wraps the PHP datetime functions
 * specifically for the date format used in Fedora. This allows the easy
 * comparison of dates for example.
 */

class FedoraDate {

  /**
   * The structure which holds the date.
   *
   * @var DateTime
   */
  protected $date;

  /**
   * Get the date in a format that Fedora can use.
   *
   * @return string
   *   A fedora formated iso8601 date.
   */
  function __toString() {
    // Fedora will only accept 3 decial places for fractional seconds and PHP
    // returns 6 by default. So we do a little string mangling to make them
    // friends again.
    $string = (string) $this->date->format("Y-m-d\TH:i:s.u\Z");
    $exploded = explode('.', $string);
    $exploded[1] = substr($exploded[1],0,3);
    $string = implode('.', $exploded);
    return $string;
  }

  /**
   * Equivalent to DateTime::format.
   *
   * @param string $format
   *   Format accepted by date().
   *
   * @return string
   *   Returns the formatted date string on success or FALSE on failure.
   */
  function format($format) {
    return $this->date->format($format);
  }

  /**
   * Instantiate FedoraDate.
   *
   * @param string $time
   *   The date as you would pass to create a DateTime object.
   */
  function  __construct($time) {
    // Make sure we have a default timezone set. We need to use the shutup
    // operator because getting the timezone if its not set will actually
    // throw a warning. Ugh.
    date_default_timezone_set(@date_default_timezone_get());
    $this->date = new DateTime($time, new DateTimeZone('UTC'));
  }

  /**
   * Serialize this object.
   *
   * @return array
   *   The class members to be serialized.
   */
  public function __sleep(){
    // PHP Date class loses information when serialized so we need to convert
    // it to a string and then reconstruct it.
    $this->date = (string) $this->date->format("Y-m-d\TH:i:s.u\Z");
    return array('date');
  }

  /**
   * Unserialize this object.
   */
  public function __wakeup() {
    // PHP Date class loses information when serialized so we need to convert
    // it to a string and then reconstruct it.
    $this->date = new DateTime($this->date);
  }
}
