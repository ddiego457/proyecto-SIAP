<?php

namespace EquipoSiap\Siap\controller;

//Carga el archivo del Trait directamente
require_once dirname(__DIR__) . '/helpers/SessionTrait.php';
use EquipoSiap\Siap\helpers\SessionTrait;

// Traits de validación por módulo (expresiones regulares de cada formulario)
require_once dirname(__DIR__) . '/helpers/UserValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/DependenciaValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/ResponsableValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/ProveedorValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/ProductosValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/PeriodoValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/TasaBCVValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/RequerimientoValidationTrait.php';
require_once dirname(__DIR__) . '/helpers/ReporteValidationTrait.php';

use EquipoSiap\Siap\helpers\UserValidationTrait;
use EquipoSiap\Siap\helpers\DependenciaValidationTrait;
use EquipoSiap\Siap\helpers\ResponsableValidationTrait;
use EquipoSiap\Siap\helpers\ProveedorValidationTrait;
use EquipoSiap\Siap\helpers\ProductosValidationTrait;
use EquipoSiap\Siap\helpers\PeriodoValidationTrait;
use EquipoSiap\Siap\helpers\TasaBCVValidationTrait;
use EquipoSiap\Siap\helpers\RequerimientoValidationTrait;
use EquipoSiap\Siap\helpers\ReporteValidationTrait;

class FrontController
{
    // se inyecta el Trait dentro de la clase
    use SessionTrait;
    use UserValidationTrait;
    use DependenciaValidationTrait;
    use ResponsableValidationTrait;
    use ProveedorValidationTrait;
    use ProductosValidationTrait;
    use PeriodoValidationTrait;
    use TasaBCVValidationTrait;
    use RequerimientoValidationTrait;
    use ReporteValidationTrait;

    private $url;
    private $dir = __DIR__ . '/';
    private $controller = 'Controller.php';

    public function __construct()
    {
        $this->startSession();
        $this->url = isset($_REQUEST['url']) && trim((string)$_REQUEST['url']) !== '' ? trim((string)$_REQUEST['url']) : '';
        $this->getUrl();
    }

    private function getUrl()
    {
        try {
            if ($this->url === '' || $this->url === 'inicio' || $this->url === 'dashboard' || $this->url === 'index' || $this->url === '/') {
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
            $controllerFile = $this->dir . $this->url . $this->controller;
            // Muestra el mensaje, archivo y línea real del error
            $failDescript = "Error: " . $e->getMessage() 
                            . " en " . $e->getFile() 
                            . " línea " . $e->getLine();
            // Pasar la excepción a la vista para mostrar detalles
            $e = $e;
            require_once dirname(__DIR__) . '/view/errorView.php';
        }
    }
}