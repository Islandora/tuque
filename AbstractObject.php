<?php

/**
 * @file
 * This file contains all of the functionality for objects in the repository.
 */
require_once 'MagicProperty.php';

/**
 * An abstract class defining a Object in the repository. This is the class
 * that needs to be implemented in order to create new repository backends
 * that can be accessed using Tuque.
 *
 * These classes implement the php object array interfaces so that the object
 * can be accessed as an array. This provides access to datastreams. The object
 * is also traversable with foreach, so that each datastream can be accessed.
 *
 * @code
 * $object = new AbstractObject()
 *
 * // access every object
 * foreach ($object as $dsid => $dsObject) {
 *   // print dsid and set contents to "foo"
 *   print($dsid);
 *   $dsObject->content = 'foo';
 * }
 *
 * // test if there is a datastream called 'DC'
 * if (isset($object['DC'])) {
 *   // if there is print its contents
 *   print($object['DC']->content);
 * }
 *
 * @endcode
 */
abstract class AbstractObject extends MagicProperty implements Countable, ArrayAccess, IteratorAggregate {

  /**
   * The label for this object.
   *
   * @var string
   */
  public $label;
  /**
   * The user who owns this object.
   *
   * @var string
   */
  public $owner;
  /**
   * The state of this object. Must be one of: A (Active), I (Inactive) or
   * D (Deleted). This is a required property and cannot be unset.
   *
   * @var string
   */
  public $state;
  /**
   * The identifier of the object.
   *
   * @var string
   */
  public $id;
  /**
   * The date that the object was created. Only valid for objects that have
   * been ingested.
   *
   * @var FedoraDate
   */
  public $createdDate;
  /**
   * The date the object was last modified.
   *
   * @var FedoraDate
   */
  public $lastModifiedDate;
  /**
   * Log message associated with the creation of the object in Fedora.
   *
   * @var string
   */
  public $logMessage;
  /**
   * An array of strings containing the content models of the object.
   *
   * @var array
   */
  public $models;
  /**
   * Boolean specifying if the object has been ingested into the repository.
   *
   * @var boolean
   */
  public $ingested;

  /**
   * Set the state of the object to deleted.
   */
  abstract public function delete();

  /**
   * Get a datastream from the object.
   *
   * @param string $id
   *   The id of the datastream to retreve.
   *
   * @return AbstractDatastream
   *   Returns FALSE if the datastream could not be found. Otherwise it return
   *   an instantiated Datastream object.
   */
  abstract public function getDatastream($id);

  /**
   * Purges a datastream.
   *
   * @param string $id
   *   The id of the datastream to purge.
   *
   * @return boolean
   *   TRUE on success. FALSE on failure.
   */
  abstract public function purgeDatastream($id);

  /**
   * Factory to create new datastream objects. Creates a new datastream object,
   * this object is not ingested into the repository until you call
   * ingestDatastream.
   *
   * @param string $id
   *   The identifier of the new datastream.
   * @param string $control_group
   *   The control group the new datastream will be created in.
   *
   * @return AbstractDatastream
   *   Returns an instantiated Datastream object.
   */
  abstract public function constructDatastream($id, $control_group = 'M');

  /**
   * Ingests a datastream object into the repository.
   */
  abstract public function ingestDatastream(&$ds);

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
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);
    unset($this->logMessage);
    unset($this->models);
  }

}

/**
 * This is a decorator class meant to be applied to Repository Objects. This
 * lets other programs extend the functionality of the Abstract object class.
 */
class ObjectDecorator extends AbstractObject {

  /**
   * This variable contains the object we are decorating.
   * @var AbstractObject
   */
  protected $object;

  /**
   * Constructor for the decorator.
   *
   * @param AbstractObject $object
   *   The object to be decorated.
   */
  public function __construct(AbstractObject $object) {
    parent::__construct();
    $this->object = $object;
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
    return $this->object->$name;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __isset($name) {
    return isset($this->object->$name);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __set($name, $value) {
    $this->object->$name = $value;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __unset($name) {
    unset($this->object->$name);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __call($method, $arguments) {
    return call_user_func_array(array($this->object, $method), $arguments);
  }

  /**
   * @see AbstractObject::delete()
   */
  public function delete() {
    return $this->object->delete();
  }

  /**
   * @see AbstractObject::getDatastream()
   */
  public function getDatastream($id) {
    return $this->object->getDatastream($id);
  }

  /**
   * @see AbstractObject::purgeDatastream()
   */
  public function purgeDatastream($id) {
    return $this->object->purgeDatastream($id);
  }

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return $this->object->constructDatastream($id, $control_group);
  }

  /**
   * @see AbstractObject::ingestDatastream()
   */
  public function ingestDatastream(&$ds) {
    return $this->object->ingestDatastream($ds);
  }

  /**
   * @see Countable::count
   */
  public function count() {
    return $this->object->count();
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists($offset) {
    return $this->object->offsetExists($offset);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {
    return $this->object->offsetGet($offset);
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    $this->object->offsetSet($offset, $value);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    $this->object->offsetUnset($offset);
  }

  /**
   * IteratorAggregate::getIterator()
   */
  public function getIterator() {
    return $this->object->getIterator();
  }
}