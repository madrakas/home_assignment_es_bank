<?php
namespace Bank\App\Controllers;

use Bank\App\App;
use Bank\App\Controllers\CurrencyController;
use Bank\App\DB\FileBase;

class TransactionController
{

    // Process uploaded txt file
    public function upload(array $data) : string
    {
        if (isset($data['file'])) {
            $file = $data['file'];
            if ($file['error'] == 0) {
                $file_tmp = $file['tmp_name'];
                $file_size = $file['size'];
                $file_type = $file['type'];

                //Basic validation
                if ($file_type == 'text/plain' && $file_size >= 10 && $file_size <= 10000) {
                    $input_data = file_get_contents($file_tmp);
                    // Txt file Content validation
                    $validation = $this->txtIsValid($input_data);
                    if ($validation['valid'] === false) {
                        return App::view('result', [
                            'input_data' => $validation['message']
                        ]);
                    }
                    // Convert txt to array for further processing
                    $input_data = $this->txtToArray($input_data);
                } else {
                    $input_data = [];
                }

                // Tax calculation
                $output_data = $this->getTaxes($input_data);
                
                // Display result
                return App::view('result', [
                    'input_data' => $input_data,
                    'output_data' => $output_data
                ]);
            } else {
                var_dump('aa');
                return App::view('result', [
                    // server file upload error message
                    'input_data' => 'No file uploaded'
                ]);
            }
        } else {
            return App::view('result', [
                'input_data' => 'No file uploaded'
            ]);
        }
    }

    // Convert txt to array
    private function txtToArray($data) 
    {
        // Split into rows
        $rows = explode("\n", $data);

        // Split into columns
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

    // *** Taxes calculation main function ***
    private function getTaxes(array $data) : array
    {
        // Prepare to handle simple customers out transactions:
        // Count amounts after discount
        $simpleOutTransaction = $this->getSimpleOutTransactions($data);
        $simpleOutTransactionNavigator = [];
        
        //Generating taxes
        $taxes = [];
        foreach($data as $row){
            // case operation type = "in"
            if($row['operationType'] == "in"){
                $taxes[] = $this->inTax($row['amount'], $row['currency']);
            }else if($row['operationType'] == "out" && $row['clienttype'] == "legal"){
                $taxes[] = $this->outLegalTax($row['amount'], $row['currency']);
            }else if ($row['operationType'] == "out" && $row['clienttype'] == "simple"){ 
                // filter customer transactions by customer id
                $customerTransactions = array_filter($simpleOutTransaction, fn ($key) => $key == $row['customerId'],  ARRAY_FILTER_USE_KEY);
                $customerTransactions = array_shift($customerTransactions);

                //Check if  Navigator array key is defined
                if (!array_key_exists($row['customerId'], $simpleOutTransactionNavigator)) {
                    $simpleOutTransactionNavigator[$row['customerId']] = 0;
                }
                $currentTransaction = $customerTransactions[$simpleOutTransactionNavigator[$row['customerId']]];
                $simpleOutTransactionNavigator[$row['customerId']] += 1;

                $currentAmount = floatval($currentTransaction[1]);
                // var_dump($currentAmount);
                $tax = $this->outSimpleTax($currentAmount, $row['currency']);
                $taxes[] = $tax;
            } else {
                $taxes[] = $row['amount'] * 0;
            }
        }
        return $taxes;
    }

    // Calculate simple customers Out amounts after discount
    private function getSimpleOutTransactions(array $data) : array
    {
        $data = array_filter($data, fn ($item) => $item['operationType'] == 'out' && $item['clienttype'] == 'simple');

        // Group Transactions by customer ID
        $simpleOutTransactions = [];
        foreach($data as $row){
            $simpleOutTransactions[$row['customerId']][] = [$row['date'], $row['amount'], $row['currency']];
        }

        foreach($simpleOutTransactions as $key => $value) {
            $cstomerTaxes = $simpleOutTransactions[$key];
            // var_dump($key);
            $discount = 1000;

            $z = 0;
            for ($i=0; $i < count($cstomerTaxes); $i++) {

                $oldAmount = floatval($cstomerTaxes[$i][1]);
                $currency = $cstomerTaxes[$i][2];
                $transactionDate = $cstomerTaxes[$i][0];

                if ($z == 0) {                 // First transaction
                    $discountDate = $transactionDate;
                    $discountYear = intval(substr($transactionDate, 0, 4));
                    $discountWeekNoumber = idate('W', strtotime($transactionDate));
                    $amountEur = $this->convertToEur($oldAmount, $currency);
                    $amountEurDiscounted =  $amountEur - $discount < 0 ? 0 : $amountEur - $discount;
                    $discount = $discount - ($amountEur - $amountEurDiscounted);
                    $newAmount = $this->convertFromEur($amountEurDiscounted, $currency);
                    $discountCounter = 1;
                }else{
                    
                    // Check if date is in the same week
                    if ($this->sameWeek($discountDate, $transactionDate)) {
                        if ($discountCounter < 4) {  //Up to thre times per week discount is available
                            $amountEur = $this->convertToEur($oldAmount, $currency);
                            // var_dump('discount', $discount);
                            $amountEurDiscounted =  $amountEur - $discount < 0 ? 0 : $amountEur - $discount;
                            $discount = $discount - ($amountEur - $amountEurDiscounted);
                            $newAmount = $this->convertFromEur($amountEurDiscounted, $currency);
                            // var_dump('amount:', $amountEur, 'newamount: ', $newAmount);
                            $discountCounter++;
                        }else{
                            // No dicout after third time this week
                            $newAmount = $oldAmount;
                        }
                    } else {
                        // Reset discount
                        $discount = 1000;
                        $discountDate = $transactionDate;
                        $amountEur = $this->convertToEur($oldAmount, $currency);
                        $amountEurDiscounted =  $amountEur - $discount < 0 ? 0 : $amountEur - $discount;
                        $discount = $discount - ($amountEur - $amountEurDiscounted);
                        $newAmount = $this->convertFromEur($amountEurDiscounted, $currency);
                        $discountCounter = 1;
                    }
                }
                $z++;
                $newAmount = $this->roundToMinValue($newAmount, $currency); 
                $newAmount = substr($newAmount, 0, -4);
                $simpleOutTransactions[$key][$i] = [$cstomerTaxes[$i][0], $newAmount, $cstomerTaxes[$i][2]];
            }
        }
        return $simpleOutTransactions;
    }

    // Tax rules
    private function outSimpleTax(float $amount, string $currency) : string
    {
        $tax = ($amount * 0.003);
        return $this->roundToMinValue($tax, $currency);
    }


    private function inTax($amount, $currency) 
    {
        $tax = ($amount * 0.0003) > 5 ? 5 : ($amount * 0.0003);
        return $this->roundToMinValue($tax, $currency);        
    }

    private function outLegalTax($amount, $currency) {
        //Find currency exchange rate
        $reader = new FileBase('currencies');
        $currencies = $reader->showAll();
        $cur = array_filter($currencies, fn ($item) => $item['name'] == $currency);
        $cur = array_shift($cur);
        $rate = $cur['rate'];

        // Find minumum tax
        $minTax = 0.5 * $rate;

        //Calculate tax
        $tax = ($amount * 0.003) < $minTax ? $minTax : ($amount * 0.003);
        return $this->roundToMinValue($tax, $currency);        
    }

    // Round to min value
    private function roundToMinValue($amount, $currency) {
        
        //Find currency min value
        $reader = new FileBase('currencies');
        $currencies = $reader->showAll();
        $cur = array_filter($currencies, fn ($item) => $item['name'] == $currency);
        $cur = array_shift($cur);
        $minValue = $cur['minValue'];

        // Round to min value
        if (fmod(($amount / $minValue), 1) != 0) {
            $amount = round($amount / $minValue) * $minValue + $minValue;
        } else {
            $amount = round($amount / $minValue) * $minValue;
        }

        //Format acording to min value decimals
        $decimals = strlen(substr(strrchr(strval($minValue), "."), 1));
        $amount = number_format($amount, $decimals, '.', '');
        return $amount . ' ' . $currency;
    }

    private function convertToEur(float $amount, string $currency) : float
    {
        $reader = new FileBase('currencies');
        $currencies = $reader->showAll();
        $cur = array_filter($currencies, fn ($item) => $item['name'] == $currency);
        $cur = array_shift($cur);
        $rate = $cur['rate'];
        return $amount / $rate;
    }

    private function convertFromEur(float $amount, string $currency) : float
    {
        $reader = new FileBase('currencies');
        $currencies = $reader->showAll();
        $cur = array_filter($currencies, fn ($item) => $item['name'] == $currency);
        $cur = array_shift($cur);
        $rate = $cur['rate'];
        return $amount * $rate;
    }

    private function sameWeek(string $date1, string $date2) : bool
    {
        $td1 = new \DateTime($date1);
        $td2 = new \DateTime($date2);

        $week1 = $td1->format('W');
        $week2 = $td2->format('W');

        $year1 = $td1->format('o');
        $year2 = $td2->format('o');

        return $week1 == $week2 && $year1 == $year2;
    }

    private function txtIsValid(string $data) : array
    {
        $rows = explode("\n", $data);
        if (count($rows) == 0) {
            return [
                'valid' => false,
                'message' => 'Tuščias failas. Nėra ką skaičiuoti.'
            ];
        }

        $currencies = new FileBase('currencies');
        $currencies = $currencies->showAll();
        if (count($currencies) == 0) {
            return [
                'valid' => false,
                'message' => 'Valiutų lentelė yra tuščia.'
            ];
        } else {
            $currencies = array_column($currencies, 'name');
        }

        $i = 0;
        foreach ($rows as $key => $value) {
            $i++;
            $row = explode(',', $value);
            if (count($row) != 6) {
                return [
                    'valid' => false,
                    'message' => 'Blogas stulpelių skaičius eilutėje ' . ($key + 1)
                ];
            };

            foreach ($row as $key => $value) {
               switch ($key) {
                    case 0:
                        // validate date
                        $date = new \DateTime($value);
                        if (!$date) {
                            return [
                                'valid' => false,
                                'message' => 'Blogas datos formatas eilutėje ' . ($key + 1)
                            ];
                        }
                        break;
                    case 1:
                        // validate customer id
                        if (!is_numeric($value)) {
                            return [
                                'valid' => false,
                                'message' => 'Blogas kliento ID eilutėje ' . ($key + 1)
                            ];
                        }
                        break;
                    case 2:
                        // validate client type
                        if (!in_array($value, ['simple', 'legal'])) {
                            return [
                                'valid' => false,
                                'message' => 'Blogas klilento tipas eilutėje ' . ($key + 1)
                            ];
                        }
                        break;
                    case 3:
                        // validate transaction type
                        if (!in_array($value, ['in', 'out'])) {
                            return [
                                'valid' => false,
                                'message' => 'Blogas operacijos tipas eilutėje ' . ($key + 1)
                            ];
                        }
                        break;
                    case 4:
                        // validate amount must be numeric and greater than 0
                        if (!is_numeric($value) || $value <= 0) {
                            return [
                                'valid' => false,
                                'message' => 'Bloga operacijos suma eilutėje ' . ($i). ' ' . $value
                            ];
                        }
                        break;
                    case 5:
                        // validate currency
                        if (!in_array($value, $currencies)) {
                            return [
                                'valid' => false,
                                'message' => 'Bloga valiuta eilutėje ' . ($key + 1)
                            ];
                        }
                        break;
                }
            }
        }
         
        return [
            'valid' => true,
            'message' => '' 
        ];
    }

}
