<?php

namespace includes\model;

final class itModel {
  use \ModelInterest {
    \ModelInterest::__construct as private __interestConstruct;
  }
  use \ModelCVLists {
    \ModelCVLists::__construct as private __listConstruct;
  }
  use \ModelNotice {
    \ModelNotice::__construct as private __noticeConstruct;
  }
  public function __construct() {
    $this->__listConstruct();
    $this->__interestConstruct();
    $this->__noticeConstruct();
  }

}