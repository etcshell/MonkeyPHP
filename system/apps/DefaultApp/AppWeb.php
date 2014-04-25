<?php
namespace DefaultApp;

use Monkey;

/**
 * AppWeb
 * Web应用服务类，这个类是每个应用必须的，而且类名也必须是AppWeb
 * @package DefaultApp
 */
class AppWeb extends Monkey\App\Web{

    public function __construct()
    {
        $this->DEBUG=E_ALL ^ E_NOTICE ^ E_WARNING;
        parent::__construct();
    }
}




