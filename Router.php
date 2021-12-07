<?php

namespace tonyanant\phpmvc;

use tonyanant\phpmvc\exception\NotFoundException;

/**
 * Class Router
 * @package tonyanant\phpmvc
 */
class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];

    /**
     * Router constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Handles the routing for get requests.
     *
     * @param $path : The page name yo route to.
     * @param $callback " The function for the content of the page.
     */
    public function get($path, $callback) {
        // Save route on assoc array, setting the path as key and the callback as value
        $this->routes['get'][$path] = $callback;
    }

    /**
     * @param $path
     * @param $callback
     */
    public function post($path, $callback) {
        // Save route on assoc array, setting the path as key and the callback as value
        $this->routes['post'][$path] = $callback;
    }

    /**
     * Using the request object to determine the path and the method that where requested,
     * find the corresponding callback function provided to finally route to the appropriate content.
     * In other words, resolve the request.
     */
    public function resolve() {
        // Get route path from request object
        $path = $this->request->getPath();
        // Also get the method from request object
        $method = $this->request->method();
        // Get callback method from routes using the method and path keys
        $callback = $this->routes[$method][$path] ?? false;

        // Callback method was not found, render "Not Found" view.
        if (!$callback) {
            throw new NotFoundException();
        }
        // String is given as callback, find & render corresponding view.
        if (is_string($callback)) {
            return Application::$app->view->renderView($callback);
        }
        // Controller class method is given, instantiate new Controller object.
        if (is_array($callback)) {
            /** @var \tonyanant\phpmvc\Controller $controller */
            // Set new controller object in Application class
            $controller = new $callback[0]();
            Application::$app->controller = $controller;
            $controller->action = $callback[1];
            $callback[0] = $controller;

            foreach ($controller->getMiddleware() as $middleware) {
                $middleware->execute();
            }
        }
        // Actual callback function given, call user provided callback.
        return call_user_func($callback, $this->request, $this->response);
    }
}
