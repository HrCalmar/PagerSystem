<?php
namespace App\Core;

class Router {
    private array $routes = [];
    
    public function get(string $path, callable|array $handler, array $middleware = []): void {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    public function post(string $path, callable|array $handler, array $middleware = []): void {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path, $params)) {
                foreach ($route['middleware'] as $middleware) {
                    if (is_array($middleware)) {
                        $middleware = [new $middleware[0], $middleware[1]];
                    }
                    $middleware();
                }
                
                $handler = $route['handler'];
                if (is_array($handler) && is_string($handler[0])) {
                    $handler = [new $handler[0], $handler[1]];
                }
                
                call_user_func_array($handler, $params);
                return;
            }
        }
        
        http_response_code(404);
        echo '404 - Side ikke fundet';
    }
    
    private function matchPath(string $pattern, string $path, &$params): bool {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $path, $matches)) {
            array_shift($matches);
            $params = $matches;
            return true;
        }
        
        return false;
    }
}