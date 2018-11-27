<?php

namespace includes\model;

final class itModel {
  use \ModelInterest {
    \ModelInterest::__construct as private __interestConstruct;
  }
  use \ModelCVLists {
    \ModelCVLists::__construct as private __listConstruct;
  }
  public function __construct() {
    $this->__listConstruct();
    $this->__interestConstruct();
  }

}