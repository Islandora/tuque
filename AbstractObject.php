<?php

/**
 * @file
 * Defines the AbstractObject interface.
 */

namespace {

  /**
   * An abstract class defining a Object in the repository. This is the class
   * that needs to be implemented in order to create new repository backends
   * that can be accessed using Tuque.
   *
   * These classes implement the php object array interfaces so that the object
   * can be accessed as an array. This provides access to datastreams. The
   * object is also traversable with foreach, so that each datastream can be
   * accessed.
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
  interface AbstractObject extends Countable, ArrayAccess, IteratorAggregate {

    /**
     * Set the state of the object to deleted.
     */
    public function delete();

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
    public function getDatastream($id);

    /**
     * Purges a datastream.
     *
     * @param string $id
     *   The id of the datastream to purge.
     *
     * @return bool
     *   TRUE on success. FALSE on failure.
     */
    public function purgeDatastream($id);

    /**
     * Factory to create new datastream objects.
     *
     * Creates a new datastream object, this object is not ingested into the
     * repository until you call ingestDatastream.
     *
     * @param string $id
     *   The identifier of the new datastream.
     * @param string $control_group
     *   The control group the new datastream will be created in.
     *
     * @return AbstractDatastream
     *   Returns an instantiated Datastream object.
     */
    public function constructDatastream($id, $control_group = 'M');

    /**
     * Ingests a datastream object into the repository.
     */
    public function ingestDatastream(&$ds);
  }
}

namespace Tuque {
  require_once __DIR__ . '/AbstractDatastream.php';

  /**
   * This class acts as a wrapper for the actual Object implementation.
   *
   * This class doesn't ensure type safety when being constructed, it's up to
   * the programmer for now to ensure that only Object implementations are
   * passed as the argument to this objects constructor.
   */
  class Object extends Delegate implements \AbstractObject {

    /**
     * The decorator class to wrap existing datastreams.
     * @var string
     */
    protected $repositoryDecorator = 'Tuque\Repository';

    /**
     * The decorator class to wrap existing datastreams.
     * @var string
     */
    protected $datastreamDecorator = 'Tuque\Datastream';

    /**
     * The decorator class to wrap new not yet ingested datastreams.
     * @var string
     */
    protected $newDatastreamDecorator = 'Tuque\NewDatastream';

    /**
     * Constructor for the Repository object.
     *
     * @param AbstractObject $object
     *   The object this object wraps.
     */
    public function __construct(\AbstractObject $object) {
      parent::__construct($object);
    }

    /**
     * Set this objects state to deleted.
     *
     * @see AbstractObject::delete()
     */
    public function delete() {
      return parent::delete();
    }

    /**
     * Get the datastream identified by $id.
     *
     * @see AbstractObject::getDatastream()
     */
    public function getDatastream($id) {
      $datastream = parent::getDatastream($id);
      $datastream = new $this->datastreamDecorator($datastream);
      $datastream->parent = $this;
      return $datastream;
    }

    /**
     * Purge the datastream identified by $id.
     *
     * @see AbstractObject::purgeDatstream()
     */
    public function purgeDatastream($id) {
      return parent::purgeDatastream($id);
    }

    /**
     * Construct a new datastream identified by $id.
     *
     * @see AbstractObject::constructDatastream()
     */
    public function constructDatastream($id, $control_group = 'M') {
      $datastream = parent::constructDatastream($id, $control_group);
      $datastream = new $this->newDatastreamDecorator($datastream);
      $datastream->parent = $this;
      return $datastream;
    }

    /**
     * Ingest a datastream.
     *
     * @see AbstractObject::ingestDatastream()
     */
    public function ingestDatastream(&$ds) {
      $result = $this->callPassByReference(__FUNCTION__, array(&$ds));
      if ($result !== FALSE) {
        // We want to rewrap the delegate in a Tuque\Datastream class
        // rather than it's current Tuque\NewDatastream class.
        $ds = new $this->datastreamDecorator($result);
        $ds->parent = $this;
        return $ds;
      }
      return $result;
    }

    /**
     * Return the number of datastreams.
     *
     * @see Countable::count()
     */
    public function count() {
      return parent::count();
    }

    /**
     * Check it the datastream exists.
     *
     * @see ArrayAccess::offsetExists
     */
    public function offsetExists($offset) {
      return parent::offsetExists($offset);
    }

    /**
     * Get the datastream identified by $offset.
     *
     * @see ArrayAccess::offsetGet
     */
    public function offsetGet($offset) {
      return parent::offsetGet($offset);
    }

    /**
     * Will cause an error, the implementing classes don't support this method.
     *
     * @see ArrayAccess::offsetSet
     */
    public function offsetSet($offset, $value) {
      return parent::offsetSet($offset, $value);
    }

    /**
     * Purge the give datastream.
     *
     * @see ArrayAccess::offsetUnset
     */
    public function offsetUnset($offset) {
      return parent::offsetUnset($offset);
    }

    /**
     * Get an iterator for this objects datastreams.
     *
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator() {
      return parent::getIterator();
    }

    /**
     * Get the string repersentation of this object.
     */
    public function __toString() {
      return $this->id;
    }
  }

  /**
   * This class acts as a wrapper for the actual NewObject implementation.
   *
   * This class doesn't ensure type safety when being constructed, it's up to
   * the programmer for now to ensure that only NewObject implementations are
   * passed as the argument to this objects constructor.
   *
   * This class is largely the same as Tuque\Object above in that the delgate
   * handles all the logic. Any case where there is an implementation of a
   * method below indicates that the NewObject doesn't have the *same* interface
   * for a given function/member (despite having the same name and arguments) as
   * the Object class did in the original Fedora 3 implementation.
   */
  class NewObject extends Object {

    /**
     * Instantiates a NewObject.
     */
    public function __construct($object) {
      parent::__construct($object);
    }

    /**
     * Ingest a datastream.
     *
     * Not expected to persist into the repository.
     *
     * @see AbstractObject::ingestDatastream()
     */
    public function ingestDatastream(&$ds) {
      // Don't call parent as that is Object in this case, call the delegate
      // directly, the type doesn't change it should already be wrapped in
      // Tuque\NewDatastream.
      // __call doesn't support passing by reference so we must do it manually.
      return $this->callPassByReference(__FUNCTION__, array(&$ds));
   }

  }
}
