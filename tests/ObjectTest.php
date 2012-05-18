<?php

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class ObjectTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);

    // create an object 
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', '<test> test </test>', NULL);
    $this->object = new FedoraObject($this->testPid, $repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  protected function getValue($data) {
    $values = $this->api->a->getObjectProfile($this->testPid);
    return $values[$data];
  }

  public function testObjectLabel() {
    $this->assertEquals('', $this->object->label);
    $this->assertEquals('', $this->getValue('objLabel'));

    $this->object->label = 'foo';
    $this->assertEquals('foo', $this->object->label);
    $this->assertEquals('foo', $this->getValue('objLabel'));
    $this->assertTrue(isset($this->object->label));

    unset($this->object->label);
    $this->assertEquals('', $this->getValue('objLabel'));
    $this->assertFalse(isset($this->object->label));


    $this->object->label = 'woot';
    $this->assertEquals('woot', $this->object->label);
    $this->assertEquals('woot', $this->getValue('objLabel'));

    $this->object->label = 'aboot';
    $this->assertEquals('aboot', $this->object->label);
    $this->assertEquals('aboot', $this->getValue('objLabel'));
  }

  public function testObjectOwner() {
    $this->assertEquals(FEDORAUSER, $this->object->owner);
    $this->object->owner = 'foo';
    $this->assertEquals('foo', $this->object->owner);
    $this->assertEquals('foo', $this->getValue('objOwnerId'));
    $this->assertTrue(isset($this->object->owner));
    
    unset($this->object->owner);
    $this->assertEquals('', $this->object->owner);
    $this->assertEquals('', $this->getValue('objOwnerId'));
    $this->assertFalse(isset($this->object->owner));

    $this->object->owner = 'woot';
    $this->assertEquals('woot', $this->object->owner);
    $this->assertEquals('woot', $this->getValue('objOwnerId'));

    $this->object->owner = 'aboot';
    $this->assertEquals('aboot', $this->object->owner);
    $this->assertEquals('aboot', $this->getValue('objOwnerId'));
  }

  public function testObjectId() {
    $this->assertEquals($this->object->id, $this->testPid);
    $this->assertTrue(isset($this->object->id));
  }

  public function testObjectIdUnsetException() {
    $this->markTestIncomplete();
    $this->setExpectedException('Exception');
    unset($this->object->id);
  }

  public function testObjectIdChangeException() {
    $this->markTestIncomplete();
    $this->setExpectedException('Exception');
    $this->object->id = 'foo';
  }

  /**
   * @depends testObjectIdChangeException
   */
  public function testObjectIdDidntChange() {
    $this->assertEquals($this->object->id, $this->testPid);
  }

  public function testObjectState() {
    $this->assertEquals('A', $this->object->state);

    $this->object->state = 'I';
    $this->assertEquals('I', $this->object->state);
    $this->assertEquals('I', $this->getValue('objState'));
    $this->object->state = 'A';
    $this->assertEquals('A', $this->object->state);
    $this->assertEquals('A', $this->getValue('objState'));
    $this->object->state = 'D';
    $this->assertEquals('D', $this->object->state);
    $this->assertEquals('D', $this->getValue('objState'));

    $this->object->state = 'i';
    $this->assertEquals('I', $this->object->state);
    $this->assertEquals('I', $this->getValue('objState'));
    $this->object->state = 'a';
    $this->assertEquals('A', $this->object->state);
    $this->assertEquals('A', $this->getValue('objState'));
    $this->object->state = 'd';
    $this->assertEquals('D', $this->object->state);
    $this->assertEquals('D', $this->getValue('objState'));

    $this->object->state = 'inactive';
    $this->assertEquals('I', $this->object->state);
    $this->assertEquals('I', $this->getValue('objState'));
    $this->object->state = 'active';
    $this->assertEquals('A', $this->object->state);
    $this->assertEquals('A', $this->getValue('objState'));
    $this->object->state = 'deleted';
    $this->assertEquals('D', $this->object->state);
    $this->assertEquals('D', $this->getValue('objState'));

    //$this->object->state = 'foo';
    //$this->assertEquals('D', $this->object->state);
    //$this->assertEquals('D', $this->getValue('objState'));
  }

  public function testObjectDelete() {
    $this->assertEquals('A', $this->object->state);
    $this->assertEquals('A', $this->getValue('objState'));
    $this->object->delete();
    $this->assertEquals('D', $this->object->state);
    $this->assertEquals('D', $this->getValue('objState'));
  }

  public function testObjectGetDS() {
    $this->assertEquals(2, count($this->object));
    $this->assertTrue(isset($this->object['DC']));
    $this->assertTrue(isset($this->object[$this->testDsid]));
    $this->assertFalse(isset($this->object['foo']));
    $this->assertFalse($this->object['foo']);
    $this->assertInstanceOf('FedoraDatastream', $this->object['DC']);
    $this->assertEquals('DC', $this->object['DC']->id);
    foreach($this->object as $id => $ds){
      $this->assertTrue(in_array($id, array('DC', $this->testDsid)));
      $this->assertTrue(in_array($ds->id, array('DC', $this->testDsid)));
    }
    $this->assertEquals("\n<test> test </test>\n", $this->object[$this->testDsid]->content);
  }

}