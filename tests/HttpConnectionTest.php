<?php
require_once "HttpConnection.php";

class HttpConnectionTest extends PHPUnit_Framework_TestCase {

  function testGet() {
    $connection = new CurlConnection();
    $page = $connection->getRequest('http://jenkins.discoverygarden.ca:8080/xml.xml');
    $this->assertEquals("<woo><test><xml/></test></woo>\n", $page['content']);
  }

  function testGetFile() {
    $connection = new CurlConnection();
    $file = tempnam(sys_get_temp_dir(),'test');
    $page = $connection->getRequest('http://jenkins.discoverygarden.ca:8080/xml.xml', FALSE, $file);
    $this->assertEquals("<woo><test><xml/></test></woo>\n", file_get_contents($file));
    unlink($file);
  }

}
