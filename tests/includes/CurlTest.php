<?php

namespace Tuque;


class CurlTest extends \PHPUnit_Framework_TestCase {

  const GetUrl = 'http://jenkins.discoverygarden.ca:8080/xml.xml';
  const GetValue = "<woo><test><xml/></test></woo>\n";

  function testGet() {
    // Test with CURLOPT_RETURNTRANSFER = TRUE.
    $handle = new CurlHandle(self::GetUrl, array(
                CURLOPT_RETURNTRANSFER => TRUE,
              ));
    $response = Curl::get($handle);
    $this->assertEquals(self::GetValue, $response->getContent());
    $this->assertNull($handle, 'Curl handle was deallocated after call.');
    // Test with CURLOPT_RETURNTRANSFER = FALSE.
    // Writes to the standard output.
    $handle = new CurlHandle(self::GetUrl, array(
                CURLOPT_RETURNTRANSFER => FALSE,
              ));
    $response = Curl::get($handle);
    $this->assertNull($response->getContent());
    $this->assertNull($handle, 'Curl handle was deallocated after call.');
  }

  /**
   * @expectedException PHPUnit_Framework_Error
   */
  function testGetFile() {
    $file = tempnam(sys_get_temp_dir(), 'test');
    $handle = new CurlHandle(self::GetUrl);
    $handle->setOutFile($file);
    Curl::get($handle);
    $this->assertNull($handle, 'Curl handle was deallocated after call.');
    $this->assertEquals(self::GetValue, file_get_contents($file));
    unlink($file);
    // Set the out file manually.
    $file = tempnam(sys_get_temp_dir(), 'test');
    $fhandle = fopen($file, 'w+');
    $handle = new CurlHandle(self::GetUrl);
    $handle->setOpts(array(
        CURLOPT_FILE => $fhandle,
      ));
    Curl::get($handle);
    $this->assertNull($handle, 'Curl handle was deallocated after call.');
    $this->assertEquals(self::GetValue, file_get_contents($file));
    $this->assertFalse(fclose($fhandle), 'File handle was already close.');
    unlink($file);
  }

  function testPost() {
    // Stop here and mark this test as incomplete.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  function testPut() {
    // Stop here and mark this test as incomplete.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  function testDelete() {
    // Stop here and mark this test as incomplete.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

}

class CurlHandleTest extends \PHPUnit_Framework_TestCase {

  const GetUrl = 'http://jenkins.discoverygarden.ca:8080/xml.xml';

  /**
   * @expectedException BadMethodCallException
   */
  function testSleep() {
    $handle = new CurlHandle(self::GetUrl);
    serialize($handle);
  }

  /**
   */
  public static function testCreate() {
    $handle = new CurlHandle();
    $this->assertTrue(isset($handle), 'Created cURL handle without params.');
    $url = self::GetUrl;
    $handle = new CurlHandle($url);
    $this->assertTrue(isset($handle), 'Created cURL handle with url parameter.');
    $this->assertEqual($handle->getOpt(CURLOPT_URL), $url, 'URL option matched constructor value.');
    $options = array(
      CURLOPT_CUSTOMREQUEST => 'DELETE',
    );
    $handle = new CurlHandle($url, $options);
    $this->assertTrue(isset($handle), 'Created cURL handle with url & options parameters.');
    $this->assertEqual($handle->getOpt(CURLOPT_URL), $url, 'URL option matched constructor value.');
    $this->assertEqual($handle->getOpt(CURLOPT_CUSTOMREQUEST), 'DELETE', 'The additional option matched constructor value.');
    $options = array(
      CURLOPT_URL => 'different url',
      CURLOPT_CUSTOMREQUEST => 'DELETE',
    );
    $handle = new CurlHandle($url, $options);
    $this->assertTrue(isset($handle), 'Created cURL handle with url & options parameters.');
    $this->assertTrue($handle->getOpt(CURLOPT_URL) != $url, 'Additional option overrode constructor value.');
  }

}
