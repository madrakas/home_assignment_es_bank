<?php
namespace Bank\App;

use Bank\App\Controllers\HomeController;
use Bank\App\Controllers\TransactionController;

class App
{

    public static function run() : string
    {
        $server = $_SERVER['REQUEST_URI'];
        $url = explode('/', $server);
        array_shift($url);
        return self::router($url);
    }

    private static function router(array $url) : string
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ('GET' == $method && count($url) == 1 && $url[0] == '') {
            return (new HomeController())->index();
        }elseif('POST' == $method && count($url) == 1 && $url[0] == 'upload'){
            return (new TransactionController())->upload($_FILES);
        }

        return "<h1>404</h1><br>";
    }

    public static function view(string $view, array $data = []) : string
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
