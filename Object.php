<?php
/**
 * @file
 * This file contains all of the functionality for objects in the repository.
 */

require_once 'MagicProperty.php';
require_once 'FedoraDate.php';
require_once 'Datastream.php';
require_once 'FedoraRelationships.php';

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
 * foreach($object as $dsid => $dsObject) {
 *   // print dsid and set contents to "foo"
 *   print($dsid);
 *   $dsObject->content = 'foo';
 * }
 *
 * // test if there is a datastream called 'DC'
 * if(isset($object['DC'])) {
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
}

/**
 * This is the base class for a Fedora Object.
 */
abstract class AbstractFedoraObject extends AbstractObject {

  /**
   * This is an object for manipulating relationships related to this object.
   * @var FedoraRelsExt
   */
  public $relationships;

  /**
   * The repository this object belongs to.
   * @var FedoraRepository
   */
  public $repository;

  /**
   * The ID of this object.
   * @var string
   */
  protected $objectId;

  /**
   * The object profile from fedora for this object.
   * @var array
   * @see FedoraApiA::getObjectProfile
   */
  protected $objectProfile;

  /**
   * Constructosaurus.
   */
  public function  __construct($id, FedoraRepository $repository) {
    $this->repository = $repository;
    $this->objectId = $id;
    unset($this->id);
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);
    unset($this->logMessage);
    $this->relationships = new FedoraRelsExt($this);
  }

  /**
   * @see AbstractObject::delete()
   */
  public function delete() {
    $this->state = 'D';
  }

  /**
   * @see AbstractObject::id
   */
  protected function idMagicProperty($function, $value) {
    switch ($function) {
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

  /**
   * @see AbstractObject::state
   */
  protected function stateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objState'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        switch (strtolower($value)) {
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

  /**
   * @see AbstractObject::label
   */
  protected function labelMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objLabel'];
        break;

      case 'isset':
        if ($this->objectProfile['objLabel'] === '') {
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

  /**
   * @see AbstractObject::owner
   */
  protected function ownerMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objOwnerId'];
        break;

      case 'isset':
        if ($this->objectProfile['objOwnerId'] === '') {
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
  
  /**
   * @see AbstractObject::logMessage
   */
  protected function logMessageMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objLogMessage'];
        break;

      case 'isset':
        if ($this->objectProfile['objLogMessage'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objLogMessage']);
        }
        break;

      case 'set':
        $this->objectProfile['objLogMessage'] = $value;
        break;

      case 'unset':
        $this->objectProfile['objLogMessage'] = '';
        break;
    }
  }  

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return new NewFedoraDatastream($id, $control_group, $this, $this->repository);
  }
}

/**
 * This represents a new fedora object that hasn't been ingested yet. It lets
 * us pass around the object and add datastreams before we ingest it. This
 * shouldn't be constructed on its own, it should only be created from a
 * factory class.
 */
class NewFedoraObject extends AbstractFedoraObject {

  /**
   * An array of cached datastream objects.
   * @var array
   */
  protected $datastreams = array();

  /**
   * Constructoman!
   */
  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    $this->objectProfile = array();
    $this->objectProfile['objState'] = 'A';
    $this->objectProfile['objOwnerId'] = '';
    $this->objectProfile['objLabel'] = '';
    $this->objectProfile['objLogMessage'] = '';
  }

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return parent::constructDatastream($id, $control_group);
  }

  /**
   * This function doesn't actually ingest the datastream, it just adds it to
   * the queue to be ingested whenever this object is ingested.
   *
   * @see AbstractObject::ingestDatastream()
   *
   * @param NewFedoraDatastream $ds
   *   the datastream to be ingested
   *
   * @return mixed
   *   FALSE on failure otherwise a instantiated FedoraDatastream.
   */
  public function ingestDatastream(&$ds) {
    if (!isset($this->datastreams[$ds->id])) {
      $this->datastreams[$ds->id] = $ds;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see AbstractObject::purgeDatastream()
   */
  public function purgeDatastream($id) {
    if (isset($this->datastreams[$id])) {
      unset($this->datastreams[$id]);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see AbstractObject::getDatastream()
   */
  public function getDatastream($id) {
    if (isset($this->datastreams[$id])) {
      return $this->datastreams[$id];
    }
    else {
      return NULL;
    }
  }

  /**
   * @see Countable::count
   */
  public function count() {
    return count($this->datastreams);
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists ($offset) {
    return isset($this->datastreams[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet ($offset) {
    if ($this->offsetExists($offset)) {
      return $this->datastreams[$offset];
    }
    else {
      return NULL;
    }
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet ($offset, $value) {
    trigger_error("Datastreams must be added though the NewFedoraObect->ingestDatastream() function.", E_USER_WARNING);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset ($offset) {
    $this->purgeDatastream($offset);
  }

  /**
   * @see IteratorAggregate::getIterator()
   */
  public function getIterator() {
    return new ArrayIterator($this->datastreams);
  }
}

/**
 * Represents a Fedora Object. Should be created by the factory method in the
 * repository. Respects object locking. Will throw an exception if an object
 * is modified under us.
 */
class FedoraObject extends AbstractFedoraObject {

  /**
   * Instantiated list of datastream objects.
   * @var array
   */
  protected $datastreams = NULL;

  /**
   * If this is true we won't respect object locking, we will clobber anything
   * that has been changed.
   * @var boolean
   */
  public $forceUpdate = FALSE;

  /**
   * The class constructor. Should be instantiated by the repository.
   */
  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);

    $this->objectProfile = $this->repository->api->a->getObjectProfile($id);
    $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
    $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
    $this->objectProfile['objLogMessage'] = '';
  }

  /**
   * this will populate the datastream list the first time it is needed.
   */
  protected function populateDatastreams() {
    if (!isset($this->datastreams)) {
      $datastreams = $this->repository->api->a->listDatastreams($this->id);
      $this->datastreams = array();
      foreach ($datastreams as $key => $value) {
        $this->datastreams[$key] = new FedoraDatastream($key, $this, $this->repository, array("dsLabel" => $value['label'], "dsMIME" => $value['mimetype']));
      }
    }
  }

  /**
   * This is a wrapper on APIM modify object to make sure we respect locking.
   */
  protected function modifyObject($params) {
    if (!$this->forceUpdate) {
      $params['lastModifiedDate'] = (string) $this->lastModifiedDate;
    }
    $moddate = $this->repository->api->m->modifyObject($this->id, $params);
    $this->objectProfile['objLastModDate'] = new FedoraDate($moddate);
  }

  /**
   * Purge a datastream.
   *
   * @param string $id
   *   The id of hte datastream to purge.
   *
   * @return boolean
   *   Returns TRUE on success and FALSE on failure.
   */
  public function purgeDatastream($id) {
    $this->populateDatastreams();

    if (!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    $this->repository->api->m->purgeDatastream($this->id, $id);
    unset($this->datastreams[$id]);
    return TRUE;
  }

  /**
   * @see AbstractObject::getDatastream()
   */
  public function getDatastream($id) {
    $this->populateDatastreams();

    if (!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    return $this->datastreams[$id];
  }

  /**
   * @see AbstractObject::state
   */
  protected function stateMagicProperty($function, $value) {
    $previous_state = $this->objectProfile['objState'];
    $return = parent::stateMagicProperty($function, $value);

    if ($previous_state != $this->objectProfile['objState']) {
      $this->modifyObject(array('state' => $this->objectProfile['objState']));
    }
    return $return;
  }

  /**
   * @see AbstractObject::label
   */
  protected function labelMagicProperty($function, $value) {
    $previous_label = $this->objectProfile['objLabel'];
    $return = parent::labelMagicProperty($function, $value);

    if ($previous_label != $this->objectProfile['objLabel']) {
      $this->modifyObject(array('label' => $this->objectProfile['objLabel']));
    }
    return $return;
  }

  /**
   * @see AbstractObject::owner
   */
  protected function ownerMagicProperty($function, $value) {
    $previous_owner = $this->objectProfile['objOwnerId'];
    $return = parent::ownerMagicProperty($function, $value);

    if ($previous_owner != $this->objectProfile['objOwnerId']) {
      $this->modifyObject(array('ownerId' => $this->objectProfile['objOwnerId']));
    }
    return $return;
  }

  /**
   * @see AbstractObject::createdDate
   */
  protected function createdDateMagicProperty($function, $value) {
    switch ($function) {
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

  /**
   * @see AbstractObject::lastModifiedDate
   */
  protected function lastModifiedDateMagicProperty($function, $value) {
    switch ($function) {
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

  /**
   * @see AbstractObject::models
   */
  protected function modelsMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        $models = array();
        // Cut off info:fedora/.
        foreach($this->objectProfile['objModels'] as $model) {
          $models[] = substr($model, 12);
        }
        return $models;
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
  
  /**
   * @see AbstractObject::logMessage
   */
  protected function logMessageMagicProperty($function, $value) {
    $previous_message = $this->objectProfile['objLogMessage'];
    $return = parent::logMessageMagicProperty($function, $value);

    if ($previous_message != $this->objectProfile['objLogMessage']) {
      $this->modifyObject(array('logMessage' => $this->objectProfile['objLogMessage']));
    }
    return $return;
  }

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return parent::constructDatastream($id, $control_group);
  }

  /**
   * @see AbstractObject::ingestDatastream()
   */
  public function ingestDatastream(&$ds) {
    $this->populateDatastreams();
    if (!isset($this->datastreams[$ds->id])) {
      $params = array(
        'controlGroup' => $ds->controlGroup,
        'dsLabel' => $ds->label,
        'versionable' => $ds->versionable,
        'dsState' => $ds->state,
        'formatURI' => $ds->format,
        'checksumType' => $ds->checksumType,
        'mimeType' => $ds->mimetype,
        'logMessage' => $ds->logMessage,
      );
      $dsinfo = $this->repository->api->m->addDatastream($this->id, $ds->id, $ds->contentType, $ds->content, $params);
      $ds = new FedoraDatastream($ds->id, $this, $this->repository, $dsinfo);
      $this->datastreams[$ds->id] = $ds;
      return $ds;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see Countable::count
   */
  public function count() {
    $this->populateDatastreams();
    return count($this->datastreams);
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists ($offset) {
    $this->populateDatastreams();
    return isset($this->datastreams[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet ($offset) {
    $this->populateDatastreams();
    return $this->getDatastream($offset);
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet ($offset, $value) {
    trigger_error("Datastreams must be added using the FedoraObject->ingestDatastream function.", E_USER_WARNING);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset ($offset) {
    $this->populateDatastreams();
    if (isset($this->datastreams[$offset])) {
      $this->purgeDatastream($offset);
    }
  }

  /**
   * IteratorAggregate::getIterator()
   */
  public function getIterator() {
    $this->populateDatastreams();
    return new ArrayIterator($this->datastreams);
  }
}
