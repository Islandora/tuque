<?php

/**
 * @file
 * This file defines the classes used to make HTTP requests.
 */

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
class HttpConnectionException extends Exception {

  protected $response;

  /**
   * The constructor for the exception. Adds a response field.
   *
   * @param string $message
   *   The error message
   * @param int $code
   *   The error code
   * @param array $response
   *   The HTTP response
   * @param Exception $previous
   *   The previous exception in the chain
   */
  function __construct($message, $code, $response = NULL, $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->response = $response;
  }

  /**
   * Get the HTTP response that caused the exception.
   *
   * @return array
   *   Array containing the HTTP response. It has three keys: status, headers
   *   and content.
   */
  function getResponse() {
    return $this->response;
  }
}

/**
 * Abstract class defining functions for HTTP connections
 */
abstract class HttpConnection {

  /**
   * This determines if the HTTP connection should use cookies. (Default: TRUE)
   * @var type boolean
   */
  public $cookies = TRUE;
  /**
   * The username to connect with. If no username is desired then use NULL.
   * (Default: NULL)
   * @var type string
   */
  public $username = NULL;
  /**
   * The password to connect with. Used if a username is set.
   * @var type string
   */
  public $password = NULL;
  /**
   * TRUE to check the existence of a common name and also verify that it
   * matches the hostname provided. (Default: TRUE)
   * @var type boolean
   */
  public $verifyHost = TRUE;
  /**
   * FALSE to stop cURL from verifying the peer's certificate. (Default: TRUE)
   * @var type boolean
   */
  public $verifyPeer = TRUE;
  /**
   * The maximum number of seconds to allow cURL functions to execute. (Default:
   * cURL default)
   * @var type int
   */
  public $timeout = NULL;
  /**
   * The number of seconds to wait while trying to connect. Use 0 to wait
   * indefinitely. (Default: 5)
   * @var type
   */
  public $connectTimeout = 5;
  /**
   * The useragent to use. (Default: cURL default)
   * @var type string
   */
  public $userAgent = NULL;
  /**
   * If this is set to true, the connection will be recycled, so that cURL will
   * try to use the same connection for multiple requests. If this is set to
   * FALSE a new connection will be used each time.
   * @var type boolean
   */
  public $reuseConnection = TRUE;
  /**
   * Turn on to print debug infotmation to stderr.
   * @var type boolean
   */
  public $debug = FALSE;

  /**
   * Post a request to the server. This is primarily used for
   * sending files.
   *
   * @todo Test this for posting general form data. (Other then files.)
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file.
   * @param string $data
   *   What this parameter contains is decided by the $type parameter.
   * @param string $content_type
   *   The content type header to set for the post request.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function postRequest($url, $type = 'none', $data = NULL, $content_type = NULL);

  /**
   * Send a HTTP GET request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function getRequest($url);

  /**
   * Send a HTTP PUT request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file, none.
   * @param string $data
   *   What this parameter contains is decided by the $type parameter.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  abstract public function putRequest($url, $type = 'none', $file = NULL);
}

/**
 * This class defines a abstract HttpConnection using the PHP cURL library.
 */
class CurlConnection extends HttpConnection {
  const COOKIE_LOCATION = 'curl_cookie';
  protected $cookieFile = NULL;
  protected $curlContext = NULL;

  /**
   * Constructor for the connection.
   *
   * @throws HttpConnectionException
   */
  public function __construct() {

    if (!function_exists("curl_init")) {
      throw new HttpConnectionException('cURL PHP Module must to enabled.', 0);
    }

    $this->cookieFile = tempnam(sys_get_temp_dir(), 'curlcookie');

    // See if we have any cookies in the session already
    // this makes sure JESSSION ids persist.
    if (isset($_SESSION[self::COOKIE_LOCATION])) {
      file_put_contents($this->cookieFile, $_SESSION[self::COOKIE_LOCATION]);
    }
  }

  /**
   * Destructor for the connection.
   */
  public function __destruct() {
    // Before we go, save our fedora session cookie to the browsers session.
    if (isset($_SESSION)) {
      $SESSION[self::COOKIE_LOCATION] = file_get_contents($this->cookieFile);
    }

    if ($this->curlContext) {
      $this->unallocateCurlContext();
    }

    unlink($this->cookieFile);
  }

  /**
   * This function sets up the context for curl.
   */
  protected function getCurlContext() {
    $this->curlContext = curl_init();
  }

  /**
   * Unallocate curl context
   */
  protected function unallocateCurlContext() {
    curl_close($this->curlContext);
    $this->curlContext = NULL;
  }

  /**
   * This sets the curl options
   */
  protected function setupCurlContext($url) {
    if (!$this->curlContext) {
      $this->getCurlContext();
    }
    curl_setopt($this->curlContext, CURLOPT_URL, $url);
    curl_setopt($this->curlContext, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
    curl_setopt($this->curlContext, CURLOPT_SSL_VERIFYHOST, $this->verifyHost ? 2 : 1);
    if ($this->timeout) {
      curl_setopt($this->curlContext, CURLOPT_TIMEOUT, $this->timeout);
    }
    curl_setopt($this->curlContext, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
    if ($this->userAgent) {
      curl_setopt($this->curlContext, CURLOPT_USERAGENT, $this->userAgent);
    }
    if ($this->cookies) {
      curl_setopt($this->curlContext, CURLOPT_COOKIEFILE, $this->cookieFile);
      curl_setopt($this->curlContext, CURLOPT_COOKIEJAR, $this->cookieFile);
    }
    curl_setopt($this->curlContext, CURLOPT_FAILONERROR, FALSE);
    curl_setopt($this->curlContext, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($this->curlContext, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($this->curlContext, CURLOPT_HEADER, TRUE);
    if ($this->debug) {
      curl_setopt($this->curlContext, CURLOPT_VERBOSE, 1);
    }
    if ($this->username) {
      $user = $this->username;
      $pass = $this->password;
      curl_setopt($this->curlContext, CURLOPT_USERPWD, "$user:$pass");
    }
  }

  /**
   * This function actually does the cURL request. It is a private function
   * meant to be called by the public get, post and put methods.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Array has keys: (status, headers, content).
   */
  protected function doCurlRequest() {
    $curl_response = curl_exec($this->curlContext);

    // Since we are using exceptions we trap curl error
    // codes and toss an exception, here is a good error
    // code reference.
    // http://curl.haxx.se/libcurl/c/libcurl-errors.html
    $error_code = curl_errno($this->curlContext);
    $error_string = curl_error($this->curlContext);
    if ($error_code != 0) {
      throw new HttpConnectionException($error_string, $error_code);
    }

    $info = curl_getinfo($this->curlContext);

    $response = array();
    $response['status'] = $info['http_code'];
    $response['headers'] = substr($curl_response, 0, $info['header_size'] - 1);
    $response['content'] = substr($curl_response, $info['header_size']);

    // We do some ugly stuff here to strip the error string out
    // of the HTTP headers, since curl doesn't provide any helper.
    $http_error_string = explode("\r\n\r\n", $response['headers']);
    $http_error_string = $http_error_string[count($http_error_string) - 1];
    $http_error_string = explode("\r\n", $http_error_string);
    $http_error_string = substr($http_error_string[0], 13);
    $http_error_string = trim($http_error_string);

    // Throw an exception if this isn't a 2XX response.
    if (!preg_match("/^2/", $info['http_code'])) {
      throw new HttpConnectionException($http_error_string, $info['http_code'], $response);
    }

    return $response;
  }

  /**
   * Post a request to the server. This is primarily used for
   * sending files.
   *
   * @todo Test this for posting general form data. (Other then files.)
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file.
   * @param string $data
   *   What this parameter contains is decided by the $type parameter.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  public function postRequest($url, $type = 'none', $data = NULL, $content_type = NULL) {
    $this->setupCurlContext($url);
    curl_setopt($this->curlContext, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($this->curlContext, CURLOPT_POST, TRUE);

    switch (strtolower($type)) {
      case 'string':
        if ($content_type) {
          $headers = array("Content-Type: $content_type");
        }
        else {
          $headers = array("Content-Type: text/plain");
        }
        curl_setopt($this->curlContext, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, $data);
        break;

      case 'file':
        if ($content_type) {
          curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, array('file' => "@$data;type=$content_type"));
        }
        else {
          curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, array('file' => "@$data"));
        }
        break;

      case 'none':
        curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, array());
        break;

      default:
        throw new HttpConnectionException('$type must be: string, file. ' . "($type).", 0);
    }

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt($this->curlContext, CURLOPT_POST, FALSE);
      curl_setopt($this->curlContext, CURLOPT_HTTPHEADER, array());
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * Send a HTTP PUT request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   * @param string $type
   *   This paramerter must be one of: string, file.
   * @param string $file
   *   What this parameter contains is decided by the $type parameter.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  function putRequest($url, $type = 'none', $file = NULL) {
    $this->setupCurlContext($url);
    curl_setopt($this->curlContext, CURLOPT_CUSTOMREQUEST, 'PUT');
    switch (strtolower($type)) {
      case 'string':
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $file);
        rewind($fh);
        $size = strlen($file);
        curl_setopt($this->curlContext, CURLOPT_PUT, TRUE);
        curl_setopt($this->curlContext, CURLOPT_INFILE, $fh);
        curl_setopt($this->curlContext, CURLOPT_INFILESIZE, $size);
        break;

      case 'file':
        $fh = fopen($file, 'r');
        $size = filesize($file);
        curl_setopt($this->curlContext, CURLOPT_PUT, TRUE);
        curl_setopt($this->curlContext, CURLOPT_INFILE, $fh);
        curl_setopt($this->curlContext, CURLOPT_INFILESIZE, $size);
        break;

      case 'none':
        break;

      default:
        throw new HttpConnectionException('$type must be: string, file. ' . "($type).", 0);
    }

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      //curl_setopt($this->curlContext, CURLOPT_PUT, FALSE);
      //curl_setopt($this->curlContext, CURLOPT_INFILE, 'default');
      //curl_setopt($this->curlContext, CURLOPT_CUSTOMREQUEST, FALSE);
      // We can't unallocate put requests becuase CURLOPT_INFILE can't be undone
      // this is ugly, but it gets the job done for now.
      $this->unallocateCurlContext();
    }
    else {
      $this->unallocateCurlContext();
    }

    if (isset($fh)) {
      fclose($fh);
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * Send a HTTP GET request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  function getRequest($url) {
    $this->setupCurlContext($url);

    curl_setopt($this->curlContext, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($this->curlContext, CURLOPT_HTTPGET, TRUE);

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt($this->curlContext, CURLOPT_HTTPGET, FALSE);
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

  /**
   * Send a HTTP DELETE request to URL.
   *
   * @param string $url
   *   The URL to post the request to. Should start with the
   *   protocol. For example: http://.
   *
   * @throws HttpConnectionException
   *
   * @return array
   *   Associative array containing:
   *   * $return['status'] = The HTTP status code
   *   * $return['headers'] = The HTTP headers of the reply
   *   * $return['content'] = The body of the HTTP reply
   */
  function deleteRequest($url) {
    $this->setupCurlContext($url);

    curl_setopt($this->curlContext, CURLOPT_CUSTOMREQUEST, 'DELETE');

    // Ugly substitute for a try catch finally block.
    $exception = NULL;
    try {
      $results = $this->doCurlRequest();
    } catch (HttpConnectionException $e) {
      $exception = $e;
    }

    if ($this->reuseConnection) {
      curl_setopt($this->curlContext, CURLOPT_CUSTOMREQUEST, NULL);
    }
    else {
      $this->unallocateCurlContext();
    }

    if ($exception) {
      throw $exception;
    }

    return $results;
  }

}
