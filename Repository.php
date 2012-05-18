<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

require_once "FoxmlDocument.php";
require_once "Object.php";

/**
 * An abstract repository interface.
 *
 * This can be used to override the implementation of the Repository.
 *
 * Instantantiated children of this abstract class allow objects to be accessed
 * as an array for example to get an object:
 * @code
 *   $object = $repository['objectid'];
 * @endcode
 *
 * To test if an object exists (returns a BOOLEAN):
 * @code
 *   $exists = isset($repository['objectid']);
 * @endcode
 *
 * Assignment and the unset function are not supported. This functionality is
 * just a helper, and the same functionaluty can be accessed by calling class
 * member functions.
 */
abstract class AbstractRepository extends MagicProperty implements ArrayAccess {

  /**
   * This method is a factory that will return a new repositoryobject object
   * that can be manipulated and then ingested into the repository.
   *
   * @param string $id
   *   The ID to assign to this object. There are three options:
   *   - NULL: An ID will be assigned.
   *   - A namespace: An ID will be assigned in this namespace.
   *   - A whole ID: The whole ID must contains a namespace and a identifier in
   *     the form NAMESPACE:IDENTIFIER
   *
   * @return AbstractObject
   *   Returns an instantiated AbstractObject object that can be manipulated.
   *   This object will not actually be created in the repository until the
   *   ingest method is called.
   */
  abstract public function constructNewObject($id = NULL);

  /**
   * This ingests a new object into the repository.
   *
   * @param AbstractObject &$object
   *   The instantiated AbstractObject to ingest into the repository. This
   *   object is passed by reference, and the reference will be replaced by
   *   an object representing the ingested AbstractObject.
   *
   * @return AbstractObject
   *   The ingested abstract object.
   */
  abstract public function ingestNewObject(NewFedoraObject &$object);

  /**
   * Gets a object from the repository.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return AbstractObject
   *   The requested object.
   */
  abstract public function getObject($id);

  /**
   * Removes an object from the repository.
   *
   * This function removes an object from the repository premenenty. It is a
   * dangerous function since it remvoes an object and all of its history from
   * the repository permenently.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return boolean
   *   TRUE if object was purged.
   */
  abstract public function purgeObject($id);

  /**
   * Search the repository for objects.
   *
   * This function isn't fully implemented yet.
   *
   * @todo Flesh out the function definition for this.
   */
  abstract public function findObjects(array $search);
}

/**
 * Concrete implementation of the AbstractRepository for Fedora.
 *
 * The parent class has more detailed documentation about how this class can
 * be called as an Array.
 *
 * @see AbstractRepository
 */
class FedoraRepository extends AbstractRepository {

  /**
   * This is an instantiated AbstractCache that we use to make sure we aren't
   * instantiating the same objects over and over.
   * 
   * @var AbstractCache
   */
  protected $cache;

  /**
   * Constructor for the FedoraRepository Object.
   *
   * @param FedoraApi $api
   *   An instantiated FedoraAPI which will be used to connect to the
   *   repository.
   * @param AbstractCache $cache
   *   An instantiated AbstractCache which will be used to cache fedora objects.
   */
  public function __construct(FedoraApi $api, AbstractCache $cache) {
    $this->api = $api;
    $this->cache = $cache;
  }

  /**
   * Find objects in the Repository.
   *
   * @param array $search
   * @see AbstractRepository::findObjects
   */
  public function findObjects(array $search) {
  }

  /**
   * @todo validate the ID
   * @todo catch the getNextPid errors
   *
   * @see AbstractRepository::constructNewObject
   */
  public function constructNewObject($id = NULL) {
    if($this->cache->get($id) !== FALSE) {
      return FALSE;
    }

    $exploded = explode(':', $id);

    if(!$id) {
      $id = $this->api->m->getNextPid();
    }
    elseif (count($exploded) == 1) {
      $id = $this->api->m->getNextPid($exploded[0]);
    }

    return new NewFedoraObject($id, $this);
  }

  /*
   * @todo error handling
   */
  public function ingestNewObject(NewFedoraObject &$object) {
    $dom = new FoxmlDocument($object);
    $xml = $dom->saveXml();
    $id = $this->api->m->ingest(array('string' => $xml));
    $object = new FedoraObject($id, $this);
    $this->cache->set($id, $object);
    return $object;
  }

  public function getObject($id) {
    $object = $this->cache->get($id);
    if($object !== FALSE) {
      return $object;
    }

    try {
      $object = new FedoraObject($id, $this);
      $this->cache->set($id, $object);
      return $object;
    }
    catch (RepositoryException $e) {
      // check to see if its a 401 or a 404
      $previous = $e->getPrevious();
      if($previous && ($previous->getCode == 404 || $previous->getCode == 401)) {
        return NULL;
      }
      else {
        // @todo fix this, it should throw something else.
        throw $e;
      }
    }
  }

  public function purgeObject($id) {
    $object = $this->cache->get($id);
    if($object !== FALSE) {
      $this->cache->delete($id);
    }

    try {
      $this->api->m->purgeObject($id);
    }
    catch (RepositoryException $e) {
      // @todo chain exceptions here.
      throw $e;
    }
  }

  public function offsetExists ( $offset ) {}
  public function offsetGet ( $offset ) {}
  public function offsetSet ( $offset , $value ) {}
  public function offsetUnset ( $offset ) {}
}
