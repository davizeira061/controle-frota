<?php
/**
 * CLASS Router
 * 
 * Sistema de rotas simples e funcional
 * Suporta: GET, POST, PUT, DELETE
 * Suporta: Parâmetros, Middlewares, Controladores
 * 
 * Uso:
 * $router = new Router();
 * $router->get('/veiculos', 'VeiculoController@index');
 * $router->post('/veiculos', 'VeiculoController@store');
 * $router->get('/veiculos/{id}', 'VeiculoController@show');
 * $router->put('/veiculos/{id}', 'VeiculoController@update');
 * $router->delete('/veiculos/{id}', 'VeiculoController@destroy');
 * 
 * Com middlewares:
 * $router->get('/admin/dashboard', 'DashboardController@index', ['auth', 'admin']);
 */

namespace Core;

class Router
{
    private array $routes = [];
    private string $baseNamespace = 'App\\Controllers\\';
    private string $currentMethod = '';
    private string $currentPath = '';
    private array $middlewares = [];

    /**
     * Registra rota GET
     */
    public function get(string $path, string $routeTarget, array $middlewares = []): void
    {
        $this->registerRoute('GET', $path, $routeTarget, $middlewares);
    }

    /**
     * Registra rota POST
     */
    public function post(string $path, string $routeTarget, array $middlewares = []): void
    {
        $this->registerRoute('POST', $path, $routeTarget, $middlewares);
    }

    /**
     * Registra rota PUT
     */
    public function put(string $path, string $routeTarget, array $middlewares = []): void
    {
        $this->registerRoute('PUT', $path, $routeTarget, $middlewares);
    }

    /**
     * Registra rota DELETE
     */
    public function delete(string $path, string $routeTarget, array $middlewares = []): void
    {
        $this->registerRoute('DELETE', $path, $routeTarget, $middlewares);
    }

    /**
     * Registra rota com o método HTTP especificado
     */
    private function registerRoute(string $method, string $path, string $routeTarget, array $middlewares): void
    {
        // Normalizar path
        $path = '/' . ltrim($path, '/');

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'target' => $routeTarget,
            'middlewares' => $middlewares,
            'regex' => $this->pathToRegex($path)
        ];
    }

    /**
     * Converte path com parâmetros para regex
     * /veiculos/{id} → /veiculos/(?P<id>\d+)
     * /veiculos/{id}/registros/{registroId} → /veiculos/(?P<id>\d+)/registros/(?P<registroId>\d+)
     */
    private function pathToRegex(string $path): string
    {
        // Escape special regex chars (exceto { e })
        $regex = preg_quote($path, '#');
        
        // Substituir {param} por regex
        $regex = preg_replace_callback('#\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}#', function($matches) {
            return "(?P<{$matches[1]}>[a-zA-Z0-9_-]+)";
        }, $regex);

        return '#^' . $regex . '$#';
    }

    /**
     * Dispara a rota apropriada
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remover query string
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Normalizar path (remover múltiplos slashes e trailing slashes)
        $path = '/' . trim($path, '/');

        // Lógica para detectar o caminho da aplicação
        // SCRIPT_NAME é o caminho real do index.php (ex: /controle-frota/public/index.php)
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = str_replace('\\', '/', dirname($scriptName)); // /controle-frota/public

        // Se o path começa com o baseDir, removemos ele
        if ($baseDir !== '/' && strpos($path, $baseDir) === 0) {
            $path = substr($path, strlen($baseDir));
        } else {
            // Se o path não começa com baseDir, talvez o usuário esteja acessando via redirecionamento do .htaccess da raiz
            // Nesse caso, o baseDir pode ser apenas a parte antes do /public
            $rootBase = str_replace('/public', '', $baseDir);
            if ($rootBase !== '' && strpos($path, $rootBase) === 0) {
                $path = substr($path, strlen($rootBase));
            }
        }

        // Garantir que o path comece com / e não termine com / (exceto se for apenas /)
        $path = '/' . trim($path, '/');

        // Debug se necessário (descomente para ver o path processado)
        // error_log("Method: $method | URI: $uri | BaseDir: $baseDir | Processed Path: $path");

        // Procurar pela rota
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $path, $matches)) {
                // Executar middlewares
                $this->executeMiddlewares($route['middlewares']);

                // Extrair parâmetros
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Disparar controller
                $this->callController($route['target'], $params);
                return;
            }
        }

        // Rota não encontrada
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Rota não encontrada: ' . htmlspecialchars($method . ' ' . $path)
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Executa middlewares
     */
    private function executeMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $middlewareClass = 'App\\Middlewares\\' . ucfirst($middleware) . 'Middleware';
            
            if (!class_exists($middlewareClass)) {
                throw new \Exception("Middleware não encontrado: {$middlewareClass}");
            }

            $middlewareInstance = new $middlewareClass();
            
            if (!method_exists($middlewareInstance, 'handle')) {
                throw new \Exception("Método handle() não encontrado em {$middlewareClass}");
            }

            $middlewareInstance->handle();
        }
    }

    /**
     * Chama o controller
     */
    private function callController(string $target, array $params): void
    {
        [$controller, $method] = explode('@', $target);
        
        $controllerClass = $this->baseNamespace . $controller;

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controlador não encontrado: {$controllerClass}");
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $method)) {
            throw new \Exception("Método {$method} não encontrado em {$controllerClass}");
        }

        call_user_func_array([$controllerInstance, $method], [$params]);
    }

    /**
     * Retorna todas as rotas registradas (útil para debug)
     */
    public function getRoutes(): array
    {
        return array_map(function($route) {
            return [
                'method' => $route['method'],
                'path' => $route['path'],
                'target' => $route['target'],
                'middlewares' => $route['middlewares']
            ];
        }, $this->routes);
    }
}
