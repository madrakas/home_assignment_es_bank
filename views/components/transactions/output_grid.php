<div class ="report-section">
    <h2>Output data:</h2>
</div>

<div class="detail-headings">
        <div class="tax">Tax</div>
</div>

<?php 
    $output = $data['output_data'];
    foreach ($output as $tax) {
        echo '<div class="detail-row">';
            echo '<div class="tax">' . $tax . '</div>';
        echo '</div>';
    }
?>

<button>Download txt</button>