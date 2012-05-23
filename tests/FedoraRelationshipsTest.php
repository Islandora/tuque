<?php
require_once "FedoraRelationships.php";
/**
 * @todo pull more tests out of tjhe microservices version of these functions
 *  to make sure we handle more cases.
 *
 * @todo remove any calls to StringEqualsXmlString because it uses the
 *  domdocument cannonicalization function that doesn't work properly on cent
 */
class FedoraRelationshipsTest extends PHPUnit_Framework_TestCase {

  function testRelationshipDescription() {
$expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <Description rdf:about="info:fedora/one">
    <fuckyah:woot>test</fuckyah:woot>
  </Description>
</RDF>
XML;
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);
    $object = $repository->constructObject("test:test");
    $rel = $object->relationships;

    $rel->registerNamespace('fuckyah', 'http://crazycool.com#');
    $rel->add('http://crazycool.com#', 'woot', 'test', TRUE);

    //$this->assertXmlStringEqualsXmlString($expected, $object['RELS-EXT']->content);

    $relationships = $rel->get();
    $this->assertEquals(1, count($relationships));
    $this->assertEquals('fuckyah', $relationships[0]['predicate']['alias']);
    $this->assertEquals('http://crazycool.com#', $relationships[0]['predicate']['namespace']);
    $this->assertEquals('woot', $relationships[0]['predicate']['value']);
    $this->assertTrue($relationships[0]['object']['literal']);
    $this->assertEquals('test', $relationships[0]['object']['value']);
  }

  function testRelationshipLowerD() {
    $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <description rdf:about="info:fedora/test:test">
    <fuckyah:woot>test</fuckyah:woot>
  </description>
</RDF>
XML;

    $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <description rdf:about="info:fedora/test:test">
    <fuckyah:woot>test</fuckyah:woot>
    <fuckyah:woot>1234</fuckyah:woot>
  </description>
</RDF>
XML;
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);
    $object = $repository->constructObject("test:test");
    $datastream = $object->constructDatastream('RELS-EXT', 'M');
    $datastream->content = $content;
    $object->ingestDatastream($datastream);

    $object->relationships->add('http://crazycool.com#', 'woot', '1234', TRUE);
    $rels = $object->relationships->get();
    $this->assertEquals(2, count($rels));
    $this->assertEquals('test', $rels[0]['object']['value']);
    $this->assertEquals('1234', $rels[1]['object']['value']);
  }
}
