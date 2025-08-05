<?php
class Router {
    private $routes = [];
    private $middleware = [];
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function middleware($middleware) {
        $this->middleware[] = $middleware;
    }
    
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($requestPath !== '/' && substr($requestPath, -1) === '/') {
            $requestPath = rtrim($requestPath, '/');
        }
        
        // Apply global middleware
        foreach ($this->middleware as $middleware) {
            $this->runMiddleware($middleware);
        }
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                return $this->handleRoute($route['handler']);
            }
        }
        
        // 404 Not Found
        $this->handle404();
    }
    
    private function matchPath($routePath, $requestPath) {
        // Simple exact match for now
        return $routePath === $requestPath;
    }
    
    private function handleRoute($handler) {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            if (class_exists($controller)) {
                $controllerInstance = new $controller();
                if (method_exists($controllerInstance, $method)) {
                    return $controllerInstance->$method();
                }
            }
        } elseif (is_callable($handler)) {
            return $handler();
        }
        
        throw new Exception("Invalid route handler: $handler");
    }
    
    private function runMiddleware($middleware) {
        if (is_string($middleware) && class_exists($middleware)) {
            $middlewareInstance = new $middleware();
            $middlewareInstance->handle();
        } elseif (is_callable($middleware)) {
            $middleware();
        }
    }
    
    private function handle404() {
        http_response_code(404);
        include __DIR__ . '/../views/404.php';
        exit();
    }
}
?>