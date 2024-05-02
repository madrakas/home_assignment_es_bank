<h1>Results</h1>

<form method="post" enctype="multipart/form-data" action="/upload">
  <label for="file">Log file</label>
  <input id="file" name="file" type="file" accept=".txt"/>
  <button type="submit">Upload</button>
</form>

<?php
    // check if data contains input data
    if (isset($data['input_data']) && $data['input_data'] != 'No data uploaded') {
    // include input grid view
        require_once __DIR__ . '/components/transactions/input_grid.php';
    } else {
        echo 'No data uploaded';
    }

    // check if data contains output data
    if (isset($data['output_data'])) {
        // include output grid view
        require_once __DIR__ . '/components/transactions/output_grid.php';
    } else {
        echo 'No data uploaded';
    }
?>



