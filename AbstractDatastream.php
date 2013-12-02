<?php

/**
 * @file
 * Defines the AbstractObject interface.
 */

namespace {
  /**
   * This class can be overriden by anything implementing a datastream.
   */
  interface AbstractDatastream {

    /**
     * This will set the state of the datastream to deleted.
     */
    public function delete();

    /**
     * Set the contents of the datastream from a file.
     *
     * @param string $file
     *   The full path of the file to set to the contents of the datastream.
     */
    public function setContentFromFile($file);

    /**
     * Set the contents of the datastream from a URL.
     *
     * The contents of this URL will be fetched, and the datastream will be
     * updated to contain the contents of the URL.
     *
     * @param string $url
     *   The full URL to fetch.
     */
    public function setContentFromUrl($url);

    /**
     * Set the contents of the datastream from a string.
     *
     * @param string $string
     *   The string whose contents will become the contents of the datastream.
     */
    public function setContentFromString($string);

    /**
     * Get the contents of a datastream and output it to the file provided.
     *
     * @param string $file
     *   The path of the file to output the contents of the datastream to.
     *
     * @return bool
     *   TRUE on success or FALSE on failure.
     */
    public function getContent($file);
  }
}

namespace Tuque {

  /**
   * This class acts as a wrapper for the actual Object implementation.
   */
  class Datastream extends Delegate implements \AbstractDatastream {

    /**
     * Constructor for the Repository object.
     *
     * @param AbstractDatastream $datastream
     *   The object this object wraps.
     */
    public function __construct(\AbstractDatastream $datastream) {
      parent::__construct($datastream);
    }

    /**
     * Get a datastream version from the object and decorate it.
     *
     * @see AbstractDatastream::getDatastreamVersion
     */
    public function getDatastreamVersion(array $datastream_info) {
      return parent::getDatastreamVersion($datastream_info);
    }

    /**
     * This will set the state of the datastream to deleted.
     *
     * @see AbstractDatastream::delete
     */
    public function delete() {
      return parent::delete();
    }

    /**
     * Set the contents of the datastream from a file.
     *
     * @see AbstractDatastream::setContentFromFile
     */
    public function setContentFromFile($file) {
      return parent::setContentFromFile($file);
    }

    /**
     * Set the contents of the datastream from a URL.
     *
     * @see AbstractDatastream::setContentFromFile
     */
    public function setContentFromUrl($url) {
      return parent::setContentFromUrl($url);
    }

    /**
     * Set the contents of the datastream from a string.
     *
     * @see AbstractDatastream::setContentFromString
     */
    public function setContentFromString($string) {
      return parent::setContentFromString($string);
    }

    /**
     * Get the contents of a datastream and output it to the file provided.
     *
     * @see AbstractDatastream::getContent
     */
    public function getContent($file) {
      return parent::getContent($file);
    }

    /**
     * @see Countable::count
     */
    public function count() {
      return parent::count();
    }

    /**
     * @see ArrayAccess::offsetExists
     */
    public function offsetExists($offset) {
      return parent::offsetExists($offset);
    }

    /**
     * @see ArrayAccess::offsetGet
     */
    public function offsetGet($offset) {
      return parent::offsetGet($offset);
    }

    /**
     * @see ArrayAccess::offsetSet
     */
    public function offsetSet($offset, $value) {
      return parent::offsetSet($offset, $value);
    }

    /**
     * @see ArrayAccess::offsetUnset
     */
    public function offsetUnset($offset) {
      return parent::offsetUnset($offset);
    }

    /**
     * IteratorAggregate::getIterator()
     */
    public function getIterator() {
      return parent::getIterator();
    }

  }

  /**
   * This class acts as a wrapper for the actual Object implementation.
   *
   * This class doesn't ensure type safety when being constructed, it's up to
   * the programmer for now to ensure that only NewObject implementations are
   * passed as the argument to this objects constructor.
   *
   * This class is largely the same as Tuque\Datastream above in that the
   * delgate handles all the logic. Any case where there is an implementation of
   * a method below indicates that the NewDatastream doesn't have the *same*
   * interface for a given function/member (despite having the same name and
   * arguments) as the Object class did in the original Fedora 3 implementation.
   */
  class NewDatastream extends Datastream {

  }
}
