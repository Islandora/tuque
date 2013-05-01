<?php

require_once 'tests/implementations/ObjectTestBase.php';

class ObjectDecoratorTest extends ObjectTestBase {

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
    $this->object = new ObjectDecorator(new FedoraObject($this->testPid, $this->repository));
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  protected function getValue($data) {
    $values = $this->api->a->getObjectProfile($this->testPid);
    return $values[$data];
  }
}
