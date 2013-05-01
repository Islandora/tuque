<?php

require_once 'tests/implementations/DatastreamTestBase.php';

class DatastreamTest extends DatastreamTestBase {

  protected function setUp() {
    $this->repository = RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
    $this->api = $this->repository->api;

    // create an object
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));

    // create a DSID
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testDsidR = FedoraTestHelpers::randomCharString(10);
    $this->testDsidE = FedoraTestHelpers::randomCharString(10);
    $this->testDsidX = FedoraTestHelpers::randomCharString(10);
    $this->testDsContents = '<test><xml/></test>';
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', $this->testDsContents, array('controlGroup' => 'M'));
    $this->api->m->addDatastream($this->testPid, $this->testDsidR, 'url', 'http://test.com.fop', array('controlGroup' => 'R'));
    $this->api->m->addDatastream($this->testPid, $this->testDsidE, 'url', 'http://test.com.fop', array('controlGroup' => 'E'));
    $this->api->m->addDatastream($this->testPid, $this->testDsidX, 'string', $this->testDsContents, array('controlGroup' => 'X'));
    $this->object = new FedoraObject($this->testPid, $this->repository);
    $this->ds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);
    $this->e = new FedoraDatastream($this->testDsidE, $this->object, $this->repository);
    $this->r = new FedoraDatastream($this->testDsidR, $this->object, $this->repository);
    $this->x = new FedoraDatastream($this->testDsidX, $this->object, $this->repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }
}