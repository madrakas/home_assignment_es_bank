<div class ="report-section">
    <h2>Input data:</h2>
</div>

    <div class="detail-headings">
        <div class="date">Data</div>
        <div class="cutomerID">ID</div>
        <div class="customerType">Operacijos tipas</div>
        <div class="trasactionAmount">Operacijos suma</div>
        <div class="currency">Operacijos valiuta</div>
    </div>


    <?php 
        $input = $data['input_data'];
        foreach ($input as $key => $value) {
            echo '<div class="detail-row">';
                echo '<div class="date">' . $value['date'] . '</div>';
                echo '<div class="cutomerID">' . $value['customerId'] . '</div>';
                echo '<div class="customerType">' . $value['clienttype'] . '</div>';
                echo '<div class="trasactionAmount">' . $value['amount'] . '</div>';
                echo '<div class="currency">' . $value['currency'] . '</div>';
            echo '</div>';
        }
    ?>
