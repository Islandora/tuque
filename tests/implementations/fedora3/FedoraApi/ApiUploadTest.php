<?php

require_once 'RepositoryFactory.php';
require_once 'tests/TestHelpers.php';

class UploadTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $this->repository = RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
    $this->api = $this->repository->api;
  }

  public function testUploadString() {
    $this->markTestIncomplete();
    $filepath = getcwd() . '/tests/test_data/test.png';
    $return = $this->api->m->upload('string', 'string string string');
    print_r($return);
  }

  public function testManagedAdd() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $testPid = "$string1:$string2";

    $object = $this->repository->constructObject($testPid);
    $ds = $object->constructDatastream('test1', 'M');
    $filepath = getcwd() . '/tests/test_data/test.png';
    $ds->setContentFromFile($filepath);
    $ds->mimetype = 'image/png';
    $object->ingestDatastream($ds);
    $ds = $object->constructDatastream('test2', 'M');
    $filepath = getcwd() . '/tests/test_data/test.png';
    $ds->setContentFromString('this is a test... test test test');
    $ds->mimetype = 'text/plain';
    $object->ingestDatastream($ds);
    $this->repository->ingestObject($object);

    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);

    $object = $repository->getObject($testPid);
    $this->assertTrue(isset($object['test1']));
    $this->assertTrue(isset($object['test2']));

    $this->assertEquals('M', $object['test1']->controlGroup);
    $this->assertEquals('M', $object['test2']->controlGroup);

    $this->assertEquals(file_get_contents($filepath), $object['test1']->content);
    $this->assertEquals('this is a test... test test test', $object['test2']->content);
  }
}