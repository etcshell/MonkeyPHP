<?php
namespace DefaultApp;

use Monkey;

class AppWeb extends Monkey\App\Web{

    public function __construct()
    {
        $this->DEBUG=E_ALL ^ E_NOTICE ^ E_WARNING;
        parent::__construct();
    }
}




