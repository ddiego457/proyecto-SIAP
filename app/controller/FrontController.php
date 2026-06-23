<?php

namespace App\PracticaCrud\Controller;

class FrontController
{
    private $url;
    private $dir = __DIR__ . '/';
    private $controller = 'Controller.php';

    public function __construct()
    {
        $this->url = isset($_REQUEST['url']) && trim((string)$_REQUEST['url']) !== '' ? trim((string)$_REQUEST['url']) : '';
        $this->getUrl();
    }

    private function getUrl()
    {
        if ($this->url === '' || $this->url === 'inicio' || $this->url === 'dashboard') {
            include dirname(__DIR__) . '/Controller/loginDesingController.php';
            return;
        }

        $controllerFile = $this->dir . $this->url . $this->controller;

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        } else {
            echo 'ERROR: la pagina no existe';
        }
    }
}