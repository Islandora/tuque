<?php
require_once 'RepositoryFactory.php';
require_once 'tests/TestHelpers.php';

class FedoraRelationshipsExternalTest extends PHPUnit_Framework_TestCase {

  function setUp() {
    $this->repository = RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
    $this->api = $this->repository->api;

    $this->object = $this->repository->constructObject('test:awesome');

    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'hasAwesomeness', 'jonathan:green');
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:model');
    $this->object->relationships->add(ISLANDORA_RELS_EXT_URI, 'isPage', '22', TRUE);
    $this->object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'theawesomecollection:awesome');
    $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:woot');

    $this->repository->ingestObject($this->object);
  }

  function tearDown() {
    $this->repository->purgeObject($this->object->id);
  }

  function testGetAll() {
    $relationships = $this->object->relationships->get();
    $this->assertEquals(5, count($relationships));
    $this->assertEquals('hasAwesomeness', $relationships[0]['predicate']['value']);
    $this->assertEquals('jonathan:green', $relationships[0]['object']['value']);
    $this->assertEquals('hasModel', $relationships[1]['predicate']['value']);
    $this->assertEquals('islandora:model', $relationships[1]['object']['value']);
    $this->assertEquals('isPage', $relationships[2]['predicate']['value']);
    $this->assertEquals('22', $relationships[2]['object']['value']);
    $this->assertTrue($relationships[2]['object']['literal']);
    $this->assertEquals('isMemberOfCollection', $relationships[3]['predicate']['value']);
    $this->assertEquals('theawesomecollection:awesome', $relationships[3]['object']['value']);
  }

  function testGetOne() {
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(2, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:model', $rels[0]['object']['value']);
    $this->assertEquals('hasModel', $rels[1]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[1]['object']['value']);
  }

  function testRemovePredicate() {
    $this->object->relationships->remove(FEDORA_MODEL_URI, 'hasModel');
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(0, count($rels));
  }

  function testRemoveSpecificPredicate() {
    $this->object->relationships->remove(FEDORA_MODEL_URI, 'hasModel', 'islandora:model');
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(1, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[0]['object']['value']);
  }

  function testRemoveObject() {
    $this->object->relationships->remove(NULL, NULL, 'islandora:model');
    $rels = $this->object->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(1, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[0]['object']['value']);
  }
}