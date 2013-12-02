<?php

use Tuque\Fedora\v3\RepositoryConnection,
  Tuque\Fedora\v3\FedoraAPI,
  Tuque\Fedora\v3\FedoraRepository,
  Tuque\SimpleCache;

require_once 'TestHelpers.php';

class RepositoryQueryTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
  }

  public function testItql() {
    $query = 'select $pid $label from <#ri>
where $pid <fedora-model:label> $label';
    $results = $this->repository->ri->itqlQuery($query);
    $this->assertTrue(TRUE, 'The query did not throw an exception.');
  }

  public function testCount() {
    $query = 'select $pid $label from <#ri>
where $pid <fedora-model:label> $label';
    $results = count($this->repository->ri->itqlQuery($query));
    $number = $this->repository->ri->countQuery($query, 'itql');

    $this->assertEquals($results, $number, 'The number of tuples returned was equal.');
  }
}
