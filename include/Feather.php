<?php
require_once 'include\Route.php';

class Feather
{
    private static $instance = null;
    private $route;
    private $actions = array();

    function __construct()
    {
        $this->actions = array();
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Feather();
        }

        return self::$instance;
    }

    public function run()
    {
        $this->route = new Route();
        $this->route->run();

        $controller = $this->route->controller;
        $action = $this->route->action;
        $id = $this->route->id;

        //var_dump($this->route);
        try {
            $app = $this;
            include('controllers/Controller.php');
            if (!empty($controller)) $hasController = include('controllers/' . $controller . 'Controller.php');

            $requestType = strtolower($_SERVER['REQUEST_METHOD']);

            $action = $hasController ? $requestType . $action : $requestType . $controller;
            if ($id) $action = $action . ':id';

            //echo "action=$action";

            // print_r(array_keys($this->actions));
            if (array_key_exists($action, $this->actions)) {
                $obj = json_decode(file_get_contents('php://input'));
                $params = array();
                if ($id) $params['id'] = $id;
                $res = $this->actions[$action]($params, $obj);
                $this->response($res);
            } else {
                throw new Exception("method was not found!");
            }
        } catch (Exception $ex) {
            //return "error: "+$ex->getMessage();
            $this->response($ex->getMessage());
        }
    }

    function get($route, $f)
    {
        $action = $this->trim($route);
        if (strpos($action, '/') !== false) {
            $arr = explode('/', $action);
            $action = $arr[0] . 'id';
        }
        $this->actions['get' . $this->trim($action)] = $f;
    }

    function put($route, $f)
    {
        $action = $this->trim($route);
        if (strpos($action, '/') !== false) {
            $arr = explode('/', $action);
            $action = $arr[0] . 'id';
        }
        $this->actions['put' . $this->trim($action)] = $f;
    }

    function delete($route, $f)
    {
        $action = $this->trim($route);
        if (strpos($action, '/') !== false) {
            $arr = explode('/', $action);
            $action = $arr[0] . 'id';
        }
        $this->actions['delete' . $this->trim($action)] = $f;
    }

    function post($action, $f)
    {
        $this->actions['post' . $this->trim($action)] = $f;
    }

    function response($obj)
    {
        $json_response = json_encode($obj);
        echo $json_response;
    }

    function getOrigin()
    {
        $startIndex = stripos($_SERVER['PHP_SELF'], '/index.php');
        return $startIndex > 0 ? substr($_SERVER['PHP_SELF'], 1, $startIndex - 1) : "";
    }

    function getPath($origin)
    {
        $url = $_SERVER['REQUEST_URI'];
        $endIndex = stripos($url, '?');
        if (empty($origin)) {
            return $endIndex > 0 ? substr($url, 0, $endIndex - 1) : $url;
        } else {
            $startIndex = stripos($url, '/' . $origin . '/');
            if ($startIndex < 0) throw new Exception("Error");
            return !$endIndex ? substr($url, $startIndex + strlen($origin) + 1) : substr($url, $startIndex + strlen($origin) + 1, $endIndex - ($startIndex + strlen($origin) + 1));
        }
    }

    function getController($path)
    {
        $arr = explode('/', $path);
        $k = 0;
        $name = "";
        foreach ($arr as $item) {
            if (!empty($item)) {
                $k++;
                if ($k > 1) return $name;
                $name = $item;
            }
        }
        return $this->trim($path);
    }

    function getAction($path, $controller)
    {
        if ($controller == "") return "";
        $startIndex = stripos($path,  '/' . $controller . '/');
        return substr($path, $startIndex + 1 + strlen($controller . '/'));
    }

    function trim($str)
    {
        $str = ltrim($str, '/');
        $str = rtrim($str, '/');
        return $str;
    }
}
