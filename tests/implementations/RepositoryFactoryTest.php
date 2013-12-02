<?php

/**
 * @file
 * Test that the Repository Factory will load the correct implementation.
 */

use Tuque\RepositoryFactory, Tuque\RepositoryConfig;

class RepositoryFactoryTest extends PHPUnit_Framework_TestCase {

  function testDescribeFedora3() {
    $config = new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $description = RepositoryFactory::describe($config);
    $expected = array(
      'type' => 'fedora',
      'version' => FEDORAVERSION,
    );
    $this->assertEquals($description, $expected, 'Accurately described the repository.');
  }

  function testGetRepositoryFedora3() {
    $config = new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $repository = RepositoryFactory::getRepository($config);
    $this->assertTrue(isset($repository), 'Created a repository instance.');
    $this->assertEquals(get_class($repository), 'Tuque\Fedora\v3\FedoraRepository', 'Created the expected repository instance');
  }

}
