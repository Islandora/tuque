<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

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
   * @param boolean $create_uuid
   *   Indicates if the objects ID should contain a UUID.
   *
   * @return AbstractObject
   *   Returns an instantiated AbstractObject object that can be manipulated.
   *   This object will not actually be created in the repository until the
   *   ingest method is called.
   */
  abstract public function constructObject($id = NULL, $create_uuid = FALSE);

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
  abstract public function ingestObject(AbstractObject &$object);

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

  /**
   * Will return an unused identifier for an object.
   *
   * @note
   *   It is not mathematically impossible to have collisions if the
   *   $create_uuid parameter is set to true.
   *
   * @param mixed $namespace
   *   NULL if we should use the default namespace.
   *   string the namespace to be used for the identifier.
   * @param boolean $create_uuid
   *   True if a V4 UUID should be used as part of the identifier.
   * @param integer $number_of_identifiers
   *   The number of identifers to return
   *   Defaults to 1.
   *
   * @return mixed
   *   string An identifier for an object.
   *   array  An array of identifiers for an object.
   *     @code
   *       Array
   *         (
   *           [0] => test:7
   *           [1] => test:8
   *         )
   *     @endcode
   */
  abstract public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1);

}

/**
 * This is a decorator class meant for implementations of AbstractRepository.
 */
class RepositoryDecorator extends AbstractRepository {

  /**
   * This is the repository this is being decorated.
   * @var type AbstractRepository.
   */
  protected $repository;

  /**
   * Constructor.
   *
   * @param AbstractRepository $repository
   *   The repository that is being decorated.
   */
  public function __construct(AbstractRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __get($name) {
    return $this->repository->$name;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __isset($name) {
    return isset($this->repository->$name);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __set($name, $value) {
    $this->repository->$name = $value;
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __unset($name) {
    unset($this->repository->$name);
  }

  /**
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __call($method, $arguments) {
    return call_user_func_array(array($this->repository, $method), $arguments);
  }

  /**
   * @see AbstractRepository::constructObject()
   */
  public function constructObject($id = NULL, $create_uuid = FALSE) {
    return $this->repository->constructObject($id, $create_uuid);
  }

  /**
   * @see AbstractRepository::ingestObject()
   */
  public function ingestObject(AbstractObject &$object) {
    return $this->repository->ingestObject($object);
  }

  /**
   * @see AbstractRepository::getObject()
   */
  public function getObject($id) {
    return $this->repository->getObject($id);
  }

  /**
   * @see AbstractRepository::purgeObject()
   */
  public function purgeObject($id) {
    return $this->repository->purgeObject($id);
  }

  /**
   * @see AbstractRepository::findObjects()
   */
  public function findObjects(array $search) {
    return $this->repository->findObjects($search);
  }

  /**
   * @see AbstractRepository::getNextIdentifier()
   */
  public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1) {
    return $this->repository->getNextIdentifier($namespace, $create_uuid, $number_of_identifiers);
  }
}