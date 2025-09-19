<?php
// app/Core/Router.php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Основной метод для обработки входящего запроса
     * @param string $method HTTP-метод (GET, POST, ...)
     * @param string $uri Запрошенный URI
     */
    public function dispatch(string $method, string $uri): void
    {
        // Убираем query string (часть после ?)
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            // Ожидаемый формат: [$method, $path, $handler, $middlewares]
            if (count($route) < 3) {
                continue;
            }

            $routeMethod  = $route[0];
            $routePath    = $route[1];
            $handler      = $route[2];
            $middlewares  = $route[3] ?? [];

            // 1. Проверка HTTP-метода
            if ($method !== $routeMethod) {
                continue;
            }

            // 2. Проверка пути с поддержкой параметров
            $params = $this->match($routePath, $uri);
            if ($params !== false) {
                // Найдено совпадение
                
                // 3. --- НОВОЕ: Запуск Middleware ---
                // Проверяем, есть ли middleware для этого маршрута
                if (isset($route[3]) && is_array($route[3])) {
                    $middlewares = $route[3];
                    foreach ($middlewares as $middleware) {
                        $this->runMiddleware($middleware);
                    }
                }
                
                // 4. Вызов обработчика маршрута
                $this->callHandler($handler, $params);
                return; // Завершаем после первого совпадения
            }
        }

        // Если ни один маршрут не совпал
        http_response_code(404);
        // В production лучше использовать View::render('errors/404')
        echo "Страница не найдена (404)";
    }

    /**
    * --- НОВОЕ: Запустить один middleware ---
    * @param string $middlewareName Имя middleware или строка с параметром (например, 'permission:user_view')
    * @throws \Exception
    */
    private function runMiddleware(string $middlewareName): void
    {
        // Простой фабричный метод для создания middleware
        $middleware = null;

        if ($middlewareName === 'auth') {
            $middleware = new \App\Middleware\AuthMiddleware();
        } elseif (str_starts_with($middlewareName, 'permission:')) {
            // permission:user_view
            $permissionName = substr($middlewareName, 11); // Убираем 'permission:'
            $middleware = new \App\Middleware\PermissionMiddleware($permissionName);
        } else {
            // Неизвестный тип middleware
            error_log("Router: Неизвестный middleware '$middlewareName'");
            // Можно бросить исключение или просто пропустить
            return;
        }

        if ($middleware) {
            $middleware->handle();
        }
    }

    /**
     * Сопоставляет маршрут с URI и извлекает параметры
     * @param string $routePath Путь из определения маршрута (например, /admin/roles/{id}/edit)
     * @param string $requestUri Запрошенный URI (например, /admin/roles/123/edit)
     * @return array|false Массив параметров или false, если не совпало
     */
    private function match(string $routePath, string $requestUri): array|false
    {
        // 1. Экранируем слэши и точки для использования в регулярном выражении
        //    /admin/roles/{id}/edit -> \/admin\/roles\/\{id\}\/edit
        $pattern = preg_quote($routePath, '/');

        // 2. Заменяем плейсхолдеры {paramName} на именованные группы захвата
        //    \/admin\/roles\/\{id\}\/edit -> \/admin\/roles\/(?<id>[^\/]+)\/edit
        $pattern = preg_replace('/\\\{([^\/]+)\\\}/', '(?<$1>[^\/]+)', $pattern);

        // 3. Формируем полное регулярное выражение
        //    ^\/admin\/roles\/(?<id>[^\/]+)\/edit$
        $pattern = '/^' . $pattern . '$/i'; // i - регистронезависимость, можно убрать

        // 4. Пытаемся сопоставить
        if (preg_match($pattern, $requestUri, $matches)) {
            // 5. Фильтруем совпадения, оставляя только именованные группы (параметры)
            //    $matches может содержать как числовые, так и строковые ключи
            $params = [];
            foreach ($matches as $key => $value) {
                // is_string($key) означает, что это именованная группа
                if (is_string($key)) {
                    // Преобразуем в нужный тип, если нужно
                    // Пока передаем как строку, контроллер сам преобразует в int
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        // Совпадений нет
        return false;
    }

    /**
     * Вызывает обработчик маршрута с параметрами
     * @param callable|array $handler Обработчик (функция или [Controller, method])
     * @param array $params Параметры из URI
     */
    private function callHandler(callable|array $handler, array $params): void
    {
        try {
            if (is_callable($handler)) {
                // Если обработчик - анонимная функция или callable
                call_user_func_array($handler, $params);
            } elseif (is_array($handler) && count($handler) === 2) {
                // Если обработчик - [ControllerName, methodName]
                [$controllerName, $methodName] = $handler;
                
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    
                    if (method_exists($controller, $methodName)) {
                        // Получаем ReflectionMethod для проверки параметров метода
                        $reflection = new \ReflectionMethod($controller, $methodName);
                        $methodParams = $reflection->getParameters();
                        
                        // Подготавливаем аргументы для вызова метода
                        $args = [];
                        foreach ($methodParams as $param) {
                            $paramName = $param->getName();
                            if (array_key_exists($paramName, $params)) {
                                // Если параметр есть в URI, передаем его
                                // Пытаемся преобразовать в нужный тип
                                $argValue = $params[$paramName];
                                if ($param->hasType() && !$param->getType()->isBuiltin()) {
                                     // Если тип не встроенный (объект), передаем как есть
                                     $args[] = $argValue;
                                } else {
                                    // Если встроенный тип (int, string, bool...)
                                    $typeName = $param->getType() ? $param->getType()->getName() : 'string';
                                    switch ($typeName) {
                                        case 'int':
                                            $args[] = (int)$argValue;
                                            break;
                                        case 'bool':
                                            $args[] = (bool)$argValue;
                                            break;
                                        case 'float':
                                            $args[] = (float)$argValue;
                                            break;
                                        default:
                                            $args[] = $argValue; // string или другой тип
                                    }
                                }
                            } else {
                                // Если параметра нет в URI, передаем значение по умолчанию или null
                                if ($param->isDefaultValueAvailable()) {
                                    $args[] = $param->getDefaultValue();
                                } else {
                                    $args[] = null;
                                }
                            }
                        }
                        
                        // Вызываем метод контроллера с подготовленными аргументами
                        call_user_func_array([$controller, $methodName], $args);
                    } else {
                        http_response_code(500);
                        echo "Метод не найден: " . $controllerName . "::" . $methodName;
                    }
                } else {
                    http_response_code(500);
                    echo "Контроллер не найден: " . $controllerName;
                }
            } else {
                http_response_code(500);
                echo "Неверный формат обработчика маршрута.";
            }
        } catch (\Exception $e) {
            // Логируем исключение
            error_log("Ошибка в обработчике маршрута: " . $e->getMessage() . " в " . $e->getFile() . " на строке " . $e->getLine());
            
            // В production лучше показать общую 500 ошибку
            http_response_code(500);
            // В dev можно показать детали
            if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                echo "<h1>Ошибка в обработчике маршрута</h1>";
                echo "<p><strong>Сообщение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>Файл:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
                echo "<p><strong>Строка:</strong> " . $e->getLine() . "</p>";
                echo "<h2>Стек вызовов:</h2>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            } else {
                 echo "Внутренняя ошибка сервера.";
            }
        }
    }
}
?>