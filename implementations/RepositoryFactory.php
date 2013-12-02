<?php

/**
 * @file
 * Returns the correct repository given the configuration if possible.
 */

namespace Tuque;
use Tuque\Fedora\v3 as Fedora3;
use InvalidArgumentException, SimpleXMLElement;

class RepositoryFactory {

  /**
   * Gets a repository given the repositories configuration.
   *
   * @throws InvalidArgumentException
   *
   * @param RepositoryConfig $config
   *   The configuration that describes the repository to instantiate.
   *
   * @return Tuque/Repository
   *   Returns the requested repository.
   */
  public static function getRepository(RepositoryConfig $config) {
    $description = self::describe($config);
    if (version_compare($description['version'], '4.0.0', '>=')) {
      return self::getRepositoryFedora4($config);
    }
    elseif (version_compare($description['version'], '3.0.0', '>=')) {
      return self::getRepositoryFedora3($config);
    }
    throw new InvalidArgumentException("{$description['type']} -> {$description['version']} is not supported.");
  }

  /**
   * Describes a repository by type and version for the given configuration.
   *
   * @param RepositoryConfig $config
   *   The configuration that allows us to connect with the repository.
   *
   * @return array
   *   An associative array which defines the repository type and version.
   *   - type: The type of repository, for now always expected to be 'fedora'.
   *   - version: The numeric version of the repository.
   */
  public static function describe(RepositoryConfig $config) {
    $version = self::getVersionFedora3($config);
    $version = ($version === NULL) ? self::getVersionFedora4($config) : $version;
    if ($version === NULL) {
      throw new InvalidArgumentException('RepositoryConfig argument does not describe a valid repository.');
    }
    return array(
      'type' => 'fedora',
      'version' => $version,
    );
  }

  /**
   * Attempts to get the version of the repository assuming its Fedora 3.
   *
   * @param RepositoryConfig $config
   *   The configuration that allows us to connect with the repository.
   *
   * @return string
   *   The version if successful, NULL otherwise.
   */
  protected static function getVersionFedora3(RepositoryConfig $config) {
    $options = array(
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_USERPWD => $config->getUsername() . ':' . $config->getPassword(),
    );
    $handle = new CurlHandle($config->getURL() . "/describe?xml=true", $options);
    try {
      $response = Curl::get($handle);
    }
    catch(Exception $e) {
      return NULL;
    }
    $xml = new SimpleXMLElement($response->getContent());
    $xml->registerXPathNamespace('default', 'http://www.fedora.info/definitions/1/0/access/');
    $result = $xml->xpath('//default:repositoryVersion');
    if (isset($result[0])) {
      return (string) $result[0];
    }
    return NULL;
  }

  /**
   * Attempts to get the version of the repository assuming its Fedora 4.
   *
   * @param RepositoryConfig $config
   *   The configuration that allows us to connect with the repository.
   *
   * @return string
   *   The version if successful, NULL otherwise.
   */
  protected static function getVersionFedora4(RepositoryConfig $config) {
    // @todo Implement this function.
    return NULL;
  }

  /**
   * Instaintates a Fedora 3.x repository.
   *
   * This function may fail.
   *
   * @throws Exception
   *
   * @param RepositoryConfig $config
   *   The configuration that allows us to connect with the repository.
   *
   * @return FedoraRepository
   *   The Fedora 3 repository implementation.
   */
  protected static function getRepositoryFedora3(RepositoryConfig $config) {
    $connection = new Fedora3\RepositoryConnection($config->getURL(), $config->getUsername(), $config->getPassword());
    $connection->reuseConnection = TRUE;
    $api = new Fedora3\FedoraApi($connection);
    return new Fedora3\FedoraRepository($api, $config->getCache());
  }

  /**
   * Instaintates a Fedora 4.x repository.
   *
   * @param RepositoryConfig $config
   *   The configuration that allows us to connect with the repository.
   */
  protected static function getRepositoryFedora4(RepositoryConfig $config) {
    // @todo Implement.
    return NULL;
  }
}
