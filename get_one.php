<?php


function show_one_field($fieldname) {
    global $ijdc_path;

    if (!file_exists($ijdc_path)) {
        return '';
    }

    $lines = file($ijdc_path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') continue;
        if (strpos($line, '-') === 0) continue;

        if (strpos($line, $fieldname . ':') === 0) {
            return trim(substr($line, strlen($fieldname) + 1));
        }
    }

    return '';
}

echo show_one_field('nickname');
?>