<?php

require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';
require_once 'RepositoryConnection.php';
require_once 'FedoraApi.php';

class RepositoryTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
  }

  public function testIngestNamespace() {
    $namespace = FedoraTestHelpers::randomString(10);
    $object = $this->repository->constructObject($namespace);
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);
    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);
    $this->repository->purgeObject($object->id);
  }

  public function testIngestNoParams() {
    $object = $this->repository->constructObject();
    $id = $object->id;
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);
    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals($id, $object->id);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);
    $this->repository->purgeObject($object->id);
  }

  public function testIngestFullPid() {
    $namespace = FedoraTestHelpers::randomString(10);
    $localid = FedoraTestHelpers::randomString(10);
    $id = "$namespace:$localid";
    $object = $this->repository->constructObject($id);
    $object->label = 'foo';
    $object->state = 'd';
    $object->owner = 'woot';
    $this->repository->ingestObject($object);
    $this->assertTrue($object instanceof FedoraObject);
    $this->assertEquals('foo', $object->label);
    $this->assertEquals('D', $object->state);
    $this->assertEquals('woot', $object->owner);
    $this->repository->purgeObject($object->id);
  }

}