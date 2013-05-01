<?php

// Fedora 3 includes.
require_once 'implementations/fedora3/Datastream.php';
require_once 'implementations/fedora3/FedoraApi.php';
require_once 'implementations/fedora3/FedoraApiSerializer.php';
require_once 'implementations/fedora3/Object.php';
require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora3/Repository.php';
require_once 'implementations/fedora3/FedoraRelationships.php';

// Generic includes.
include_once 'Cache.php';
include_once 'RepositoryException.php';

class RepositoryFactory {
  public static function getRepository($type, RepositoryConfigInterface $config, AbstractCache $cache = NULL) {
    switch ($type) {
      case 'fedora3':
        if($cache === NULL) {
          $cache = new SimpleCache();
        }
        $api = new FedoraApi(new RepositoryConnection($config), new FedoraApiSerializer());
        return new FedoraRepository($api, $cache);
      break;
      default:
        throw new RepositoryBadArguementException("$type is not a supported repository type.");
      break;
    }
  }
}