<?php
class App
{
    protected $routes = array();
    protected $responseStatus = '200 OK';
    protected $responseContentTyep = 'text/html';
    protected $responseBody = 'Hello World';

    public function addRoute($routePath, $routeCallBack)
    {
        $this->routes[$routePath] = $routeCallBack->bindTo($this, __CLASS__);
    }

    public function dispatch($currentPath)
    {
        foreach ($this->routes as $routePath => $callBack) {
            if ($routePath === $currentPath) {
                $callBack();
            }
        }

        header('HTTP/1.1 ' . $this->responseStatus);
        header('Content-type: ' . $this->responseContentType);
        header('Content-length: ' . mb_strlen($this->responseBody));
        echo $this->responseBody;
    }
}

$app = new App();
$app->addRoute('/users/martini', function() {
    $this->responseContentType = 'application/json;charset-utf8';
    $this->responseBody = '{"name": "martini"}';
});
$app->dispatch('/users/martini');