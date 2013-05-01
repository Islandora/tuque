<?php
/**
 * @file
 * This is an interface which is what we expect to need to connect to a
 * repository.
 */

/**
 * The general interface for a RepositoryConfig object.
 */
interface RepositoryConfigInterface {
  /**
   * Simple constructor defintion for the repository
   */
  function __construct($url, $username, $password);
}

/**
 * Implementation of a repository config.
 */
class RepositoryConfig implements RepositoryConfigInterface{
  /**
   * Simple constructor defintion for the repository
   */
  function __construct($url, $username = NULL, $password = NULL){
    $this->url = $url;
    $this->username = $username;
    $this->password = $password;
  }
}