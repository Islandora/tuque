<?php

require_once 'RepositoryFactory.php';
require_once 'tests/TestHelpers.php';
require_once 'tests/implementations/RepositoryTestBase.php';

class Fedora3RepositoryDecoratorTest extends RepositoryTestBase {

  protected function setUp() {
    $this->repository = new RepositoryDecorator(RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS)));
    $this->api = $this->repository->api;
  }
}