<div class ="report-section">
    <h2>Suskaičiuoti mokesčiai:</h2>
</div>

<div class="detail-headings">
        <div class="tax">Mokestis</div>
</div>

<div class="detail" id="taxTable">
    <?php 
        $output = $data['output_data'];
        foreach ($output as $tax) {
            echo '<div class="detail-row">';
                echo '<div class="tax">' . $tax . '</div>';
            echo '</div>';
        }
    ?>
</div>

<button type="button" id="downloadButton">Atsisiųsti txt</button>
