<?php

namespace Bank\App;

use Bank\App\Controllers\HomeController;

class App
{

    public static function run()
    {
        $server = $_SERVER['REQUEST_URI'];
        $url = explode('/', $server);
        array_shift($url);
        return self::router($url);
    }

    private static function router($url)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ('GET' == $method && count($url) == 1 && $url[0] == '') {
            
            return (new HomeController())->index();
        }

        return "<h1>404</h1><br>";
    }

    public static function view($view, $data = [])
    {
        extract($data);
        ob_start();
        require ROOT . 'views/components/header.php';
        require ROOT . "views/$view.php";
        require ROOT . 'views/components/footer.php';
        $content = ob_get_clean();
        return $content;
    }
}
