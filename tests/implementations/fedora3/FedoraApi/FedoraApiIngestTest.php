<?php
/**
 * @file
 * A set of test classes that test the implementations/fedora3/FedoraApi.php file
 */

require_once 'RepositoryFactory.php';
require_once 'tests/TestHelpers.php';

class FedoraApiIngestTest extends PHPUnit_Framework_TestCase {
  protected $pids = array();
  protected $files = array();

  protected function setUp() {
    $this->connection = new RepositoryConnection(new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
    $this->serializer = new FedoraApiSerializer();

    $this->apim = new FedoraApiM($this->connection, $this->serializer);
    $this->apia = new FedoraApiA($this->connection, $this->serializer);
  }

  protected function tearDown() {
    if (isset($this->pids) && is_array($this->pids)) {
      while ($pid = array_pop($this->pids)) {
        try {
          $this->apim->purgeObject($pid);
        }
        catch (RepositoryException $e) {}
      }
    }

    if (isset($this->files) && is_array($this->files)) {
      while ($file = array_pop($this->files)) {
        unlink($file);
      }
    }
  }

  public function testDescribeRepository() {
    $describe = $this->apia->describeRepository();
    $this->assertArrayHasKey('repositoryName', $describe);
    $this->assertArrayHasKey('repositoryBaseURL', $describe);
    $this->assertArrayHasKey('repositoryPID', $describe);
    $this->assertArrayHasKey('PID-namespaceIdentifier', $describe['repositoryPID']);
    $this->assertArrayHasKey('PID-delimiter', $describe['repositoryPID']);
    $this->assertArrayHasKey('PID-sample', $describe['repositoryPID']);
    $this->assertArrayHasKey('retainPID', $describe['repositoryPID']);
    $this->assertArrayHasKey('repositoryOAI-identifier', $describe);
    $this->assertArrayHasKey('OAI-namespaceIdentifier', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('OAI-delimiter', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('OAI-sample', $describe['repositoryOAI-identifier']);
    $this->assertArrayHasKey('sampleSearch-URL', $describe);
    $this->assertArrayHasKey('sampleAccess-URL', $describe);
    $this->assertArrayHasKey('sampleOAI-URL', $describe);
    $this->assertArrayHasKey('adminEmail', $describe);
  }

  public function testIngestNoPid() {
    $pid = $this->apim->ingest();
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
  }

  public function testIngestRandomPid() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $actual_pid = $this->apim->ingest(array('pid' => $expected_pid));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
  }

  public function testIngestStringFoxml() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $foxml = <<<FOXML
<?xml version="1.0" encoding="UTF-8"?>
<foxml:digitalObject
  xmlns:foxml="info:fedora/fedora-system:def/foxml#"
  xmlns="info:fedora/fedora-system:def/foxml#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  VERSION="1.1"
  PID="$expected_pid"
  xsi:schemaLocation="info:fedora/fedora-system:def/foxml#
  http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
  <foxml:objectProperties>
    <foxml:property NAME="info:fedora/fedora-system:def/model#state" VALUE="A"/>
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="$expected_label"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestFileFoxml() {
    $file_name = tempnam(sys_get_temp_dir(),'fedora_fixture');
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $foxml = <<<FOXML
<?xml version="1.0" encoding="UTF-8"?>
<foxml:digitalObject
  xmlns:foxml="info:fedora/fedora-system:def/foxml#"
  xmlns="info:fedora/fedora-system:def/foxml#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  VERSION="1.1"
  PID="$expected_pid"
  xsi:schemaLocation="info:fedora/fedora-system:def/foxml#
  http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
  <foxml:objectProperties>
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="$expected_label"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;
    file_put_contents($file_name, $foxml);
    $this->files[] = $file_name;

    $actual_pid = $this->apim->ingest(array('file' => $file_name));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestLabel() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'label' => $expected_label));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestLogMessage() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_log_message = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'logMessage' => $expected_log_message));
    $this->pids[] = $pid;

    // Check the audit trail.
    $xml = $this->apim->export($pid);
    $dom = new DomDocument();
    $dom->loadXml($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('audit', 'info:fedora/fedora-system:def/audit#');
    $result = $xpath->query('//audit:action[.="ingest"]/../audit:justification');
    $this->assertEquals(1, $result->length);
    $tag = $result->item(0);
    $this->assertEquals($expected_log_message, $tag->nodeValue);
  }

  public function testIngestNamespace() {
    $expected_namespace = FedoraTestHelpers::randomString(10);
    $pid = $this->apim->ingest(array('namespace' => $expected_namespace));
    $this->pids[] = $pid;
    $pid_parts = explode(':', $pid);
    $this->assertEquals($expected_namespace, $pid_parts[0]);
  }

  /**
   * @todo fix this test
   */
  public function testIngestOwnerId() {
    $this->markTestIncomplete();
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_owner = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'ownerId' => $expected_owner));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'ownerId'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_owner, $results['results'][0]['ownerId']);
  }

  /**
   * @todo finish this test
   * @todo we need some documents with different character encoding for this
   *   to work.
   */
  public function testIngestEncoding() {
    $this->markTestIncomplete();
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
  }

  /**
   * we need some files to ingest to test this
   */
  public function testIngestFormat() {
    $this->markTestIncomplete();
  }
}