<?php

/**
 * @file
 * This file defines the classes that are used for manipulaing the fedora
 * relationships datastreams.
 */

define("XMLNS", "http://www.w3.org/2000/xmlns/");
define("RDF_URI", 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('FEDORA_RELS_EXT_URI', 'info:fedora/fedora-system:def/relations-external#');
define("FEDORA_MODEL_URI", 'info:fedora/fedora-system:def/model#');
define("ISLANDORA_RELS_EXT_URI", 'http://islandora.ca/ontology/relsext#');
define("ISLANDORA_RELS_INT_URI", "http://islandora.ca/ontology/relsint#");

require_once "RepositoryException.php";

/**
 * This is the base class for Fedora Relationships.
 *
 * @todo potentially we should validate the predicate URI
 */
class FedoraRelationships {
  /**
   * The datastream this class is manipulating.
   * @var AbstractFedoraDatastream
   */
  public $datastream = NULL;

  /**
   * An array of namespaces that is used in the document.
   * @var array
   */
  protected $namespaces = array(
    'rdf' => RDF_URI,
  );

  /**
   * The constructor. This will usually be called by one of its subclasses.
   *
   * @param array $namespaces
   *   An array of default namespaces.
   */
  public function  __construct(array $namespaces = NULL) {
    if ($namespaces) {
      $this->namespaces = array_merge($this->namespaces, $namespaces);
    }
  }

  /**
   * Add a new namespace to the relationship xml. Doing this before adding new
   * predicates with differnt URIs makes the XML look a little prettier.
   *
   * @param string $alias
   *   The alias to add.
   * @param string $uri
   *   The URI to associate with the alias.
   */
  public function registerNamespace($alias, $uri) {
    $this->namespaces[$alias] = $uri;
  }

  /**
   * This function returns a domXPath object with all the current namespaces
   * already registered.
   *
   * @return DomXPath
   *   The object
   */
  protected function getXpath($document) {
    $xpath = new DomXPath($document);
    foreach ($this->namespaces as $alias => $uri) {
      $xpath->registerNamespace($alias, $uri);
    }
    return $xpath;
  }

  /**
   * Sets up a domdocument for the functions.
   *
   * @return DomDocument
   *   The domdocument to modify
   */
  protected function getDom() {
    if (isset($this->datastream->content)) {
      // @todo Proper exception handling.
      $document = new DomDocument();
      $document->preserveWhiteSpace = FALSE;
      $document->loadXml($this->datastream->content);
    }
    else {
      $document = new DomDocument("1.0", "UTF-8");
      $rootelement = $document->createElement('rdf:RDF');
      $document->appendChild($rootelement);
    }

    // Setup the default namespace aliases.
    foreach ($this->namespaces as $alias => $uri) {
      // if we use setAttributeNS here we drop the rdf: from about which
      // breaks things, so we do this, then the hack below.
      $document->documentElement->setAttribute("xmlns:$alias", $uri);
    }

    // this is a hack, but it makes sure namespaces are properly registered
    $document_namespaces = new DomDocument();
    $document_namespaces->preserveWhiteSpace = FALSE;
    $document_namespaces->loadXml($document->saveXML());

    return $document_namespaces;
  }

  /**
   * This updates the associated datastreams content.
   */
  protected function updateDatastream($document) {
      $document->formatOutput = TRUE;
      $this->datastream->refresh();
      $this->datastream->content = $document->saveXml();
  }

  /**
   * Add a new relationship.
   *
   * @param string $subject
   *   The subject. This can be a PID, or a PID/DSID combo. This string does
   *   not contain the info:fedora/ part of the URI this is added automatically.
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. If you would like the
   *   XML to use a prefix instead of the full predicate call the
   *   FedoraRelationships::registerNamespace() function first.
   * @param string $predicate
   *   The predicate tag to add.
   * @param string $object
   *   The object for the relationship that is being created.
   * @param boolean $literal
   *   Specifies if the object is a literal or not.
   */
  protected function internalAdd($subject, $predicate_uri, $predicate, $object, $literal = FALSE) {
    $document = $this->getDom();
    $xpath = $this->getXpath($document);

    $description_upper = $xpath->query('/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]');
    $description_lower = $xpath->query('/rdf:RDF/rdf:description[@rdf:about="info:fedora/' . $subject . '"]');

    if ($description_lower->length == 0 && $description_upper->length == 0) {
      $description = $document->createElementNS(RDF_URI, 'Description');
      $document->documentElement->appendChild($description);
      $description->setAttributeNS(RDF_URI, 'rdf:about', "info:fedora/$subject");
    }
    elseif ($description_lower->length) {
      $description = $description_lower->item(0);
    }
    else {
      $description = $description_upper->item(0);
    }

    $relationship = $document->createElementNS($predicate_uri, $predicate);
    $description->appendChild($relationship);

    if ($literal) {
      $relationship->nodeValue = $object;
    }
    else {
      $relationship->setAttributeNS(RDF_URI, 'rdf:resource', 'info:fedora/' . $object);
    }

    $this->updateDatastream($document);
  }

  /**
   * This function is used to create an xpath expression based on the input.
   *
   * @return DOMNodeList
   *   The node list
   */
  protected function getXpathResults($xpath_object, $subject, $predicate_uri, $predicate, $object, $literal) {
    $xpath = '/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]';

    // We do this to deal with the lowercase d.
    $result = $xpath_object->query($xpath);
    if ($result->length == 0) {
      $xpath = '/rdf:RDF/rdf:description[@rdf:about="info:fedora/' . $subject . '"]';
    }

    if ($predicate == NULL) {
      $xpath .= '/*';
    }
    else {
      if ($predicate_uri != NULL) {
        $xpath_object->registerNamespace('pred_uri', $predicate_uri);
        $xpath .= '/pred_uri:' . $predicate;
      }
      else {
        $xpath .= '/' . $predicate;
      }
    }

    if ($object) {
      if ($literal) {
        $xpath .= '[.="' . $object . '"]';
      }
      else {
        $xpath .= '[@rdf:resource="info:fedora/' . $object . '"]';
      }
    }
    return $xpath_object->query($xpath);
  }

  /**
   * This function queries the relationships in the assocaited datastream. Any
   * parameter except for $subject can be set to NULL to act as a wildcard.
   * Calling with just $subject will return all relationships.
   *
   * @param string $subject
   *   The subject. This can be a PID, or a PID/DSID combo. This string does
   *   not contain the info:fedora/ part of the URI this is added automatically.
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. This is ignored if
   *   predicate is NULL.
   * @param string $predicate
   *   The predicate tag to filter by.
   * @param string $object
   *   The object for the relationship to filter by.
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return array
   *   This returns an indexed array with all the matching relationships. The
   *   array is of the form:
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [predicate] => Array
   *                   (
   *                       [value] => thepredicate
   *                       [alias] => thexmlprefix
   *                       [namespace] => http://crazycool.com#
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => TRUE
   *                       [value] => test
   *                   )
   *
   *           )
   *
   *   )
   *   @endcode
   */
  protected function internalGet($subject, $predicate_uri = NULL, $predicate = NULL, $object = NULL, $literal = FALSE) {
    $document = $this->getDom();
    $xpath = $this->getXpath($document);

    $result_elements = $this->getXpathResults($xpath, $subject, $predicate_uri, $predicate, $object, $literal);
    $results = array();
    foreach ($result_elements as $element) {
      $result = array();

      $result['predicate'] = array();
      $result['predicate']['value'] = $element->localName;
      if (isset($element->prefix)) {
        $result['predicate']['alias'] = $element->prefix;
      }
      if (isset($element->namespaceURI)) {
        $result['predicate']['namespace'] = $element->namespaceURI;
      }

      $object = array();
      if ($element->hasAttributeNS($this->namespaces['rdf'], 'resource')) {
        $attrib = $element->getAttributeNS($this->namespaces['rdf'], 'resource');
        $attrib = explode('/', $attrib);
        unset($attrib[0]);
        $attrib = implode('/', $attrib);
        $object['literal'] = FALSE;
        $object['value'] = $attrib;
      }
      else {
        $object['literal'] = TRUE;
        $object['value'] = $element->nodeValue;
      }
      $result['object'] = $object;

      $results[] = $result;
    }

    return $results;
  }

  /**
   * This function removes relationships that match the pattern from the
   * datastream. Any parameter can be given as NULL which will make it a
   * wildcard.
   *
   * @param string $subject
   *   The subject. This can be a PID, or a PID/DSID combo. This string does
   *   not contain the info:fedora/ part of the URI this is added automatically.
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. This is ignored if
   *   predicate is NULL.
   * @param string $predicate
   *   The predicate tag to filter removed results by.
   * @param string $object
   *   The object for the relationship to filter by.
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return boolean
   *   TRUE if relationships were removed, FALSE otherwise.
   */
  protected function internalRemove($subject, $predicate_uri, $predicate, $object, $literal = FALSE) {
    $return = FALSE;
    $document = $this->getDom();
    $xpath = $this->getXpath($document);

    $result_elements = $this->getXpathResults($xpath, $subject, $predicate_uri, $predicate, $object, $literal = FALSE);

    if ($result_elements->length > 0) {
      $return = TRUE;
    }

    foreach ($result_elements as $element) {
      $parent = $element->parentNode;
      $parent->removeChild($element);

      if (!$parent->hasChildNodes()) {
        $parent->parentNode->removeChild($parent);
      }
    }

    if ($return) {
      $this->updateDatastream($document);
    }

    return $return;
  }

  /**
   * This function allows you to change the ID referenced in the rdf:about
   * attribute. This allows the updating of all the about attribures if the
   * datastream is being attached to another object.
   *
   * @param string $id
   *   The new ID
   */
  public function changeObjectID($id) {
    $document = $this->getDom();
    $xpath = $this->getXpath($document);
    $results = $xpath->query('/rdf:RDF/rdf:Description/@rdf:about | /rdf:RDF/rdf:description/@rdf:about');
    $count = $results->length;
    if ($count > 0) {
      for ($i = 0; $i < $count; $i++) {
        $about = $results->item($i);
        $uri = explode('/', $about->value);
        $uri[1] = $id;
        $about->value = implode('/', $uri);
      }
      $this->updateDatastream($document);
    }
  }
}

class FedoraRelsExt extends FedoraRelationships {
  protected $new = FALSE;
  protected $initialized = FALSE;

  /**
   * Objects Construct!
   *
   * @param AbstractFedoraObject $object
   *   The object whose relationships we are manipulating
   */
  public function __construct(AbstractFedoraObject $object) {
    $this->object = $object;

    $namespaces = array(
      'fedora' => FEDORA_RELS_EXT_URI,
      'fedora-model' => FEDORA_MODEL_URI,
      'islandora' => ISLANDORA_RELS_EXT_URI,
    );

    parent::__construct($namespaces);
  }

  /**
   * Initialize the datastrem that we are using. We use this function to
   * delay this as long as possible, in case it never has be be called.
   */
  protected function initializeDatastream() {
    if (!$this->initialized) {
      $this->initialized = TRUE;
      if (isset($this->object['RELS-EXT'])) {
        $ds = $this->object['RELS-EXT'];
      }
      else {
        $ds = $this->object->constructDatastream('RELS-EXT', 'X');
        $ds->label = 'Fedora Object to Object Relationship Metadata.';
        $ds->format = 'info:fedora/fedora-system:FedoraRELSExt-1.0';
        $ds->mimetype = 'application/rdf+xml';
        $this->new = TRUE;
      }

      $this->datastream = $ds;
    }
  }

  /**
   * Add a new relationship.
   *
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. If you would like the
   *   XML to use a prefix instead of the full predicate call the
   *   FedoraRelationships::registerNamespace() function first.
   * @param string $predicate
   *   The predicate tag to add.
   * @param string $object
   *   The object for the relationship that is being created.
   * @param boolean $literal
   *   Specifies if the object is a literal or not.
   */
  public function add($predicate_uri, $predicate, $object, $literal = FALSE) {
    $this->initializeDatastream();
    parent::internalAdd($this->object->id, $predicate_uri, $predicate, $object, $literal);

    if ($this->new) {
      $this->object->ingestDatastream($this->datastream);
    }
  }

  /**
   * This function removes relationships that match the pattern from the
   * datastream. Any parameter can be given as NULL which will make it a
   * wildcard.
   *
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. This is ignored if
   *   predicate is NULL.
   * @param string $predicate
   *   The predicate tag to filter removed results by.
   * @param string $object
   *   The object for the relationship to filter by.
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return boolean
   *   TRUE if relationships were removed, FALSE otherwise.
   */
  public function remove($predicate_uri = NULL, $predicate = NULL, $object = NULL, $literal = FALSE) {
    $this->initializeDatastream();
    $return = parent::internalRemove($this->object->id, $predicate_uri, $predicate, $object, $literal);

    if ($this->new && $return) {
      $this->object->ingestDatastream($this->datastream);
    }

    return $return;
  }

  /**
   * This function queries the relationships in the assocaited datastream. Any
   * parameter except for $subject can be set to NULL to act as a wildcard.
   * Calling with just $subject will return all relationships.
   *
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. This is ignored if
   *   predicate is NULL.
   * @param string $predicate
   *   The predicate tag to filter by.
   * @param string $object
   *   The object for the relationship to filter by.
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return array
   *   This returns an indexed array with all the matching relationships. The
   *   array is of the form:
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [predicate] => Array
   *                   (
   *                       [value] => thepredicate
   *                       [alias] => thexmlprefix
   *                       [namespace] => http://crazycool.com#
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => TRUE
   *                       [value] => test
   *                   )
   *
   *           )
   *
   *   )
   *   @endcode
   */
  public function get($predicate_uri = NULL, $predicate = NULL, $object = NULL, $literal = FALSE) {
    $this->initializeDatastream();
    return parent::internalget($this->object->id, $predicate_uri, $predicate, $object, $literal);
  }

  public function changeObjectID($id) {
    $this->initializeDatastream();
    return parent::changeObjectID($id);
  }
}

class FedoraRelsInt extends FedoraRelationships {
  protected $new = FALSE;
  protected $initialized = FALSE;
  protected $aboutDs;

  /**
   * Objects Construct!
   *
   * @param AbstractFedoraObject $datastream
   *   The datastream whose relationships we are manipulating
   */
  public function __construct(AbstractFedoraDatastream $datastream) {
    $this->aboutDs = $datastream;

    $namespaces = array(
      'islandora' => ISLANDORA_RELS_INT_URI,
    );

    parent::__construct($namespaces);
  }

  /**
   * Delay initialization by waiting to set datastream with this function.
   */
  protected function initializeDatastream() {
    if (!$this->initialized) {
      $this->initialized = TRUE;
      if (isset($this->aboutDs->parent['RELS-INT'])) {
        $ds = $this->aboutDs->parent['RELS-INT'];
      }
      else {
        $ds = $this->aboutDs->parent->constructDatastream('RELS-INT', 'X');
        $ds->label = 'Fedora Relationship Metadata.';
        $ds->format = 'info:fedora/fedora-system:FedoraRELSInt-1.0';
        $ds->mimetype = 'application/rdf+xml';
        $this->new = TRUE;
      }
      $this->datastream = $ds;
    }
  }

  /**
   * Add a new relationship.
   *
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. If you would like the
   *   XML to use a prefix instead of the full predicate call the
   *   FedoraRelationships::registerNamespace() function first.
   * @param string $predicate
   *   The predicate tag to add.
   * @param string $object
   *   The object for the relationship that is being created.
   * @param boolean $literal
   *   Specifies if the object is a literal or not.
   */
  public function add($predicate_uri, $predicate, $object, $literal = FALSE) {
    $this->initializeDatastream();
    parent::internalAdd("{$this->aboutDs->parent->id}/{$this->aboutDs->id}", $predicate_uri, $predicate, $object, $literal);

    if ($this->new) {
      $this->aboutDs->parent->ingestDatastream($this->datastream);
    }
  }

  /**
   * This function removes relationships that match the pattern from the
   * datastream. Any parameter can be given as NULL which will make it a
   * wildcard.
   *
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. This is ignored if
   *   predicate is NULL.
   * @param string $predicate
   *   The predicate tag to filter removed results by.
   * @param string $object
   *   The object for the relationship to filter by.
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return boolean
   *   TRUE if relationships were removed, FALSE otherwise.
   */
  public function remove($predicate_uri = NULL, $predicate = NULL, $object = NULL, $literal = FALSE) {
    $this->initializeDatastream();
    $return = parent::internalRemove("{$this->aboutDs->parent->id}/{$this->aboutDs->id}", $predicate_uri, $predicate, $object, $literal);

    if ($this->new && $return) {
      $this->aboutDs->parent->ingestDatastream($this->datastream);
    }

    return $return;
  }

  /**
   * This function queries the relationships in the assocaited datastream. Any
   * parameter except for $subject can be set to NULL to act as a wildcard.
   * Calling with just $subject will return all relationships.
   *
   * @param string $predicate_uri
   *   The URI to use as the namespace of the predicate. This is ignored if
   *   predicate is NULL.
   * @param string $predicate
   *   The predicate tag to filter by.
   * @param string $object
   *   The object for the relationship to filter by.
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return array
   *   This returns an indexed array with all the matching relationships. The
   *   array is of the form:
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [predicate] => Array
   *                   (
   *                       [value] => thepredicate
   *                       [alias] => thexmlprefix
   *                       [namespace] => http://crazycool.com#
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => TRUE
   *                       [value] => test
   *                   )
   *
   *           )
   *
   *   )
   *   @endcode
   */
  public function get($predicate_uri = NULL, $predicate = NULL, $object = NULL, $literal = FALSE) {
    $this->initializeDatastream();
    return parent::internalGet("{$this->aboutDs->parent->id}/{$this->aboutDs->id}", $predicate_uri, $predicate, $object, $literal);
  }

  public function changeObjectID($id) {
    $this->initializeDatastream();
    return parent::changeObjectID($id);
  }
}
