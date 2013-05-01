<?php

require_once 'RepositoryFactory.php';
require_once 'tests/TestHelpers.php';

class RepositoryQueryTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $this->repository = RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
    $this->api = $this->repository->api;
  }

  public function testItql() {
    $query = 'select $pid $label from <#ri>
where $pid <fedora-model:label> $label';
    $results = $this->repository->ri->itqlQuery($query);
    $this->assertTrue(TRUE);
  }
}