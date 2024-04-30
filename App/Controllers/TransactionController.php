<?php
namespace Bank\App\Controllers;

use Bank\App\App;
use Bank\App\Controllers\CurrencyController;
use Bank\App\DB\FileBase;

class TransactionController
{
    private function index()
    {
        return App::view('result');
    }

    public function upload($data)
    {
        if (isset($data['file'])) {
            $file = $data['file'];
            if ($file['error'] == 0) {
                $file_tmp = $file['tmp_name'];
                $file_size = $file['size'];
                $file_type = $file['type'];

                if ($file_type == 'text/plain' && $file_size >= 10 && $file_size <= 10000) {
                    $input_data = file_get_contents($file_tmp);
                    $input_data = $this->txtToArray($input_data);
                } else {
                    $input_data = [];
                }
                $output_data = $this->getTaxes($input_data);
                
                return App::view('result', [
                    'input_data' => $input_data,
                    'output_data' => $output_data
                ]);
            } else {
                return App::view('result', ['input_data' => $data['file']['error']]);
            }
        } else {
            return App::view('result', ['input_data' => 'No data uploaded']);
        }
    }

    private function txtToArray($data) 
    {
        $rows = explode("\n", $data);

        $result = [];
        foreach ($rows as $row) {
            $columns = explode(",", $row);

            $row_data = [
                'date' => $columns[0],
                'customerId' => $columns[1],
                'clienttype' => $columns[2],
                'operationType' => $columns[3],
                'amount' => $columns[4],
                'currency' => $columns[5]
            ];

            $result[] = $row_data;
        }
        return $result;
    }

    private function getTaxes(array $data) : array
    {
        $taxes = [];

        foreach($data as $row){
            // case operation type = "in"
            if($row['operationType'] == "in"){
                $taxes[] = $this->inTax($row['amount'], $row['currency']);
            }else if($row['operationType'] == "out"){
                $taxes[] = $row['amount'] * 0;
            }
        }
        return $taxes;
    }

    private function inTax($amount, $currency) {
        $tax = ($amount * 0.05) > 5 ? 5 : ($amount * 0.05);
        return $this->roundToMinValue($tax, $currency);        
    }

    private function roundToMinValue($amount, $currency) {
        //Find currency min value
        $reader = new FileBase('currencies');
        $currencies = $reader->showAll();
        $cur = array_filter($currencies, fn ($item) => $item['name'] == $currency);
        var_dump($cur);
        

        $minValue = $cur['minValue'];

        // Round to min value
        if (($amount / $minValue) % 1 != 0) {
            $amount = round($tax / $minValue) * $minValue + $minValue;
        } else {
            $amount = round($tax / $minValue) * $minValue;
        }
        
        return $amount;
    }
}
