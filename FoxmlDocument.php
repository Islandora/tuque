<?php

class FoxmlDocument extends DOMDocument {
  const FOXML = 'info:fedora/fedora-system:def/foxml#';
  const xlink = 'http://www.w3.org/1999/xlink';
  const xsi = 'http://www.w3.org/2001/XMLSchema-instance';
  const xmlns = 'http://www.w3.org/2000/xmlns/';
  const rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
  const rdfs = 'http://www.w3.org/2000/01/rdf-schema#';
  const fedora = 'info:fedora/fedora-system:def/relations-external#';
  const dc = 'http://purl.org/dc/elements/1.1/';
  const oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
  const fedora_model = 'info:fedora/fedora-system:def/model#';

  protected $root;
  protected $object;

  public function __construct(NewFedoraObject $object) {
    parent::__construct("1.0", "UTF-8"); // DomDocument
    $this->formatOutput = TRUE;
    $this->preserveWhiteSpace = FALSE;
    $this->object = $object;
    $this->root = $this->createRootElement();
    $this->createDocument();
  }

  private function createRootElement() {
    $root = $this->createElementNS(self::FOXML, 'foxml:digitalObject');
    $root->setAttribute('VERSION', '1.1');
    $root->setAttribute('PID', "{$this->object->id}");
    $root->setAttributeNS(self::xmlns, 'xmlns', self::FOXML);
    $root->setAttributeNS(self::xmlns, 'xmlns:foxml', self::FOXML);
    $root->setAttributeNS(self::xmlns, 'xmlns:xsi', self::xsi);
    $root->setAttributeNS(self::xsi, 'xsi:schemaLocation', self::FOXML . " http://www.fedora.info/definitions/1/0/foxml1-1.xsd");
    $this->appendChild($root);
    return $root;
  }

  private function createDocument() {
    /**
     * If DOMNodes are not appended in the corrected order root -> leaf, namespaces may break...
     * So be be cautious, add DOMNodes to their parent element before adding child elements to them.
     */
    $this->createObjectProperties();
    //$this->createPolicy();
    $this->createDocumentDatastreams();
    //$this->createCollectionPolicy();
    //$this->createWorkflowStream();
  }

  /**
   *
   * @param DOMElement $root
   * @return DOMElement
   */
  private function createObjectProperties() {
    $object_properties = $this->createElementNS(self::FOXML, 'foxml:objectProperties');
    $this->root->appendChild($object_properties);

    $property = $this->createElementNS(self::FOXML, 'foxml:property');
    $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#state');
    $property->setAttribute('VALUE', $this->object->state);
    $object_properties->appendChild($property);

    $property = $this->createElementNS(self::FOXML, 'foxml:property');
    $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#label');
    $property->setAttribute('VALUE', $this->object->label);
    $object_properties->appendChild($property);

    if (isset($this->object->owner)) {
      $property = $this->createElementNS(self::FOXML, 'foxml:property');
      $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#ownerId');
      $property->setAttribute('VALUE', $this->object->owner);
      $object_properties->appendChild($property);
    }

    return $object_properties;
  }

  private function createPolicy() {
    $policy_element = $this->getPolicyStreamElement();
    if ($policy_element) {
      $datastream = $this->createDatastreamElement('POLICY', 'A', 'X');
      $version = $this->createDatastreamVersionElement('POLICY.0', 'POLICY', 'text/xml');
      $content = $this->createDatastreamContentElement();
      $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($policy_element);
    }
  }

  private function getPolicyStreamElement() {
    module_load_include('inc', 'fedora_repository', 'ObjectHelper');
    $object_helper = new ObjectHelper();
    $policy_stream = $object_helper->getStream($this->object->collectionPid, 'CHILD_SECURITY', FALSE);
    if (!isset($policy_stream)) {
      return NULL; //there is no policy stream so object will not have a policy stream
    }
    try {
      $xml = new SimpleXMLElement($policy_stream);
    } catch (Exception $exception) {
      watchdog(t("Fedora_Repository"), t("Problem getting security policy."), NULL, WATCHDOG_ERROR);
      drupal_set_message(t('Problem getting security policy: !e', array('!e' => $exception->getMessage())), 'error');
      return FALSE;
    }
    $policy_element = $this->createDocumentFragment();
    if (!$policy_element) {
      drupal_set_message(t('Error parsing security policy stream.'));
      watchdog(t("Fedora_Repository"), t("Error parsing security policy stream, could not parse policy stream."), NULL, WATCHDOG_NOTICE);
      return FALSE;
    }
    $this->importNode($policy_element, TRUE);
    $value = $policy_element->appendXML($policy_stream);
    if (!$value) {
      drupal_set_message(t('Error creating security policy stream.'));
      watchdog(t("Fedora_Repository"), t("Error creating security policy stream, could not parse collection policy template file."), NULL, WATCHDOG_NOTICE);
      return FALSE;
    }
    return $policy_element;
  }

  private function createCollectionPolicy() {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $fedora_item = new fedora_item($this->contentModelPid);
    $datastreams = $fedora_item->get_datastreams_list_as_array();
    if (isset($datastreams['COLLECTION_POLICY_TMPL'])) {
      $collection_policy_template = $fedora_item->get_datastream_dissemination('COLLECTION_POLICY_TMPL');
      $collection_policy_template_dom = DOMDocument::loadXML($collection_policy_template);
      $collection_policy_template_root = $collection_policy_template_dom->getElementsByTagName('collection_policy');
      if ($collection_policy_template_root->length > 0) {
        $collection_policy_template_root = $collection_policy_template_root->item(0);
        $node = $this->importNode($collection_policy_template_root, TRUE);
        $datastream = $this->createDatastreamElement('COLLECTION_POLICY', 'A', 'X');
        $version = $this->createDatastreamVersionElement('COLLECTION_POLICY.0', 'Collection Policy', 'text/xml');
        $content = $this->createDatastreamContentElement();
        $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($node);
      }
    }
  }

  private function createDatastreamElement($id = NULL, $state = NULL, $control_group = NULL, $versionable = NULL) {
    $datastream = $this->createElementNS(self::FOXML, 'foxml:datastream');
    if (isset($id)) {
      $datastream->setAttribute('ID', $id);
    }
    if (isset($state)) {
      $datastream->setAttribute('STATE', $state);
    }
    if (isset($control_group)) {
      $datastream->setAttribute('CONTROL_GROUP', $control_group);
    }
    if (isset($versionable)) {
      $datastream->setAttribute('VERSIONABLE', $versionable);
    }
    return $datastream;
  }

  private function createDatastreamVersionElement($id = NULL, $label = NULL, $mime_type = NULL, $format_uri = NULL) {
    $version = $this->createElementNS(self::FOXML, 'foxml:datastreamVersion');
    if (isset($id)) {
      $version->setAttribute('ID', $id);
    }
    if (isset($label)) {
      $version->setAttribute('LABEL', $label);
    }
    if (isset($mime_type)) {
      $version->setAttribute('MIMETYPE', $mime_type);
    }
    if (isset($format_uri)) {
      $version->setAttribute('FORMAT_URI', $format_uri);
    }
    return $version;
  }

  private function createDatastreamDigestElement($type = NULL, $digest = NULL) {
    $digest = $this->createElementNS(self::FOXML, 'foxml:contentDigest');
    if (isset($type)) {
      $digest->setAttribute('TYPE', $type);
    }
    if (isset($digest)) {
      $digest->setAttribute('DIGEST', $digest);
    }
    return $digest;
  }

  private function createDatastreamContentElement() {
    $content = $this->createElementNS(self::FOXML, 'foxml:xmlContent');
    return $content;
  }

  private function createDatastreamContentLocationElement($type = NULL, $ref = NULL) {
    $location = $this->createElementNS(self::FOXML, 'foxml:contentLocation');
    if (isset($type)) {
      $location->setAttribute('TYPE', $type);
    }
    if (isset($ref)) {
      $location->setAttribute('REF', $ref);
    }
    return $location;
  }

  private function createWorkflowStream() {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $fedora_item = new fedora_item($this->contentModelPid);
    $datastreams = $fedora_item->get_datastreams_list_as_array();
    if (isset($datastreams['WORKFLOW_TMPL'])) {
      $work_flow_template = $fedora_item->get_datastream_dissemination('WORKFLOW_TMPL');
      $work_flow_template_dom = DOMDocument::loadXML($work_flow_template);
      $work_flow_template_root = $work_flow_template_dom->getElementsByTagName('workflow');
      if ($work_flow_template_root->length > 0) {
        $work_flow_template_root = $work_flow_template_root->item(0);
        $node = $this->importNode($work_flow_template_root, TRUE);
        $datastream = $this->createDatastreamElement('WORKFLOW', 'A', 'X');
        $version = $this->createDatastreamVersionElement("{$this->dsid}.0", "{$this->dsid} Record", 'text/xml');
        $content = $this->createDatastreamContentElement();
        $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($node);
      }
    }
  }

  /**
   * Checks that the content and dsid are valid and then passes the FOXML creation off
   * to the relevant function. Currently any 'string' content that is marked as a managed
   * datastream will be ingested as inline.
   * 
   * @todo Implement fedora upload function to allow strings to be added as managed datastreams
   */
  public function createDocumentDatastreams() {
    foreach ($this->object as $ds) {
      if (!isset($ds->id) || strlen($ds->content) < 1) {
        return "";
      }
      if ($ds->contentType == 'string') {
          $this->createInlineDocumentDatastream($ds);
      }
      else {
        $this->createDocumentDatastream($ds);
      }
    }
  }

  /**
   * Creates FOXML for any inline datastreams based on the information passed in the $ds object.
   * 
   * @param object $ds
   *   The datastream object 
   */
  private function createInlineDocumentDatastream($ds) {
    $datastream = $this->createDatastreamElement($ds->id, $ds->state, $ds->controlGroup, $ds->versionable);
    $version = $this->createDatastreamVersionElement("{$ds->id}.0", $ds->label, $ds->mimetype, $ds->format);
    $content = $this->createDatastreamContentElement();
    $xml_dom = new DOMDocument();
    $xml_dom->loadXML($ds->content);
    $child = $this->importNode($xml_dom->documentElement, TRUE);
    $version_node = $this->root->appendChild($datastream)->appendChild($version);
    if (isset($ds->checksumType)) {
      $digest = $this->createDatastreamDigestElement($ds->checksumType, $ds->checksum);
      $version_node->appendChild($digest);
    }
    $version_node->appendChild($content)->appendChild($child);
    // Once again god damn you libxml...
    $class = get_class($ds->content);
    $namespaces = call_user_func(array($class, 'getRequiredNamespaces'));
    foreach ($namespaces as $prefix => $uri) {
      $child->setAttributeNS(self::xmlns, "xmlns:$prefix", $uri);
    }
  }

  /**
   * Creates FOXML for any managed, externally referenced or redirect datastreams bases on the $ds object
   * 
   * @param object $ds
   *   The datastream object 
   */
  private function createDocumentDatastream($ds) {
    $datastream = $this->createDatastreamElement($ds->id, $ds->state, $ds->controlGroup, $ds->versionable);
    $version = $this->createDatastreamVersionElement($ds->id . '.0', $ds->label, $ds->mimetype, $ds->format);
    $content = $this->createDatastreamContentLocationElement('URL', $ds->content);
    $version_node = $this->root->appendChild($datastream)->appendChild($version);
    if (isset($ds->checksumType)) {
      $digest = $this->createDatastreamDigestElement($ds->checksumType, $ds->checksum);
      $version_node->appendChild($digest);
    }
    $version_node->appendChild($content);
  }
}