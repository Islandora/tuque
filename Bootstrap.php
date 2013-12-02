<?php

/**
 * @file
 * Bootstrap Tuque to allow for autoloading of classes and other goodness.
 */

/**
 * Autoload Tuque classes.
 */
spl_autoload_register(function ($class) {
    $files = array(
      'AbstractObject' => 'AbstractObject.php',
      'AbstractDatastream' => 'AbstractDatastream.php',
      'Tuque\Object' => 'AbstractObject.php',
      'Tuque\NewObject' => 'AbstractObject.php',
      'Tuque\Datastream' => 'AbstractDatastream.php',
      'Tuque\RepositoryConfig' => 'AbstractRepository.php',
      'Tuque\Repository' => 'AbstractRepository.php',
      'Tuque\SimpleCache' => 'includes/SimpleCache.php',
      'Tuque\Delegate' => 'includes/Delegate.php',
      'Tuque\Curl' => 'includes/Curl.php',
      'Tuque\CurlHandle' => 'includes/Curl.php',
      'Tuque\CurlSharedHandles' => 'includes/Curl.php',
      'Tuque\HttpResponse' => 'includes/Curl.php',
      'Tuque\HttpResponseException' => 'includes/Curl.php',
      'Tuque\RepositoryFactory' => 'implementations/RepositoryFactory.php',
      'Tuque\Fedora\v3\FoxmlDocument' => 'implementations/fedora3/FoxmlDocument.php',
      'Tuque\Fedora\v3\RepositoryQuery' => 'implementations/fedora3/RepositoryQuery.php',
      'Tuque\Fedora\v3\NewFedoraDatastream' => 'implementations/fedora3/Datastream.php',
      'Tuque\Fedora\v3\FedoraDatastreamVersion' => 'implementations/fedora3/Datastream.php',
      'Tuque\Fedora\v3\FedoraDatastream' => 'implementations/fedora3/Datastream.php',
      'Tuque\Fedora\v3\NewFedoraObject' => 'implementations/fedora3/Object.php',
      'Tuque\Fedora\v3\FedoraObject' => 'implementations/fedora3/Object.php',
      'Tuque\Fedora\v3\FedoraRelationships' => 'implementations/fedora3/FedoraRelationships.php',
      'Tuque\Fedora\v3\FedoraRelsExt' => 'implementations/fedora3/FedoraRelationships.php',
      'Tuque\Fedora\v3\FedoraRelsInt' => 'implementations/fedora3/FedoraRelationships.php',
      'Tuque\Fedora\v3\RepositoryConnection' => 'implementations/fedora3/RepositoryConnection.php',
      'Tuque\Fedora\v3\FedoraRepository' => 'implementations/fedora3/Repository.php',
      'Tuque\Fedora\v3\FedoraAPI' => 'implementations/fedora3/FedoraApi.php',
      'Tuque\Fedora\v3\FedoraAPIA' => 'implementations/fedora3/FedoraApi.php',
      'Tuque\Fedora\v3\FedoraAPIM' => 'implementations/fedora3/FedoraApi.php',
      'Tuque\Fedora\v3\FedoraDate' => 'implementations/fedora3/FedoraDate.php',
      'Tuque\Fedora\v3\RepositoryException' => 'implementations/fedora3/RepositoryException.php',
      'Tuque\Fedora\v3\RepositoryXmlError' => 'implementations/fedora3/RepositoryException.php',
      'Tuque\Fedora\v3\RepositoryBadArguementException' => 'implementations/fedora3/RepositoryException.php',
      'Tuque\Fedora\v3\RepositoryRelationshipsException' => 'implementations/fedora3/RepositoryException.php',
      'Tuque\Fedora\v3\FedoraApiSerializer' => 'implementations/fedora3/FedoraApiSerializer.php',
      'Tuque\Fedora\v3\HttpConnectionException' => 'implementations/fedora3/HttpConnection.php',
      'Tuque\Fedora\v3\CurlConnection' => 'implementations/fedora3/HttpConnection.php',
    );
    foreach ($files as $key => $file) {
      if (strcasecmp($key, $class) == 0) {
        include_once $files[$key];
      }
    }
  }
);


/**
 * @todo Remove debug code.
 */
if (!function_exists('debug')) {
  function debug() {};
}

/**
 *
 */
function trace() {
  $stack = debug_backtrace();
  array_shift($stack);
  $out = array();
  foreach ($stack as &$frame) {
    $f = isset($frame['class']) ? $frame['class'] . '::' : '';
    $f .= $frame['function'];
    $out[] = $f;
  }
  dsm($out, 'trace');
}

/**
 *
 */
function fulltrace() {
  $stack = debug_backtrace();
  array_shift($stack);
  foreach ($stack as &$frame) {
    unset($frame['args']);
    unset($frame['object']);
  }
  dsm($stack, 'fulltrace');
}
