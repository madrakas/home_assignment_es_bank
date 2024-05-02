<?php 
namespace Bank\App\Controllers;

use Bank\App\DB\FileBase;

class CurrencyController
{
    public static function All()
    {
        $reader = new FileBase('currencies');
        $data = $reader->showAll();
        return $data;
    }
}
