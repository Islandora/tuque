<?php

/**
 * @file
 * Defines a connection to CURL through which requests can be made.
 *
 * @see http://www.php.net/manual/en/book.curl.php
 *
 * @todo At some point support multiple conncurrent requests.
 */

namespace Tuque;
use \RuntimeException,
  \InvalidArgumentException,
  \Exception;

/**
 * HTTP Exception. This is thrown when a status code other then 2XX is returned.
 *
 * @param string $message
 *   A message describing the exception.
 * @param int $code
 *   The error code. These are often the HTTP status codes, however less then
 *   100 is defined by the class extending HttpConnection, for eample cURL.
 * @param array $response
 *   The array containing: status, headers, and content of the HTTP request
 *   causing the error. This is only set if there was a HTTP response sent.
 */
class HttpResponseException extends Exception {

  protected $response;

  /**
   * The constructor for the HttpResponseException.
   *
   * @param HttpResponse $response
   *   The HTTP response.
   * @param Exception $previous
   *   The previous exception in the chain.
   */
  public function __construct(HttpResponse $response, Exception $previous = NULL) {
    $this->response = $response;
    parent::__construct($this->parseHeaderForErrorMessage(), $response->getStatus(), $previous);
  }

  /**
   * Parses the HTTP Response header for the error message.
   */
  protected function parseHeaderForErrorMessage() {
    // We do some ugly stuff here to strip the error string out
    // of the HTTP headers, since curl doesn't provide any helper.
    $message = explode("\r\n\r\n", $this->response->getHeader());
    $message = $message[count($message) - 1];
    $message = explode("\r\n", $message);
    $message = substr($message[0], 13);
    return trim($message);
  }

  /**
   * Get the HTTP response that caused the exception.
   */
  public function getResponse() {
    return $this->response;
  }
}

/**
 *
 */
class HttpResponse {

  /**
   * The info array returned by CurlHandle::getInfo().
   * @var array
   */
  protected $info = array();

  /**
   * The raw status of the HTTP response.
   * @var string
   */
  protected $status = NULL;

  /**
   * The raw header of the HTTP response.
   * @var string
   */
  protected $header = NULL;

  /**
   * The content of the HTTP response, if CURLOPT_RETURNTRANSFER is TRUE.
   * @var mixed
   */
  protected $content = NULL;

  /**
   * The response from the request.
   */
  function __construct($response, array $info) {
    $this->info = $info;
    $this->status = $this->info['http_code'];
    $this->header = $this->info['request_header'];
    if (!is_bool($response)) {
      $this->content = $response;
    }
  }

  /**
   * HttpResponse's do not support serialization.
   */
  public function __sleep() {
    throw new BadMethodCallException('You cannot serialize this object.');
  }

  /**
   * Check if the response was successfull; has 2xx response code.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function successful() {
    return preg_match("/^2/", $this->getStatus());
  }

  /**
   * Get the raw status of the HTTP Response.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Get the raw header of the HTTP Response.
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * Get the raw content of the HTTP Response.
   *
   * If CURLOPT_RETURNTRANSFER is set to FALSE the content does not get
   * populated and its up to calling code to fetch the content.
   */
  public function getContent() {
    return $this->content;
  }
}

/**
 */
class Curl {

  /**
   */
  public static function get(CurlHandle &$handle) {
    $handle->setOpts(array(
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPGET => TRUE,
      ));
    return self::exec($handle);
  }

  /**
   */
  public static function post(CurlHandle &$handle) {
    $handle->setOpts(array(
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POST => TRUE,
      ));
    return self::exec($handle);
  }

  /**
   */
  public static function put(CurlHandle &$handle) {
    $handle->setOpts(array(
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_PUT => TRUE,
      ));
    return self::exec($handle);
  }

  /**
   */
  public static function delete(CurlHandle &$handle) {
    $handle->setOpts(array(
        CURLOPT_CUSTOMREQUEST => 'DELETE',
      ));
    return self::exec($handle);
  }

  /**
   * Executes the given cURL handle and returns the HTTP response if successful.
   *
   * @throws RuntimeException
   *   If the cURL handle was not configured correctly and a error occured.
   * @throws HttpResponseException
   *   If the HTTP response did not return successfully aka 2xx response code.
   *
   * @return HttpResponse
   *   The HTTP Response from the executed cURL handle.
   */
  protected static function exec(CurlHandle &$handle) {
    self::setDefaults($handle);
    $raw_handle = $handle->getHandle();
    $response = curl_exec($raw_handle);
    $info = $handle->getInfo();
    if ($response == FALSE) {
      $error = curl_error($raw_handle);
      $code = curl_errno($raw_handle);
      // Unallocate the Handle.
      $handle = NULL;
      throw new RuntimeException($error, $code);
    }
    // Unallocate the Handle.
    $handle = NULL;
    $response = new HttpResponse($response, $info);
    // Throw an exception if this isn't a 2XX response.
    if (!$response->successful()) {
      throw new HttpResponseException($this, $response);
    }
    return $response;
  }

  /**
   * The handle object does not hold onto file handles so acquire them.
   *
   * We only want to open handles to files for the duration of the cURL request.
   * It is possible although not advised for user to manually set the file
   * handles on the cURL handle in which case they will still be freed after the
   * cURL request is executed.
   */
  protected static function acquireFileHandles(CurlHandle $handle) {
  }

  protected static function setDefaults(CurlHandle $handle) {
    // The other objects and code that deal with responses generated by this
    // class require that these defaults be set and not overridden.
    $options = array(
      CURLINFO_HEADER_OUT => TRUE,
    );
    $handle->setOpts($options);
  }
}

/**
 * Manages a single Curl Handle.
 *
 * This class is meant for a single request only.
 *
 * Beware when setting CURLOPT_* that are file handles, this class will close
 * them when it goes out of scope.
 */
class CurlHandle {

  /**
   * The curl handle.
   * @var handle
   */
  protected $handle = NULL;

  /**
   * All the options that have been set. Does not include defaults.
   * @var array
   */
  protected $options = array();

  /**
   * Create a new CurlHandle.
   *
   * @param string $url
   *   The URL this handle will make a request to.
   * @param array $options
   *   Any additional cURL options to provide to the handle, this can override
   *   the value passed to $url.
   */
  public function __construct($url, array $options = NULL) {
    if (!function_exists("curl_init")) {
      throw new RuntimeException('cURL PHP Module must to enabled.', 0);
    }
    $this->handle = curl_init();
    if (!$this->handle) {
      throw new RuntimeException('Could not allocate a cURL handle.');
    }
    $this->setOpt(CURLOPT_URL, $url);
    if ($options) {
      $this->setOpts($options);
    }
  }

  /**
   * CurlHandles's do not support serialization.
   */
  public function __sleep() {
    throw new BadMethodCallException('You cannot serialize this object.');
  }

  /**
   * Free all acquired resources.
   */
  public function __destruct() {
    $options = array(
      CURLOPT_FILE,
      CURLOPT_INFILE,
      CURLOPT_STDERR,
      CURLOPT_WRITEHEADER,
    );
    foreach ($options as $option) {
      if ($this->hasOpt($option)) {
        $handle = $this->getOpt($option);
        if (is_resource($handle)) {
          fclose($handle);
        }
      }
    }
    curl_close($this->handle);
  }

  /**
   * Get's a Raw pointer to a handle.
   *
   * This function should be used with caution, you loose the saftey the wrapper
   * class provides, options added to the handle will not be recorded.
   */
  public function getHandle() {
    return $this->handle;
  }

  /**
   * Gets info from the raw cURL handle and suppliments it with additional info.
   *
   * @see curl_getinfo()
   *
   * @return array
   *   An associated array containing all the info associated with the raw cURL
   *   handle and any options set on it.
   */
  public function getInfo($option = 0) {
    $info = curl_getinfo($this->handle);
    $info['options'] = $this->options;
    return $info;
  }

  /**
   * Checks to see if the given option has the given value.
   */
  public function isOpt($option, $value) {
    if ($this->hasOpt($option)) {
      return $this->getOpt($option) == $value;
    }
    return FALSE;
  }

  /**
   *
   */
  public function hasOpt($option) {
    return isset($this->options[$option]);
  }

  /**
   * @todo Throw exception?
   */
  public function getOpt($option) {
    if ($this->hasOpt($option)) {
      return $this->options[$option];
    }
    throw new InvalidArgumentException("CurlHandle option {$option} is not defined,");
  }

  /**
   * Sets the given options with the given value.
   */
  public function setOpt($option, $value) {
    $this->options[$option] = $value;
    curl_setopt($this->handle, $option, $value);
  }

  /**
   * Sets the provided options.
   */
  public function setOpts(array $options) {
    foreach ($options as $key => $value) {
      $this->options[$key] = $value;
    }
    curl_setopt_array($this->handle, $options);
  }

  /**
   * The file that the transfer should be written to. The default is STDOUT (the browser window).
   */
  public function setOutfile($file) {
    // If already set, close the previous file handle.
    if ($this->hasOpt(CURLOPT_FILE)) {
      fclose($this->getOpt(CURLOPT_FILE));
    }
    $file = fopen($file, 'w+');
    $this->setOpt(CURLOPT_FILE, $file);
  }

  /**
   * The file that the transfer should be read from when uploading.
   */
  public function setInfile($file) {
    // If already set, close the previous file handle.
    if ($this->hasOpt(CURLOPT_INFILE)) {
      fclose($this->getOpt(CURLOPT_INFILE));
    }
    $filesize = filesize($file);
    $file = fopen($file, 'r');
    $this->setOpt(CURLOPT_INFILE, $file);
    $this->setOpt(CURLOPT_INFILESIZE, $filesize);
  }

  /**
   * The file that the transfer should be read from when uploading.
   */
  public function setInFileFromString($string) {
    // If already set, close the previous file handle.
    if ($this->hasOpt(CURLOPT_INFILE)) {
      fclose($this->getOpt(CURLOPT_INFILE));
    }
    $file = fopen('php://memory', 'rw');
    fwrite($file, $string);
    $filesize = ftell($file);
    rewind($file);
    $this->setOpt(CURLOPT_INFILE, $file);
    $this->setOpt(CURLOPT_INFILESIZE, $filesize);
  }

}

class CurlSharedHandles {

  /**
   * Shared handle resources.
   */
  protected $sharedHandles;

  /**
   * Options set for shared handles.
   * @var array
   */
  protected $options;

  /**
   */
  public function __construct() {
    $this->sharedHandles = curl_share_init();
    curl_share_setopt($this->sharedHandles, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);
    curl_share_setopt($this->sharedHandles, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);
    curl_share_setopt($this->sharedHandles, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
  }

  /**
   */
  public function getOptions() {
    return $this->options;
  }

  public function shareOption($option) {
    $this->options[$option] = CURLSHOPT_SHARE;
    curl_share_setopt($this->sharedHandles, CURLSHOPT_SHARE, $option);
  }

  public function shareOptions(array $options) {
    foreach ($options as $option) {
      $this->shareOption($option);
    }
  }

  public function unShareOption($option) {
    $this->options[$option] = CURLSHOPT_UNSHARE;
    curl_share_setopt($this->sharedHandles, CURLSHOPT_UNSHARE, $option);
  }

  public function unShareOptions(array $option) {
    foreach ($options as $option) {
      $this->unShareOption($option);
    }
  }

  public function createHandle($url, array $options = NULL) {
    $handle = new CurlHandle($url, $options);
    $handle->setOpt(CURLOPT_SHARE, $this->sharedHandles);
    return $handle;
  }
}
