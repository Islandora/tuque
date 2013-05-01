<?php
require_once "HttpConnection.php";

class HttpConnectionTest extends PHPUnit_Framework_TestCase {

  function testGet() {
    // @todo fix.
    $this->markTestIncomplete('We need a publically accessable place for these files.');
    $connection = new CurlConnection();
    $page = $connection->getRequest('http://hudson.islandora.ca/files/xml.xml');
    $this->assertEquals("<woo><test><xml/></test></woo>\n", $page['content']);
  }

  function testGetFile() {
    // @todo fix.
    $this->markTestIncomplete('We need a publically accessable place for these files.');
    $connection = new CurlConnection();
    $file = tempnam(sys_get_temp_dir(),'test');
    $page = $connection->getRequest('http://hudson.islandora.ca/files/xml.xml', array('file' => $file));
    $this->assertEquals("<woo><test><xml/></test></woo>\n", file_get_contents($file));
    unlink($file);
  }

}
