<?php

namespace EquipoSiap\Siap\controller;

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
        try {
            if ($this->url === '' || $this->url === 'inicio' || $this->url === 'dashboard') {
                include dirname(__DIR__) . '/controller/loginDesingController.php';
                return;
            }

            $controllerFile = $this->dir . $this->url . $this->controller;

            if (file_exists($controllerFile)) {
                require_once $controllerFile;
            } else {
                // Captura URL no válida (Error 404)
                http_response_code(404);
                $failDescript = "La ruta solicitada ('" . htmlspecialchars($this->url) . "') no existe en el sistema.";
                
                // Ajusta esta ruta dependiendo de dónde guardes 'errorView.php'
                require_once dirname(__DIR__) . '/view/errorView.php'; 
            }
        } catch (\Throwable $e) {
            // Captura errores internos, fallos de base de datos o sintaxis en los controladores (Error 500)
            http_response_code(500);
            $failDescript = "Error crítico durante la ejecución: " . $e->getMessage();
            
            require_once dirname(__DIR__) . '/view/errorView.php';
        }
    }
}