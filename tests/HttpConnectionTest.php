<?php
require_once "HttpConnection.php";

class HttpConnectionTest extends PHPUnit_Framework_TestCase {

  function testAdd() {
    $connection = new CurlConnection();
    $page = $connection->getRequest('http://hudson.islandora.ca/files/xml.xml');
    $this->assertEquals("<woo><test><xml/></test></woo>\n", $page['content']);
  }

}
