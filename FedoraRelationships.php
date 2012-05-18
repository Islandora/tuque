<?php

define("XMLNS", "http://www.w3.org/2000/xmlns/");

class FedoraRelationshipException extends Exception {}

class FedoraRelationship {

  protected $namespaces = array(
    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'fedora' => 'info:fedora/fedora-system:def/relations-external#',
  );

  protected $modified = FALSE;
  protected $default;
  protected $document;
  protected $xpath;

  public function  __construct($args = array('xml'=>NULL, 'namespaces'=>NULL, 'default_namespace'=>NULL)) {
    if(isset($args['namespaces'])) {
      foreach ($args['namespaces'] as $alias => $uri) {
        $this->namespaces[$alias] = $uri;
      }
    }

    if(isset($args['default_namespace'])) {
      if(!isset($this->namespaces[$args['default_namespace']])) {
        throw new FedoraRelationshipException('Default namespace must be in the namespaces array.');
      }
      $this->default = $args['default_namespace'];
    }
    else {
      $this->default = 'fedora';
    }

    if(isset($args['xml'])) {
      $this->document = new DomDocument();
      $this->document->preserveWhiteSpace = False;

      // Throw exception if DomDocument gave us a Parse error
      if($this->document->loadXML($args['xml']) == FALSE) {
        throw new FedoraRelationshipException('Error Parsing XML, DomDocument returned false.');
      }

      $root = $this->document->documentElement;

      //make sure our namespaces are good
      foreach($this->namespaces as $alias => $uri) {
        $root->setAttributeNS(XMLNS,"xmlns:$alias", $uri);
      }
    }
    else {
      $this->document = new DomDocument("1.0", "UTF-8");
      $rootelement = $this->document->createElementNS($this->namespaces['rdf'], 'RDF');
      foreach ($this->namespaces as $alias => $uri) {
        $rootelement->setAttributeNS(XMLNS, "xmlns:$alias", $uri);
      }
      $this->document->appendChild($rootelement);
    }

    $this->xpath = new DomXPath($this->document);
    foreach($this->namespaces as $alias => $uri) {
      $this->xpath->registerNamespace($alias, $uri);
    }
  }

  public function toString($prettyPrint = TRUE) {
    $this->document->formatOutput = $prettyPrint;
    return $this->document->saveXml();
  }
  
  private function normalizePredicate($predicate) {
    if($predicate == NULL) {
      return $predicate;
    }
    elseif(is_array($predicate)) {
      if(!isset($predicate['alias'])) {
        $predicate['alias'] = $this->default;
      }
      else {
        if(!isset($this->namespaces[$predicate['alias']])) {
          throw new FedoraRelationshipException('Given alias does not exists in namespaces.');
        }
      }
      $predicate['uri'] = $this->namespaces[$predicate['alias']];
      return $predicate;
    }
    else {
      return array('alias' => $this->default, 'predicate' => $predicate, 'uri' => $this->namespaces[$this->default]);
    }
  }

  public function addRelationship($subject = NULL, $predicate = NULL, $object = NULL) {
    if($subject == NULL || $predicate == NULL || $object == NULL) {
      throw new FedoraRelationshipException('Must specify a Subject, Predicate and Object.');
    }

    $this->modified = TRUE;

    $predicate = $this->normalizePredicate($predicate);

    $description = $this->xpath->query('/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]');

    if($description->length == 0) {
      $description = $this->document->createElementNS($this->namespaces['rdf'], 'Description');
      $this->document->documentElement->appendChild($description);
      $description->setAttributeNS($this->namespaces['rdf'], 'about', "info:fedora/$subject");
    }
    else {
      $description = $description->item(0);
    }

    $relationship = $this->document->createElementNS($predicate['uri'], $predicate['predicate']);
    $description->appendChild($relationship);

    if( $object['type'] == 'dsid' ) {
      $relationship->setAttributeNS($this->namespaces['rdf'], 'resource', 'info:fedora/' . $object['pid'] . '/' . $object['dsid']);
    }
    elseif( $object['type'] == 'pid' ){
        $relationship->setAttributeNS($this->namespaces['rdf'], 'resource', 'info:fedora/' . $object['pid']);
    }
    elseif($object['type'] == 'literal'){
        $relationship->nodeValue = $object['value'];
    }
  }

  private function getXpathResults($subject, $predicate, $object) {
    $predicate = $this->normalizePredicate($predicate);

    if ($subject == NULL) {
      $xpath = '/rdf:RDF/rdf:Description';
    }
    else {
      $xpath = '/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]';
    }

    if ($predicate == NULL) {
      $xpath .= '/*';
    }
    else {
      $xpath .= '/' . $predicate['uri'] . ':' . $predicate['predicate'];
    }

    if ($object) {
      if($object['type'] == 'pid') {
        $xpath .= '[@rdf:resource="info:fedora/' . $object['pid'] . '"]';
      }
      elseif($object['type'] == 'dsid') {
        $xpath .= '[@rdf:resource="info:fedora/' . $object['pid'] . '/' . $object['dsid'] . '"]';
      }
      elseif($object['type'] == 'literal') {
        $xpath .= '[.="' . $object['literal'] . '"]';
      }
    }

    return $this->xpath->query($xpath);
  }

  public function getRelationships($subject = NULL, $predicate = NULL, $object = NULL) {
    $result_elements = $this->getXpathResults($subject, $predicate, $object);
    $results = array();
    foreach ($result_elements as $element){
      $result = array();
      $parent = $element->parentNode;
      $subject = $parent->getAttributeNS($this->namespaces['rdf'],'about');
      $subject = explode('/', $subject);
      $subject = $subject[1];

      $predicate = explode(':', $element->tagName);
      $predicate = count($predicate) == 1 ? $predicate[0] : $predicate[1];
      $predicate = array('predicate' => $predicate);
      $predicate['uri'] = $element->namespaceURI;
      $predicate['alias'] = $element->lookupPrefix($predicate['uri']);

      $object = array();

      if($element->hasAttributeNS($this->namespaces['rdf'],'resource')) {
        $attrib = $element->getAttributeNS($this->namespaces['rdf'], 'resource');
        $attrib = explode('/', $attrib);
        if(count($attrib) == 2) {
          $object['type'] = 'pid';
          $object['pid'] = $attrib[1];
        }
        else {
          $object['type'] = 'dsid';
          $object['pid'] = $attrib[1];
          $object['dsid'] = $attrib[2];
        }
      }
      else {
        $object['type'] = 'literal';
        $object['value'] = $element->nodeValue;
      }

      $result['subject'] = $subject;
      $result['predicate'] = $predicate;
      $result['object'] = $object;

      $results[] = $result;
    }

    return $results;
  }

  public function purgeRelationships($subject = NULL, $predicate = NULL, $object = NULL) {
    $return = FALSE;
    $result_elements = $this->getXpathResults($subject, $predicate, $object);

    if($result_elements->length > 0) {
      $return = TRUE;
      $this->modified = TRUE;
    }

    foreach($result_elements as $element) {
      $parent = $element->parentNode;
      $parent->removeChild($element);

      if(!$parent->hasChildNodes()) {
        $parent->parentNode->removeChild($parent);
      }
    }
    return $return;
  }
}