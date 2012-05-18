<?php
/**
 * @file
 * This file contains all of the functionality for objects in the repository.
 */

require_once 'MagicProperty.php';
require_once 'FedoraDate.php';
require_once 'Datastream.php';

/**
 * An abstract class defining a Object in the repository. This is the class
 * that needs to be implemented in order to create new repository backends
 * that can be accessed using Tuque.
 */
abstract class AbstractObject extends MagicProperty {

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
   * D (Deleted). This is a manditory property and cannot be unset.
   *
   * @var string
   */
  public $state;
  public $id;
  public $createdDate;
  public $lastModifiedDate;

  abstract public function delete();
  abstract public function getDatastream($id);

  /**
   * Add a new datastream to the object.
   *
   * @param string $id
   *   The unique identifier of the datastream.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - label: Label for the datastream.
   *   - state: State of the datastream. (Active (default), Inactive, Deleted).
   *   - mimetype: The mimetype of the datastream.
   *   - versionable: (boolean) Enable/disable versioning of the datastream.
   *   - controlGroup:  one of "X", "M" (default), "R", or "E". (Inline *X*ML,
   *     *M*anaged Content, *R*edirect, or *E*xternal Referenced).
   *   - format: The format URI for the datastream.
   *   - checksumType: the algorithm used to compute a checksum for the
   *     datastream. One of DEFAULT, DISABLED (default), MD5, SHA-1, SHA-256,
   *     SHA-385, SHA-512. If this is specified and no checksum is supplied it
   *     will be automatically computed.
   *   - checksum: the value of the checksum represented as a hexadecimal
   *     string.
   *   - string: a string containing the datastream contents.
   *   - url: a url that contains the datastream contents.
   *   - file: a file containing the datastream contents.
   */
  abstract public function addDatastream($id, $params = array());
}

abstract class AbstractFedoraObject extends AbstractObject {
  protected $repository;
  protected $objectId;
  protected $objectProfile;

  public function  __construct($id, FedoraRepository $repository) {
    $this->repository = $repository;
    $this->objectId = $id;
    unset($this->id);
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);
  }

  protected function idMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectId;
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->id property.", E_USER_WARNING);
        throw new Exception();
        break;
    }
  }

  protected function stateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objState'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        switch(strtolower($value)) {
          case 'd':
          case 'deleted':
            $this->objectProfile['objState'] = 'D';
            break;
          case 'a':
          case 'active':
            $this->objectProfile['objState'] = 'A';
            break;
          case 'i':
          case 'inactive':
            $this->objectProfile['objState'] = 'I';
            break;
          default:
            trigger_error("$value is not a valid value for the object->state property.", E_USER_WARNING);
            break;
        }
        break;
      case 'unset':
        trigger_error("Cannot unset the required object->state property.", E_USER_WARNING);
        break;
    }
  }

  protected function labelMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objLabel'];
        break;
      case 'isset':
        if($this->objectProfile['objLabel'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objLabel']);
        }
        break;
      case 'set':
        $this->objectProfile['objLabel'] = $value;
        break;
      case 'unset':
        $this->objectProfile['objLabel'] = '';
        break;
    }
  }

  protected function ownerMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objOwnerId'];
        break;
      case 'isset':
        if($this->objectProfile['objOwnerId'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objOwnerId']);
        }
        break;
      case 'set':
        $this->objectProfile['objOwnerId'] = $value;
        break;
      case 'unset':
        $this->objectProfile['objOwnerId'] = '';
        break;
    }
  }
}

class NewFedoraObject extends AbstractFedoraObject {

  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    $this->objectProfile = array();
    $this->objectProfile['objState'] = 'A';
    $this->objectProfile['objOwnerId'] = '';
    $this->objectProfile['objLabel'] = '';
  }

  public function delete() {
    $this->state = 'D';
  }

  public function getDatastream($id) {}

  public function addDatastream($id, $params = array()) {}
}

class FedoraObject extends AbstractFedoraObject implements Countable, ArrayAccess, IteratorAggregate  {

  protected $datastreams = NULL;
  public $forceUpdate = FALSE;

  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    
    $this->objectProfile = $this->repository->api->a->getObjectProfile($id);
    $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
    $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
  }

  public function delete() {
    $this->state = 'd';
  }

  protected function populateDatastreams() {
    if(!isset($this->datastreams)) {
      $datastreams = $this->repository->api->a->listDatastreams($this->id);
      $this->datastreams = array();
      foreach($datastreams as $key => $value) {
        $this->datastreams[$key] = new FedoraDatastream($key, $this, $this->repository);
      }
    }
  }

  protected function modifyObject($params) {
    if(!$this->forceUpdate) {
      $params['lastModifiedDate'] = (string) $this->lastModifiedDate;
    }
    $moddate = $this->repository->api->m->modifyObject($this->id, $params);
    $this->objectProfile['objLastModDate'] = new FedoraDate($moddate);
  }

  public function purgeDatastream($id) {
    $this->populateDatastreams();

    if(!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    $this->repository->api->m->purgeDatastream($this->id, $id);
    unset($this->datastreams[$id]);
    return TRUE;
  }
  
  public function getDatastream($id) {
    $this->populateDatastreams();

    if(!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    return $this->datastreams[$id];
  }

  public function addDatastream($id, $params = array()) {}

  protected function stateMagicProperty($function, $value) {
    $previous_state = $this->objectProfile['objState'];
    $return = parent::stateMagicProperty($function, $value);

    if ($previous_state != $this->objectProfile['objState']) {
      $this->modifyObject(array('state' => $this->objectProfile['objState']));
    }
    return $return;
  }

  protected function labelMagicProperty($function, $value) {
    $previous_label = $this->objectProfile['objLabel'];
    $return = parent::labelMagicProperty($function, $value);

    if ($previous_label != $this->objectProfile['objLabel']) {
      $this->modifyObject(array('label' => $this->objectProfile['objLabel']));
    }
    return $return;
  }

  protected function ownerMagicProperty($function, $value) {
    $previous_owner = $this->objectProfile['objOwnerId'];
    $return = parent::ownerMagicProperty($function, $value);

    if ($previous_owner != $this->objectProfile['objOwnerId']) {
      $this->modifyObject(array('ownerId' => $this->objectProfile['objOwnerId']));
    }
    return $return;
  }

  protected function createdDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objCreateDate'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->createdDate property.", E_USER_WARNING);
        break;
    }
  }

  protected function lastModifiedDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objLastModDate'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->lastModifiedDate property.", E_USER_WARNING);
        break;
    }
  }

  protected function modelsMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objModels'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->models.", E_USER_WARNING);
        break;
    }
  }

  public function count() {
    $this->populateDatastreams();
    return count($this->datastreams);
  }

  public function offsetExists ($offset) {
    $this->populateDatastreams();
    return isset($this->datastreams[$offset]);
  }

  public function offsetGet ($offset) {
    return $this->getDatastream($offset);
  }

  public function offsetSet ($offset, $value) {
    trigger_error("Datastreams must be modified through the datastream object.", E_USER_WARNING);
  }

  public function offsetUnset ($offset) {
    trigger_error("Datastreams must be removed through the datastream functions.", E_USER_WARNING);
  }

  public function getIterator() {
    return new ArrayIterator($this->datastreams);
  }
}