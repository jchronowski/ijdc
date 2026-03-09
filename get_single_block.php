<?php
/*
|--------------------------------------------------------------------------
| get_block_field($fieldname)
|--------------------------------------------------------------------------
| PURPOSE
|   Finds a single block-style field and returns its contents exactly as stored,
|   so HTML inside it can render normally.
|
| WORKS WITH
|   call_transcribed:<<^ some stuff here <img src='logo.png'> even html^>>
|
| ALSO WORKS WITH
|   any other field name passed into the function, such as:
|   notes:<<^hello<b>world</b>^>>
|   bio:<<^this is <i>formatted</i> text^>>
|
| SKIPS
|   -call_transcribed:<<^old disabled content^>>
|
| RETURNS
|   The raw inner content only:
|   some stuff here <img src='logo.png'> even html
|
| IMPORTANT
|   This returns raw HTML on purpose.
|   Use only if you trust what is stored in the file.
|--------------------------------------------------------------------------
*/
function get_block_field($fieldname) {
    global $ijdc_path;

    if (!file_exists($ijdc_path)) {
        return '';
    }

    $lines = file($ijdc_path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // skip blank lines
        if ($line === '') continue;

        // skip disabled lines like -fieldname:...
        if (strpos($line, '-') === 0) continue;

        // only process the field requested
        if (strpos($line, $fieldname . ':') !== 0) continue;

        // get everything after "fieldname:"
        $value = trim(substr($line, strlen($fieldname) + 1));

        // if wrapped in <<^ ^>>, strip those markers
        if (str_starts_with($value, '<<^') && str_ends_with($value, '^>>')) {
            return substr($value, 3, -3);
        }

        // if it exists but is not wrapped, still return the value
        return $value;
    }

    return '';
}


/*
|--------------------------------------------------------------------------
| EXAMPLE CALL
|--------------------------------------------------------------------------
| This returns the raw block content from:
| call_transcribed:<<^ some stuff here <img src='logo.png'> even html^>>
|--------------------------------------------------------------------------
*/
$transcribed_html = get_block_field('call_transcribed');


/*
|--------------------------------------------------------------------------
| DISPLAY IT
|--------------------------------------------------------------------------
| Echo directly so any HTML inside it renders.
|--------------------------------------------------------------------------
*/
echo $transcribed_html;

?>