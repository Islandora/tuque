<?php

require_once 'tests/implementations/ObjectTestBase.php';

class ObjectTest extends ObjectTestBase {

  protected function setUp() {
    $this->repository = RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
    $this->api = $this->repository->api;

    // create an object
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', '<test> test </test>', NULL);
    $this->object = new FedoraObject($this->testPid, $this->repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  protected function getValue($data) {
    $values = $this->api->a->getObjectProfile($this->testPid);
    return $values[$data];
  }

  public function testDatastreamMutation() {
    $newds = $this->object->constructDatastream('test', 'M');
    $newds->label = 'I am a new day!';
    $newds->mimetype = 'text/plain';
    $newds->content = 'walla walla';

    $this->assertTrue($newds instanceof NewFedoraDatastream, 'Datastream is new.');
    $this->assertTrue($this->object->ingestDatastream($newds) !== FALSE, 'Datastream ingest succeeded.');
    $this->assertTrue($newds instanceof FedoraDatastream, 'Datastream mutated on ingestion.');
  }
}
