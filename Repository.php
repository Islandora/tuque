<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

require_once "RepositoryQuery.php";
require_once "FoxmlDocument.php";
require_once "Object.php";

/**
 * An abstract repository interface.
 *
 * This can be used to override the implementation of the Repository.
 */
abstract class AbstractRepository extends MagicProperty {

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
  abstract public function constructObject($id = NULL);

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
  abstract public function ingestObject(NewFedoraObject &$object);

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
   * This function isn't implemented yet.
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
   * This provides some convientent methods for searching the resource index.
   *
   * @var RepositoryQuery
   */
  public $ri;

  public $api;

  protected $queryClass = 'RepositoryQuery';
  protected $newObjectClass = 'NewFedoraObject';
  protected $objectClass = 'FedoraObject';

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
    $this->ri = new $this->queryClass($this->api->connection);
  }

  /**
   * @see AbstractRepository::findObjects
   * @todo this needs to be implemented!
   */
  public function findObjects(array $search) {
  }

  /**
   * @todo validate the ID
   * @todo catch the getNextPid errors
   *
   * @see AbstractRepository::constructObject
   */
  public function constructObject($id = NULL) {
    $exploded = explode(':', $id);
    if (!$id) {
      $id = $this->api->m->getNextPid();
    }
    elseif (count($exploded) == 1) {
      $id = $this->api->m->getNextPid($exploded[0]);
    }
    return new $this->newObjectClass($id, $this);
  }

  /**
   * @see AbstractRepository::ingestObject()
   * @todo error handling
   */
  public function ingestObject(NewFedoraObject &$object) {
    // we want all the managed datastreams to be uploaded
    foreach ($object as $ds) {
      if ($ds->controlGroup == 'M') {
        $temp = tempnam(sys_get_temp_dir(), 'tuque');
        $return = $ds->getContent($temp);
        if ($return === TRUE) {
          $url = $this->api->m->upload($temp);
          $ds->setContentFromUrl($url);
        }
        unlink($temp);
      }
    }

    $dom = new FoxmlDocument($object);
    $xml = $dom->saveXml();
    $id = $this->api->m->ingest(array('string' => $xml, 'logMessage' => $object->logMessage));
    $object = new $this->objectClass($id, $this);
    $this->cache->set($id, $object);
    return $object;
  }

  /**
   * @see AbstractRepository::getObject()
   * @todo perhaps we should check if an object exists instead of catching
   *   the exception
   */
  public function getObject($id) {
    $object = $this->cache->get($id);
    if ($object !== FALSE) {
      return $object;
    }

    try {
      $object = new $this->objectClass($id, $this);
      $this->cache->set($id, $object);
      return $object;
    }
    catch (RepositoryException $e) {
        throw $e;
    }
  }

  /**
   * @see AbstractRepository::purgeObject()
   */
  public function purgeObject($id) {
    try {
      $this->api->m->purgeObject($id);
      $object = $this->cache->get($id);
      if ($object !== FALSE) {
        $this->cache->delete($id);
      }
    }
    catch (RepositoryException $e) {
      // @todo chain exceptions here.
      throw $e;
    }
  }
}
