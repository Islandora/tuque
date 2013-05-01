<?php

/**
 * @file
 * This file defines all the classes used to manipulate datastreams in the
 * repository.
 */
require_once 'MagicProperty.php';

/**
 * This abstract class can be overriden by anything implementing a datastream.
 */
abstract class AbstractDatastream extends MagicProperty implements Countable, ArrayAccess, IteratorAggregate {

  /**
   * This will set the state of the datastream to deleted.
   */
  abstract public function delete();

  /**
   * Set the contents of the datastream from a file.
   *
   * @param string $file
   *   The full path of the file to set to the contents of the datastream.
   */
  abstract public function setContentFromFile($file);

  /**
   * Set the contents of the datastream from a URL. The contents of this
   * URL will be fetched, and the datastream will be updated to contain the
   * contents of the URL.
   *
   * @param string $url
   *   The full URL to fetch.
   */
  abstract public function setContentFromUrl($url);

  /**
   * Set the contents of the datastream from a string.
   *
   * @param string $string
   *   The string whose contents will become the contents of the datastream.
   */
  abstract public function setContentFromString($string);

  /**
   * Get the contents of a datastream and output it to the file provided.
   *
   * @param string $file
   *   The path of the file to output the contents of the datastream to.
   *
   * @return
   *   TRUE on success or FALSE on failure.
   */
  abstract public function getContent($file);

  /**
   * The identifier of the datastream. This is a read-only property.
   *
   * @var string
   */
  public $id;
  /**
   * The label of the datastream.
   * @var string
   */
  public $label;
  /**
   * the location of consists of a combination of
   * datastream id and datastream version id
   * @var type
   */
  public $location;
  /**
   * The control group of the datastream. This property is read-only. This will
   * return one of: "X", "M", "R", or "E" (Inline *X*ML, *M*anaged Content,
   * *R*edirect, or *E*xternal Referenced). Defaults to "M".
   * @var string
   */
  public $controlGroup;
  /**
   * This defines if the datastream will be versioned or not.
   * @var boolean
   */
  public $versionable;
  /**
   * The state of the datastream. This will be one of: "A", "I", "D". When
   * setting the property you can use: A, I, D or Active, Inactive, Deleted.
   * @var string
   */
  public $state;
  /**
   * The mimetype of the datastrem.
   * @var string
   */
  public $mimetype;
  /**
   * The format of the datastream.
   * @var string
   */
  public $format;
  /**
   * The size in bytes of the datastream. This is only valid once a datastream
   * has been ingested.
   *
   * @var int
   */
  public $size;
  /**
   * The base64 encoded checksum string.
   *
   * @var string
   */
  public $checksum;
  /**
   * The type of checksum that will be done on this datastream. Defaults to
   * DISABLED. One of: DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
   *
   * @var string
   */
  public $checksumType;
  /**
   * The date the datastream was created.
   *
   * @var FedoraDate
   */
  public $createdDate;
  /**
   * The contents of the datastream as a string. This can only be set for
   * M and X datastreams. For R and E datastreams the URL property needs to be
   * set which will change the contents of this property. This should only be
   * used for small files, as it loads the contents into PHP memory. Otherwise
   * you should use the getContent function.
   *
   * @var string
   */
  public $content;
  /**
   * This is only valid for R and E datastreams. This is the URL that the
   * datastream references.
   *
   * @var string
   */
  public $url;
  /**
   * This is the log message that will be associated with the action in the
   * Fedora audit datastream.
   *
   * @var string
   */
  public $logMessage;
  /**
   * Boolean specifying if the datastream has been ingested into the repository.
   *
   * @var boolean
   */
  public $ingested;


  /**
   * Unsets public members.
   *
   * We only define the public members of the object for Doxygen, they aren't actually accessed or used,
   * and if they are not unset, they can cause problems after unserialization.
   */
  public function __construct() {
    $this->unset_members();
  }

  /**
   * Upon unserialization unset any public members.
   */
  public function __wakeup() {
    $this->unset_members();
  }

  /**
   * Unsets public members, required for child classes to funciton properly with MagicProperties.
   */
  private function unset_members() {
    unset($this->id);
    unset($this->label);
    unset($this->controlGroup);
    unset($this->versionable);
    unset($this->state);
    unset($this->mimetype);
    unset($this->format);
    unset($this->size);
    unset($this->checksum);
    unset($this->checksumType);
    unset($this->createdDate);
    unset($this->content);
    unset($this->url);
    unset($this->location);
    unset($this->logMessage);
  }
}

/**
 * This is a decorator class menat to the applied to imeplmentations of the
 * AbstractDatastream class. This allows other programs to decorate instances
 * of AbstractDatastreams.
 */
class DatastreamDecorator extends AbstractDatastream {

  /**
   * The datastream being decorated.
   * @var AbstractDatastream
   */
  protected $datastream;

  /**
   * Constructor for the datastream decorator.
   *
   * @param AbstractDatastream $datastream
   *   The datastream to be decorated.
   */
  public function __construct(AbstractDatastream $datastream) {
    parent::__construct();
    $this->datastream = $datastream;
    unset($this->ingested);
  }

  public function __wakeup() {
    parent::__wakeup();
    unset($this->ingested);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __get($name) {
    return $this->datastream->$name;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __isset($name) {
    return isset($this->datastream->$name);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __set($name, $value) {
    $this->datastream->$name = $value;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __unset($name) {
    unset($this->datastream->$name);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __call($method, $arguments) {
    return call_user_func_array(array($this->datastream, $method), $arguments);
  }

  /**
   * @see AbstractDatastream::delete()
   */
  public function delete() {
    return $this->datastream->delete();
  }

  /**
   * @see AbstractDatastream::setContentFromFile()
   */
  public function setContentFromFile($file) {
    return $this->datastream->setContentFromFile($file);
  }

  /**
   * @see AbstractDatastream::setContentFromUrl()
   */
  public function setContentFromUrl($url) {
    return $this->datastream->setContentFromUrl($url);
  }

  /**
   * @see AbstractDatastream::setContentFromString()
   */
  public function setContentFromString($string) {
    return $this->datastream->setContentFromString($string);
  }

  /**
   * @see AbstractDatastream::getContent()
   */
  public function getContent($file) {
    return $this->datastream->getContent($file);
  }

  /**
   * @see AbstractObject::delete()
   */
  public function count() {
    return $this->datastream->count();
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists($offset) {
    return $this->datastream->offsetExists($offset);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {
    return $this->datastream->offsetGet($offset);
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    return $this->datastream->offsetSet($offset, $value);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    return $this->datastream->offsetUnset($offset);
  }

  /**
   * IteratorAggregate::getIterator()
   */
  public function getIterator() {
    return $this->datastream->getIterator();
  }
}